<?php

declare(strict_types=1);

namespace App\Http\Controllers\Resident;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class LeaderboardController extends BaseController
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
        $this->requireInclude('leaderboard_helpers.php');
        $this->ensureSystemSettingsTable();
        
        // Get system settings
        $system_name = $this->setting('barangay_name', 'BoteBank');
        
        // Get time period from URL parameter (weekly or monthly)
        $period = isset($_GET['period']) ? $_GET['period'] : 'weekly';
        $valid_periods = ['weekly', 'monthly'];
        if (!in_array($period, $valid_periods)) {
            $period = 'weekly';
        }
        
        // Calculate date range based on period
        if ($period === 'weekly') {
            $start_date = date('Y-m-d', strtotime('-7 days'));
            $period_label = 'Weekly';
        } else {
            $start_date = date('Y-m-d', strtotime('-30 days'));
            $period_label = 'Monthly';
        }
        
        // Get individual leaderboard data
        $individual_query = "
            SELECT 
                u.user_id,
                u.first_name,
                u.last_name,
                u.profile_photo,
                COALESCE(SUM(CASE WHEN pl.transaction_type = 'Earned' THEN pl.points ELSE 0 END), 0) as total_points
            FROM users u
            LEFT JOIN pointsledger pl ON u.user_id = pl.user_id AND pl.created_at >= ?
            WHERE u.role = 'resident'
            GROUP BY u.user_id, u.first_name, u.last_name, u.profile_photo
            ORDER BY total_points DESC, u.first_name ASC
            LIMIT 50
        ";
        
        $individual_leaderboard = [];
        $stmt = $this->legacy()->prepare($individual_query);
        if ($stmt) {
            $stmt->bind_param("s", $start_date);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $individual_leaderboard = $result->fetch_all(MYSQLI_ASSOC);
                
                // Get request counts separately to avoid Cartesian product
                foreach ($individual_leaderboard as $key => $user) {
                    $request_query = "
                        SELECT 
                            COUNT(DISTINCT CASE WHEN pr.status = 'Completed' THEN pr.request_id END) + 
                            COUNT(DISTINCT CASE WHEN dd.status = 'Completed' THEN dd.dropoff_id END) as total_requests
                        FROM users u
                        LEFT JOIN pickuprequests pr ON u.user_id = pr.resident_id AND pr.created_at >= ?
                        LEFT JOIN direct_dropoffs dd ON u.user_id = dd.resident_id AND dd.created_at >= ?
                        WHERE u.user_id = ?
                    ";
                    $request_stmt = $this->legacy()->prepare($request_query);
                    if ($request_stmt) {
                        $request_stmt->bind_param("ssi", $start_date, $start_date, $user['user_id']);
                        if ($request_stmt->execute()) {
                            $request_result = $request_stmt->get_result();
                            $request_data = $request_result->fetch_assoc();
                            $individual_leaderboard[$key]['total_requests'] = $request_data['total_requests'] ?? 0;
                        } else {
                            $individual_leaderboard[$key]['total_requests'] = 0;
                        }
                        $request_stmt->close();
                    } else {
                        $individual_leaderboard[$key]['total_requests'] = 0;
                    }
                }
            } else {
                error_log("Error executing individual leaderboard query: " . $stmt->error);
            }
            $stmt->close(); 
        } else {
            error_log("Error preparing individual leaderboard query");
        }
        
        // Get purok leaderboard data
        $purok_query = "
            SELECT 
                p.purok_id,
                p.name as purok_name,
                COALESCE(SUM(CASE WHEN pl.transaction_type = 'Earned' THEN pl.points ELSE 0 END), 0) as total_points,
                COUNT(DISTINCT u.user_id) as active_members
            FROM puroks p
            LEFT JOIN users u ON p.purok_id = u.purok_id AND u.role = 'resident'
            LEFT JOIN pointsledger pl ON u.user_id = pl.user_id AND pl.created_at >= ?
            GROUP BY p.purok_id, p.name
            ORDER BY total_points DESC, p.name ASC
        ";
        
        $purok_leaderboard = [];
        $stmt = $this->legacy()->prepare($purok_query);
        if ($stmt) {
            $stmt->bind_param("s", $start_date);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $purok_leaderboard = $result->fetch_all(MYSQLI_ASSOC);
                
                // Get request counts separately for each purok to avoid Cartesian product
                foreach ($purok_leaderboard as $key => $purok) {
                    $purok_request_query = "
                        SELECT 
                            COUNT(DISTINCT CASE WHEN pr.status = 'Completed' THEN pr.request_id END) + 
                            COUNT(DISTINCT CASE WHEN dd.status = 'Completed' THEN dd.dropoff_id END) as total_requests
                        FROM puroks p
                        LEFT JOIN users u ON p.purok_id = u.purok_id AND u.role = 'resident'
                        LEFT JOIN pickuprequests pr ON u.user_id = pr.resident_id AND pr.created_at >= ?
                        LEFT JOIN direct_dropoffs dd ON u.user_id = dd.resident_id AND dd.created_at >= ?
                        WHERE p.purok_id = ?
                    ";
                    $purok_request_stmt = $this->legacy()->prepare($purok_request_query);
                    if ($purok_request_stmt) {
                        $purok_request_stmt->bind_param("ssi", $start_date, $start_date, $purok['purok_id']);
                        if ($purok_request_stmt->execute()) {
                            $purok_request_result = $purok_request_stmt->get_result();
                            $purok_request_data = $purok_request_result->fetch_assoc();
                            $purok_leaderboard[$key]['total_requests'] = $purok_request_data['total_requests'] ?? 0;
                        } else {
                            $purok_leaderboard[$key]['total_requests'] = 0;
                        }
                        $purok_request_stmt->close();
                    } else {
                        $purok_leaderboard[$key]['total_requests'] = 0;
                    }
                }
            } else {
                error_log("Error executing purok leaderboard query: " . $stmt->error);
            }
            $stmt->close();
        } else {
            error_log("Error preparing purok leaderboard query");
        }
        
        // Get community stats
        $stats_query = "
            SELECT 
                COALESCE(SUM(CASE WHEN pl.transaction_type = 'Earned' THEN pl.points ELSE 0 END), 0) as total_points,
                COUNT(DISTINCT u.user_id) as active_recyclers
            FROM users u
            LEFT JOIN pointsledger pl ON u.user_id = pl.user_id AND pl.created_at >= ?
            WHERE u.role = 'resident'
        ";
        
        $community_stats = ['total_points' => 0, 'active_recyclers' => 0, 'total_weight' => 0];
        $stmt = $this->legacy()->prepare($stats_query);
        if ($stmt) {
            $stmt->bind_param("s", $start_date);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $fetched_stats = $result->fetch_assoc();
                if ($fetched_stats) {
                    $community_stats['total_points'] = $fetched_stats['total_points'];
                    $community_stats['active_recyclers'] = $fetched_stats['active_recyclers'];
                }
                
                // Get total weight separately to avoid Cartesian product
                $weight_query = "
                    SELECT 
                        COALESCE(SUM(pr.weight_kg), 0) + COALESCE(SUM(dd.weight_kg), 0) as total_weight
                    FROM users u
                    LEFT JOIN pickuprequests pr ON u.user_id = pr.resident_id AND pr.created_at >= ? AND pr.status = 'Completed'
                    LEFT JOIN direct_dropoffs dd ON u.user_id = dd.resident_id AND dd.created_at >= ? AND dd.status = 'Completed'
                    WHERE u.role = 'resident'
                ";
                $weight_stmt = $this->legacy()->prepare($weight_query);
                if ($weight_stmt) {
                    $weight_stmt->bind_param("ss", $start_date, $start_date);
                    if ($weight_stmt->execute()) {
                        $weight_result = $weight_stmt->get_result();
                        $weight_data = $weight_result->fetch_assoc();
                        $community_stats['total_weight'] = $weight_data['total_weight'] ?? 0;
                    }
                    $weight_stmt->close();
                }
            } else {
                error_log("Error executing community stats query: " . $stmt->error);
            }
            $stmt->close();
        } else {
            error_log("Error preparing community stats query");
        }
        
        $data = get_defined_vars();

        $this->view('resident.leaderboard', $data);
    }
}