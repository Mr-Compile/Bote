<?php

declare(strict_types=1);

namespace App\Http\Controllers\Resident;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class RewardsController extends BaseController
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
        
        // Get user's current points
        $stmt = $this->legacy()->prepare("SELECT COALESCE(SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE -points END), 0) as total_points FROM pointsledger WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_points = $stmt->get_result()->fetch_assoc()['total_points'];
        
        // Fetch available rewards
        $stmt = $this->legacy()->prepare("SELECT * FROM rewards WHERE stock > 0 ORDER BY points_required ASC");
        $stmt->execute();
        $rewards = $stmt->get_result();
        
        // Fetch user's redemption history
        $stmt = $this->legacy()->prepare("
            SELECT rr.redemption_id, rr.quantity, rr.status, rr.created_at,
                   r.name as reward_name, r.points_required
            FROM rewardredemptions rr
            JOIN rewards r ON rr.reward_id = r.reward_id
            WHERE rr.user_id = ?
            ORDER BY rr.created_at DESC
            LIMIT 10
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $redemptions = $stmt->get_result();

        $data = get_defined_vars();

        $this->view('resident.rewards', $data);
    }
}