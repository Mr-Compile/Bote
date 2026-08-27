<?php

declare(strict_types=1);

namespace App\Http\Controllers\Resident;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class HistoryController extends BaseController
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
        // Get user data for sidebar
        $user_id = $_SESSION['user_id'];
        
        // Fetch comprehensive history: points, pickup requests, dropoffs, and redemptions
        $stmt = $this->legacy()->prepare("
            SELECT 
                'points' as activity_type,
                pl.transaction_id as id,
                pl.points,
                pl.transaction_type,
                pl.created_at,
                pr.request_id,
                pr.status as request_status,
                pr.pickup_type,
                pr.preferred_date,
                pr.actual_weight,
                rt.name as recyclable_type,
                rt.image as recyclable_image,
                rt.unit as recyclable_unit,
                pr.quantity_pcs,
                NULL as redemption_id,
                NULL as redemption_status,
                NULL as reward_name,
                NULL as reward_image,
                dd.dropoff_id,
                dd.status as dropoff_status,
                dd.weight_kg as dropoff_weight,
                rt2.name as dropoff_recyclable_type,
                rt2.image as dropoff_recyclable_image,
                COALESCE(cp.name, cp2.name) as collection_point_name
            FROM pointsledger pl
            LEFT JOIN pickuprequests pr ON pl.request_id = pr.request_id
            LEFT JOIN recyclabletypes rt ON pr.type_id = rt.type_id
            LEFT JOIN direct_dropoffs dd ON pl.dropoff_id = dd.dropoff_id
            LEFT JOIN recyclabletypes rt2 ON dd.type_id = rt2.type_id
            LEFT JOIN collection_points cp ON pr.point_id = cp.point_id
            LEFT JOIN collection_points cp2 ON dd.point_id = cp2.point_id
            WHERE pl.user_id = ?
            
            UNION ALL
            
            SELECT 
                'request' as activity_type,
                pr.request_id as id,
                COALESCE(pr.points_earned, 0) as points,
                'Request' as transaction_type,
                pr.created_at,
                pr.request_id,
                pr.status as request_status,
                pr.pickup_type,
                pr.preferred_date,
                pr.actual_weight,
                rt.name as recyclable_type,
                rt.image as recyclable_image,
                rt.unit as recyclable_unit,
                pr.quantity_pcs,
                NULL as redemption_id,
                NULL as redemption_status,
                NULL as reward_name,
                NULL as reward_image,
                NULL as dropoff_id,
                NULL as dropoff_status,
                NULL as dropoff_weight,
                NULL as dropoff_recyclable_type,
                NULL as dropoff_recyclable_image,
                cp.name as collection_point_name
            FROM pickuprequests pr
            LEFT JOIN recyclabletypes rt ON pr.type_id = rt.type_id
            LEFT JOIN collection_points cp ON pr.point_id = cp.point_id
            WHERE pr.resident_id = ? 
            AND NOT EXISTS (
                SELECT 1 FROM pointsledger WHERE user_id = ? AND request_id = pr.request_id
            )
            
            UNION ALL
            
            SELECT 
                'dropoff' as activity_type,
                dd.dropoff_id as id,
                COALESCE(dd.points_earned, 0) as points,
                'Dropoff' as transaction_type,
                dd.created_at,
                NULL as request_id,
                NULL as request_status,
                'DirectDropoff' as pickup_type,
                NULL as preferred_date,
                dd.weight_kg as actual_weight,
                rt.name as recyclable_type,
                rt.image as recyclable_image,
                rt.unit as recyclable_unit,
                dd.quantity_pcs,
                NULL as redemption_id,
                NULL as redemption_status,
                NULL as reward_name,
                NULL as reward_image,
                dd.dropoff_id,
                dd.status as dropoff_status,
                dd.weight_kg as dropoff_weight,
                rt.name as dropoff_recyclable_type,
                rt.image as dropoff_recyclable_image,
                cp.name as collection_point_name
            FROM direct_dropoffs dd
            LEFT JOIN recyclabletypes rt ON dd.type_id = rt.type_id
            LEFT JOIN collection_points cp ON dd.point_id = cp.point_id
            WHERE dd.resident_id = ? 
            AND NOT EXISTS (
                SELECT 1 FROM pointsledger WHERE user_id = ? AND dropoff_id = dd.dropoff_id
            )
            
            UNION ALL
            
            SELECT 
                'redemption' as activity_type,
                rr.redemption_id as id,
                r.points_required * rr.quantity as points,
                'Redeemed' as transaction_type,
                rr.created_at,
                NULL as request_id,
                NULL as request_status,
                NULL as pickup_type,
                NULL as preferred_date,
                NULL as actual_weight,
                NULL as recyclable_type,
                NULL as recyclable_image,
                NULL as recyclable_unit,
                rr.quantity as quantity_pcs,
                rr.redemption_id,
                rr.status as redemption_status,
                r.name as reward_name,
                r.image as reward_image,
                NULL as dropoff_id,
                NULL as dropoff_status,
                NULL as dropoff_weight,
                NULL as dropoff_recyclable_type,
                NULL as dropoff_recyclable_image,
                NULL as collection_point_name
            FROM rewardredemptions rr
            LEFT JOIN rewards r ON rr.reward_id = r.reward_id
            WHERE rr.user_id = ?
            
            ORDER BY created_at DESC
        ");
        
        if (!$stmt) {
            die("Error preparing statement. Please check the error log.");
        }
        
        $stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        $transactions = $stmt->get_result();
        
        // Get available points (total earned minus total spent)
        $stmt = $this->legacy()->prepare("SELECT COALESCE(SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE -points END), 0) as available_points FROM pointsledger WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $available_points = $stmt->get_result()->fetch_assoc()['available_points'];
        
        // Get total points earned
        $stmt = $this->legacy()->prepare("SELECT COALESCE(SUM(points), 0) as total_earned FROM pointsledger WHERE user_id = ? AND transaction_type = 'Earned'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $total_earned = $stmt->get_result()->fetch_assoc()['total_earned'];
        
        // Get points earned this month
        $stmt = $this->legacy()->prepare("SELECT COALESCE(SUM(points), 0) as month_points FROM pointsledger WHERE user_id = ? AND transaction_type = 'Earned' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $month_points = $stmt->get_result()->fetch_assoc()['month_points'];
        
        // Get total points spent on rewards
        $stmt = $this->legacy()->prepare("SELECT COALESCE(SUM(points), 0) as total_spent FROM pointsledger WHERE user_id = ? AND transaction_type = 'Redeemed'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $total_spent = $stmt->get_result()->fetch_assoc()['total_spent'];
        
        // Get total pickup requests
        $stmt = $this->legacy()->prepare("SELECT COUNT(*) as total_requests FROM pickuprequests WHERE resident_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $total_requests = $stmt->get_result()->fetch_assoc()['total_requests'];
        
        // Get total direct dropoffs
        $stmt = $this->legacy()->prepare("SELECT COUNT(*) as total_dropoffs FROM direct_dropoffs WHERE resident_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $total_dropoffs = $stmt->get_result()->fetch_assoc()['total_dropoffs'];
        
        // Get total weight recycled
        $stmt = $this->legacy()->prepare("
            SELECT 
                (SELECT COALESCE(SUM(actual_weight), 0) FROM pickuprequests WHERE resident_id = ? AND status = 'Completed') +
                (SELECT COALESCE(SUM(weight_kg), 0) FROM direct_dropoffs WHERE resident_id = ? AND status = 'Verified') as total_weight
        ");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $total_weight = $stmt->get_result()->fetch_assoc()['total_weight'];

        $data = get_defined_vars();

        $this->view('resident.history', $data);
    }
}