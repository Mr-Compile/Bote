<?php
require_once '../config/database.php';
require '../includes/auth_worker.php';
header('Content-Type: application/json');

$worker_id = $_SESSION['user_id'];

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$optimize = $input['optimize'] ?? false;
$include_return = $input['include_return'] ?? false;

try {
    // Get starting point (admin location or fallback to purok)
    $starting_point = null;
    $admin_stmt = prepare_statement($conn, "SELECT latitude, longitude FROM users WHERE role = 'admin' AND latitude IS NOT NULL AND longitude IS NOT NULL LIMIT 1");
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result()->fetch_assoc();
    
    if ($admin_result) {
        $starting_point = [
            'latitude' => floatval($admin_result['latitude']),
            'longitude' => floatval($admin_result['longitude']),
            'name' => 'Barangay Office'
        ];
    } else {
        // Fallback to purok coordinates
        $purok_stmt = prepare_statement($conn, "SELECT latitude, longitude, name FROM puroks WHERE latitude IS NOT NULL AND longitude IS NOT NULL LIMIT 1");
        $purok_stmt->execute();
        $purok_result = $purok_stmt->get_result()->fetch_assoc();
        
        if ($purok_result) {
            $starting_point = [
                'latitude' => floatval($purok_result['latitude']),
                'longitude' => floatval($purok_result['longitude']),
                'name' => $purok_result['name']
            ];
        } else {
            // Default coordinates for Barangay Nagpan
            $starting_point = [
                'latitude' => 6.4216,
                'longitude' => 124.7810,
                'name' => 'Barangay Nagpan'
            ];
        }
    }

    // Get worker's scheduled pickup requests that need routing
    $pickup_stmt = prepare_statement($conn, "
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
    $pickup_stmt->bind_param("ii", $worker_id, $worker_id);
    $pickup_stmt->execute();
    $pickup_result = $pickup_stmt->get_result();

    // Get active collection points that should be included in routes
    $collection_points_stmt = prepare_statement($conn, "
        SELECT cp.point_id, cp.name, cp.latitude, cp.longitude, cp.description,
               p.name as purok_name
        FROM collection_points cp
        JOIN puroks p ON cp.purok_id = p.purok_id
        WHERE cp.status = 'Active' 
        AND cp.latitude IS NOT NULL 
        AND cp.longitude IS NOT NULL
        ORDER BY cp.name
    ");
    $collection_points_stmt->execute();
    $collection_points_result = $collection_points_stmt->get_result();

    $pickup_requests = [];
    $collection_points = [];
    
    // Process pickup requests
    while ($row = $pickup_result->fetch_assoc()) {
        $pickup_requests[] = [
            'request_id' => (int)$row['request_id'],
            'type' => 'pickup',
            'latitude' => floatval($row['latitude']),
            'longitude' => floatval($row['longitude']),
            'weight_kg' => floatval($row['weight_kg']),
            'preferred_date' => $row['preferred_date'],
            'status' => $row['status'],
            'resident_name' => $row['resident_name'],
            'address' => $row['address'],
            'phone' => $row['phone'],
            'material_type' => $row['material_type'],
            'points_per_kg' => floatval($row['points_per_kg']),
            'priority_score' => calculatePriorityScore($row)
        ];
    }
    
    // Process collection points
    while ($row = $collection_points_result->fetch_assoc()) {
        $collection_points[] = [
            'point_id' => (int)$row['point_id'],
            'type' => 'collection_point',
            'latitude' => floatval($row['latitude']),
            'longitude' => floatval($row['longitude']),
            'name' => $row['name'],
            'description' => $row['description'],
            'purok_name' => $row['purok_name'],
            'priority_score' => 50 // Medium priority for collection points
        ];
    }

    // Combine all stops (pickup requests and collection points)
    $all_stops = array_merge($pickup_requests, $collection_points);
    
    if (empty($all_stops)) {
        echo json_encode([
            'success' => false, 
            'message' => 'No pickup requests or collection points found for routing.'
        ]);
        exit;
    }

    // Generate optimized route using TSP algorithms
    if ($optimize) {
        $optimized_route = solveTSP($starting_point, $all_stops, $include_return);
    } else {
        $optimized_route = optimizeRoute($starting_point, $all_stops);
    }

    // Get detailed street routing for the optimized route
    $street_route_geometry = getCompleteStreetRoute($optimized_route, $include_return);

    // Calculate route statistics using street routing
    $total_distance = calculateTotalDistance($optimized_route, $include_return);
    $estimated_time = estimateTime($total_distance, count($all_stops));
    $fuel_savings = calculateFuelSavings($all_stops, $optimized_route);

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Create route geometry with street routing data
        $route_geometry = json_encode([
            'type' => 'LineString',
            'coordinates' => $street_route_geometry['coordinates'] ?? array_map(function($stop) {
                return [$stop['longitude'], $stop['latitude']];
            }, $optimized_route),
            'street_geometry' => $street_route_geometry,
            'total_distance' => $total_distance,
            'estimated_time' => $estimated_time,
            'fuel_savings' => $fuel_savings,
            'routing_method' => 'street_routing_with_osrm'
        ]);
        
        // Insert route into collectionroutes
        $route_stmt = prepare_statement($conn, "INSERT INTO collectionroutes (worker_id, route_geometry) VALUES (?, ?)");
        $route_stmt->bind_param("is", $worker_id, $route_geometry);
        $route_stmt->execute();
        $route_id = $conn->insert_id;

        // Insert route stops
        $stop_stmt = prepare_statement($conn, "INSERT INTO routestops (route_id, request_id, point_id, stop_order, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($optimized_route as $index => $stop) {
            $request_id = isset($stop['request_id']) ? $stop['request_id'] : null;
            $point_id = isset($stop['point_id']) ? $stop['point_id'] : null;
            $stop_stmt->bind_param("iiidd", 
                $route_id, 
                $request_id,
                $point_id, 
                $index + 1, 
                $stop['latitude'], 
                $stop['longitude']
            );
            $stop_stmt->execute();
        }

        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'route_id' => $route_id,
            'message' => 'Optimized route generated successfully!',
            'route_info' => [
                'total_stops' => count($all_stops),
                'pickup_requests' => count($pickup_requests),
                'collection_points' => count($collection_points),
                'total_distance' => $total_distance,
                'estimated_time' => $estimated_time,
                'fuel_savings' => $fuel_savings,
                'optimization_method' => $optimize ? 'Advanced TSP' : 'Smart Nearest Neighbor'
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error generating route: ' . $e->getMessage()
    ]);
}

/**
 * Solve Traveling Salesman Problem using 2-opt optimization
 */
function solveTSP($start, $pickups, $include_return = true) {
    if (empty($pickups)) {
        return [$start];
    }
    
    // Start with nearest neighbor solution
    $route = nearestNeighborTSP($start, $pickups);
    
    // Improve with 2-opt optimization
    $route = twoOptOptimization($route);
    
    return $route;
}

/**
 * Nearest Neighbor algorithm for TSP
 */
function nearestNeighborTSP($start, $pickups) {
    $route = [$start];
    $remaining = $pickups;
    $current = $start;
    
    while (!empty($remaining)) {
        $nearest_index = 0;
        $nearest_distance = calculateDistance(
            $current['latitude'], $current['longitude'],
            $remaining[0]['latitude'], $remaining[0]['longitude']
        );
        
        for ($i = 1; $i < count($remaining); $i++) {
            $distance = calculateDistance(
                $current['latitude'], $current['longitude'],
                $remaining[$i]['latitude'], $remaining[$i]['longitude']
            );
            
            if ($distance < $nearest_distance) {
                $nearest_distance = $distance;
                $nearest_index = $i;
            }
        }
        
        $current = $remaining[$nearest_index];
        $route[] = $current;
        array_splice($remaining, $nearest_index, 1);
    }
    
    return $route;
}

/**
 * 2-opt optimization to improve TSP solution
 */
function twoOptOptimization($route) {
    $improved = true;
    $best_route = $route;
    $best_distance = calculateTotalDistance($route, false);
    
    while ($improved) {
        $improved = false;
        
        for ($i = 1; $i < count($route) - 2; $i++) {
            for ($j = $i + 1; $j < count($route); $j++) {
                if ($j - $i == 1) continue; // Skip adjacent edges
                
                $new_route = twoOptSwap($route, $i, $j);
                $new_distance = calculateTotalDistance($new_route, false);
                
                if ($new_distance < $best_distance) {
                    $best_route = $new_route;
                    $best_distance = $new_distance;
                    $improved = true;
                }
            }
        }
        
        $route = $best_route;
    }
    
    return $best_route;
}

/**
 * Perform 2-opt swap
 */
function twoOptSwap($route, $i, $j) {
    $new_route = array_slice($route, 0, $i);
    $new_route = array_merge($new_route, array_reverse(array_slice($route, $i, $j - $i + 1)));
    $new_route = array_merge($new_route, array_slice($route, $j + 1));
    
    return $new_route;
}

/**
 * Enhanced route optimization with priority scoring
 */
function optimizeRoute($start, $pickups) {
    if (empty($pickups)) {
        return [$start];
    }
    
    // Sort by priority score first
    usort($pickups, function($a, $b) {
        return $b['priority_score'] - $a['priority_score'];
    });
    
    $route = [$start];
    $remaining = $pickups;
    $current = $start;
    
    // Apply weighted nearest neighbor algorithm
    while (!empty($remaining)) {
        $best_index = 0;
        $best_score = -1;
        
        foreach ($remaining as $index => $pickup) {
            $distance = calculateDistance(
                $current['latitude'], $current['longitude'],
                $pickup['latitude'], $pickup['longitude']
            );
            
            // Combine distance and priority (closer + higher priority = better score)
            $distance_score = max(0, 50 - ($distance * 5)); // Distance penalty
            $priority_weight = $pickup['priority_score'] * 0.8;
            $combined_score = $distance_score + $priority_weight;
            
            if ($combined_score > $best_score) {
                $best_score = $combined_score;
                $best_index = $index;
            }
        }
        
        $next_pickup = $remaining[$best_index];
        $route[] = $next_pickup;
        $current = $next_pickup;
        array_splice($remaining, $best_index, 1);
    }
    
    return $route;
}

/**
 * Calculate priority score for pickup requests
 */
function calculatePriorityScore($pickup) {
    $score = 0;
    
    // Time priority (urgent dates get higher score)
    $days_until = (strtotime($pickup['preferred_date']) - time()) / (24 * 60 * 60);
    if ($days_until <= 1) {
        $score += 100; // Very urgent
    } elseif ($days_until <= 3) {
        $score += 60; // Urgent
    } elseif ($days_until <= 7) {
        $score += 30; // Moderately urgent
    }
    
    // Weight priority (heavier items get slightly higher score)
    $score += min($pickup['weight_kg'] * 3, 30);
    
    // Points value priority
    $points_value = $pickup['points_per_kg'] * $pickup['weight_kg'];
    $score += min($points_value * 0.2, 20);
    
    return $score;
}

/**
 * Get complete street route geometry for the entire route
 */
function getCompleteStreetRoute($route_stops, $include_return = false) {
    if (count($route_stops) < 2) {
        return [
            'coordinates' => [],
            'segments' => [],
            'total_duration' => 0
        ];
    }
    
    $all_coordinates = [];
    $segments = [];
    $total_duration = 0;
    
    // Build route segments between consecutive stops
    for ($i = 0; $i < count($route_stops) - 1; $i++) {
        $from = $route_stops[$i];
        $to = $route_stops[$i + 1];
        
        $segment = getStreetRouteSegment(
            $from['latitude'], $from['longitude'],
            $to['latitude'], $to['longitude']
        );
        
        if ($segment) {
            $segments[] = $segment;
            $all_coordinates = array_merge($all_coordinates, $segment['coordinates']);
            $total_duration += $segment['duration'];
        } else {
            // Fallback to direct line if street routing fails
            $segments[] = [
                'coordinates' => [
                    [$from['longitude'], $from['latitude']],
                    [$to['longitude'], $to['latitude']]
                ],
                'distance' => calculateHaversineDistance(
                    $from['latitude'], $from['longitude'],
                    $to['latitude'], $to['longitude']
                ) * 1000, // Convert to meters
                'duration' => 0,
                'fallback' => true
            ];
            $all_coordinates[] = [$from['longitude'], $from['latitude']];
            $all_coordinates[] = [$to['longitude'], $to['latitude']];
        }
    }
    
    // Add return path if requested
    if ($include_return && count($route_stops) > 1) {
        $last_stop = $route_stops[count($route_stops) - 1];
        $first_stop = $route_stops[0];
        
        $return_segment = getStreetRouteSegment(
            $last_stop['latitude'], $last_stop['longitude'],
            $first_stop['latitude'], $first_stop['longitude']
        );
        
        if ($return_segment) {
            $segments[] = array_merge($return_segment, ['is_return' => true]);
            $all_coordinates = array_merge($all_coordinates, $return_segment['coordinates']);
            $total_duration += $return_segment['duration'];
        }
    }
    
    return [
        'coordinates' => $all_coordinates,
        'segments' => $segments,
        'total_duration' => $total_duration,
        'waypoints' => array_map(function($stop) {
            $name = 'Waypoint';
            if (isset($stop['resident_name'])) {
                $name = $stop['resident_name'];
            } else if (isset($stop['name'])) {
                $name = $stop['name'];
            } else if (isset($stop['type']) && $stop['type'] === 'collection_point') {
                $name = 'Collection Point';
            }
            
            return [
                'latitude' => $stop['latitude'],
                'longitude' => $stop['longitude'],
                'name' => $name,
                'type' => $stop['type'] ?? 'pickup'
            ];
        }, $route_stops)
    ];
}

/**
 * Get street route segment between two points with full geometry
 */
function getStreetRouteSegment($lat1, $lon1, $lat2, $lon2) {
    try {
        // Use OSRM with full geometry
        $osrm_url = "http://router.project-osrm.org/route/v1/driving/{$lon1},{$lat1};{$lon2},{$lat2}?overview=full&geometries=geojson&steps=false";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET',
                'header' => 'User-Agent: BoteBank-StreetRouter/1.0'
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
                'distance' => $route['distance'] ?? 0, // meters
                'duration' => $route['duration'] ?? 0, // seconds
                'geometry_type' => 'street_routing'
            ];
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Street route segment error: " . $e->getMessage());
        return false;
    }
}

