<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Container;
use App\Core\Csrf;
use App\Core\Legacy;
use App\Core\LegacyResult;
use App\Core\TypeCaster;
use App\Core\View;
use App\Http\Middleware\RequireRole;
use App\View\SidebarFactory;

abstract class BaseController
{
    protected Container $container;
    protected View $view;
    protected Legacy $legacy;
    protected string $basePath = '../';

    /** @return list<string> */
    abstract protected function allowedRoles(): array;

    protected function legacy(): Legacy
    {
        if (!isset($this->legacy)) {
            $this->legacy = new Legacy($this->container->database);
        }

        return $this->legacy;
    }

    protected function requireInclude(string $name): void
    {
        require_once dirname(__DIR__, 3) . '/includes/' . $name;
    }

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->view = new View();

        require_once __DIR__ . '/../../../includes/session.php';
        require_once __DIR__ . '/../../../includes/system_functions.php';

        RequireRole::assert(...$this->allowedRoles());
    }

    protected function currentUserId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    protected function currentUser(): array
    {
        $row = $this->container->database->selectOne(
            'SELECT user_id, first_name, last_name, email, phone, address, profile_photo, role FROM users WHERE user_id = ?',
            [$this->currentUserId()],
            'i',
        );

        return $row ?? [
            'user_id' => $this->currentUserId(),
            'first_name' => 'Unknown',
            'last_name' => 'User',
            'email' => '',
            'phone' => '',
            'address' => '',
            'profile_photo' => '',
            'role' => '',
        ];
    }

    /**
     * Render a view with the shared layout and sidebar.
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $name, array $data = []): void
    {
        $user = $this->currentUser();
        $role = (string) ($_SESSION['role'] ?? '');

        $this->view->render($name, array_merge($data, [
            'basePath' => $this->basePath,
            'csrfField' => Csrf::field(),
            'currentUser' => $user,
            'role' => $role,
            'sidebar' => (new SidebarFactory($this->container))->forRole($role, $this->currentUserId(), $user),
            'systemName' => $this->setting('barangay_name', 'BoteBank'),
        ]));
    }

    protected function redirect(string $url): never
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        exit;
    }

    protected function setting(string $key, string $default = ''): string
    {
        $row = $this->container->database->selectOne(
            'SELECT setting_value FROM system_settings WHERE setting_key = ?',
            [$key],
            's',
        );

        if ($row === null || !isset($row['setting_value'])) {
            return $default;
        }

        return TypeCaster::string($row['setting_value'], 'setting_value');
    }

    protected function setSetting(string $key, string $value): bool
    {
        return $this->container->database->execute(
            "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP",
            [$key, $value],
            'ss',
        );
    }

    /**
     * @return array<string, string>
     */
    protected function allSettings(): array
    {
        $rows = $this->container->database->query(
            'SELECT setting_key, setting_value FROM system_settings',
            [],
            '',
        );

        $settings = [];
        foreach ($rows as $row) {
            $settings[TypeCaster::string($row['setting_key'] ?? '', 'setting_key')] = TypeCaster::string($row['setting_value'] ?? '', 'setting_value');
        }

        return $settings;
    }

    protected function ensureSystemSettingsTable(): bool
    {
        $result = $this->legacy()->query("SHOW TABLES LIKE 'system_settings'", [], '');
        if ($result instanceof LegacyResult && $result->num_rows === 0) {
            $sql = "CREATE TABLE `system_settings` (
              `setting_id` int(11) NOT NULL AUTO_INCREMENT,
              `setting_key` varchar(100) NOT NULL,
              `setting_value` text DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              PRIMARY KEY (`setting_id`),
              UNIQUE KEY `setting_key` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

            INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`) VALUES
            ('barangay_name', 'BoteBank'),
            ('barangay_contact', ''),
            ('barangay_email', ''),
            ('barangay_address', ''),
            ('barangay_logo', ''),
            ('system_banner', ''),
            ('banner_title', 'Welcome to BoteBank'),
            ('banner_subtitle', 'Turn your recyclables into rewards and help build a greener community'),
            ('banner_enabled', '1'),
            ('login_image', ''),
            ('max_points_per_day', '1000'),
            ('min_redemption_points', '100'),
            ('points_notification_template', 'You have earned {points} points for your recyclable submission!'),
            ('reward_notification_template', 'Your reward claim for {reward_name} has been processed!'),
            ('email_notification_enabled', '1'),
            ('sms_notification_enabled', '1'),
            ('password_min_length', '8'),
            ('password_require_special', '1'),
            ('session_timeout', '30');";

            $this->container->database->connection()->multi_query($sql);

            return true;
        }

        return true;
    }

    protected function checkDbHealth(): bool
    {
        $conn = $this->container->database->connection();
        if ($conn->connect_error !== '' && $conn->connect_error !== null || $conn->ping() === false) {
            return false;
        }

        return $conn->query('SELECT 1') !== false;
    }

    protected function validateCsrf(): void
    {
        try {
            Csrf::validateRequest();
        } catch (\InvalidArgumentException $e) {
            http_response_code(403);
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
