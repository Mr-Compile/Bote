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
$user_id = (int) $_SESSION['user_id'];

try {
    $pickup = $container->pickupRepository->findById($request_id);

    if ($pickup === null) {
        throw new Exception('Pickup request not found');
    }

    $completed = $container->pickupService->complete(
        requestId: $request_id,
        workerId: $user_id,
        actualWeight: $pickup->weightKg,
        actualQuantity: $pickup->quantityPcs,
        description: null,
    );

    echo json_encode([
        'success' => true,
        'message' => 'Pickup completed successfully',
        'points_earned' => $completed->pointsEarned,
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
