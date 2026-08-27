<?php

declare(strict_types=1);

namespace App\Http\Controllers\Worker;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class DailyReportsController extends BaseController
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
        
        // Handle form submission for new report
        $errors = [];
        $success_message = null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_report'])) {
            $report_date = $_POST['report_date'] ?? null;
            $collections_count = (int) ($_POST['collections_count'] ?? 0);
            $total_weight = (float) ($_POST['total_weight'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            $route_efficiency = $_POST['route_efficiency'] ?? 'good';
        
            if (empty($report_date)) {
                $errors[] = "Report date is required";
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $report_date)) {
                $errors[] = "Report date is invalid";
            }
            if ($collections_count < 0) $errors[] = "Collections count cannot be negative";
            if ($total_weight < 0) $errors[] = "Total weight cannot be negative";
            if (!in_array($route_efficiency, ['excellent', 'good', 'average', 'needs_improvement'], true)) {
                $errors[] = "Invalid route efficiency";
            }
        
            if (empty($errors)) {
                $stmt = $this->legacy()->prepare("
                    INSERT INTO daily_reports (worker_id, report_date, collections_count, total_weight, route_efficiency, notes)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        collections_count = VALUES(collections_count),
                        total_weight = VALUES(total_weight),
                        route_efficiency = VALUES(route_efficiency),
                        notes = VALUES(notes)
                ");
                if ($stmt) {
                    $stmt->bind_param("isidss", $user_id, $report_date, $collections_count, $total_weight, $route_efficiency, $notes);
                    if ($stmt->execute()) {
                        $success_message = "Daily report saved successfully!";
                    } else {
                        $errors[] = "Failed to save report. Please try again.";
                    }
                } else {
                    $errors[] = "Database error. Please try again.";
                }
            }
        }
        
        // Get today's stats
        $today = date('Y-m-d');
        $stmt = $this->legacy()->prepare("
            SELECT 
                COUNT(*) as today_collections,
                SUM(pr.weight_kg) as today_weight,
                SUM(pl.points) as today_points
            FROM pickuprequests pr
            LEFT JOIN pointsledger pl ON pr.request_id = pl.request_id
            WHERE pr.assigned_worker_id = ? 
            AND pr.status = 'Completed'
            AND DATE(pr.preferred_date) = ?
        ");
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        $today_stats = $stmt->get_result()->fetch_assoc();
        
        // Get this week's stats
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $stmt = $this->legacy()->prepare("
            SELECT 
                COUNT(*) as week_collections,
                SUM(pr.weight_kg) as week_weight
            FROM pickuprequests pr
            WHERE pr.assigned_worker_id = ? 
            AND pr.status = 'Completed'
            AND DATE(pr.preferred_date) >= ?
        ");
        $stmt->bind_param("is", $user_id, $week_start);
        $stmt->execute();
        $week_stats = $stmt->get_result()->fetch_assoc();
        
        // Get saved reports for the last 7 days
        $stmt = $this->legacy()->prepare("
            SELECT report_id, report_date, collections_count, total_weight, route_efficiency, notes
            FROM daily_reports
            WHERE worker_id = ?
            AND report_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ORDER BY report_date DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $saved_reports = $stmt->get_result();
        
        // Get today's saved report for form defaults
        $stmt = $this->legacy()->prepare("
            SELECT collections_count, total_weight, route_efficiency, notes
            FROM daily_reports
            WHERE worker_id = ? AND report_date = ?
        ");
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        $today_report = $stmt->get_result()->fetch_assoc();
        
        // Get daily breakdown for the last 7 days
        $stmt = $this->legacy()->prepare("
            SELECT 
                DATE(pr.preferred_date) as collection_date,
                COUNT(*) as daily_collections,
                SUM(pr.weight_kg) as daily_weight
            FROM pickuprequests pr
            WHERE pr.assigned_worker_id = ? 
            AND pr.status = 'Completed'
            AND DATE(pr.preferred_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(pr.preferred_date)
            ORDER BY collection_date DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $daily_breakdown = $stmt->get_result();

        $data = get_defined_vars();

        $this->view('worker.daily_reports', $data);
    }
}