<?php

declare(strict_types=1);

namespace App\Http\Controllers\Resident;

use App\Core\Csrf;
use App\Http\Controllers\BaseController;

final class RedeemRewardController extends BaseController
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $this->json(['success' => false, 'message' => 'Invalid JSON body'], 400);
        }

        try {
            Csrf::validate($input['csrf_token'] ?? null);
        } catch (\InvalidArgumentException $e) {
            $this->json(['success' => false, 'message' => 'Invalid or expired session token.'], 403);
        }

        $user_id = (int) $_SESSION['user_id'];
        $reward_id = isset($input['reward_id']) ? (int) $input['reward_id'] : 0;
        $quantity = isset($input['quantity']) ? (int) $input['quantity'] : 0;

        if ($reward_id <= 0 || $quantity <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid reward or quantity'], 400);
        }

        try {
            $this->legacy()->beginTransaction();

            // Get reward details and check stock
            $stmt = $this->legacy()->prepare("SELECT * FROM rewards WHERE reward_id = ? FOR UPDATE");
            $stmt->bind_param("i", $reward_id);
            $stmt->execute();
            $reward = $stmt->get_result()->fetch_assoc();

            if (!$reward) {
                throw new \Exception('Reward not found');
            }

            if ($reward['stock'] < $quantity) {
                throw new \Exception('Insufficient stock');
            }

            $total_points_required = $reward['points_required'] * $quantity;

            // Get user's current points
            $stmt = $this->legacy()->prepare("SELECT COALESCE(SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE -points END), 0) as total_points FROM pointsledger WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_points = (int) $stmt->get_result()->fetch_assoc()['total_points'];

            if ($user_points < $total_points_required) {
                throw new \Exception('Insufficient points');
            }

            // Create redemption record
            $stmt = $this->legacy()->prepare("INSERT INTO rewardredemptions (user_id, reward_id, quantity, status) VALUES (?, ?, ?, 'Pending')");
            $stmt->bind_param("iii", $user_id, $reward_id, $quantity);
            $stmt->execute();
            $redemption_id = $this->legacy()->last_insert_id;

            // Deduct points from user
            $stmt = $this->legacy()->prepare("INSERT INTO pointsledger (user_id, points, transaction_type, description) VALUES (?, ?, 'Redeemed', ?)");
            $description = "Redeemed {$quantity}x {$reward['name']}";
            $stmt->bind_param("iis", $user_id, $total_points_required, $description);
            $stmt->execute();

            // Update reward stock
            $new_stock = $reward['stock'] - $quantity;
            $stmt = $this->legacy()->prepare("UPDATE rewards SET stock = ? WHERE reward_id = ?");
            $stmt->bind_param("ii", $new_stock, $reward_id);
            $stmt->execute();

            $this->legacy()->commit();

            $this->json([
                'success' => true,
                'message' => 'Reward redeemed successfully',
                'redemption_id' => $redemption_id,
                'remaining_points' => $user_points - $total_points_required
            ]);
        } catch (\Exception $e) {
            $this->legacy()->rollback();
            error_log("Reward redemption error: " . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
