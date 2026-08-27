<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class DashboardController extends BaseController
{
    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return ['admin'];
    }

    public function render(): void
    {
        $dashboardStats = $this->dashboardStats();
        $topResidents = $this->topResidents();
        $topPuroks = $this->topPuroks();
        $pickupOverview = $this->pickupOverview();
        $rewardsStatus = $this->rewardsStatus();
        $collectionRoutes = $this->collectionRoutes();
        $recentRedemptions = $this->recentRedemptions();
        $recentNotifications = $this->recentNotifications();
        $analyticsData = $this->analyticsData();

        $defaultLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        $dashboardData = [
            'pickupOverview' => [
                'pending' => TypeCaster::int($pickupOverview['pending'] ?? 0, 'pending'),
                'scheduled' => TypeCaster::int($pickupOverview['scheduled'] ?? 0, 'scheduled'),
                'completed_today' => TypeCaster::int($pickupOverview['completed_today'] ?? 0, 'completed_today'),
            ],
            'analyticsData' => [
                'labels' => !empty($analyticsData)
                    ? array_map(fn ($item) => substr(TypeCaster::string($item['month_name'] ?? '', 'month_name'), 0, 3), $analyticsData)
                    : $defaultLabels,
                'points_earned' => !empty($analyticsData)
                    ? array_map(fn ($item) => TypeCaster::int($item['points_earned'] ?? 0, 'points_earned'), $analyticsData)
                    : [0, 0, 0, 0, 0, 0],
                'points_redeemed' => !empty($analyticsData)
                    ? array_map(fn ($item) => TypeCaster::int($item['points_redeemed'] ?? 0, 'points_redeemed'), $analyticsData)
                    : [0, 0, 0, 0, 0, 0],
            ],
        ];

        $this->view('admin.dashboard', [
            'title' => 'Admin Dashboard',
            'bodyClass' => 'bg-gradient-to-br from-bote-stone-50 to-bote-stone-100 min-h-screen font-sans',
            'system_name' => $this->setting('barangay_name', 'BoteBank'),
            'dashboard_stats' => $dashboardStats,
            'top_residents' => $topResidents,
            'top_puroks' => $topPuroks,
            'pickup_overview' => $pickupOverview,
            'rewards_status' => $rewardsStatus,
            'collection_routes' => $collectionRoutes,
            'recent_redemptions' => $recentRedemptions,
            'recent_notifications' => $recentNotifications,
            'analytics_data' => $analyticsData,
            'json_dashboard_data' => json_encode($dashboardData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function dashboardStats(): array
    {
        $defaults = [
            'total_residents' => 0,
            'total_workers' => 0,
            'total_pickups' => 0,
            'points_distributed' => 0,
            'new_residents_month' => 0,
            'available_rewards' => 0,
            'total_reward_stock' => 0,
        ];

        try {
            $residents = $this->container->database->selectOne(
                "SELECT COUNT(*) as count FROM users WHERE role = 'resident'",
                [],
                '',
            );
            $workers = $this->container->database->selectOne(
                "SELECT COUNT(*) as count FROM users WHERE role = 'worker'",
                [],
                '',
            );
            $pickups = $this->container->database->selectOne(
                'SELECT COUNT(*) as count FROM pickuprequests',
                [],
                '',
            );
            $points = $this->container->database->selectOne(
                "SELECT COALESCE(SUM(points), 0) as total FROM pointsledger WHERE transaction_type = 'Earned'",
                [],
                '',
            );
            $newMonth = $this->container->database->selectOne(
                "SELECT COUNT(*) as count FROM users WHERE role = 'resident' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())",
                [],
                '',
            );
            $rewards = $this->container->database->selectOne(
                'SELECT COUNT(*) as count, SUM(stock) as total_stock FROM rewards WHERE stock > 0',
                [],
                '',
            );

            return array_merge($defaults, [
                'total_residents' => TypeCaster::int($residents['count'] ?? 0, 'count'),
                'total_workers' => TypeCaster::int($workers['count'] ?? 0, 'count'),
                'total_pickups' => TypeCaster::int($pickups['count'] ?? 0, 'count'),
                'points_distributed' => TypeCaster::int($points['total'] ?? 0, 'total'),
                'new_residents_month' => TypeCaster::int($newMonth['count'] ?? 0, 'count'),
                'available_rewards' => TypeCaster::int($rewards['count'] ?? 0, 'count'),
                'total_reward_stock' => TypeCaster::int($rewards['total_stock'] ?? 0, 'total_stock'),
            ]);
        } catch (\Throwable $e) {
            error_log('Admin dashboard stats error: ' . $e->getMessage());

            return $defaults;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function topResidents(): array
    {
        try {
            return $this->container->database->query(
                "SELECT u.user_id, u.first_name, u.last_name, u.address,
                       COALESCE(SUM(CASE WHEN pl.transaction_type = 'Earned' THEN pl.points ELSE -pl.points END), 0) as total_points
                FROM users u
                LEFT JOIN pointsledger pl ON u.user_id = pl.user_id
                WHERE u.role = 'resident'
                GROUP BY u.user_id, u.first_name, u.last_name, u.address
                ORDER BY total_points DESC
                LIMIT 5",
                [],
                '',
            );
        } catch (\Throwable $e) {
            error_log('Admin top residents error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function topPuroks(): array
    {
        try {
            return $this->container->database->query(
                "SELECT p.purok_id, p.name as purok_name,
                       COALESCE(SUM(CASE WHEN pl.transaction_type = 'Earned' THEN pl.points ELSE -pl.points END), 0) as total_points,
                       COUNT(DISTINCT u.user_id) as resident_count
                FROM puroks p
                LEFT JOIN users u ON p.purok_id = u.purok_id AND u.role = 'resident'
                LEFT JOIN pointsledger pl ON u.user_id = pl.user_id
                GROUP BY p.purok_id, p.name
                HAVING resident_count > 0
                ORDER BY total_points DESC
                LIMIT 5",
                [],
                '',
            );
        } catch (\Throwable $e) {
            error_log('Admin top puroks error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @return array<string, int>
     */
    private function pickupOverview(): array
    {
        $overview = ['pending' => 0, 'scheduled' => 0, 'completed' => 0];

        try {
            foreach (array_keys($overview) as $status) {
                $row = $this->container->database->selectOne(
                    "SELECT COUNT(*) as count FROM pickuprequests WHERE status = ?",
                    [$status],
                    's',
                );
                $overview[strtolower($status)] = TypeCaster::int($row['count'] ?? 0, 'count');
            }

            $today = $this->container->database->selectOne(
                "SELECT COUNT(*) as count FROM pickuprequests WHERE status = 'Completed' AND DATE(created_at) = CURDATE()",
                [],
                '',
            );
            $overview['completed_today'] = TypeCaster::int($today['count'] ?? 0, 'count');
        } catch (\Throwable $e) {
            error_log('Admin pickup overview error: ' . $e->getMessage());
        }

        return $overview;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rewardsStatus(): array
    {
        try {
            return $this->container->database->query(
                "SELECT r.*,
                       CASE
                           WHEN r.stock = 0 THEN 'Out of Stock'
                           WHEN r.stock <= 10 THEN 'Low Stock'
                           ELSE 'In Stock'
                       END as stock_status
                FROM rewards r
                ORDER BY r.stock ASC
                LIMIT 3",
                [],
                '',
            );
        } catch (\Throwable $e) {
            error_log('Admin rewards status error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectionRoutes(): array
    {
        try {
            return $this->container->database->query(
                "SELECT cr.route_id, cr.generated_on,
                       CONCAT(w.first_name, ' ', w.last_name) as worker_name,
                       COUNT(rs.stop_id) as total_stops,
                       COUNT(CASE WHEN pr.status = 'Completed' THEN 1 END) as completed_stops
                FROM collectionroutes cr
                JOIN users w ON cr.worker_id = w.user_id
                LEFT JOIN routestops rs ON cr.route_id = rs.route_id
                LEFT JOIN pickuprequests pr ON rs.request_id = pr.request_id
                WHERE DATE(cr.generated_on) = CURDATE()
                GROUP BY cr.route_id, cr.generated_on, w.first_name, w.last_name
                ORDER BY cr.generated_on DESC
                LIMIT 3",
                [],
                '',
            );
        } catch (\Throwable $e) {
            error_log('Admin collection routes error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentRedemptions(): array
    {
        try {
            return $this->container->database->query(
                "SELECT rr.*, r.name as reward_name, u.first_name, u.last_name
                FROM rewardredemptions rr
                JOIN rewards r ON rr.reward_id = r.reward_id
                JOIN users u ON rr.user_id = u.user_id
                ORDER BY rr.created_at DESC
                LIMIT 3",
                [],
                '',
            );
        } catch (\Throwable $e) {
            error_log('Admin recent redemptions error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentNotifications(): array
    {
        try {
            return $this->container->database->query(
                "SELECT 'Pickup Request' as trigger_event,
                       CONCAT('New pickup request from ', u.first_name, ' ', u.last_name) as message,
                       pr.created_at as sent_at,
                       u.first_name,
                       u.last_name
                FROM pickuprequests pr
                JOIN users u ON pr.resident_id = u.user_id
                WHERE pr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)

                UNION ALL

                SELECT 'Reward Redemption' as trigger_event,
                       CONCAT(u.first_name, ' ', u.last_name, ' redeemed ', r.name) as message,
                       rr.created_at as sent_at,
                       u.first_name,
                       u.last_name
                FROM rewardredemptions rr
                JOIN users u ON rr.user_id = u.user_id
                JOIN rewards r ON rr.reward_id = r.reward_id
                WHERE rr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)

                ORDER BY sent_at DESC
                LIMIT 5",
                [],
                '',
            );
        } catch (\Throwable $e) {
            error_log('Admin recent notifications error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function analyticsData(): array
    {
        try {
            return $this->container->database->query(
                "SELECT MONTH(created_at) as month,
                       YEAR(created_at) as year,
                       MONTHNAME(created_at) as month_name,
                       SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE 0 END) as points_earned,
                       SUM(CASE WHEN transaction_type = 'Redeemed' THEN points ELSE 0 END) as points_redeemed
                FROM pointsledger
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY YEAR(created_at), MONTH(created_at)
                ORDER BY year, month",
                [],
                '',
            );
        } catch (\Throwable $e) {
            error_log('Admin analytics data error: ' . $e->getMessage());

            return [];
        }
    }
}
