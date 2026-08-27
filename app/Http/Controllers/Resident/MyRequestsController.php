<?php

declare(strict_types=1);

namespace App\Http\Controllers\Resident;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class MyRequestsController extends BaseController
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
        
        // Fetch user requests with recyclable type information
        $stmt = $this->legacy()->prepare("
            SELECT pr.request_id, pr.weight_kg, pr.quantity_pcs, pr.preferred_date, pr.status, pr.created_at, pr.points_earned,
                   rt.name as recyclable_type, rt.points_per_kg, rt.points_per_unit, rt.unit, rt.image,
                   CONCAT(u.first_name, ' ', u.last_name) as worker_name
            FROM pickuprequests pr
            JOIN recyclabletypes rt ON pr.type_id = rt.type_id
            LEFT JOIN users u ON pr.assigned_worker_id = u.user_id
            WHERE pr.resident_id = ?
            ORDER BY pr.created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $requests = $stmt->get_result();
        $requests_data = $requests->fetch_all(MYSQLI_ASSOC);
        
        $request_locations = array_filter(array_map(function ($request) {
            if ($request['latitude'] && $request['longitude']) {
                return [
                    'latitude' => (float) $request['latitude'],
                    'longitude' => (float) $request['longitude'],
                    'status' => $request['status'],
                ];
            }
            return null;
        }, $requests_data));
        
        $my_requests_data = [
            'csrfToken' => \App\Core\Csrf::token(),
            'requestLocations' => array_values($request_locations),
        ];
        $json_my_requests_data = json_encode($my_requests_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $data = get_defined_vars();

        $this->view('resident.my_requests', $data);
    }
}