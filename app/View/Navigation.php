<?php

declare(strict_types=1);

namespace App\View;

final class Navigation
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function forRole(string $role): array
    {
        return match ($role) {
            'admin' => self::admin(),
            'resident' => self::resident(),
            'worker' => self::worker(),
            default => [],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function admin(): array
    {
        return [
            ['href' => 'admin_dashboard.php', 'icon' => 'layout-dashboard', 'label' => 'Dashboard', 'color' => 'bote-green'],
            ['href' => 'admin_pickup_requests.php', 'icon' => 'plus-circle', 'label' => 'Pickup Requests', 'color' => 'bote-info'],
            ['href' => 'admin_users.php', 'icon' => 'list', 'label' => 'Users', 'color' => 'orange'],
            ['href' => 'admin_rewards.php', 'icon' => 'gift', 'label' => 'Rewards', 'color' => 'bote-green'],
            ['href' => 'admin_reports.php', 'icon' => 'file-text', 'label' => 'Reports', 'color' => 'yellow'],
            ['href' => 'admin_puroks.php', 'icon' => 'map-pin', 'label' => 'Puroks', 'color' => 'bote-green'],
            ['href' => 'admin_recyclable_types.php', 'icon' => 'recycle', 'label' => 'Recyclable Types', 'color' => 'bote-info'],
            ['href' => 'admin_collection_points.php', 'icon' => 'map-pin', 'label' => 'Collection Points', 'color' => 'bote-green'],
            ['href' => 'admin_profile.php', 'icon' => 'user-circle', 'label' => 'My Profile', 'color' => 'bote-green'],
            ['href' => 'admin_settings.php', 'icon' => 'settings', 'label' => 'Settings', 'color' => 'bote-stone'],
            ['href' => 'admin_help.php', 'icon' => 'help-circle', 'label' => 'Help & Support', 'color' => 'bote-info'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function resident(): array
    {
        return [
            ['href' => 'resident_dashboard.php', 'icon' => 'layout-dashboard', 'label' => 'Dashboard', 'color' => 'bote-green'],
            ['href' => 'resident_new_request.php', 'icon' => 'plus-circle', 'label' => 'Schedule Pickup', 'color' => 'bote-info'],
            ['href' => 'resident_direct_dropoff.php', 'icon' => 'map-pin', 'label' => 'Direct Drop-off', 'color' => 'bote-info'],
            ['href' => 'resident_my_requests.php', 'icon' => 'list', 'label' => 'My Requests', 'color' => 'orange', 'badge' => 'pending_requests'],
            ['href' => 'resident_history.php', 'icon' => 'coins', 'label' => 'History', 'color' => 'yellow'],
            ['href' => 'resident_rewards.php', 'icon' => 'gift', 'label' => 'Redeem Rewards', 'color' => 'purple'],
            ['href' => 'resident_leaderboard.php', 'icon' => 'trophy', 'label' => 'Leaderboard', 'color' => 'yellow'],
            ['divider' => true],
            ['href' => 'resident_profile.php', 'icon' => 'user', 'label' => 'My Profile', 'color' => 'bote-stone'],
            ['href' => 'resident_help.php', 'icon' => 'help-circle', 'label' => 'Help & Support', 'color' => 'bote-info'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function worker(): array
    {
        return [
            ['href' => 'worker_dashboard.php', 'icon' => 'layout-dashboard', 'label' => 'Dashboard', 'color' => 'bote-green'],
            ['href' => 'worker_collection_routes.php', 'icon' => 'map', 'label' => 'Collection Routes', 'color' => 'bote-info', 'badge' => 'active_routes'],
            ['href' => 'worker_assigned_pickups.php', 'icon' => 'truck', 'label' => 'Pickup Request', 'color' => 'orange', 'badge' => 'pending_pickups'],
            ['href' => 'worker_completed_collections.php', 'icon' => 'check-circle', 'label' => 'Completed Collections', 'color' => 'bote-green'],
            ['href' => 'worker_daily_reports.php', 'icon' => 'clipboard-list', 'label' => 'Daily Reports', 'color' => 'bote-green'],
            ['href' => 'worker_verification.php', 'icon' => 'shield-check', 'label' => 'Worker Verification', 'color' => 'red'],
            ['divider' => true],
            ['href' => 'worker_profile.php', 'icon' => 'user', 'label' => 'My Profile', 'color' => 'bote-stone'],
            ['href' => 'worker_help.php', 'icon' => 'headphones', 'label' => 'Support', 'color' => 'bote-info'],
        ];
    }
}
