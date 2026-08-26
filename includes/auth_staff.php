<?php
require_once 'session.php';

// Check if user role is worker or admin (both can verify drop-offs)
if (!in_array($_SESSION['role'], ['worker', 'admin'])) {
    header("Location: ../login.php");
    exit();
}

// Validate CSRF token for state-changing requests
if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    try {
        \App\Core\Csrf::validateRequest();
    } catch (\InvalidArgumentException $e) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid or expired session token.']);
        } else {
            header('Location: ../login.php?error=' . urlencode('Invalid or expired session token.'));
        }
        exit();
    }
}
