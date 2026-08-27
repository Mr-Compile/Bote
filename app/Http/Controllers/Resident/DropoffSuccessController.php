<?php

declare(strict_types=1);

namespace App\Http\Controllers\Resident;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class DropoffSuccessController extends BaseController
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
        $dropoff_id = isset($_GET['dropoff_id']) ? (int)$_GET['dropoff_id'] : 0;
        
        if (!$dropoff_id) {
            $this->redirect('resident_dashboard.php');exit();
        }
        
        // Get drop-off details
        $stmt = $this->legacy()->prepare("
            SELECT dd.*, rt.name as recyclable_type, rt.unit, rt.points_per_kg, rt.points_per_unit,
                   cp.name as collection_point, cp.description as point_description
            FROM direct_dropoffs dd
            JOIN recyclabletypes rt ON dd.type_id = rt.type_id
            JOIN collection_points cp ON dd.point_id = cp.point_id
            WHERE dd.dropoff_id = ? AND dd.resident_id = ?
        ");
        $stmt->bind_param("ii", $dropoff_id, $user_id);
        $stmt->execute();
        $dropoff = $stmt->get_result()->fetch_assoc();
        
        if (!$dropoff) {
            $this->redirect('resident_dashboard.php');exit();
        }

        $data = get_defined_vars();

        $this->view('resident.dropoff_success', $data);
    }
}