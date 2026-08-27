<?php

declare(strict_types=1);

namespace App\View;

use App\Core\Container;
use App\Core\TypeCaster;

final class SidebarFactory
{
    public function __construct(private readonly Container $container) {}

    /**
     * Build the full sidebar data for the current role.
     *
     * @param array<string, mixed>|null $user
     * @return array<string, mixed>
     */
    public function forRole(string $role, int $userId, ?array $user = null): array
    {
        $user ??= $this->fetchUser($userId);
        $profile = $this->profileDisplay($user);

        $navItems = Navigation::forRole($role);
        $stats = $this->statsForRole($role, $userId);

        foreach ($navItems as $index => $item) {
            if (isset($item['badge']) && is_string($item['badge'])) {
                $navItems[$index]['badge'] = $this->resolveBadge($stats, $item['badge']);
            }
        }

        return [
            'user' => $user,
            'profile' => $profile,
            'systemName' => $this->setting('barangay_name', 'BoteBank'),
            'systemLogo' => $this->setting('barangay_logo', ''),
            'role' => $role,
            'roleLabel' => $this->roleLabel($role),
            'navItems' => $navItems,
            'stats' => $stats,
            'currentPage' => basename($_SERVER['PHP_SELF'] ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchUser(int $userId): array
    {
        $row = $this->container->database->selectOne(
            'SELECT user_id, first_name, last_name, email, phone, address, profile_photo, role FROM users WHERE user_id = ?',
            [$userId],
            'i',
        );

        return $row ?? [
            'user_id' => $userId,
            'first_name' => 'Unknown',
            'last_name' => 'User',
            'email' => '',
            'phone' => '',
            'address' => '',
            'profile_photo' => '',
            'role' => 'resident',
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, string>
     */
    private function profileDisplay(array $user): array
    {
        $photo = TypeCaster::string($user['profile_photo'] ?? '', 'profile_photo');

        if ($photo !== '' && file_exists("../uploads/profiles/thumbs/{$photo}")) {
            return ['type' => 'photo', 'value' => $photo];
        }

        $first = TypeCaster::string($user['first_name'] ?? '', 'first_name');
        $last = TypeCaster::string($user['last_name'] ?? '', 'last_name');
        $initials = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));

        return ['type' => 'initials', 'value' => $initials ?: 'U'];
    }

    private function setting(string $key, string $default): string
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

    /**
     * @return array<string, mixed>
     */
    private function statsForRole(string $role, int $userId): array
    {
        return match ($role) {
            'admin' => $this->adminStats(),
            'resident' => $this->residentStats($userId),
            'worker' => $this->workerStats($userId),
            default => [],
        };
    }

    /**
     * @return array<string, int>
     */
    private function adminStats(): array
    {
        $users = $this->container->database->selectOne('SELECT COUNT(*) as total FROM users WHERE role = ?', ['resident'], 's');
        $requests = $this->container->database->selectOne("SELECT COUNT(*) as total FROM pickuprequests", [], '');
        $pending = $this->container->database->selectOne("SELECT COUNT(*) as total FROM pickuprequests WHERE status = 'Pending'", [], '');
        $rewards = $this->container->database->selectOne('SELECT COUNT(*) as total FROM rewards', [], '');

        return [
            'total_users' => TypeCaster::int($users['total'] ?? 0, 'total'),
            'total_requests' => TypeCaster::int($requests['total'] ?? 0, 'total'),
            'pending_requests' => TypeCaster::int($pending['total'] ?? 0, 'total'),
            'total_rewards' => TypeCaster::int($rewards['total'] ?? 0, 'total'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function residentStats(int $userId): array
    {
        $points = $this->container->database->selectOne(
            "SELECT COALESCE(SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE -points END), 0) as total_points FROM pointsledger WHERE user_id = ?",
            [$userId],
            'i',
        );

        $requests = $this->container->database->selectOne(
            "SELECT
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_requests,
                SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled_requests,
                SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_requests,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_requests,
                SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_requests,
                COALESCE(SUM(CASE WHEN status = 'Completed' THEN weight_kg ELSE 0 END), 0) as total_weight
            FROM pickuprequests WHERE resident_id = ?",
            [$userId],
            'i',
        );

        $merged = array_merge(
            ['total_points' => TypeCaster::int($points['total_points'] ?? 0, 'total_points')],
            $requests ?? [],
        );

        return array_map(fn ($value) => is_numeric($value) ? (int) $value : 0, $merged);
    }

    /**
     * @return array<string, int>
     */
    private function workerStats(int $userId): array
    {
        $assigned = $this->container->database->selectOne(
            'SELECT COUNT(*) as assigned_pickups FROM pickuprequests WHERE assigned_worker_id = ?',
            [$userId],
            'i',
        );
        $pending = $this->container->database->selectOne(
            "SELECT COUNT(*) as pending_pickups FROM pickuprequests WHERE assigned_worker_id = ? AND status = 'Scheduled'",
            [$userId],
            'i',
        );
        $completed = $this->container->database->selectOne(
            "SELECT COUNT(*) as completed_pickups FROM pickuprequests WHERE assigned_worker_id = ? AND status = 'Completed'",
            [$userId],
            'i',
        );
        $routes = $this->container->database->selectOne(
            "SELECT COUNT(*) as active_routes FROM collectionroutes WHERE worker_id = ? AND DATE(generated_on) = CURDATE()",
            [$userId],
            'i',
        );

        return [
            'assigned_pickups' => TypeCaster::int($assigned['assigned_pickups'] ?? 0, 'assigned_pickups'),
            'pending_pickups' => TypeCaster::int($pending['pending_pickups'] ?? 0, 'pending_pickups'),
            'completed_pickups' => TypeCaster::int($completed['completed_pickups'] ?? 0, 'completed_pickups'),
            'active_routes' => TypeCaster::int($routes['active_routes'] ?? 0, 'active_routes'),
        ];
    }

    /**
     * @param array<string, mixed> $stats
     */
    private function resolveBadge(array $stats, string $key): int
    {
        return TypeCaster::int($stats[$key] ?? 0, $key);
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'admin' => 'System Administrator',
            'resident' => 'Eco Resident',
            'worker' => 'Collection Worker',
            default => 'User',
        };
    }
}
