<?php

declare(strict_types=1);

namespace App\Http\Controllers\Worker;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class CompletedCollectionsController extends BaseController
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
        
        // Fetch completed collections for the worker
        $stmt = $this->legacy()->prepare("
            SELECT pr.request_id, pr.weight_kg, pr.preferred_date, pr.created_at,
                   rt.name as recyclable_type, rt.points_per_kg,
                   CONCAT(u.first_name, ' ', u.last_name) as resident_name,
                   u.address as pickup_address,
                   pl.points as points_awarded
            FROM pickuprequests pr
            JOIN recyclabletypes rt ON pr.type_id = rt.type_id
            JOIN users u ON pr.resident_id = u.user_id
            LEFT JOIN pointsledger pl ON pr.request_id = pl.request_id
            WHERE pr.assigned_worker_id = ? AND pr.status = 'Completed'
            ORDER BY pr.preferred_date DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $completed_collections = $stmt->get_result();
        
        // Get summary statistics
        $stmt = $this->legacy()->prepare("
            SELECT 
                COUNT(*) as total_collections,
                SUM(pr.weight_kg) as total_weight,
                SUM(pl.points) as total_points_awarded
            FROM pickuprequests pr
            LEFT JOIN pointsledger pl ON pr.request_id = pl.request_id
            WHERE pr.assigned_worker_id = ? AND pr.status = 'Completed'
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        
        // Get this month's stats
        $stmt = $this->legacy()->prepare("
            SELECT 
                COUNT(*) as month_collections,
                SUM(pr.weight_kg) as month_weight
            FROM pickuprequests pr
            WHERE pr.assigned_worker_id = ? AND pr.status = 'Completed'
            AND MONTH(pr.preferred_date) = MONTH(CURDATE()) 
            AND YEAR(pr.preferred_date) = YEAR(CURDATE())
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $month_stats = $stmt->get_result()->fetch_assoc();

        $data = get_defined_vars();

        $this->view('worker.completed_collections', $data);
    }
}