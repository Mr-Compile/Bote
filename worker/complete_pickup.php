<?php
require_once '../config/database.php';
require '../includes/auth_worker.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['request_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing request_id']);
    exit;
}

$request_id = (int) $input['request_id'];
$user_id = $_SESSION['user_id'];

try {
    $conn->begin_transaction();

    // Verify the pickup request is assigned to this worker
    $verify_sql = "SELECT pr.request_id, pr.weight_kg, pr.resident_id, pr.type_id, rt.points_per_kg 
                   FROM pickuprequests pr 
                   JOIN recyclabletypes rt ON pr.type_id = rt.type_id 
                   WHERE pr.request_id = ? AND pr.assigned_worker_id = ? AND pr.status != 'Completed'";
    
    $result = execute_query($conn, $verify_sql, [$request_id, $user_id], 'ii');
    
    if (!$result || $result->num_rows === 0) {
        throw new Exception('Pickup request not found or not assigned to you');
    }
    
    $pickup = $result->fetch_assoc();
    
    // Update pickup status to completed
    $update_sql = "UPDATE pickuprequests 
                   SET status = 'Completed', 
                       actual_weight = weight_kg, 
                       points_earned = weight_kg * ?,
                       updated_at = NOW() 
                   WHERE request_id = ?";
    
    $points_earned = $pickup['weight_kg'] * $pickup['points_per_kg'];
    execute_query($conn, $update_sql, [$pickup['points_per_kg'], $request_id], 'di');
    
    // Check if points have already been awarded for this pickup to prevent duplicates
    $check_sql = "SELECT 1 FROM pointsledger WHERE request_id = ? AND user_id = ? AND transaction_type = 'Earned' LIMIT 1";
    $check_result = execute_query($conn, $check_sql, [$request_id, $pickup['resident_id']], 'ii');
    
    // Only add points if they haven't been awarded already
    if (!$check_result || $check_result->num_rows === 0) {
        $points_sql = "INSERT INTO pointsledger (user_id, request_id, points, transaction_type) 
                       VALUES (?, ?, ?, 'Earned')";
        execute_query($conn, $points_sql, [$pickup['resident_id'], $request_id, $points_earned], 'iii');
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Pickup completed successfully',
        'points_earned' => $points_earned
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>