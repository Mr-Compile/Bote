<?php

declare(strict_types=1);

namespace App\Http\Controllers\Worker;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class DashboardController extends BaseController
{
    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return ['worker'];
    }

    public function render(): void
    {
        $userId = $this->currentUserId();

        $userData = $this->userData($userId);
        $profileDisplay = $this->profileDisplay($userData);
        $stats = $this->workerStats($userId);
        $pendingPickups = $this->pendingPickups($userId);
        $todayRoute = $this->todayRoute($userId);
        $routeStops = $todayRoute ? $this->routeStops(TypeCaster::int($todayRoute['route_id'] ?? 0, 'route_id')) : [];
        $recentNotifications = $this->recentNotifications($userId);

        if ($todayRoute && !empty($todayRoute['route_geometry'])) {
            $routeGeometry = TypeCaster::string($todayRoute['route_geometry'], 'route_geometry');
            $routeData = json_decode($routeGeometry, true);
            $routeData = is_array($routeData) ? $routeData : [];
            $stats['route_distance'] = TypeCaster::float($routeData['total_distance'] ?? 0, 'total_distance');
            $stats['route_time'] = TypeCaster::string($routeData['estimated_time'] ?? '0h 0m', 'estimated_time');
        } else {
            $stats['route_distance'] = 0;
            $stats['route_time'] = '0h 0m';
        }

        $totalWeight = TypeCaster::float($stats['total_weight'] ?? 0, 'total_weight');
        $completedPickups = TypeCaster::int($stats['completed_pickups'] ?? 0, 'completed_pickups');
        $assignedPickups = TypeCaster::int($stats['assigned_pickups'] ?? 0, 'assigned_pickups');
        $plasticSaved = $totalWeight * 0.8;
        $co2Reduced = $plasticSaved * 2.5;
        $treesEquivalent = $co2Reduced / 21;

        $avgWeight = $completedPickups > 0 ? $totalWeight / $completedPickups : 0;
        $completionRate = $assignedPickups > 0 ? ($completedPickups / $assignedPickups) * 100 : 0;

        $this->view('worker.dashboard', [
            'title' => 'Worker Dashboard',
            'bodyClass' => 'bg-bote-stone-50 min-h-screen',
            'system_name' => $this->setting('barangay_name', 'BoteBank'),
            'user_id' => $userId,
            'user_data' => $userData,
            'profile_display' => $profileDisplay,
            'stats' => $stats,
            'pending_pickups' => $pendingPickups,
            'today_route' => $todayRoute,
            'route_stops' => $routeStops,
            'recent_notifications' => $recentNotifications,
            'total_weight' => $totalWeight,
            'plastic_saved' => $plasticSaved,
            'co2_reduced' => $co2Reduced,
            'trees_equivalent' => $treesEquivalent,
            'avg_weight' => $avgWeight,
            'completion_rate' => $completionRate,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userData(int $userId): array
    {
        try {
            $row = $this->container->database->selectOne(
                'SELECT first_name, last_name, email, phone, profile_photo, address FROM users WHERE user_id = ?',
                [$userId],
                'i',
            );

            return $row ?? [];
        } catch (\Throwable $e) {
            error_log('Worker user data error: ' . $e->getMessage());

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

    /**
     * @return array<string, mixed>
     */
    private function workerStats(int $userId): array
    {
        $defaults = [
            'assigned_pickups' => 0,
            'pending_pickups' => 0,
            'completed_pickups' => 0,
            'active_routes' => 0,
            'today_weight' => 0,
            'total_weight' => 0,
            'avg_weight' => 0,
            'completion_rate' => 0,
            'route_distance' => 0,
            'route_time' => '0h 0m',
        ];

        try {
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
            $activeRoutes = $this->container->database->selectOne(
                "SELECT COUNT(*) as active_routes FROM collectionroutes WHERE worker_id = ? AND DATE(generated_on) = CURDATE()",
                [$userId],
                'i',
            );
            $todayWeight = $this->container->database->selectOne(
                "SELECT COALESCE(SUM(weight_kg), 0) as today_weight FROM pickuprequests WHERE assigned_worker_id = ? AND status = 'Completed' AND DATE(created_at) = CURDATE()",
                [$userId],
                'i',
            );
            $totalWeight = $this->container->database->selectOne(
                "SELECT COALESCE(SUM(weight_kg), 0) as total_weight FROM pickuprequests WHERE assigned_worker_id = ? AND status = 'Completed'",
                [$userId],
                'i',
            );

            $stats = [
                'assigned_pickups' => TypeCaster::int($assigned['assigned_pickups'] ?? 0, 'assigned_pickups'),
                'pending_pickups' => TypeCaster::int($pending['pending_pickups'] ?? 0, 'pending_pickups'),
                'completed_pickups' => TypeCaster::int($completed['completed_pickups'] ?? 0, 'completed_pickups'),
                'active_routes' => TypeCaster::int($activeRoutes['active_routes'] ?? 0, 'active_routes'),
                'today_weight' => TypeCaster::float($todayWeight['today_weight'] ?? 0, 'today_weight'),
                'total_weight' => TypeCaster::float($totalWeight['total_weight'] ?? 0, 'total_weight'),
            ];

            $stats['avg_weight'] = $stats['completed_pickups'] > 0 ? $stats['total_weight'] / $stats['completed_pickups'] : 0;
            $stats['completion_rate'] = $stats['assigned_pickups'] > 0 ? ($stats['completed_pickups'] / $stats['assigned_pickups']) * 100 : 0;

            return $stats;
        } catch (\Throwable $e) {
            error_log('Worker stats error: ' . $e->getMessage());

            return $defaults;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pendingPickups(int $userId): array
    {
        try {
            return $this->container->database->query(
                "SELECT pr.request_id, pr.preferred_date, pr.status, pr.latitude, pr.longitude, pr.weight_kg,
                       u.first_name, u.last_name, u.phone, rt.name as recyclable_type
                FROM pickuprequests pr
                JOIN users u ON pr.resident_id = u.user_id
                LEFT JOIN recyclabletypes rt ON pr.type_id = rt.type_id
                WHERE pr.assigned_worker_id = ? AND pr.status = 'Scheduled'
                ORDER BY pr.preferred_date ASC
                LIMIT 3",
                [$userId],
                'i',
            );
        } catch (\Throwable $e) {
            error_log('Worker pending pickups error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function todayRoute(int $userId): ?array
    {
        try {
            return $this->container->database->selectOne(
                "SELECT cr.route_id, cr.generated_on, cr.route_geometry
                FROM collectionroutes cr
                WHERE cr.worker_id = ? AND DATE(cr.generated_on) = CURDATE()
                ORDER BY cr.generated_on DESC
                LIMIT 1",
                [$userId],
                'i',
            );
        } catch (\Throwable $e) {
            error_log('Worker today route error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function routeStops(int $routeId): array
    {
        try {
            return $this->container->database->query(
                "SELECT rs.stop_id, rs.request_id, rs.point_id, rs.stop_order, rs.latitude, rs.longitude,
                       pr.weight_kg, pr.status as request_status, u.first_name, u.last_name,
                       cp.name as point_name
                FROM routestops rs
                LEFT JOIN pickuprequests pr ON rs.request_id = pr.request_id
                LEFT JOIN users u ON pr.resident_id = u.user_id
                LEFT JOIN collection_points cp ON rs.point_id = cp.point_id
                WHERE rs.route_id = ?
                ORDER BY rs.stop_order ASC",
                [$routeId],
                'i',
            );
        } catch (\Throwable $e) {
            error_log('Worker route stops error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentNotifications(int $userId): array
    {
        try {
            return $this->container->database->query(
                "SELECT pr.request_id, pr.created_at, pr.status, pr.weight_kg,
                       u.first_name, u.last_name, rt.name as recyclable_type
                FROM pickuprequests pr
                JOIN users u ON pr.resident_id = u.user_id
                LEFT JOIN recyclabletypes rt ON pr.type_id = rt.type_id
                WHERE pr.assigned_worker_id = ? AND pr.status IN ('Scheduled', 'Completed')
                ORDER BY pr.created_at DESC
                LIMIT 3",
                [$userId],
                'i',
            );
        } catch (\Throwable $e) {
            error_log('Worker recent notifications error: ' . $e->getMessage());

            return [];
        }
    }
}
