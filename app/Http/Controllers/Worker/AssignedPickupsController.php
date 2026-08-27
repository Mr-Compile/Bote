<?php

declare(strict_types=1);

namespace App\Http\Controllers\Worker;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class AssignedPickupsController extends BaseController
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
                $conn = $this->container->database->connection();
        $this->requireInclude('sms_notifications.php');
        $this->requireInclude('auto_route_functions.php');
        
        $worker_id = $_SESSION['user_id'];
        
        // Handle pickup operations
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            try {
                \App\Core\Csrf::validate($_POST['csrf_token'] ?? null);
            } catch (\InvalidArgumentException $e) {
                $error_message = 'Invalid or expired session token.';
                return;
            }
        
            if ($_POST['action'] === 'accept_pickup') {
                $request_id = (int) $_POST['request_id'];
        
                try {
                    $this->container->pickupRequestService->assignToWorker(
                        requestId: $request_id,
                        workerId: (int) $worker_id,
                    );
        
                    // Auto-generate route if this is the worker's first pickup of the day
                    $check_route_stmt = $this->legacy()->prepare("
                        SELECT COUNT(*) as route_count
                        FROM collectionroutes cr
                        WHERE cr.worker_id = ? AND DATE(cr.generated_on) = CURDATE()
                    ");
                    $check_route_stmt->bind_param("i", $worker_id);
                    $check_route_stmt->execute();
                    $route_count = $check_route_stmt->get_result()->fetch_assoc()['route_count'];
        
                    // If no route exists for today, auto-generate one
                    if ($route_count == 0) {
                        // Default starting point (can be customized as needed)
                        $starting_point = ['lat' => 6.4214082, 'lng' => 124.7811843];
                        generateAutoRoute($conn, $worker_id, $starting_point);
                        $success_message = 'Pickup request accepted successfully! Auto route generated - check Collection Routes tab.';
                    } else {
                        $success_message = 'Pickup request accepted successfully!';
                    }
        
                    // Send SMS notification to resident about assignment
                    sendPickupAssignedSMS($request_id);
        
                } catch (\Exception $e) {
                    $error_message = 'Failed to accept pickup: ' . $e->getMessage();
                }
            }
            elseif ($_POST['action'] === 'complete_pickup') {
                $request_id = (int) $_POST['request_id'];
                $actual_weight = isset($_POST['actual_weight']) ? (float) $_POST['actual_weight'] : null;
                $actual_quantity = isset($_POST['actual_quantity']) ? (int) $_POST['actual_quantity'] : null;
        
                try {
                    $pickup = $this->container->pickupRepository->findById($request_id);
        
                    if ($pickup === null) {
                        throw new \Exception('Pickup request not found');
                    }
        
                    $effectiveWeight = $actual_weight ?? $pickup->weightKg;
                    $effectiveQuantity = $actual_quantity ?? $pickup->quantityPcs;
        
                    $completed = $this->container->pickupService->complete(
                        requestId: $request_id,
                        workerId: (int) $worker_id,
                        actualWeight: $effectiveWeight,
                        actualQuantity: $effectiveQuantity,
                        description: null,
                    );
        
                    // Send SMS notification to resident about completion
                    sendPickupCompletedSMS($completed->requestId);
        
                    // Send SMS notification to admin about completion
                    sendAdminPickupCompletionSMS($completed->requestId);
        
                    $actual_amount = $completed->recyclableType->unit === \App\Enums\Unit::Pcs
                        ? $completed->quantityPcs
                        : $completed->actualWeight;
                    $unit_text = $completed->recyclableType->unit === \App\Enums\Unit::Pcs ? 'pieces' : 'kg';
        
                    $success_message = "Pickup completed successfully! Collected {$actual_amount} {$unit_text} of {$completed->recyclableType->name}. Resident earned {$completed->pointsEarned} points.";
                } catch (\Exception $e) {
                    $error_message = "Failed to complete pickup: " . $e->getMessage();
                }
            }
        }
        
        // Get available pickup requests (pending, not assigned to anyone)
        $stmt = $this->legacy()->prepare("
            SELECT pr.*, u.first_name, u.last_name, u.phone, u.address, rt.name as material_type, rt.points_per_kg, rt.points_per_unit, rt.unit
            FROM pickuprequests pr 
            JOIN users u ON pr.resident_id = u.user_id 
            JOIN recyclabletypes rt ON pr.type_id = rt.type_id 
            WHERE pr.status = 'Pending' AND pr.assigned_worker_id IS NULL
            ORDER BY pr.preferred_date ASC
        ");
        $stmt->execute();
        $available_pickups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get assigned pickups for this worker
        $stmt = $this->legacy()->prepare("
            SELECT pr.*, u.first_name, u.last_name, u.phone, u.address, rt.name as material_type, rt.points_per_kg, rt.points_per_unit, rt.unit
            FROM pickuprequests pr 
            JOIN users u ON pr.resident_id = u.user_id 
            JOIN recyclabletypes rt ON pr.type_id = rt.type_id 
            WHERE pr.assigned_worker_id = ? AND pr.status = 'Scheduled'
            ORDER BY pr.preferred_date ASC
        ");
        $stmt->bind_param("i", $worker_id);
        $stmt->execute();
        $assigned_pickups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get statistics
        $total_available = count($available_pickups);
        $total_assigned = count($assigned_pickups);
        $today_count = count(array_filter($assigned_pickups, function($pickup) { 
            return date('Y-m-d', strtotime($pickup['preferred_date'])) === date('Y-m-d'); 
        }));
        $pending_count = count(array_filter($assigned_pickups, function($pickup) { return $pickup['status'] === 'Scheduled'; }));

        $data = get_defined_vars();

        $this->view('worker.assigned_pickups', $data);
    }
}