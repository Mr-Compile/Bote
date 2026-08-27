<?php

declare(strict_types=1);

namespace App\Http\Controllers\Resident;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class SaveRequestController extends BaseController
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
        $this->requireInclude('sms_notifications.php');
        
        try {
            \App\Core\Csrf::validate($_POST['csrf_token'] ?? null);
        
            if (!isset($_POST['type_id'], $_POST['weight_kg'], $_POST['preferred_date'])) {
                throw new \Exception('Missing required fields');
            }
        
            $resident_id = (int) $_SESSION['user_id'];
            $type_id = (int) $_POST['type_id'];
            $weight_kg = $_POST['weight_kg'] !== '' ? (float) $_POST['weight_kg'] : 0.0;
            $quantity_pcs = isset($_POST['quantity_pcs']) && $_POST['quantity_pcs'] !== '' ? (int) $_POST['quantity_pcs'] : null;
            $pickup_type = $_POST['pickup_type'] ?? 'Home';
            $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float) $_POST['latitude'] : null;
            $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;
            $point_id = isset($_POST['point_id']) && $_POST['point_id'] !== '' ? (int) $_POST['point_id'] : null;
            $preferred_date = $_POST['preferred_date'];
        
            if ($pickup_type === 'CollectionPoint' && $point_id !== null) {
                $point = $this->container->database->selectOne(
                    'SELECT latitude, longitude FROM collection_points WHERE point_id = ?',
                    [$point_id],
                    'i',
                );
        
                if ($point === null) {
                    throw new \Exception('Invalid collection point selected');
                }
        
                $latitude = (float) $point['latitude'];
                $longitude = (float) $point['longitude'];
            }
        
            $pickup = $this->container->pickupRequestService->create(
                residentId: $resident_id,
                typeId: $type_id,
                weightKg: $weight_kg,
                quantityPcs: $quantity_pcs,
                preferredDate: $preferred_date,
                pickupType: $pickup_type,
                pointId: $point_id,
                latitude: $latitude,
                longitude: $longitude,
            );
        
            // Send SMS confirmation to resident
            sendPickupConfirmationSMS($pickup->requestId);
        
            header("Location: resident_my_requests.php?msg=" . urlencode("Request submitted successfully! We'll contact you within 24 hours."));
            exit();
        } catch (\Exception $e) {
            error_log("Error in resident_save_request.php: " . $e->getMessage());
            header("Location: resident_new_request.php?error=" . urlencode($e->getMessage()));
            exit();
        }
    }
}