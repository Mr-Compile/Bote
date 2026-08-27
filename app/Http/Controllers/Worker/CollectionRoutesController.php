<?php

declare(strict_types=1);

namespace App\Http\Controllers\Worker;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class CollectionRoutesController extends BaseController
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
        // Get user data for sidebar
        $user_id = $_SESSION['user_id'];
        
        // Get the selected date from query parameter, default to today
        $selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        
        // Validate date format
        if (!\DateTime::createFromFormat('Y-m-d', $selected_date)) {
            $selected_date = date('Y-m-d');
        }
        
        // Fetch pickup requests assigned to this worker for the selected date
        $stmt = $this->legacy()->prepare("
            SELECT pr.request_id, pr.resident_id, pr.type_id, pr.weight_kg, pr.preferred_date, pr.status,
                   pr.latitude, pr.longitude, pr.pickup_type, pr.point_id, pr.actual_weight, pr.points_earned,
                   pr.created_at, pr.updated_at, pr.quantity_pcs,
                   u.first_name, u.last_name, u.address, u.phone,
                   rt.name as material_type, rt.unit, rt.points_per_kg, rt.points_per_unit,
                   cp.name as collection_point_name
            FROM pickuprequests pr
            JOIN users u ON pr.resident_id = u.user_id
            JOIN recyclabletypes rt ON pr.type_id = rt.type_id
            LEFT JOIN collection_points cp ON pr.point_id = cp.point_id
            WHERE pr.assigned_worker_id = ? 
            AND DATE(pr.preferred_date) = ?
            ORDER BY pr.preferred_date ASC, pr.status ASC
        ");
        $stmt->bind_param("is", $user_id, $selected_date);
        $stmt->execute();
        $today_pickups = $stmt->get_result();
        
        // Get pickup statistics for the selected date
        $stmt = $this->legacy()->prepare("
            SELECT 
                COUNT(*) as total_pickups,
                COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_pickups,
                COUNT(CASE WHEN status = 'Scheduled' THEN 1 END) as scheduled_pickups,
                COUNT(CASE WHEN status = 'In Progress' THEN 1 END) as in_progress_pickups,
                COALESCE(SUM(CASE WHEN status = 'Completed' THEN actual_weight END), 0) as total_weight_collected,
                COALESCE(SUM(CASE WHEN status = 'Completed' THEN points_earned END), 0) as total_points_awarded
            FROM pickuprequests 
            WHERE assigned_worker_id = ? 
            AND DATE(preferred_date) = ?
        ");
        $stmt->bind_param("is", $user_id, $selected_date);
        $stmt->execute();
        $pickup_stats = $stmt->get_result()->fetch_assoc();
        
        // Get available dates with pickup requests for this worker (for date selector)
        $stmt = $this->legacy()->prepare("
            SELECT DISTINCT DATE(preferred_date) as pickup_date, 
                   COUNT(*) as pickup_count,
                   COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_count
            FROM pickuprequests 
            WHERE assigned_worker_id = ? 
            AND DATE(preferred_date) >= CURDATE() - INTERVAL 7 DAY
            AND DATE(preferred_date) <= CURDATE() + INTERVAL 30 DAY
            GROUP BY DATE(preferred_date)
            ORDER BY pickup_date ASC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $available_dates = $stmt->get_result();
        
        // Fetch collection routes for the worker for the selected date
        $stmt = $this->legacy()->prepare("
            SELECT cr.route_id, cr.generated_on, cr.route_geometry, COUNT(rs.stop_id) as total_stops,
                   COUNT(CASE WHEN pr.status = 'Completed' THEN 1 END) as completed_stops,
                   SUM(CASE WHEN pr.status != 'Completed' THEN 1 ELSE 0 END) as pending_stops
            FROM collectionroutes cr
            LEFT JOIN routestops rs ON cr.route_id = rs.route_id
            LEFT JOIN pickuprequests pr ON rs.request_id = pr.request_id
            WHERE cr.worker_id = ? AND DATE(cr.generated_on) = ?
            GROUP BY cr.route_id
            ORDER BY cr.generated_on DESC
            LIMIT 1
        ");
        $stmt->bind_param("is", $user_id, $selected_date);
        $stmt->execute();
        $routes = $stmt->get_result();
        
        // Check if worker has scheduled pickups without a route
        $stmt = $this->legacy()->prepare("
            SELECT COUNT(*) as unrouted_pickups
            FROM pickuprequests pr
            WHERE pr.assigned_worker_id = ? AND pr.status = 'Scheduled'
            AND pr.request_id NOT IN (
                SELECT COALESCE(rs.request_id, 0) 
                FROM collectionroutes cr 
                LEFT JOIN routestops rs ON cr.route_id = rs.route_id 
                WHERE cr.worker_id = ? AND DATE(cr.generated_on) = CURDATE()
            )
        ");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $unrouted_result = $stmt->get_result()->fetch_assoc();
        $has_unrouted_pickups = $unrouted_result['unrouted_pickups'] > 0;

        $data = get_defined_vars();

        $this->view('worker.collection_routes', $data);
    }
}