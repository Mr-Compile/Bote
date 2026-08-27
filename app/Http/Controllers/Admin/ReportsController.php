<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class ReportsController extends BaseController
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
        // Get date range from query parameters or set defaults
        $start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
        $end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
        
        // Overall Statistics
        $stmt = $this->legacy()->prepare("
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_requests,
                SUM(CASE WHEN status = 'Completed' THEN weight_kg ELSE 0 END) as total_weight
            FROM pickuprequests 
            WHERE created_at BETWEEN ? AND ?
        ");
        
        if ($stmt) {
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $overall_stats = $stmt->get_result()->fetch_assoc();
        } else {
            $overall_stats = ['total_requests' => 0, 'completed_requests' => 0, 'total_weight' => 0];
        }
        
        // Recyclable Types Performance
        $stmt = $this->legacy()->prepare("
            SELECT 
                rt.name AS type_name,
                COUNT(pr.request_id) as request_count,
                SUM(CASE WHEN pr.status = 'Completed' THEN pr.weight_kg ELSE 0 END) as total_weight
            FROM recyclabletypes rt
            LEFT JOIN pickuprequests pr 
                ON rt.type_id = pr.type_id 
                AND pr.created_at BETWEEN ? AND ?
            GROUP BY rt.type_id, rt.name
            ORDER BY total_weight DESC
        ");
        
        if ($stmt) {
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $type_performance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $type_performance = [];
        }
        
        // Worker Performance
        $stmt = $this->legacy()->prepare("
            SELECT 
                CONCAT(u.first_name, ' ', u.last_name) as worker_name,
                COUNT(pr.request_id) as assigned_requests,
                SUM(CASE WHEN pr.status = 'Completed' THEN 1 ELSE 0 END) as completed_requests,
                SUM(CASE WHEN pr.status = 'Completed' THEN pr.actual_weight ELSE 0 END) as total_weight,
                ROUND(AVG(CASE WHEN pr.status = 'Completed' THEN 
                    TIMESTAMPDIFF(HOUR, pr.created_at, pr.updated_at) 
                END), 1) as avg_completion_hours
            FROM users u
            LEFT JOIN pickuprequests pr ON u.user_id = pr.assigned_worker_id 
                AND pr.created_at BETWEEN ? AND ?
            WHERE u.role = 'worker'
            GROUP BY u.user_id, u.first_name, u.last_name
            ORDER BY completed_requests DESC
        ");
        if ($stmt) {
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $worker_performance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $worker_performance = [];
        }
        
        // Top Residents
        $stmt = $this->legacy()->prepare("
            SELECT 
                CONCAT(u.first_name, ' ', u.last_name) as resident_name,
                COUNT(pr.request_id) as total_requests,
                SUM(CASE WHEN pr.status = 'Completed' THEN pr.actual_weight ELSE 0 END) as total_weight,
                SUM(CASE WHEN pr.status = 'Completed' THEN pr.points_earned ELSE 0 END) as total_points
            FROM users u
            LEFT JOIN pickuprequests pr ON u.user_id = pr.resident_id 
                AND pr.created_at BETWEEN ? AND ?
            WHERE u.role = 'resident'
            GROUP BY u.user_id, u.first_name, u.last_name
            HAVING total_requests > 0
            ORDER BY total_points DESC
            LIMIT 10
        ");
        if ($stmt) {
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $top_residents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $top_residents = [];
        }
        
        // Daily Collection Data for Chart
        $stmt = $this->legacy()->prepare("
            SELECT 
                DATE(created_at) as collection_date,
                COUNT(*) as requests_count,
                SUM(CASE WHEN status = 'completed' THEN actual_weight ELSE 0 END) as total_weight
            FROM pickuprequests 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY collection_date
        ");
        if ($stmt) {
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $daily_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $daily_data = [];
        }
        
        // Status Distribution
        $stmt = $this->legacy()->prepare("
            SELECT 
                status,
                COUNT(*) as count
            FROM pickuprequests 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY status
        ");
        if ($stmt) {
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $status_distribution = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $status_distribution = [];
        }

        $data = get_defined_vars();

        $this->view('admin.reports', $data);
    }
}