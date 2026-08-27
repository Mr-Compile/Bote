<?php

declare(strict_types=1);

namespace App\Http\Controllers\Worker;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class VerificationController extends BaseController
{
    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return ['worker', 'admin'];
    }

    public function render(): void
    {
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role'];
        
        // Handle verification action
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            try {
                \App\Core\Csrf::validate($_POST['csrf_token'] ?? null);
            } catch (\InvalidArgumentException $e) {
                $this->redirect('worker_verification.php?error=' . urlencode('Invalid or expired session token.'));
            }
        
            $dropoff_id = (int) $_POST['dropoff_id'];
            $action = $_POST['action'];
        
            try {
                if ($action === 'verify') {
                    $verification_notes = $_POST['verification_notes'] ?? '';
        
                    $this->container->dropoffService->verify(
                        dropoffId: $dropoff_id,
                        staffId: (int) $user_id,
                        notes: $verification_notes,
                    );
        
                    // Send SMS notification for drop-off verification
        $this->requireInclude('sms_notifications.php');
                    sendDropoffVerificationSMS($dropoff_id);
        
                    $message = 'Drop-off verified and points credited successfully!';
                } elseif ($action === 'reject') {
                    $rejection_reason = $_POST['rejection_reason'] ?? '';
        
                    $this->container->dropoffService->reject(
                        dropoffId: $dropoff_id,
                        staffId: (int) $user_id,
                        reason: $rejection_reason,
                    );
        
                    // Send SMS notification for drop-off rejection
        $this->requireInclude('sms_notifications.php');
                    sendDropoffRejectionSMS($dropoff_id);
        
                    $message = 'Drop-off rejected successfully!';
                } else {
                    $message = 'Unknown action.';
                    $this->redirect('worker_verification.php?error=' . urlencode($message));
                }

                $this->redirect('worker_verification.php?success=' . urlencode($message));
            } catch (\Exception $e) {
                error_log('Error in worker verification: ' . $e->getMessage());
                $this->redirect('worker_verification.php?error=' . urlencode($e->getMessage()));
            }
        }
        
        // Get pending drop-offs for verification
        $stmt = $this->legacy()->prepare("
            SELECT dd.*, rt.name as recyclable_type, rt.unit, rt.points_per_kg, rt.points_per_unit,
                   cp.name as collection_point, cp.description as point_description,
                   CONCAT(u.first_name, ' ', u.last_name) as resident_name, u.phone
            FROM direct_dropoffs dd
            JOIN recyclabletypes rt ON dd.type_id = rt.type_id
            JOIN collection_points cp ON dd.point_id = cp.point_id
            JOIN users u ON dd.resident_id = u.user_id
            WHERE dd.status = 'Pending'
            ORDER BY dd.created_at ASC
        ");
        $stmt->execute();
        $pending_dropoffs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get recent verified drop-offs
        $stmt = $this->legacy()->prepare("
            SELECT dd.*, rt.name as recyclable_type, rt.unit,
                   cp.name as collection_point,
                   CONCAT(u.first_name, ' ', u.last_name) as resident_name,
                   CONCAT(verifier.first_name, ' ', verifier.last_name) as verified_by_name
            FROM direct_dropoffs dd
            JOIN recyclabletypes rt ON dd.type_id = rt.type_id
            JOIN collection_points cp ON dd.point_id = cp.point_id
            JOIN users u ON dd.resident_id = u.user_id
            LEFT JOIN users verifier ON dd.verified_by = verifier.user_id
            WHERE dd.status IN ('Verified', 'Completed')
            ORDER BY dd.verified_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $recent_verified = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $data = get_defined_vars();

        $this->view('worker.verification', $data);
    }
}