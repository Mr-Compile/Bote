<?php
require_once '../config/database.php';
require '../includes/auth_worker.php';

header('Content-Type: application/json');

if (!isset($_GET['route_id'])) {
    echo json_encode(['success' => false, 'message' => 'Route ID required']);
    exit;
}

$route_id = (int) $_GET['route_id'];
$worker_id = $_SESSION['user_id'];

try {
    // Verify route belongs to current worker and get route geometry
    $stmt = prepare_statement($conn, "SELECT route_geometry FROM collectionroutes WHERE route_id = ? AND worker_id = ?");
    $stmt->bind_param('ii', $route_id, $worker_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Route not found']);
        exit;
    }

    // Parse route geometry to extract street routing data
    $route_geometry = json_decode($result['route_geometry'], true);
    
    // Get route stops coordinates
    $stops_stmt = prepare_statement($conn, "
        SELECT rs.stop_order, rs.latitude, rs.longitude, rs.request_id,
               pr.status, CONCAT(u.first_name, ' ', u.last_name) as resident_name
        FROM routestops rs
        LEFT JOIN pickuprequests pr ON rs.request_id = pr.request_id
        LEFT JOIN users u ON pr.resident_id = u.user_id
        WHERE rs.route_id = ?
        ORDER BY rs.stop_order
    ");
    $stops_stmt->bind_param('i', $route_id);
    $stops_stmt->execute();
    $stops_result = $stops_stmt->get_result();

    $coordinates = [];
    while ($stop = $stops_result->fetch_assoc()) {
        $coordinates[] = [
            'lat' => floatval($stop['latitude']),
            'lng' => floatval($stop['longitude']),
            'stop_order' => (int)$stop['stop_order'],
            'resident_name' => $stop['resident_name'],
            'status' => $stop['status'],
            'request_id' => $stop['request_id'] ? (int)$stop['request_id'] : null
        ];
    }

    $response = [
        'success' => true,
        'coordinates' => $coordinates,
        'street_geometry' => $route_geometry['street_geometry'] ?? null,
        'total_distance' => $route_geometry['total_distance'] ?? 0,
        'estimated_time' => $route_geometry['estimated_time'] ?? 'Unknown',
        'routing_method' => $route_geometry['routing_method'] ?? 'fallback',
        'fuel_savings' => $route_geometry['fuel_savings'] ?? 'N/A'
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error retrieving route coordinates: ' . $e->getMessage()
    ]);
}
?>