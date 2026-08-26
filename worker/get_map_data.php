<?php
// Handle different include paths
if (file_exists('../config/database.php')) {
    require_once '../config/database.php';
    require_once '../includes/auto_route_functions.php';
    require '../includes/auth_worker.php';
} else {
    require_once 'config/database.php';
    require_once 'includes/auto_route_functions.php';
}

header('Content-Type: application/json');

try {
    $worker_id = $_SESSION['user_id'];
    $focus = $_GET['focus'] ?? 'all';
    $requested_worker_id = isset($_GET['worker_id']) ? (int)$_GET['worker_id'] : $worker_id;
    $requested_date = $_GET['date'] ?? date('Y-m-d');
    
    $response = [
        'success' => true,
        'admin_location' => null,
        'worker_pickups' => [],
        'today_pickups' => [],
        'collection_points' => [],
        'routes' => []
    ];
    
    // Get admin/barangay location (starting point)
    $stmt = prepare_statement($conn, "SELECT latitude, longitude FROM users WHERE role = 'admin' AND latitude IS NOT NULL AND longitude IS NOT NULL LIMIT 1");
    $stmt->execute();
    $admin_result = $stmt->get_result()->fetch_assoc();
    
    if ($admin_result) {
        $response['admin_location'] = [
            'lat' => floatval($admin_result['latitude']),
            'lng' => floatval($admin_result['longitude'])
        ];
    } else {
        // Fallback to purok coordinates if no admin location
        $purok_stmt = prepare_statement($conn, "SELECT latitude, longitude FROM puroks WHERE latitude IS NOT NULL AND longitude IS NOT NULL LIMIT 1");
        $purok_stmt->execute();
        $purok_result = $purok_stmt->get_result()->fetch_assoc();
        
        if ($purok_result) {
            $response['admin_location'] = [
                'lat' => floatval($purok_result['latitude']),
                'lng' => floatval($purok_result['longitude'])
            ];
        } else {
            // Default to Barangay Nagpan coordinates
            $response['admin_location'] = [
                'lat' => 6.4216,
                'lng' => 124.7810
            ];
        }
    }
    
    // Auto-generate today's route if worker has scheduled pickups but no route exists
    autoGenerateRouteIfNeeded($conn, $requested_worker_id, $response['admin_location']);
    
    // Get today's pickup requests for the worker
    $today_pickup_stmt = prepare_statement($conn, "
        SELECT pr.request_id, pr.latitude, pr.longitude, pr.weight_kg, pr.quantity_pcs, pr.status, 
               pr.preferred_date, pr.created_at, pr.updated_at, pr.actual_weight, pr.points_earned,
               CONCAT(u.first_name, ' ', u.last_name) as resident_name, u.address, u.phone,
               rt.name as material_type, rt.unit, rt.points_per_kg, rt.points_per_unit
        FROM pickuprequests pr
        JOIN users u ON pr.resident_id = u.user_id
        JOIN recyclabletypes rt ON pr.type_id = rt.type_id
        WHERE pr.assigned_worker_id = ? 
        AND DATE(pr.preferred_date) = ?
        AND pr.latitude IS NOT NULL 
        AND pr.longitude IS NOT NULL
        ORDER BY pr.preferred_date ASC, pr.status ASC
    ");
    $today_pickup_stmt->bind_param("is", $requested_worker_id, $requested_date);
    $today_pickup_stmt->execute();
    $today_pickup_result = $today_pickup_stmt->get_result();
    
    while ($pickup = $today_pickup_result->fetch_assoc()) {
        $response['today_pickups'][] = [
            'request_id' => (int)$pickup['request_id'],
            'lat' => floatval($pickup['latitude']),
            'lng' => floatval($pickup['longitude']),
            'weight_kg' => floatval($pickup['weight_kg']),
            'quantity_pcs' => $pickup['quantity_pcs'] ? (int)$pickup['quantity_pcs'] : null,
            'status' => $pickup['status'],
            'preferred_date' => $pickup['preferred_date'],
            'created_at' => $pickup['created_at'],
            'updated_at' => $pickup['updated_at'],
            'actual_weight' => $pickup['actual_weight'] ? floatval($pickup['actual_weight']) : null,
            'points_earned' => $pickup['points_earned'] ? (int)$pickup['points_earned'] : null,
            'resident_name' => $pickup['resident_name'],
            'address' => $pickup['address'],
            'phone' => $pickup['phone'],
            'material_type' => $pickup['material_type'],
            'unit' => $pickup['unit'],
            'points_per_kg' => $pickup['points_per_kg'] ? floatval($pickup['points_per_kg']) : null,
            'points_per_unit' => $pickup['points_per_unit'] ? floatval($pickup['points_per_unit']) : null
        ];
    }
    
    // Get worker's assigned pickup requests with details (all time for legacy support)
    $pickup_stmt = prepare_statement($conn, "
        SELECT pr.request_id, pr.latitude, pr.longitude, pr.weight_kg, pr.status, pr.preferred_date,
               CONCAT(u.first_name, ' ', u.last_name) as resident_name, u.address, u.phone,
               rt.name as material_type, rt.points_per_kg
        FROM pickuprequests pr
        JOIN users u ON pr.resident_id = u.user_id
        JOIN recyclabletypes rt ON pr.type_id = rt.type_id
        WHERE pr.assigned_worker_id = ? AND pr.latitude IS NOT NULL AND pr.longitude IS NOT NULL
        ORDER BY pr.status, pr.preferred_date
    ");
    $pickup_stmt->bind_param("i", $requested_worker_id);
    $pickup_stmt->execute();
    $pickup_result = $pickup_stmt->get_result();
    
    while ($pickup = $pickup_result->fetch_assoc()) {
        $response['worker_pickups'][] = [
            'request_id' => (int)$pickup['request_id'],
            'lat' => floatval($pickup['latitude']),
            'lng' => floatval($pickup['longitude']),
            'weight_kg' => floatval($pickup['weight_kg']),
            'status' => $pickup['status'],
            'preferred_date' => date('M j, Y g:i A', strtotime($pickup['preferred_date'])),
            'resident_name' => $pickup['resident_name'],
            'address' => $pickup['address'],
            'phone' => $pickup['phone'],
            'material_type' => $pickup['material_type'],
            'points_per_kg' => floatval($pickup['points_per_kg'])
        ];
    }
    
    // Get active collection points
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
    
    while ($point = $collection_points_result->fetch_assoc()) {
        $response['collection_points'][] = [
            'point_id' => (int)$point['point_id'],
            'lat' => floatval($point['latitude']),
            'lng' => floatval($point['longitude']),
            'name' => $point['name'],
            'description' => $point['description'],
            'purok_name' => $point['purok_name']
        ];
    }
    
    // Get today's routes for this worker
    $route_stmt = prepare_statement($conn, "
        SELECT cr.route_id, cr.generated_on, cr.route_geometry
        FROM collectionroutes cr
        WHERE cr.worker_id = ? AND DATE(cr.generated_on) = ?
        ORDER BY cr.generated_on DESC
    ");
    $route_stmt->bind_param("is", $requested_worker_id, $requested_date);
    $route_stmt->execute();
    $route_result = $route_stmt->get_result();
    
    while ($route = $route_result->fetch_assoc()) {
        // Parse route geometry to extract street routing data
        $route_geometry = json_decode($route['route_geometry'], true);
        $street_geometry = $route_geometry['street_geometry'] ?? null;
        
        // Get route stops with coordinates
        $stops_stmt = prepare_statement($conn, "
            SELECT rs.stop_order, rs.latitude, rs.longitude, rs.request_id, rs.point_id,
                   CONCAT(u.first_name, ' ', u.last_name) as resident_name, pr.status,
                   cp.name as collection_point_name, rt.name as material_type
            FROM routestops rs
            LEFT JOIN pickuprequests pr ON rs.request_id = pr.request_id
            LEFT JOIN users u ON pr.resident_id = u.user_id
            LEFT JOIN collection_points cp ON rs.point_id = cp.point_id
            LEFT JOIN recyclabletypes rt ON pr.type_id = rt.type_id
            WHERE rs.route_id = ?
            ORDER BY rs.stop_order
        ");
        $stops_stmt->bind_param("i", $route['route_id']);
        $stops_stmt->execute();
        $stops_result = $stops_stmt->get_result();
        
        $stops = [];
        $total_distance = $route_geometry['total_distance'] ?? 0;
        
        // Add starting point as first stop
        $stops[] = [
            'stop_order' => 0,
            'lat' => $response['admin_location']['lat'],
            'lng' => $response['admin_location']['lng'],
            'resident_name' => 'Starting Point',
            'status' => 'start'
        ];
        
        while ($stop = $stops_result->fetch_assoc()) {
            $stop_name = $stop['resident_name'] ?: $stop['collection_point_name'] ?: 'Collection Point';
            $stop_type = $stop['request_id'] ? 'pickup' : 'collection_point';
            
            $stops[] = [
                'stop_order' => (int)$stop['stop_order'],
                'lat' => floatval($stop['latitude']),
                'lng' => floatval($stop['longitude']),
                'resident_name' => $stop_name,
                'name' => $stop['collection_point_name'],
                'status' => $stop['status'] ?: 'scheduled',
                'request_id' => $stop['request_id'],
                'point_id' => $stop['point_id'],
                'type' => $stop_type,
                'material_type' => $stop['material_type']
            ];
        }
        
        $response['routes'][] = [
            'route_id' => (int)$route['route_id'],
            'generated_on' => $route['generated_on'],
            'stops' => $stops,
            'total_distance' => $total_distance,
            'estimated_time' => $route_geometry['estimated_time'] ?? estimateTime($total_distance, count($stops) - 1),
            'street_geometry' => $street_geometry,
            'routing_method' => $route_geometry['routing_method'] ?? 'haversine_fallback'
        ];
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?>