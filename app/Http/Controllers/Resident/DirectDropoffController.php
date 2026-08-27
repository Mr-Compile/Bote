<?php

declare(strict_types=1);

namespace App\Http\Controllers\Resident;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class DirectDropoffController extends BaseController
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
        $user_id = $_SESSION['user_id'];
        
        // Handle direct drop-off submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                \App\Core\Csrf::validate($_POST['csrf_token'] ?? null);
        
                if (!isset($_POST['type_id'], $_POST['point_id'])) {
                    throw new \Exception('Missing required fields');
                }
        
                $dropoff = $this->container->dropoffRequestService->create(
                    residentId: (int) $user_id,
                    pointId: (int) $_POST['point_id'],
                    typeId: (int) $_POST['type_id'],
                    weightKg: isset($_POST['weight_kg']) && $_POST['weight_kg'] !== '' ? (float) $_POST['weight_kg'] : 0.0,
                    quantityPcs: isset($_POST['quantity_pcs']) && $_POST['quantity_pcs'] !== '' ? (int) $_POST['quantity_pcs'] : null,
                );
        
                // Send SMS notification for drop-off submission
        $this->requireInclude('sms_notifications.php');
                sendDropoffSubmissionSMS($dropoff->dropoffId);
        
                $this->redirect('resident_dropoff_success.php?dropoff_id=' . $dropoff->dropoffId);
            } catch (\Exception $e) {
                error_log('Error in resident_direct_dropoff.php: ' . $e->getMessage());
                $this->redirect('resident_direct_dropoff.php?error=' . urlencode($e->getMessage()));
            }
        }
        
        // Get recyclable types with images
        $stmt = $this->legacy()->prepare("SELECT * FROM recyclabletypes ORDER BY name ASC");
        $stmt->execute();
        $recyclable_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get collection points with coordinates
        $stmt = $this->legacy()->prepare("SELECT point_id, name, description, latitude, longitude FROM collection_points ORDER BY name ASC");
        $stmt->execute();
        $collection_points = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get user statistics
        $stmt = $this->legacy()->prepare("SELECT COUNT(*) as total_dropoffs FROM direct_dropoffs WHERE resident_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $total_dropoffs = $stmt->get_result()->fetch_assoc()['total_dropoffs'];
        
        $stmt = $this->legacy()->prepare("SELECT COALESCE(SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE -points END), 0) as total_points FROM pointsledger WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $total_points = $stmt->get_result()->fetch_assoc()['total_points'];

        $data = get_defined_vars();

        $this->view('resident.direct_dropoff', $data);
    }
}