/**
 * Calculate distance between two points using street routing (OSRM) with fallback to Haversine
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2, $use_street_routing = true) {
    if ($use_street_routing) {
        $street_distance = getStreetRoutingDistance($lat1, $lon1, $lat2, $lon2);
        if ($street_distance !== false) {
            return $street_distance;
        }
    }
    
    // Fallback to Haversine formula for direct distance
    return calculateHaversineDistance($lat1, $lon1, $lat2, $lon2);
}

/**
 * Get actual street routing distance using OSRM (OpenStreetMap Routing Machine)
 */
function getStreetRoutingDistance($lat1, $lon1, $lat2, $lon2) {
    try {
        // Use free OSRM demo server (replace with local instance for production)
        $osrm_url = "http://router.project-osrm.org/route/v1/driving/{$lon1},{$lat1};{$lon2},{$lat2}?overview=false&alternatives=false&steps=false";
        
        // Create context for HTTP request with timeout
        $context = stream_context_create([
            'http' => [
                'timeout' => 5, // 5 second timeout
                'method' => 'GET',
                'header' => 'User-Agent: BoteBank-RouteOptimizer/1.0'
            ]
        ]);
        
        $response = @file_get_contents($osrm_url, false, $context);
        
        if ($response === false) {
            return false; // Fall back to Haversine
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['routes'][0]['distance'])) {
            // Convert meters to kilometers
            return round($data['routes'][0]['distance'] / 1000, 3);
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Street routing error: " . $e->getMessage());
        return false;
    }
}

