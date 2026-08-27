<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Csrf;

final class RequireRole
{
    /**
     * Ensure the current request is authenticated as one of the given roles.
     *
     * @param string ...$roles
     */
    public static function assert(string ...$roles): void
    {
        require_once __DIR__ . '/../../../includes/session.php';

        $userId = $_SESSION['user_id'] ?? null;
        $role = $_SESSION['role'] ?? null;

        if ($userId === null || $role === null || !in_array($role, $roles, true)) {
            self::deny();
        }

        if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            try {
                Csrf::validateRequest();
            } catch (\InvalidArgumentException) {
                self::deny('Invalid or expired session token.');
            }
        }
    }

    private static function deny(?string $message = null): never
    {
        $message ??= 'You must be logged in to access this page.';

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }

        header('Location: ../login.php?error=' . urlencode($message));
        exit;
    }
}
