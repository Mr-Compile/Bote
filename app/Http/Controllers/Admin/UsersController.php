<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Database;
use App\Http\Controllers\BaseController;

final class UsersController extends BaseController
{
    private Database $db;

    public function __construct(\App\Core\Container $container)
    {
        parent::__construct($container);
        $this->db = $container->database;
    }

    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return ['admin'];
    }

    public function render(): void
    {
        $success_message = null;
        $error_message = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            try {
                Csrf::validate($_POST['csrf_token'] ?? '');

                switch ($_POST['action']) {
                    case 'add_worker':
                        $success_message = $this->addWorker() ?? null;
                        break;

                    case 'delete_user':
                        $success_message = $this->deleteUser() ?? null;
                        break;

                    case 'update_role':
                        $success_message = $this->updateRole() ?? null;
                        break;
                }
            } catch (\InvalidArgumentException $e) {
                $error_message = $e->getMessage();
            } catch (\Exception $e) {
                $error_message = $e->getMessage();
                error_log('User management error: ' . $e->getMessage());
            }
        }

        if ($error_message !== null) {
            $success_message = null;
        }

        $sort_by = in_array($_GET['sort_by'] ?? '', ['first_name', 'last_name', 'email', 'role', 'created_at', 'purok_name'], true)
            ? $_GET['sort_by']
            : 'created_at';

        $sort_order = (($_GET['sort_order'] ?? '') === 'asc') ? 'ASC' : 'DESC';
        $role_filter = in_array($_GET['role_filter'] ?? '', ['admin', 'worker', 'resident'], true)
            ? $_GET['role_filter']
            : '';

        $where_clause = '';
        $params = [];
        $param_types = '';

        if ($role_filter !== '') {
            $where_clause = 'WHERE u.role = ?';
            $params[] = $role_filter;
            $param_types = 's';
        }

        $order_clause = match ($sort_by) {
            'first_name', 'last_name' => "ORDER BY u.first_name {$sort_order}, u.last_name {$sort_order}",
            'purok_name' => "ORDER BY p.name {$sort_order}",
            default => "ORDER BY u.{$sort_by} {$sort_order}",
        };

        $users = $this->db->query(
            "SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone, u.address, u.profile_photo, u.role, u.created_at, p.name as purok_name
             FROM users u
             LEFT JOIN puroks p ON u.purok_id = p.purok_id
             {$where_clause}
             {$order_clause}",
            $params,
            $param_types,
        );

        $total_users = 0;
        foreach ($users as $user) {
            if ($user['role'] !== 'admin') {
                $total_users++;
            }
        }

        $stats = ['resident' => 0, 'worker' => 0, 'admin' => 0];
        $role_stats = $this->db->query('SELECT role, COUNT(*) as count FROM users GROUP BY role');
        foreach ($role_stats as $stat) {
            $stats[$stat['role']] = (int) ($stat['count'] ?? 0);
        }

        $recent_row = $this->db->selectOne('SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
        $recent_users_count = (int) ($recent_row['count'] ?? 0);

        $puroks = $this->db->query('SELECT purok_id, name FROM puroks ORDER BY name ASC');

        $this->view('admin.users', [
            'success_message' => $success_message,
            'error_message' => $error_message,
            'users' => $users,
            'stats' => $stats,
            'total_users' => $total_users,
            'recent_users_count' => $recent_users_count,
            'puroks' => $puroks,
            'sort_by' => $sort_by,
            'sort_order' => $sort_order,
            'role_filter' => $role_filter,
        ]);
    }

    private function addWorker(): ?string
    {
        $first_name = trim((string) ($_POST['first_name'] ?? ''));
        $last_name = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $purok_id = !empty($_POST['purok_id']) ? (int) $_POST['purok_id'] : null;
        $password = (string) ($_POST['password'] ?? '');

        if ($first_name === '' || $last_name === '' || $email === '' || $address === '' || $password === '') {
            throw new \Exception('All required fields must be filled.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid email format.');
        }

        if (strlen($password) < 6) {
            throw new \Exception('Password must be at least 6 characters long.');
        }

        $existing = $this->db->selectOne('SELECT user_id FROM users WHERE email = ?', [$email], 's');
        if ($existing !== null) {
            throw new \Exception('Email already exists.');
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $this->db->execute(
            "INSERT INTO users (first_name, last_name, email, phone, address, password, role, purok_id) VALUES (?, ?, ?, ?, ?, ?, 'worker', ?)",
            [$first_name, $last_name, $email, $phone, $address, $hashed_password, $purok_id],
            'sssssss',
        );

        error_log("Admin {$_SESSION['user_id']} added new worker: {$email}");

        return 'Worker added successfully!';
    }

    private function deleteUser(): ?string
    {
        $user_id = (int) ($_POST['user_id'] ?? 0);

        if ($user_id <= 0) {
            throw new \Exception('Invalid user ID.');
        }

        if ($user_id === $this->currentUserId()) {
            throw new \Exception('You cannot delete your own account.');
        }

        $this->db->execute(
            'DELETE FROM users WHERE user_id = ? AND role != ?',
            [$user_id, 'admin'],
            'is',
        );

        if ($this->db->connection()->affected_rows === 0) {
            throw new \Exception('Failed to delete user or user not found.');
        }

        error_log("Admin {$_SESSION['user_id']} deleted user: {$user_id}");

        return 'User deleted successfully!';
    }

    private function updateRole(): ?string
    {
        $user_id = (int) ($_POST['user_id'] ?? 0);
        $new_role = trim((string) ($_POST['new_role'] ?? ''));

        if ($user_id <= 0) {
            throw new \Exception('Invalid user ID.');
        }

        if (!in_array($new_role, ['resident', 'worker', 'admin'], true)) {
            throw new \Exception('Invalid role specified.');
        }

        if ($user_id === $this->currentUserId()) {
            throw new \Exception('You cannot change your own role.');
        }

        $this->db->execute(
            'UPDATE users SET role = ? WHERE user_id = ?',
            [$new_role, $user_id],
            'si',
        );

        if ($this->db->connection()->affected_rows === 0) {
            throw new \Exception('Failed to update user role or no changes made.');
        }

        error_log("Admin {$_SESSION['user_id']} updated role for user {$user_id} to {$new_role}");

        return 'User role updated successfully!';
    }
}
