<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Core\Csrf;
use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class LoginHandlerController extends BaseController
{
    protected string $basePath = '';

    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return [];
    }

    public function render(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('login.php');
        }

        try {
            Csrf::validate($_POST['csrf_token'] ?? null);

            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if ($email === '' || $password === '') {
                throw new \RuntimeException('Invalid email or password');
            }

            $user = $this->container->database->selectOne(
                'SELECT user_id, first_name, last_name, email, password, role FROM users WHERE email = ?',
                [$email],
                's',
            );

            if ($user === null || !password_verify($password, TypeCaster::string($user['password'] ?? '', 'password'))) {
                throw new \RuntimeException('Invalid email or password');
            }

            $_SESSION['user_id'] = TypeCaster::int($user['user_id'], 'user_id');
            $_SESSION['first_name'] = TypeCaster::string($user['first_name'], 'first_name');
            $_SESSION['last_name'] = TypeCaster::string($user['last_name'], 'last_name');
            $_SESSION['email'] = TypeCaster::string($user['email'], 'email');
            $_SESSION['role'] = TypeCaster::string($user['role'], 'role');

            session_regenerate_id(true);

            match ($_SESSION['role']) {
                'admin' => $this->redirect('admin/admin_dashboard.php'),
                'worker' => $this->redirect('worker/worker_dashboard.php'),
                'resident' => $this->redirect('resident/resident_dashboard.php'),
                default => throw new \RuntimeException('Invalid user role'),
            };
        } catch (\Throwable $e) {
            error_log('Login error: ' . $e->getMessage());
            $this->redirect('login.php?error=' . urlencode($e->getMessage()));
        }
    }
}
