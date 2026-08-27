<?php

declare(strict_types=1);

namespace App\Http\Controllers\Resident;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class DashboardController extends BaseController
{
    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return ['resident'];
    }

    public function render(): void
    {
        $userId = $this->currentUserId();

        $period = $_GET['period'] ?? 'monthly';
        $validPeriods = ['weekly', 'monthly', 'all-time'];
        if (!in_array($period, $validPeriods, true)) {
            $period = 'monthly';
        }

        $sinceDate = match ($period) {
            'weekly' => date('Y-m-d', strtotime('-7 days')),
            'monthly' => date('Y-m-d', strtotime('-30 days')),
            default => null,
        };
        $periodLabel = match ($period) {
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            default => 'All-Time',
        };

        $userData = $this->userData($userId);
        $profileDisplay = $this->profileDisplay($userData);
        $totalPoints = $this->totalPoints($userId);
        $stats = $this->requestStats($userId);
        $recentRequests = $this->recentRequests($userId);
        $recentRedemptions = $this->recentRedemptions($userId);
        $userPurokId = $this->userPurokId($userId);
        $leaderboard = $this->leaderboard($sinceDate);
        $topPuroks = $this->topPuroks($sinceDate);
        $recentTransactions = $this->recentTransactions($userId);

        $userPosition = 0;
        foreach ($leaderboard as $position => $user) {
            if ($user['user_id'] == $userId) {
                $userPosition = $position + 1;
                break;
            }
        }

        $pointsData = $this->pointsBreakdown($userId);

        $dashboardData = [
            'pointsEarned' => TypeCaster::int($pointsData['points_earned'] ?? 0, 'points_earned'),
            'pointsRedeemed' => TypeCaster::int($pointsData['points_redeemed'] ?? 0, 'points_redeemed'),
            'totalPoints' => TypeCaster::int($totalPoints, 'totalPoints'),
        ];

        $this->view('resident.dashboard', [
            'title' => 'Resident Dashboard',
            'bodyClass' => 'bg-gradient-to-br from-bote-stone-50 via-bote-info-50 to-bote-info-50 min-h-screen',
            'system_name' => $this->setting('barangay_name', 'BoteBank'),
            'user_id' => $userId,
            'user_data' => $userData,
            'profile_display' => $profileDisplay,
            'total_points' => $totalPoints,
            'stats' => array_merge($stats, $pointsData),
            'recent_requests' => $recentRequests,
            'recent_redemptions' => $recentRedemptions,
            'recent_transactions' => $recentTransactions,
            'user_purok_id' => $userPurokId,
            'leaderboard' => $leaderboard,
            'top_residents' => $leaderboard,
            'top_puroks' => $topPuroks,
            'user_position' => $userPosition,
            'period' => $period,
            'period_label' => $periodLabel,
            'json_dashboard_data' => json_encode($dashboardData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userData(int $userId): array
    {
        try {
            $row = $this->container->database->selectOne(
                'SELECT first_name, last_name, email, phone, address, profile_photo, purok_id FROM users WHERE user_id = ?',
                [$userId],
                'i',
            );

            return $row ?? [];
        } catch (\Throwable $e) {
            error_log('Resident user data error: ' . $e->getMessage());

            return [];
        }
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

    private function totalPoints(int $userId): int
    {
        try {
            $row = $this->container->database->selectOne(
                "SELECT COALESCE(SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE -points END), 0) as total_points
                FROM pointsledger WHERE user_id = ?",
                [$userId],
                'i',
            );

            return TypeCaster::int($row['total_points'] ?? 0, 'total_points');
        } catch (\Throwable $e) {
            error_log('Resident total points error: ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function pointsBreakdown(int $userId): array
    {
        try {
            $row = $this->container->database->selectOne(
                "SELECT
                    COALESCE(SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE 0 END), 0) as points_earned,
                    COALESCE(SUM(CASE WHEN transaction_type = 'Redeemed' THEN points ELSE 0 END), 0) as points_redeemed
                FROM pointsledger WHERE user_id = ?",
                [$userId],
                'i',
            );

            return [
                'points_earned' => TypeCaster::int($row['points_earned'] ?? 0, 'points_earned'),
                'points_redeemed' => TypeCaster::int($row['points_redeemed'] ?? 0, 'points_redeemed'),
            ];
        } catch (\Throwable $e) {
            error_log('Resident points breakdown error: ' . $e->getMessage());

            return ['points_earned' => 0, 'points_redeemed' => 0];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function requestStats(int $userId): array
    {
        $defaults = [
            'total_requests' => 0,
            'pending_requests' => 0,
            'scheduled_requests' => 0,
            'in_progress_requests' => 0,
            'completed_requests' => 0,
            'cancelled_requests' => 0,
            'total_weight' => 0,
        ];

        try {
            $row = $this->container->database->selectOne(
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

            if ($row === null) {
                return $defaults;
            }

            return [
                'total_requests' => TypeCaster::int($row['total_requests'] ?? 0, 'total_requests'),
                'pending_requests' => TypeCaster::int($row['pending_requests'] ?? 0, 'pending_requests'),
                'scheduled_requests' => TypeCaster::int($row['scheduled_requests'] ?? 0, 'scheduled_requests'),
                'in_progress_requests' => TypeCaster::int($row['in_progress_requests'] ?? 0, 'in_progress_requests'),
                'completed_requests' => TypeCaster::int($row['completed_requests'] ?? 0, 'completed_requests'),
                'cancelled_requests' => TypeCaster::int($row['cancelled_requests'] ?? 0, 'cancelled_requests'),
                'total_weight' => TypeCaster::float($row['total_weight'] ?? 0, 'total_weight'),
            ];
        } catch (\Throwable $e) {
            error_log('Resident request stats error: ' . $e->getMessage());

            return $defaults;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentRequests(int $userId): array
    {
        try {
            return $this->container->database->query(
                "SELECT pr.*, rt.name as recyclable_type, rt.points_per_kg
                FROM pickuprequests pr
                LEFT JOIN recyclabletypes rt ON pr.type_id = rt.type_id
                WHERE pr.resident_id = ?
                ORDER BY pr.created_at DESC
                LIMIT 5",
                [$userId],
                'i',
            );
        } catch (\Throwable $e) {
            error_log('Resident recent requests error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentRedemptions(int $userId): array
    {
        try {
            return $this->container->database->query(
                "SELECT rr.*, r.name as reward_name, r.points_required
                FROM rewardredemptions rr
                LEFT JOIN rewards r ON rr.reward_id = r.reward_id
                WHERE rr.user_id = ?
                ORDER BY rr.created_at DESC
                LIMIT 5",
                [$userId],
                'i',
            );
        } catch (\Throwable $e) {
            error_log('Resident recent redemptions error: ' . $e->getMessage());

            return [];
        }
    }

    private function userPurokId(int $userId): ?int
    {
        try {
            $row = $this->container->database->selectOne(
                'SELECT purok_id FROM users WHERE user_id = ?',
                [$userId],
                'i',
            );

            return $row !== null ? TypeCaster::nullableInt($row['purok_id'] ?? null, 'purok_id') : null;
        } catch (\Throwable $e) {
            error_log('Resident purok id error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @param string|null $sinceDate
     * @return list<array<string, mixed>>
     */
    private function leaderboard(?string $sinceDate): array
    {
        $params = [];
        $types = '';
        $dateFilter = '';

        if ($sinceDate !== null) {
            $dateFilter = ' AND pl.created_at >= ?';
            $params[] = $sinceDate;
            $types .= 's';
        }

        try {
            return $this->container->database->query(
                "SELECT
                    u.user_id,
                    u.first_name,
                    u.last_name,
                    COALESCE(SUM(CASE WHEN pl.transaction_type = 'Earned' THEN pl.points ELSE -pl.points END), 0) as total_points,
                    ROW_NUMBER() OVER (ORDER BY COALESCE(SUM(CASE WHEN pl.transaction_type = 'Earned' THEN pl.points ELSE -pl.points END), 0) DESC) as ranking
                FROM users u
                LEFT JOIN pointsledger pl ON u.user_id = pl.user_id" . $dateFilter . "
                WHERE u.role = 'resident'
                GROUP BY u.user_id, u.first_name, u.last_name
                HAVING total_points > 0
                ORDER BY total_points DESC
                LIMIT 10",
                $params,
                $types,
            );
        } catch (\Throwable $e) {
            error_log('Resident leaderboard error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @param string|null $sinceDate
     * @return list<array<string, mixed>>
     */
    private function topPuroks(?string $sinceDate): array
    {
        $params = [];
        $types = '';
        $dateFilter = '';

        if ($sinceDate !== null) {
            $dateFilter = ' AND pl.created_at >= ?';
            $params[] = $sinceDate;
            $types .= 's';
        }

        try {
            return $this->container->database->query(
                "SELECT
                    p.purok_id,
                    p.name as purok_name,
                    COALESCE(SUM(CASE WHEN pl.transaction_type = 'Earned' THEN pl.points ELSE -pl.points END), 0) as total_points
                FROM puroks p
                LEFT JOIN users u ON p.purok_id = u.purok_id
                LEFT JOIN pointsledger pl ON u.user_id = pl.user_id" . $dateFilter . "
                WHERE u.role = 'resident'
                GROUP BY p.purok_id, p.name
                HAVING total_points > 0
                ORDER BY total_points DESC
                LIMIT 10",
                $params,
                $types,
            );
        } catch (\Throwable $e) {
            error_log('Resident top puroks error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentTransactions(int $userId): array
    {
        try {
            return $this->container->database->query(
                "SELECT pl.*, pr.preferred_date as pickup_date, rt.name as recyclable_type
                FROM pointsledger pl
                LEFT JOIN pickuprequests pr ON pl.request_id = pr.request_id
                LEFT JOIN recyclabletypes rt ON pr.type_id = rt.type_id
                WHERE pl.user_id = ?
                ORDER BY pl.created_at DESC
                LIMIT 5",
                [$userId],
                'i',
            );
        } catch (\Throwable $e) {
            error_log('Resident recent transactions error: ' . $e->getMessage());

            return [];
        }
    }
}
