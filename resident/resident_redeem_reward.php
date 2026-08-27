<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/system_functions.php';

header('Content-Type: application/json');

// Role check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'resident') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Read JSON body once and validate CSRF token from it.
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
    exit;
}

try {
    \App\Core\Csrf::validate($input['csrf_token'] ?? null);
} catch (\InvalidArgumentException $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired session token.']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$reward_id = isset($input['reward_id']) ? (int) $input['reward_id'] : 0;
$quantity = isset($input['quantity']) ? (int) $input['quantity'] : 0;

if ($reward_id <= 0 || $quantity <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid reward or quantity']);
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
    $user_points = (int) $stmt->get_result()->fetch_assoc()['total_points'];

    if ($user_points < $total_points_required) {
        throw new Exception('Insufficient points');
    }

    // Create redemption record
    $stmt = prepare_statement($conn, "INSERT INTO rewardredemptions (user_id, reward_id, quantity, status) VALUES (?, ?, ?, 'Pending')");
    $stmt->bind_param("iii", $user_id, $reward_id, $quantity);
    $stmt->execute();
    $redemption_id = $conn->insert_id;

    // Deduct points from user
    $stmt = prepare_statement($conn, "INSERT INTO pointsledger (user_id, points, transaction_type, description) VALUES (?, ?, 'Redeemed', ?)");
    $description = "Redeemed {$quantity}x {$reward['name']}";
    $stmt->bind_param("iis", $user_id, $total_points_required, $description);
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
    error_log("Reward redemption error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
