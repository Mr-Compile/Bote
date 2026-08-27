<?php

declare(strict_types=1);

namespace App\Http\Controllers\Worker\Api;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class CompletePickupController extends BaseController
{
    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return ['worker'];
    }

    private function redirectWith(string $type, string $message): never
    {
        $this->redirect('worker_dashboard.php?' . $type . '=' . urlencode($message));
    }

    public function render(): void
    {
        $worker_id = (int) $_SESSION['user_id'];
        $error = '';
        $success = '';
        

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $request_id = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
            $actual_weight = isset($_POST['actual_weight']) && $_POST['actual_weight'] !== '' ? (float) $_POST['actual_weight'] : null;
            $actual_quantity = isset($_POST['actual_quantity']) && $_POST['actual_quantity'] !== '' ? (int) $_POST['actual_quantity'] : null;
            $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
        
            try {
                $pickup = $this->container->pickupRepository->findById($request_id);
        
                if ($pickup === null) {
                    throw new \DomainException('Pickup request not found');
                }
        
                if ($pickup->assignedWorkerId !== $worker_id) {
                    throw new \DomainException('Pickup is not assigned to you');
                }
        
                $effective_weight = $actual_weight ?? $pickup->weightKg;
                $effective_quantity = $actual_quantity ?? $pickup->quantityPcs;
        
                $completed = $this->container->pickupService->complete(
                    requestId: $request_id,
                    workerId: $worker_id,
                    actualWeight: $effective_weight,
                    actualQuantity: $effective_quantity,
                    description: $notes,
                );
        
                $actual_amount = $completed->recyclableType->unit === \App\Enums\Unit::Pcs
                    ? $completed->quantityPcs
                    : $completed->actualWeight;
                $unit_text = $completed->recyclableType->unit === \App\Enums\Unit::Pcs ? 'pieces' : 'kg';
        
                $success = "Pickup completed! Collected {$actual_amount} {$unit_text} of {$completed->recyclableType->name}. Resident earned {$completed->pointsEarned} points.";
                $this->redirectWith('success', $success);
            } catch (\Throwable $e) {
                error_log("complete_pickup error: " . $e->getMessage());
                $error = $e->getMessage();
            }
        }
        
        $request_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $pickup = null;
        $resident = null;
        
        if ($request_id > 0) {
            $pickup = $this->container->pickupRepository->findById($request_id);
        
            if ($pickup !== null) {
                if ($pickup->assignedWorkerId !== $worker_id || $pickup->status !== \App\Enums\PickupStatus::Scheduled) {
                    $pickup = null;
                } else {
                    $stmt = $this->legacy()->prepare("SELECT first_name, last_name, address, phone FROM users WHERE user_id = ?");
                    $resident_id = $pickup->residentId;
                    $stmt->bind_param("i", $resident_id);
                    $stmt->execute();
                    $resident = $stmt->get_result()->fetch_assoc();
                }
            }
        }
        
        if ($pickup === null) {
            $this->redirectWith('error', 'Pickup request not found or cannot be completed');
        }
        
        $is_piece_based = $pickup->recyclableType->unit === \App\Enums\Unit::Pcs;
    }
}