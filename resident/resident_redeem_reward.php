<?php
session_start();
require_once '../config/database.php';
require_once '../includes/system_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['reward_id']) || !isset($input['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$reward_id = intval($input['reward_id']);
$quantity = intval($input['quantity']);

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

try {
    $conn->begin_transaction();
    
    // Get reward details and check stock
    $stmt = prepare_statement($conn, "SELECT * FROM rewards WHERE reward_id = ? FOR UPDATE");
    $stmt->bind_param("i", $reward_id);
    $stmt->execute();
    $reward = $stmt->get_result()->fetch_assoc();
    
    if (!$reward) {
        throw new Exception('Reward not found');
    }
    
    if ($reward['stock'] < $quantity) {
        throw new Exception('Insufficient stock');
    }
    
    $total_points_required = $reward['points_required'] * $quantity;
    
    // Get user's current points
    $stmt = prepare_statement($conn, "SELECT COALESCE(SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE -points END), 0) as total_points FROM pointsledger WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_points = $stmt->get_result()->fetch_assoc()['total_points'];
    
    if ($user_points < $total_points_required) {
        throw new Exception('Insufficient points');
    }
    
    // Create redemption record
    $stmt = prepare_statement($conn, "INSERT INTO rewardredemptions (user_id, reward_id, quantity, status) VALUES (?, ?, ?, 'Pending')");
    $stmt->bind_param("iii", $user_id, $reward_id, $quantity);
    $stmt->execute();
    $redemption_id = $conn->insert_id;
    
    // Deduct points from user
    $stmt = prepare_statement($conn, "INSERT INTO pointsledger (user_id, points, transaction_type) VALUES (?, ?, 'Redeemed')");
    $stmt->bind_param("ii", $user_id, $total_points_required);
    $stmt->execute();
    
    // Update reward stock
    $new_stock = $reward['stock'] - $quantity;
    $stmt = prepare_statement($conn, "UPDATE rewards SET stock = ? WHERE reward_id = ?");
    $stmt->bind_param("ii", $new_stock, $reward_id);
    $stmt->execute();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Reward redeemed successfully',
        'redemption_id' => $redemption_id,
        'remaining_points' => $user_points - $total_points_required
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
