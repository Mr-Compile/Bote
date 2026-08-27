<?php

declare(strict_types=1);

namespace App\Http\Controllers\Resident;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class NewRequestController extends BaseController
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
        
        // Get recyclable types with images
        $stmt = $this->legacy()->prepare("SELECT * FROM recyclabletypes ORDER BY name ASC");
        $stmt->execute();
        $recyclable_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get collection points with coordinates
        $stmt = $this->legacy()->prepare("SELECT point_id, name, description, latitude, longitude FROM collection_points ORDER BY name ASC");
        $stmt->execute();
        $collection_points = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $new_request_data = array_map(function ($point) {
            return [
                'pointId' => (int) $point['point_id'],
                'name' => $point['name'],
                'lat' => (float) $point['latitude'],
                'lng' => (float) $point['longitude'],
                'description' => $point['description'] ?? '',
            ];
        }, $collection_points);
        $json_new_request_data = json_encode(['points' => $new_request_data], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        
        // Get user coordinates for fallback
        $stmt = $this->legacy()->prepare("SELECT latitude, longitude FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_coords = $stmt->get_result()->fetch_assoc();
        
        // Get user statistics
        $stmt = $this->legacy()->prepare("SELECT COUNT(*) as total_requests FROM pickuprequests WHERE resident_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $total_requests = $stmt->get_result()->fetch_assoc()['total_requests'];
        
        $stmt = $this->legacy()->prepare("SELECT COALESCE(SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE -points END), 0) as total_points FROM pointsledger WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $total_points = $stmt->get_result()->fetch_assoc()['total_points'];

        $data = get_defined_vars();

        $this->view('resident.new_request', $data);
    }
}