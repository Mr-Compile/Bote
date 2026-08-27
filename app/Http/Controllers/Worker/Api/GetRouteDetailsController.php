<?php

declare(strict_types=1);

namespace App\Http\Controllers\Worker\Api;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class GetRouteDetailsController extends BaseController
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
        if (!isset($_GET['route_id'])) {
        	echo json_encode(['success' => false, 'message' => 'Missing route_id']);
        	
        }
        
        $route_id = (int) $_GET['route_id'];
        
        // Fetch route meta
        $stmt = $this->legacy()->prepare("SELECT route_id, worker_id, generated_on FROM collectionroutes WHERE route_id = ?");
        $stmt->bind_param('i', $route_id);
        $stmt->execute();
        $route = $stmt->get_result()->fetch_assoc();
        
        if (!$route) {
        	echo json_encode(['success' => false, 'message' => 'Route not found']);
        	
        }
        
        // Fetch ordered stops with optional pickup details and collection points
        $stmt = $this->legacy()->prepare("
        	SELECT rs.stop_id, rs.stop_order, rs.latitude, rs.longitude,
        	       pr.request_id, pr.status,
        	       u.first_name, u.last_name, u.address, u.phone,
        	       rt.name AS material_type,
        	       cp.name AS collection_point_name, cp.description AS collection_description,
        	       rs.point_id
        	FROM routestops rs
        	LEFT JOIN pickuprequests pr ON rs.request_id = pr.request_id
        	LEFT JOIN users u ON pr.resident_id = u.user_id
        	LEFT JOIN recyclabletypes rt ON pr.type_id = rt.type_id
        	LEFT JOIN collection_points cp ON rs.point_id = cp.point_id
        	WHERE rs.route_id = ?
        	ORDER BY rs.stop_order ASC
        ");
        $stmt->bind_param('i', $route_id);
        $stmt->execute();
        $stops = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        ob_start();
    }
}