/**
 * Calculate direct distance between two points using Haversine formula
 */
function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) + 
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
         sin($dLon/2) * sin($dLon/2);
         
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earth_radius * $c;
}

/**
 * Calculate total distance of route with optional return path
 */
function calculateTotalDistance($route, $include_return = false) {
    if (count($route) < 2) return 0;
    
    $total_distance = 0;
    
    for ($i = 0; $i < count($route) - 1; $i++) {
        $total_distance += calculateDistance(
            $route[$i]['latitude'], $route[$i]['longitude'],
            $route[$i + 1]['latitude'], $route[$i + 1]['longitude']
        );
    }
    
    // Add return trip distance
    if ($include_return && count($route) > 1) {
        $last_stop = $route[count($route) - 1];
        $first_stop = $route[0];
        $total_distance += calculateDistance(
            $last_stop['latitude'], $last_stop['longitude'],
            $first_stop['latitude'], $first_stop['longitude']
        );
    }
    
    return round($total_distance, 2);
}

/**
 * Estimate travel time
 */
function estimateTime($distance_km, $num_stops) {
    $average_speed = 25; // km/h in urban areas with traffic
    $stop_time = 20; // minutes per pickup stop
    
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

/**
 * Calculate estimated fuel savings compared to random routing
 */
function calculateFuelSavings($pickups, $optimized_route) {
    if (count($pickups) < 2) return "N/A";
    
    // Estimate random route distance (worst case scenario)
    $random_distance = 0;
    foreach ($pickups as $pickup) {
        $random_distance += calculateDistance(
            $optimized_route[0]['latitude'], $optimized_route[0]['longitude'],
            $pickup['latitude'], $pickup['longitude']
        ) * 2; // Round trip for each pickup
    }
    
    $optimized_distance = calculateTotalDistance($optimized_route, true);
    $savings_km = max(0, $random_distance - $optimized_distance);
    $savings_percent = $random_distance > 0 ? round(($savings_km / $random_distance) * 100) : 0;
    
    return round($savings_km, 1) . "km ({$savings_percent}% savings)";
}
?>
