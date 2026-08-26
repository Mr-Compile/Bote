<?php
// Auto-generation functions for routes

// Auto-generate route if worker has scheduled pickups but no route today
function autoGenerateRouteIfNeeded($conn, $worker_id, $starting_point) {
    // Check if worker has route for today
    $route_check = prepare_statement($conn, "
        SELECT COUNT(*) as route_count 
        FROM collectionroutes 
        WHERE worker_id = ? AND DATE(generated_on) = CURDATE()
    ");
    $route_check->bind_param("i", $worker_id);
    $route_check->execute();
    $has_route = $route_check->get_result()->fetch_assoc()['route_count'] > 0;
    
    if ($has_route) {
        return; // Route already exists
    }
    
    // Check if worker has scheduled pickups
    $pickup_check = prepare_statement($conn, "
        SELECT COUNT(*) as pickup_count 
        FROM pickuprequests 
        WHERE assigned_worker_id = ? AND status = 'Scheduled' 
        AND latitude IS NOT NULL AND longitude IS NOT NULL
    ");
    $pickup_check->bind_param("i", $worker_id);
    $pickup_check->execute();
    $has_pickups = $pickup_check->get_result()->fetch_assoc()['pickup_count'] > 0;
    
    if (!$has_pickups) {
        return; // No pickups to route
    }
    
    // Auto-generate route
    generateAutoRoute($conn, $worker_id, $starting_point);
}

function generateAutoRoute($conn, $worker_id, $starting_point) {
    try {
        // Get scheduled pickup requests
        $pickup_stmt = prepare_statement($conn, "
            SELECT pr.request_id, pr.latitude, pr.longitude, pr.weight_kg, pr.preferred_date,
                   CONCAT(u.first_name, ' ', u.last_name) as resident_name, u.address,
                   rt.name as material_type, rt.points_per_kg
            FROM pickuprequests pr
            JOIN users u ON pr.resident_id = u.user_id
            JOIN recyclabletypes rt ON pr.type_id = rt.type_id
            WHERE pr.assigned_worker_id = ? AND pr.status = 'Scheduled'
            AND pr.latitude IS NOT NULL AND pr.longitude IS NOT NULL
            ORDER BY pr.preferred_date ASC
        ");
        $pickup_stmt->bind_param("i", $worker_id);
        $pickup_stmt->execute();
        $pickup_result = $pickup_stmt->get_result();
        
        // Get all active collection points
        $collection_stmt = prepare_statement($conn, "
            SELECT cp.point_id, cp.name, cp.latitude, cp.longitude, cp.description,
                   p.name as purok_name
            FROM collection_points cp
            JOIN puroks p ON cp.purok_id = p.purok_id
            WHERE cp.status = 'Active' 
            AND cp.latitude IS NOT NULL AND cp.longitude IS NOT NULL
            ORDER BY cp.name
        ");
        $collection_stmt->execute();
        $collection_result = $collection_stmt->get_result();
        
        $all_stops = [];
        
        // Add starting point
        $all_stops[] = [
            'type' => 'start',
            'latitude' => $starting_point['lat'],
            'longitude' => $starting_point['lng'],
            'name' => 'Starting Point'
        ];
        
        // Add pickup requests
        while ($pickup = $pickup_result->fetch_assoc()) {
            $all_stops[] = [
                'type' => 'pickup',
                'request_id' => (int)$pickup['request_id'],
                'latitude' => floatval($pickup['latitude']),
                'longitude' => floatval($pickup['longitude']),
                'resident_name' => $pickup['resident_name'],
                'material_type' => $pickup['material_type']
            ];
        }
        
        // Add all collection points
        while ($point = $collection_result->fetch_assoc()) {
            $all_stops[] = [
                'type' => 'collection_point',
                'point_id' => (int)$point['point_id'],
                'latitude' => floatval($point['latitude']),
                'longitude' => floatval($point['longitude']),
                'name' => $point['name'],
                'purok_name' => $point['purok_name']
            ];
        }
        
        if (count($all_stops) <= 1) {
            return; // No stops to route
        }
        
        // Optimize route using nearest neighbor algorithm
        $optimized_route = optimizeRouteSimple($all_stops);
        
        // Generate street routing
        $street_geometry = generateStreetRoute($optimized_route);
        
        // Calculate totals from street routing if available, otherwise use Haversine
        $total_distance = $street_geometry['total_distance'] > 0 
            ? ($street_geometry['total_distance'] / 1000) // Convert from meters to km
            : calculateTotalDistance($optimized_route);
        
        $estimated_time = estimateTime($total_distance, count($optimized_route) - 1);
        
        // Create route geometry
        $route_geometry = json_encode([
            'type' => 'LineString',
            'coordinates' => array_map(function($stop) {
                return [$stop['longitude'], $stop['latitude']];
            }, $optimized_route),
            'street_geometry' => $street_geometry,
            'total_distance' => $total_distance,
            'estimated_time' => $estimated_time,
            'auto_generated' => true,
            'routing_method' => 'osrm_street_routing',
            'stops_count' => count($optimized_route) - 1, // Exclude starting point
            'pickup_count' => count(array_filter($optimized_route, function($stop) { return $stop['type'] === 'pickup'; })),
            'collection_point_count' => count(array_filter($optimized_route, function($stop) { return $stop['type'] === 'collection_point'; }))
        ]);
        
        // Insert route
        $route_stmt = prepare_statement($conn, "INSERT INTO collectionroutes (worker_id, route_geometry) VALUES (?, ?)");
        $route_stmt->bind_param("is", $worker_id, $route_geometry);
        $route_stmt->execute();
        $route_id = $conn->insert_id;
        
        // Insert route stops (skip the starting point for route stops table)
        $stop_stmt = prepare_statement($conn, "INSERT INTO routestops (route_id, request_id, point_id, stop_order, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($optimized_route as $index => $stop) {
            // Skip the starting point and the return to start point
            if ($stop['type'] === 'start') {
                continue;
            }
            
            $request_id = isset($stop['request_id']) ? $stop['request_id'] : null;
            $point_id = isset($stop['point_id']) ? $stop['point_id'] : null;
            $stop_order = $index; // Adjusted for skipped starting point
            $latitude = $stop['latitude'];
            $longitude = $stop['longitude'];
            
            $stop_stmt->bind_param("iiiidd",
                $route_id, 
                $request_id,
                $point_id, 
                $stop_order, 
                $latitude, 
                $longitude
            );
            $stop_stmt->execute();
        }        echo "✓ Generated route with ID: $route_id\n";
        
    } catch (Exception $e) {
        error_log("Auto route generation error: " . $e->getMessage());
        echo "Error generating route: " . $e->getMessage() . "\n";
    }
}

function optimizeRouteSimple($stops) {
    if (count($stops) <= 2) return $stops;
    
    $start = $stops[0];
    $remaining = array_slice($stops, 1);
    $route = [$start];
    $current = $start;
    
    // Separate pickups and collection points
    $pickups = array_filter($remaining, function($stop) {
        return $stop['type'] === 'pickup';
    });
    $collections = array_filter($remaining, function($stop) {
        return $stop['type'] === 'collection_point';
    });
    
    // Strategy: Create an optimized mixed route
    // This finds the nearest stop regardless of type, creating a more efficient overall route
    $allStops = array_merge(array_values($pickups), array_values($collections));
    $remainingStops = $allStops;
    
    while (!empty($remainingStops)) {
        $nearest_index = 0;
        $nearest_distance = calculateHaversineDistance(
            $current['latitude'], $current['longitude'],
            $remainingStops[0]['latitude'], $remainingStops[0]['longitude']
        );
        
        // Find the nearest stop regardless of type
        for ($i = 1; $i < count($remainingStops); $i++) {
            $distance = calculateHaversineDistance(
                $current['latitude'], $current['longitude'],
                $remainingStops[$i]['latitude'], $remainingStops[$i]['longitude']
            );
            
            if ($distance < $nearest_distance) {
                $nearest_distance = $distance;
                $nearest_index = $i;
            }
        }
        
        $current = $remainingStops[$nearest_index];
        $route[] = $current;
        array_splice($remainingStops, $nearest_index, 1);
    }
    
    // Add return to start
    $route[] = $start;
    
    return $route;
}

function generateStreetRoute($route) {
    $segments = [];
    $is_return_journey = false;
    
    for ($i = 0; $i < count($route) - 1; $i++) {
        $from = $route[$i];
        $to = $route[$i + 1];
        
        // Check if this is the return journey (last segment back to start)
        $is_return_journey = ($i == count($route) - 2) && 
                             ($to['type'] === 'start' || 
                              ($to['latitude'] == $route[0]['latitude'] && $to['longitude'] == $route[0]['longitude']));
        
        $segment = getStreetRouteSegment(
            $from['latitude'], $from['longitude'],
            $to['latitude'], $to['longitude']
        );
        
        if ($segment) {
            $segment['is_return'] = $is_return_journey;
            $segment['from_stop'] = $from['name'] ?? $from['resident_name'] ?? 'Stop ' . ($i + 1);
            $segment['to_stop'] = $to['name'] ?? $to['resident_name'] ?? 'Stop ' . ($i + 2);
            $segments[] = $segment;
        } else {
            // Fallback to direct line
            $distance = calculateHaversineDistance(
                $from['latitude'], $from['longitude'],
                $to['latitude'], $to['longitude']
            ) * 1000; // Convert to meters
            
            $segments[] = [
                'coordinates' => [
                    [$from['longitude'], $from['latitude']],
                    [$to['longitude'], $to['latitude']]
                ],
                'distance' => $distance,
                'duration' => ($distance / 1000) * 120, // Rough estimate: 30 km/h = 2 minutes per km
                'fallback' => true,
                'is_return' => $is_return_journey,
                'from_stop' => $from['name'] ?? $from['resident_name'] ?? 'Stop ' . ($i + 1),
                'to_stop' => $to['name'] ?? $to['resident_name'] ?? 'Stop ' . ($i + 2)
            ];
        }
    }
    
    return [
        'segments' => $segments,
        'total_duration' => array_sum(array_column($segments, 'duration')),
        'total_distance' => array_sum(array_column($segments, 'distance'))
    ];
}

function getStreetRouteSegment($lat1, $lon1, $lat2, $lon2) {
    try {
        $osrm_url = "http://router.project-osrm.org/route/v1/driving/{$lon1},{$lat1};{$lon2},{$lat2}?overview=full&geometries=geojson";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'method' => 'GET',
                'header' => 'User-Agent: BoteBank-AutoRouter/1.0'
            ]
        ]);
        
        $response = @file_get_contents($osrm_url, false, $context);
        
        if ($response === false) {
            return false;
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['routes'][0])) {
            $route = $data['routes'][0];
            return [
                'coordinates' => $route['geometry']['coordinates'] ?? [],
                'distance' => $route['distance'] ?? 0,
                'duration' => $route['duration'] ?? 0
            ];
        }
        
        return false;
        
    } catch (Exception $e) {
        return false;
    }
}

