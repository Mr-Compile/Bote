<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class ProfileController extends BaseController
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
        $user_id = $this->currentUserId();
        $user = $this->db->selectOne(
            'SELECT * FROM users WHERE user_id = ?',
            [$user_id],
            'i',
        );

        if ($user === null || $user['role'] !== 'admin') {
            $this->redirect('../logout.php');
        }

        $success_message = null;
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Csrf::validate($_POST['csrf_token'] ?? '');
            } catch (\InvalidArgumentException) {
                $errors[] = 'Invalid or expired security token.';
            }

            if ($errors === []) {
                $first_name = trim((string) ($_POST['first_name'] ?? ''));
                $last_name = trim((string) ($_POST['last_name'] ?? ''));
                $email = trim((string) ($_POST['email'] ?? ''));
                $phone = trim((string) ($_POST['phone'] ?? ''));
                $address = trim((string) ($_POST['address'] ?? ''));
                $latitude = is_numeric($_POST['latitude'] ?? '') ? (float) $_POST['latitude'] : null;
                $longitude = is_numeric($_POST['longitude'] ?? '') ? (float) $_POST['longitude'] : null;
                $current_password = (string) ($_POST['current_password'] ?? '');
                $new_password = (string) ($_POST['new_password'] ?? '');
                $confirm_password = (string) ($_POST['confirm_password'] ?? '');

                if ($first_name === '') {
                    $errors[] = 'First name is required';
                }
                if ($last_name === '') {
                    $errors[] = 'Last name is required';
                }
                if ($email === '') {
                    $errors[] = 'Email is required';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email format';
                }
                if ($phone === '') {
                    $errors[] = 'Phone is required';
                }
                if ($address === '') {
                    $errors[] = 'Address is required';
                }

                $existing = $this->db->selectOne(
                    'SELECT user_id FROM users WHERE email = ? AND user_id != ?',
                    [$email, $user_id],
                    'si',
                );
                if ($existing !== null) {
                    $errors[] = 'Email is already taken';
                }

                if ($new_password !== '') {
                    if ($current_password === '') {
                        $errors[] = 'Current password is required to change password';
                    } elseif (!password_verify($current_password, (string) ($user['password'] ?? ''))) {
                        $errors[] = 'Current password is incorrect';
                    } elseif (strlen($new_password) < 8) {
                        $errors[] = 'New password must be at least 8 characters';
                    } elseif ($new_password !== $confirm_password) {
                        $errors[] = 'New passwords do not match';
                    }
                }

                if ($errors === []) {
                    if ($new_password !== '') {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $this->db->execute(
                            'UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, latitude = ?, longitude = ?, password = ? WHERE user_id = ?',
                            [$first_name, $last_name, $email, $phone, $address, $latitude, $longitude, $hashed_password, $user_id],
                            'sssssddsi',
                        );
                    } else {
                        $this->db->execute(
                            'UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, latitude = ?, longitude = ? WHERE user_id = ?',
                            [$first_name, $last_name, $email, $phone, $address, $latitude, $longitude, $user_id],
                            'sssssddi',
                        );
                    }

                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['last_name'] = $last_name;
                    $success_message = 'Profile updated successfully!';

                    $user = $this->db->selectOne(
                        'SELECT * FROM users WHERE user_id = ?',
                        [$user_id],
                        'i',
                    ) ?? $user;
                }
            }
        }

        $this->view('admin.profile', [
            'user' => $user,
            'success_message' => $success_message,
            'errors' => $errors,
            'pageScript' => 'assets/js/pages/admin/profile.js',
        ]);
    }
}
