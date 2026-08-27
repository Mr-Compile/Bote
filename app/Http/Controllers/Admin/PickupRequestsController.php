<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class PickupRequestsController extends BaseController
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
        $this->requireInclude('sms_notifications.php');
        
        // Handle pickup request operations
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                \App\Core\Csrf::validate($_POST['csrf_token'] ?? null);
            } catch (\InvalidArgumentException $e) {
                $error_message = 'Invalid or expired session token.';
                goto skip_post_processing;
            }
        
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'assign_worker':
                        $request_id = (int)$_POST['request_id'];
                        $worker_id = (int)$_POST['worker_id'];
                        
                        $stmt = $this->legacy()->prepare("UPDATE pickuprequests SET assigned_worker_id = ?, status = 'Scheduled' WHERE request_id = ?");
                        $stmt->bind_param("ii", $worker_id, $request_id);
                        if ($stmt->execute()) {
                            // Send SMS notification to resident about assignment
                            sendPickupAssignedSMS($request_id);
                            $success_message = "Worker assigned successfully!";
                        } else {
                            $error_message = "Failed to assign worker.";
                        }
                        break;
                        
                    case 'update_status':
                        $request_id = (int)$_POST['request_id'];
                        $new_status = $_POST['new_status'];
                        // Whitelist to match DB enum values exactly
                        $allowed_statuses = ['Pending', 'Scheduled', 'In Progress', 'Completed', 'Cancelled'];
                        if (!in_array($new_status, $allowed_statuses, true)) {
                            $error_message = "Invalid status value.";
                            break;
                        }
        
                        // Transaction to ensure awarding + status change are atomic for Completed
                        $this->legacy()->beginTransaction();
                        try {
                            $stmt = $this->legacy()->prepare("UPDATE pickuprequests SET status = ? WHERE request_id = ?");
                            $stmt->bind_param("si", $new_status, $request_id);
                            $stmt->execute();
        
                            if ($new_status === 'Completed') {
                                // Get resident, weight, and rate
                                $stmt = $this->legacy()->prepare("
                                    SELECT pr.resident_id, pr.actual_weight, pr.weight_kg, pr.points_earned, rt.points_per_kg
                                    FROM pickuprequests pr
                                    JOIN recyclabletypes rt ON pr.type_id = rt.type_id
                                    WHERE pr.request_id = ?
                                ");
                                $stmt->bind_param("i", $request_id);
                                $stmt->execute();
                                $row = $stmt->get_result()->fetch_assoc();
        
                                if ($row) {
                                    $weight = (isset($row['actual_weight']) && $row['actual_weight'] > 0) ? (float)$row['actual_weight'] : (float)$row['weight_kg'];
                                    $computed_points = (int) round($weight * (float)$row['points_per_kg']);
        
                                    // If not yet stored on request, update it
                                    if (empty($row['points_earned']) || (int)$row['points_earned'] === 0) {
                                        $stmt = $this->legacy()->prepare("UPDATE pickuprequests SET points_earned = ?, actual_weight = COALESCE(actual_weight, ?) WHERE request_id = ?");
                                        $stmt->bind_param("dii", $computed_points, $weight, $request_id);
                                        $stmt->execute();
                                    } else {
                                        $computed_points = (int)$row['points_earned'];
                                    }
        
                                    // Insert into points ledger if not exists for this request
                                    $check = $this->legacy()->prepare("SELECT 1 FROM pointsledger WHERE request_id = ? AND user_id = ? AND transaction_type = 'Earned' LIMIT 1");
                                    $check->bind_param("ii", $request_id, $row['resident_id']);
                                    $check->execute();
                                    $exists = $check->get_result()->fetch_assoc();
        
                                    if (!$exists) {
                                        $insert = $this->legacy()->prepare("INSERT INTO pointsledger (user_id, request_id, points, transaction_type) VALUES (?, ?, ?, 'Earned')");
                                        $insert->bind_param("iii", $row['resident_id'], $request_id, $computed_points);
                                        $insert->execute();
                                    }
                                }
        
                                $this->legacy()->commit();
                                sendPickupCompletedSMS($request_id);
                            } else {
                                $this->legacy()->commit();
                                sendPickupStatusUpdateSMS($request_id, $new_status);
                            }
        
                            $success_message = "Status updated successfully!";
                        } catch (\Exception $e) {
                            $this->legacy()->rollback();
                            $error_message = "Failed to update status.";
                        }
                        break;
                        
                    case 'complete_request':
                        $request_id = (int) $_POST['request_id'];
                        $actual_weight = (float) $_POST['actual_weight'];
        
                        try {
                            $completed = $this->container->pickupService->completeAsAdmin(
                                requestId: $request_id,
                                actualWeight: $actual_weight,
                                actualQuantity: null,
                                description: 'Pickup completed - ' . ($this->container->pickupRepository->findById($request_id)?->recyclableType->name ?? ''),
                            );
        
                            // Send SMS notification to resident about completion
                            sendPickupCompletedSMS($completed->requestId);
        
                            $success_message = "Request completed successfully! " . $completed->pointsEarned . " points awarded.";
                        } catch (\Exception $e) {
                            $error_message = "Failed to complete request: " . $e->getMessage();
                        }
                        break;
                }
            }
        }
        skip_post_processing:
        $stmt = $this->legacy()->prepare("
            SELECT pr.*, 
                   CONCAT(r.first_name, ' ', r.last_name) as resident_name,
                   r.phone as resident_phone,
                   CONCAT(w.first_name, ' ', w.last_name) as worker_name,
                   rt.name as type_name, rt.points_per_kg
            FROM pickuprequests pr
            JOIN users r ON pr.resident_id = r.user_id
            LEFT JOIN users w ON pr.assigned_worker_id = w.user_id
            JOIN recyclabletypes rt ON pr.type_id = rt.type_id
            ORDER BY pr.created_at DESC
        ");
        $stmt->execute();
        $pickup_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get available workers
        $stmt = $this->legacy()->prepare("SELECT user_id, first_name, last_name FROM users WHERE role = 'worker' ORDER BY first_name, last_name");
        $stmt->execute();
        $workers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get pickup request statistics
        $pickup_stats = [];
        
        // Get counts for each status
        $result = $this->legacy()->query("SELECT status, COUNT(*) as count FROM pickuprequests GROUP BY status");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $pickup_stats[$row['status']] = $row['count'];
            }
        }
        
        // Ensure all expected statuses are present, defaulting to 0 if not found
        $statuses = ['Pending', 'Scheduled', 'In Progress', 'Completed', 'Cancelled'];
        foreach ($statuses as $status) {
            if (!isset($pickup_stats[$status])) {
                $pickup_stats[$status] = 0;
            }
        }

        $data = get_defined_vars();

        $this->view('admin.pickup_requests', $data);
    }
}