function calculateTotalDistance($route) {
    if (count($route) < 2) return 0;
    
    $total = 0;
    for ($i = 0; $i < count($route) - 1; $i++) {
        $total += calculateHaversineDistance(
            $route[$i]['latitude'], $route[$i]['longitude'],
            $route[$i + 1]['latitude'], $route[$i + 1]['longitude']
        );
    }
    
    return round($total, 2);
}

function calculateHaversineDistance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371; // km
    
    $lat1_rad = deg2rad($lat1);
    $lng1_rad = deg2rad($lng1);
    $lat2_rad = deg2rad($lat2);
    $lng2_rad = deg2rad($lng2);
    
    $delta_lat = $lat2_rad - $lat1_rad;
    $delta_lng = $lng2_rad - $lng1_rad;
    
    $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
         cos($lat1_rad) * cos($lat2_rad) *
         sin($delta_lng / 2) * sin($delta_lng / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earth_radius * $c;
}

function estimateTime($distance_km, $num_stops) {
    $average_speed = 30; // km/h in urban areas
    $stop_time = 15; // minutes per stop
    
    $travel_time_hours = $distance_km / $average_speed;
    $stop_time_hours = ($num_stops * $stop_time) / 60;
    
    $total_hours = $travel_time_hours + $stop_time_hours;
    
    if ($total_hours < 1) {
        return round($total_hours * 60) . ' minutes';
    } else {
        $hours = floor($total_hours);
        $minutes = round(($total_hours - $hours) * 60);
        return $hours . 'h ' . $minutes . 'm';
    }
}
?>