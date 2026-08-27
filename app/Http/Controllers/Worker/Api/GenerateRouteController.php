<?php

declare(strict_types=1);

namespace App\Http\Controllers\Worker\Api;

use App\Http\Controllers\BaseController;

final class GenerateRouteController extends BaseController
{
    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return ['worker'];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function json(array $data, int $status = 200): never
    {
        parent::json($data, $status);
    }

    public function render(): void
    {
        $workerId = $this->currentUserId();

        /** @var mixed $rawInput */
        $rawInput = json_decode((string) file_get_contents('php://input'), true);
        $input = is_array($rawInput) ? $rawInput : [];
        if ($input === [] && is_array($_POST) && $_POST !== []) {
            $input = $_POST;
        }

        $optimize = (bool) ($input['optimize'] ?? false);
        $includeReturn = (bool) ($input['include_return'] ?? false);

        try {
            // Get starting point
            $startingPoint = $this->getStartingPoint();

            // Get worker's scheduled pickup requests and active collection points
            $pickupRequests = $this->getPickupRequests($workerId);
            $collectionPoints = $this->getCollectionPoints();

            $allStops = array_merge($pickupRequests, $collectionPoints);

            if ($allStops === []) {
                $this->json([
                    'success' => false,
                    'message' => 'No pickup requests or collection points found for routing.',
                ], 404);
            }

            // Generate optimized route using TSP algorithms
            $optimizedRoute = $optimize
                ? $this->solveTSP($startingPoint, $allStops, $includeReturn)
                : $this->optimizeRoute($startingPoint, $allStops);

            // Get detailed street routing for the optimized route
            $streetRouteGeometry = $this->getCompleteStreetRoute($optimizedRoute, $includeReturn);

            // Calculate route statistics using street routing
            $totalDistance = $this->calculateTotalDistance($optimizedRoute, $includeReturn);
            $estimatedTime = $this->estimateTime($totalDistance, count($allStops));
            $fuelSavings = $this->calculateFuelSavings($allStops, $optimizedRoute);

            // Begin transaction
            $this->legacy()->beginTransaction();

            try {
                $routeGeometry = json_encode([
                    'type' => 'LineString',
                    'coordinates' => $streetRouteGeometry['coordinates'] ?? array_map(
                        static fn(array $stop): array => [
                            (float) ($stop['longitude'] ?? 0.0),
                            (float) ($stop['latitude'] ?? 0.0),
                        ],
                        $optimizedRoute
                    ),
                    'street_geometry' => $streetRouteGeometry,
                    'total_distance' => $totalDistance,
                    'estimated_time' => $estimatedTime,
                    'fuel_savings' => $fuelSavings,
                    'routing_method' => 'street_routing_with_osrm',
                ]);

                if ($routeGeometry === false) {
                    throw new \RuntimeException('Failed to encode route geometry.');
                }

                $routeStmt = $this->legacy()->prepare("INSERT INTO collectionroutes (worker_id, route_geometry) VALUES (?, ?)");
                $routeStmt->bind_param("is", $workerId, $routeGeometry);
                $routeStmt->execute();
                $routeId = $this->legacy()->last_insert_id;

                $stopStmt = $this->legacy()->prepare("INSERT INTO routestops (route_id, request_id, point_id, stop_order, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)");

                foreach ($optimizedRoute as $index => $stop) {
                    $requestId = $stop['request_id'] ?? null;
                    $pointId = $stop['point_id'] ?? null;
                    $stopOrder = $index + 1;

                    $stopStmt->bind_param(
                        "iiidd",
                        $routeId,
                        $requestId,
                        $pointId,
                        $stopOrder,
                        (float) ($stop['latitude'] ?? 0.0),
                        (float) ($stop['longitude'] ?? 0.0),
                    );
                    $stopStmt->execute();
                }

                $this->legacy()->commit();

                $this->json([
                    'success' => true,
                    'route_id' => $routeId,
                    'message' => 'Optimized route generated successfully!',
                    'route_info' => [
                        'total_stops' => count($allStops),
                        'pickup_requests' => count($pickupRequests),
                        'collection_points' => count($collectionPoints),
                        'total_distance' => $totalDistance,
                        'estimated_time' => $estimatedTime,
                        'fuel_savings' => $fuelSavings,
                        'optimization_method' => $optimize ? 'Advanced TSP' : 'Smart Nearest Neighbor',
                    ],
                ]);
            } catch (\Exception $e) {
                $this->legacy()->rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Error generating route: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the starting point for the route.
     *
     * @return array<string, mixed>
     */
    private function getStartingPoint(): array
    {
        $adminStmt = $this->legacy()->prepare("SELECT latitude, longitude FROM users WHERE role = 'admin' AND latitude IS NOT NULL AND longitude IS NOT NULL LIMIT 1");
        $adminStmt->execute();
        $adminResult = $adminStmt->get_result()->fetch_assoc();

        if (is_array($adminResult)) {
            return [
                'latitude' => (float) $adminResult['latitude'],
                'longitude' => (float) $adminResult['longitude'],
                'name' => 'Barangay Office',
            ];
        }

        $purokStmt = $this->legacy()->prepare("SELECT latitude, longitude, name FROM puroks WHERE latitude IS NOT NULL AND longitude IS NOT NULL LIMIT 1");
        $purokStmt->execute();
        $purokResult = $purokStmt->get_result()->fetch_assoc();

        if (is_array($purokResult)) {
            return [
                'latitude' => (float) $purokResult['latitude'],
                'longitude' => (float) $purokResult['longitude'],
                'name' => (string) $purokResult['name'],
            ];
        }

        return [
            'latitude' => 6.4216,
            'longitude' => 124.7810,
            'name' => 'Barangay Nagpan',
        ];
    }

    /**
     * Get the worker's scheduled pickup requests that need routing.
     *
     * @return list<array<string, mixed>>
     */
    private function getPickupRequests(int $workerId): array
    {
        $pickupStmt = $this->legacy()->prepare("
            SELECT pr.request_id, pr.latitude, pr.longitude, pr.weight_kg, pr.preferred_date, pr.status,
                   CONCAT(u.first_name, ' ', u.last_name) as resident_name, u.address, u.phone,
                   rt.name as material_type, rt.points_per_kg
            FROM pickuprequests pr
            JOIN users u ON pr.resident_id = u.user_id
            JOIN recyclabletypes rt ON pr.type_id = rt.type_id
            WHERE pr.assigned_worker_id = ? 
            AND pr.status = 'Scheduled'
            AND pr.latitude IS NOT NULL 
            AND pr.longitude IS NOT NULL 
            AND pr.request_id NOT IN (
                SELECT COALESCE(rs.request_id, 0) 
                FROM collectionroutes cr 
                LEFT JOIN routestops rs ON cr.route_id = rs.route_id 
                WHERE cr.worker_id = ? AND DATE(cr.generated_on) = CURDATE()
            )
            ORDER BY pr.preferred_date ASC
        ");
        $pickupStmt->bind_param("ii", $workerId, $workerId);
        $pickupStmt->execute();
        $pickupResult = $pickupStmt->get_result();

        $pickupRequests = [];
        while ($row = $pickupResult->fetch_assoc()) {
            if (!is_array($row)) {
                continue;
            }

            $pickupRequests[] = [
                'request_id' => (int) $row['request_id'],
                'type' => 'pickup',
                'latitude' => (float) $row['latitude'],
                'longitude' => (float) $row['longitude'],
                'weight_kg' => (float) $row['weight_kg'],
                'preferred_date' => $row['preferred_date'],
                'status' => $row['status'],
                'resident_name' => $row['resident_name'],
                'address' => $row['address'],
                'phone' => $row['phone'],
                'material_type' => $row['material_type'],
                'points_per_kg' => (float) $row['points_per_kg'],
                'priority_score' => $this->calculatePriorityScore($row),
            ];
        }

        return $pickupRequests;
    }

    /**
     * Get active collection points that should be included in routes.
     *
     * @return list<array<string, mixed>>
     */
    private function getCollectionPoints(): array
    {
        $collectionPointsStmt = $this->legacy()->prepare("
            SELECT cp.point_id, cp.name, cp.latitude, cp.longitude, cp.description,
                   p.name as purok_name
            FROM collection_points cp
            JOIN puroks p ON cp.purok_id = p.purok_id
            WHERE cp.status = 'Active' 
            AND cp.latitude IS NOT NULL 
            AND cp.longitude IS NOT NULL
            ORDER BY cp.name
        ");
        $collectionPointsStmt->execute();
        $collectionPointsResult = $collectionPointsStmt->get_result();

        $collectionPoints = [];
        while ($row = $collectionPointsResult->fetch_assoc()) {
            if (!is_array($row)) {
                continue;
            }

            $collectionPoints[] = [
                'point_id' => (int) $row['point_id'],
                'type' => 'collection_point',
                'latitude' => (float) $row['latitude'],
                'longitude' => (float) $row['longitude'],
                'name' => (string) $row['name'],
                'description' => $row['description'],
                'purok_name' => $row['purok_name'],
                'priority_score' => 50,
            ];
        }

        return $collectionPoints;
    }

    /**
     * Solve Traveling Salesman Problem using 2-opt optimization.
     *
     * @param array<string, mixed> $start
     * @param list<array<string, mixed>> $pickups
     * @return list<array<string, mixed>>
     */
    private function solveTSP(array $start, array $pickups, bool $includeReturn = true): array
    {
        if ($pickups === []) {
            return [$start];
        }

        $route = $this->nearestNeighborTSP($start, $pickups);
        $route = $this->twoOptOptimization($route);

        return $route;
    }

    /**
     * Nearest Neighbor algorithm for TSP.
     *
     * @param array<string, mixed> $start
     * @param list<array<string, mixed>> $pickups
     * @return list<array<string, mixed>>
     */
    private function nearestNeighborTSP(array $start, array $pickups): array
    {
        $route = [$start];
        $remaining = $pickups;
        $current = $start;

        while ($remaining !== []) {
            $nearestIndex = 0;
            $nearestDistance = $this->calculateDistance(
                (float) $current['latitude'],
                (float) $current['longitude'],
                (float) $remaining[0]['latitude'],
                (float) $remaining[0]['longitude'],
            );

            for ($i = 1; $i < count($remaining); $i++) {
                $distance = $this->calculateDistance(
                    (float) $current['latitude'],
                    (float) $current['longitude'],
                    (float) $remaining[$i]['latitude'],
                    (float) $remaining[$i]['longitude'],
                );

                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestIndex = $i;
                }
            }

            $current = $remaining[$nearestIndex];
            $route[] = $current;
            array_splice($remaining, $nearestIndex, 1);
        }

        return $route;
    }

    /**
     * 2-opt optimization to improve TSP solution.
     *
     * @param list<array<string, mixed>> $route
     * @return list<array<string, mixed>>
     */
    private function twoOptOptimization(array $route): array
    {
        $improved = true;
        $bestRoute = $route;
        $bestDistance = $this->calculateTotalDistance($route, false);

        while ($improved) {
            $improved = false;

            for ($i = 1; $i < count($route) - 2; $i++) {
                for ($j = $i + 1; $j < count($route); $j++) {
                    if ($j - $i === 1) {
                        continue; // Skip adjacent edges
                    }

                    $newRoute = $this->twoOptSwap($route, $i, $j);
                    $newDistance = $this->calculateTotalDistance($newRoute, false);

                    if ($newDistance < $bestDistance) {
                        $bestRoute = $newRoute;
                        $bestDistance = $newDistance;
                        $improved = true;
                    }
                }
            }

            $route = $bestRoute;
        }

        return $bestRoute;
    }

    /**
     * Perform 2-opt swap.
     *
     * @param list<array<string, mixed>> $route
     * @return list<array<string, mixed>>
     */
    private function twoOptSwap(array $route, int $i, int $j): array
    {
        $newRoute = array_slice($route, 0, $i);
        $newRoute = array_merge($newRoute, array_reverse(array_slice($route, $i, $j - $i + 1)));
        $newRoute = array_merge($newRoute, array_slice($route, $j + 1));

        return $newRoute;
    }

    /**
     * Enhanced route optimization with priority scoring.
     *
     * @param array<string, mixed> $start
     * @param list<array<string, mixed>> $pickups
     * @return list<array<string, mixed>>
     */
    private function optimizeRoute(array $start, array $pickups): array
    {
        if ($pickups === []) {
            return [$start];
        }

        usort($pickups, static fn(array $a, array $b): int => ($b['priority_score'] ?? 0) <=> ($a['priority_score'] ?? 0));

        $route = [$start];
        $remaining = $pickups;
        $current = $start;

        while ($remaining !== []) {
            $bestIndex = 0;
            $bestScore = -1.0;

            foreach ($remaining as $index => $pickup) {
                $distance = $this->calculateDistance(
                    (float) $current['latitude'],
                    (float) $current['longitude'],
                    (float) ($pickup['latitude'] ?? 0.0),
                    (float) ($pickup['longitude'] ?? 0.0),
                );

                $distanceScore = max(0.0, 50.0 - ($distance * 5.0));
                $priorityWeight = ((float) ($pickup['priority_score'] ?? 0)) * 0.8;
                $combinedScore = $distanceScore + $priorityWeight;

                if ($combinedScore > $bestScore) {
                    $bestScore = $combinedScore;
                    $bestIndex = $index;
                }
            }

            $nextPickup = $remaining[$bestIndex];
            $route[] = $nextPickup;
            $current = $nextPickup;
            array_splice($remaining, $bestIndex, 1);
        }

        return $route;
    }

    /**
     * Calculate priority score for a pickup request.
     *
     * @param array<string, mixed> $pickup
     */
    private function calculatePriorityScore(array $pickup): float
    {
        $score = 0.0;

        $preferredDate = $pickup['preferred_date'] ?? null;
        if (is_string($preferredDate) && $preferredDate !== '') {
            $timestamp = strtotime($preferredDate);
            if ($timestamp !== false) {
                $daysUntil = ($timestamp - time()) / 86400;
                if ($daysUntil <= 1) {
                    $score += 100.0;
                } elseif ($daysUntil <= 3) {
                    $score += 60.0;
                } elseif ($daysUntil <= 7) {
                    $score += 30.0;
                }
            }
        }

        $weight = (float) ($pickup['weight_kg'] ?? 0);
        $score += min($weight * 3.0, 30.0);

        $pointsPerKg = (float) ($pickup['points_per_kg'] ?? 0);
        $pointsValue = $pointsPerKg * $weight;
        $score += min($pointsValue * 0.2, 20.0);

        return $score;
    }

    /**
     * Get complete street route geometry for the entire route.
     *
     * @param list<array<string, mixed>> $routeStops
     * @return array<string, mixed>
     */
    private function getCompleteStreetRoute(array $routeStops, bool $includeReturn = false): array
    {
        if (count($routeStops) < 2) {
            return [
                'coordinates' => [],
                'segments' => [],
                'total_duration' => 0,
            ];
        }

        $allCoordinates = [];
        $segments = [];
        $totalDuration = 0.0;

        for ($i = 0; $i < count($routeStops) - 1; $i++) {
            $from = $routeStops[$i];
            $to = $routeStops[$i + 1];

            $segment = $this->getStreetRouteSegment(
                (float) ($from['latitude'] ?? 0.0),
                (float) ($from['longitude'] ?? 0.0),
                (float) ($to['latitude'] ?? 0.0),
                (float) ($to['longitude'] ?? 0.0),
            );

            if (is_array($segment)) {
                $segments[] = $segment;
                $allCoordinates = array_merge($allCoordinates, $segment['coordinates']);
                $totalDuration += (float) $segment['duration'];
            } else {
                $segments[] = [
                    'coordinates' => [
                        [(float) ($from['longitude'] ?? 0.0), (float) ($from['latitude'] ?? 0.0)],
                        [(float) ($to['longitude'] ?? 0.0), (float) ($to['latitude'] ?? 0.0)],
                    ],
                    'distance' => $this->calculateHaversineDistance(
                        (float) ($from['latitude'] ?? 0.0),
                        (float) ($from['longitude'] ?? 0.0),
                        (float) ($to['latitude'] ?? 0.0),
                        (float) ($to['longitude'] ?? 0.0),
                    ) * 1000,
                    'duration' => 0.0,
                    'fallback' => true,
                ];
                $allCoordinates[] = [(float) ($from['longitude'] ?? 0.0), (float) ($from['latitude'] ?? 0.0)];
                $allCoordinates[] = [(float) ($to['longitude'] ?? 0.0), (float) ($to['latitude'] ?? 0.0)];
            }
        }

        if ($includeReturn && count($routeStops) > 1) {
            $lastStop = $routeStops[count($routeStops) - 1];
            $firstStop = $routeStops[0];

            $returnSegment = $this->getStreetRouteSegment(
                (float) ($lastStop['latitude'] ?? 0.0),
                (float) ($lastStop['longitude'] ?? 0.0),
                (float) ($firstStop['latitude'] ?? 0.0),
                (float) ($firstStop['longitude'] ?? 0.0),
            );

            if (is_array($returnSegment)) {
                $segments[] = array_merge($returnSegment, ['is_return' => true]);
                $allCoordinates = array_merge($allCoordinates, $returnSegment['coordinates']);
                $totalDuration += (float) $returnSegment['duration'];
            }
        }

        return [
            'coordinates' => $allCoordinates,
            'segments' => $segments,
            'total_duration' => $totalDuration,
            'waypoints' => array_map([$this, 'formatWaypoint'], $routeStops),
        ];
    }

    /**
     * Format a stop into a route waypoint.
     *
     * @param array<string, mixed> $stop
     * @return array<string, mixed>
     */
    private function formatWaypoint(array $stop): array
    {
        $name = 'Waypoint';
        if (isset($stop['resident_name'])) {
            $name = (string) $stop['resident_name'];
        } elseif (isset($stop['name'])) {
            $name = (string) $stop['name'];
        } elseif (isset($stop['type']) && $stop['type'] === 'collection_point') {
            $name = 'Collection Point';
        }

        return [
            'latitude' => (float) ($stop['latitude'] ?? 0.0),
            'longitude' => (float) ($stop['longitude'] ?? 0.0),
            'name' => $name,
            'type' => (string) ($stop['type'] ?? 'pickup'),
        ];
    }

    /**
     * Get street route segment between two points with full geometry.
     *
     * @return array<string, mixed>|false
     */
    private function getStreetRouteSegment(float $lat1, float $lon1, float $lat2, float $lon2): array|false
    {
        try {
            $osrmUrl = "http://router.project-osrm.org/route/v1/driving/{$lon1},{$lat1};{$lon2},{$lat2}?overview=full&geometries=geojson&steps=false";

            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'method' => 'GET',
                    'header' => 'User-Agent: BoteBank-StreetRouter/1.0',
                ],
            ]);

            $response = @file_get_contents($osrmUrl, false, $context);

            if ($response === false) {
                return false;
            }

            /** @var mixed $data */
            $data = json_decode($response, true);

            if (is_array($data) && isset($data['routes'][0]) && is_array($data['routes'][0])) {
                $route = $data['routes'][0];

                return [
                    'coordinates' => is_array($route['geometry']['coordinates'] ?? null) ? $route['geometry']['coordinates'] : [],
                    'distance' => (float) ($route['distance'] ?? 0),
                    'duration' => (float) ($route['duration'] ?? 0),
                    'geometry_type' => 'street_routing',
                ];
            }

            return false;
        } catch (\Exception $e) {
            error_log("Street route segment error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate distance between two points using street routing (OSRM) with fallback to Haversine.
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2, bool $useStreetRouting = true): float
    {
        if ($useStreetRouting) {
            $streetDistance = $this->getStreetRoutingDistance($lat1, $lon1, $lat2, $lon2);
            if ($streetDistance !== false) {
                return $streetDistance;
            }
        }

        return $this->calculateHaversineDistance($lat1, $lon1, $lat2, $lon2);
    }

    /**
     * Get actual street routing distance using OSRM.
     *
     * @return float|false
     */
    private function getStreetRoutingDistance(float $lat1, float $lon1, float $lat2, float $lon2): float|false
    {
        try {
            $osrmUrl = "http://router.project-osrm.org/route/v1/driving/{$lon1},{$lat1};{$lon2},{$lat2}?overview=false&alternatives=false&steps=false";

            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET',
                    'header' => 'User-Agent: BoteBank-RouteOptimizer/1.0',
                ],
            ]);

            $response = @file_get_contents($osrmUrl, false, $context);

            if ($response === false) {
                return false;
            }

            /** @var mixed $data */
            $data = json_decode($response, true);

            if (is_array($data) && isset($data['routes'][0]['distance'])) {
                return round((float) $data['routes'][0]['distance'] / 1000, 3);
            }

            return false;
        } catch (\Exception $e) {
            error_log("Street routing error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate direct distance between two points using the Haversine formula.
     */
    private function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calculate total distance of a route with optional return path.
     *
     * @param list<array<string, mixed>> $route
     */
    private function calculateTotalDistance(array $route, bool $includeReturn = false): float
    {
        if (count($route) < 2) {
            return 0.0;
        }

        $totalDistance = 0.0;

        for ($i = 0; $i < count($route) - 1; $i++) {
            $totalDistance += $this->calculateDistance(
                (float) ($route[$i]['latitude'] ?? 0.0),
                (float) ($route[$i]['longitude'] ?? 0.0),
                (float) ($route[$i + 1]['latitude'] ?? 0.0),
                (float) ($route[$i + 1]['longitude'] ?? 0.0),
            );
        }

        if ($includeReturn && count($route) > 1) {
            $lastStop = $route[count($route) - 1];
            $firstStop = $route[0];

            $totalDistance += $this->calculateDistance(
                (float) ($lastStop['latitude'] ?? 0.0),
                (float) ($lastStop['longitude'] ?? 0.0),
                (float) ($firstStop['latitude'] ?? 0.0),
                (float) ($firstStop['longitude'] ?? 0.0),
            );
        }

        return round($totalDistance, 2);
    }

    /**
     * Estimate travel time.
     */
    private function estimateTime(float $distanceKm, int $numStops): string
    {
        $averageSpeed = 25.0; // km/h in urban areas with traffic
        $stopTime = 20; // minutes per pickup stop

        $travelTimeHours = $distanceKm / $averageSpeed;
        $stopTimeHours = ($numStops * $stopTime) / 60.0;

        $totalHours = $travelTimeHours + $stopTimeHours;

        if ($totalHours < 1.0) {
            return (string) round($totalHours * 60) . ' minutes';
        }

        $hours = (int) floor($totalHours);
        $minutes = (int) round(($totalHours - $hours) * 60.0);

        return $hours . 'h ' . $minutes . 'm';
    }

    /**
     * Calculate estimated fuel savings compared to random routing.
     *
     * @param list<array<string, mixed>> $pickups
     * @param list<array<string, mixed>> $optimizedRoute
     */
    private function calculateFuelSavings(array $pickups, array $optimizedRoute): string
    {
        if (count($pickups) < 2) {
            return "N/A";
        }

        $randomDistance = 0.0;
        foreach ($pickups as $pickup) {
            $randomDistance += $this->calculateDistance(
                (float) ($optimizedRoute[0]['latitude'] ?? 0.0),
                (float) ($optimizedRoute[0]['longitude'] ?? 0.0),
                (float) ($pickup['latitude'] ?? 0.0),
                (float) ($pickup['longitude'] ?? 0.0),
            ) * 2.0;
        }

        $optimizedDistance = $this->calculateTotalDistance($optimizedRoute, true);
        $savingsKm = max(0.0, $randomDistance - $optimizedDistance);
        $savingsPercent = $randomDistance > 0.0 ? round(($savingsKm / $randomDistance) * 100.0) : 0;

        return round($savingsKm, 1) . "km ({$savingsPercent}% savings)";
    }
}
