<?php
require_once '../config/database.php';
require_once '../includes/system_functions.php';
require '../includes/auth_res.php'; // Ensure only resident can access

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $request_id = $input['request_id'] ?? null;
    
    if (!$request_id) {
        throw new Exception('Request ID is required');
    }
    
    // Validate that the request belongs to the current user
    $stmt = prepare_statement($conn, "SELECT request_id, status FROM pickuprequests WHERE request_id = ? AND resident_id = ?");
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    
    if (!$request) {
        throw new Exception('Request not found or you do not have permission to cancel it');
    }
    
    if ($request['status'] !== 'Pending') {
        throw new Exception('Only pending requests can be cancelled');
    }
    
    // Update the request status to 'Cancelled'
    $stmt = prepare_statement($conn, "UPDATE pickuprequests SET status = 'Cancelled', updated_at = CURRENT_TIMESTAMP WHERE request_id = ?");
    $stmt->bind_param("i", $request_id);
    
    if ($stmt->execute()) {
        // Log the cancellation
        error_log("Request {$request_id} cancelled by user {$user_id}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Request cancelled successfully'
        ]);
    } else {
        throw new Exception('Failed to cancel request');
    }
    
} catch (Exception $e) {
    error_log("Error in cancel_request.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
