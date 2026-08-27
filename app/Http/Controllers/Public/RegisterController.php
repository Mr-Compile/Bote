<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Core\Csrf;
use App\Http\Controllers\BaseController;

final class RegisterController extends BaseController
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
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Csrf::validate($_POST['csrf_token'] ?? null);
                $this->register($_POST);
            } catch (\Throwable $e) {
                $error = $e->getMessage();
                error_log('Registration error: ' . $error);
            }
        }

        $loginImage = $this->setting('login_image', '');
        $puroks = $this->container->database->query(
            'SELECT purok_id, name FROM puroks ORDER BY name ASC',
            [],
            '',
        );

        $this->view->render('public.register', [
            'title' => 'BoteBank Registration',
            'login_image' => $loginImage,
            'error' => $error,
            'puroks' => $puroks,
        ], null);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function register(array $data): never
    {
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $address = trim((string) ($data['address'] ?? ''));
        $purokId = (int) ($data['purok_id'] ?? 0);

        if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $address === '' || $purokId === 0) {
            throw new \RuntimeException('All fields including purok are required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Invalid email format');
        }

        if (($data['password'] ?? '') !== ($data['confirm_password'] ?? '')) {
            throw new \RuntimeException('Passwords do not match!');
        }

        if (strlen((string) $data['password']) < 8) {
            throw new \RuntimeException('Password must be at least 8 characters long');
        }

        $password = password_hash((string) $data['password'], PASSWORD_DEFAULT);

        $phone = preg_replace('/^\+63/', '', $phone);
        $phone = preg_replace('/^0/', '', $phone);
        $phone = '+63' . $phone;

        if (!preg_match('/^\+639\d{9}$/', $phone)) {
            throw new \RuntimeException('Invalid phone number format. Must be 10 digits starting with 9');
        }

        $existing = $this->container->database->selectOne(
            'SELECT user_id FROM users WHERE email = ?',
            [$email],
            's',
        );

        if ($existing !== null) {
            throw new \RuntimeException('Email address already registered');
        }

        $this->container->database->execute(
            'INSERT INTO users (first_name, last_name, email, phone, address, password, role, purok_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$firstName, $lastName, $email, $phone, $address, $password, 'resident', $purokId],
            'sssssssi',
        );

        header('Location: login.php?registration=success');
        exit;
    }
}
