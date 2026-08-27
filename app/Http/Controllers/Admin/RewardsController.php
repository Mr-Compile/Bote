<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class RewardsController extends BaseController
{
    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return ['admin'];
    }

    public function render(): void
    {
                $conn = $this->container->database->connection();
        $this->requireInclude('upload_handler.php');
        
        // Initialize upload handler
        $uploadHandler = new \BoteBankUploadHandler();
        
        // Handle reward operations with enhanced error handling
        $success_message = '';
        $error_message = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action'])) {
                try {
                    // Validate database connection
                    if (!$conn || !$this->checkDbHealth()) {
                        throw new \Exception("Database connection error. Please try again.");
                    }
        
                    switch ($_POST['action']) {
                        case 'add_reward':
                            $name = trim($_POST['name']);
                            $points_required = (int)$_POST['points_required'];
                            $stock = (int)$_POST['stock'];
                            $description = trim($_POST['description'] ?? '');
                            $image_path = null;
                            
                            // Validation
                            if (empty($name)) {
                                throw new \Exception("Reward name is required.");
                            }
                            if ($points_required <= 0) {
                                throw new \Exception("Points required must be greater than 0.");
                            }
                            if ($stock < 0) {
                                throw new \Exception("Stock cannot be negative.");
                            }
                            
                            // Handle image upload if provided
                            if (isset($_FILES['reward_image']) && $_FILES['reward_image']['error'] === UPLOAD_ERR_OK) {
                                // Create rewards directory if it doesn't exist
                                $rewardsDir = '../uploads/rewards/';
                                if (!is_dir($rewardsDir)) {
                                    mkdir($rewardsDir, 0755, true);
                                }
                                
                                $uploadResult = $uploadHandler->uploadFile($_FILES['reward_image'], 'rewards', 'reward_' . time());
                                if ($uploadResult['success']) {
                                    $image_path = $uploadResult['path'];
                                } else {
                                    throw new \Exception("Image upload failed: " . $uploadResult['error']);
                                }
                            }
                            
                            // Check for duplicate name
                            $check_stmt = $this->legacy()->prepare("SELECT reward_id FROM rewards WHERE name = ?");
                            if (!$check_stmt) {
                                throw new \Exception("Database error occurred.");
                            }
                            $check_stmt->bind_param("s", $name);
                            $check_stmt->execute();
                            if ($check_stmt->get_result()->num_rows > 0) {
                                throw new \Exception("A reward with this name already exists.");
                            }
                            
                            $stmt = $this->legacy()->prepare("INSERT INTO rewards (name, points_required, stock, description, image) VALUES (?, ?, ?, ?, ?)");
                            if (!$stmt) {
                                throw new \Exception("Database error occurred.");
                            }
                            $stmt->bind_param("siiss", $name, $points_required, $stock, $description, $image_path);
                            if ($stmt->execute()) {
                                $success_message = "Reward added successfully!";
                                error_log("Admin {$_SESSION['user_id']} added new reward: {$name}");
                            } else {
                                throw new \Exception("Failed to add reward to database.");
                            }
                                break;
                            
                        case 'update_reward':
                            $reward_id = (int)$_POST['reward_id'];
                            $name = trim($_POST['name']);
                            $points_required = (int)$_POST['points_required'];
                            $stock = (int)$_POST['stock'];
                            $description = trim($_POST['description'] ?? '');
                            
                            // Validation
                            if ($reward_id <= 0) {
                                throw new \Exception("Invalid reward ID.");
                            }
                            if (empty($name)) {
                                throw new \Exception("Reward name is required.");
                            }
                            if ($points_required <= 0) {
                                throw new \Exception("Points required must be greater than 0.");
                            }
                            if ($stock < 0) {
                                throw new \Exception("Stock cannot be negative.");
                            }
                            
                            // Get current reward data
                            $stmt = $this->legacy()->prepare("SELECT image FROM rewards WHERE reward_id = ?");
                            if (!$stmt) {
                                throw new \Exception("Database error occurred.");
                            }
                            $stmt->bind_param("i", $reward_id);
                            $stmt->execute();
                            $current_reward = $stmt->get_result()->fetch_assoc();
                            
                            if (!$current_reward) {
                                throw new \Exception("Reward not found.");
                            }
                            
                            $image_path = $current_reward['image']; // Keep existing image by default
                            
                            // Handle image upload if provided
                            if (isset($_FILES['reward_image']) && $_FILES['reward_image']['error'] === UPLOAD_ERR_OK) {
                                // Delete old image if it exists
                                if (!empty($current_reward['image'])) {
                                    $oldImagePath = '../uploads/' . $current_reward['image'];
                                    if (file_exists($oldImagePath)) {
                                        unlink($oldImagePath);
                                    }
                                }
                                
                                // Upload new image
                                $uploadResult = $uploadHandler->uploadFile($_FILES['reward_image'], 'rewards', 'reward_' . time());
                                if ($uploadResult['success']) {
                                    $image_path = $uploadResult['path'];
                                } else {
                                    throw new \Exception("Image upload failed: " . $uploadResult['error']);
                                }
                            }
                            
                            // Check if reward exists and name is not duplicate (except current)
                            $check_stmt = $this->legacy()->prepare("SELECT reward_id FROM rewards WHERE name = ? AND reward_id != ?");
                            if (!$check_stmt) {
                                throw new \Exception("Database error occurred.");
                            }
                            $check_stmt->bind_param("si", $name, $reward_id);
                            $check_stmt->execute();
                            if ($check_stmt->get_result()->num_rows > 0) {
                                throw new \Exception("A reward with this name already exists.");
                            }
                            
                            $stmt = $this->legacy()->prepare("UPDATE rewards SET name = ?, points_required = ?, stock = ?, description = ?, image = ?, updated_at = CURRENT_TIMESTAMP WHERE reward_id = ?");
                            if (!$stmt) {
                                throw new \Exception("Database error occurred.");
                            }
                            $stmt->bind_param("siissi", $name, $points_required, $stock, $description, $image_path, $reward_id);
                            if ($stmt->execute() && $stmt->affected_rows > 0) {
                                $success_message = "Reward updated successfully!";
                                error_log("Admin {$_SESSION['user_id']} updated reward: {$reward_id}");
                            } else {
                                throw new \Exception("Failed to update reward or no changes made.");
                            }
                            break;
                            
                        case 'delete_reward':
                            $reward_id = (int)$_POST['reward_id'];
                            
                            if ($reward_id <= 0) {
                                throw new \Exception("Invalid reward ID.");
                            }
                            
                            // Get reward data to check for image
                            $stmt = $this->legacy()->prepare("SELECT image FROM rewards WHERE reward_id = ?");
                            if (!$stmt) {
                                throw new \Exception("Database error occurred.");
                            }
                            $stmt->bind_param("i", $reward_id);
                            $stmt->execute();
                            $reward_data = $stmt->get_result()->fetch_assoc();
                            
                            if (!$reward_data) {
                                throw new \Exception("Reward not found.");
                            }
                            
                            // Check if reward has pending or approved redemptions
                            $check_stmt = $this->legacy()->prepare("SELECT COUNT(*) as count FROM rewardredemptions WHERE reward_id = ? AND status IN ('Pending', 'Approved')");
                            if (!$check_stmt) {
                                throw new \Exception("Database error occurred.");
                            }
                            $check_stmt->bind_param("i", $reward_id);
                            $check_stmt->execute();
                            $redemption_count = $check_stmt->get_result()->fetch_assoc()['count'];
                            
                            if ($redemption_count > 0) {
                                throw new \Exception("Cannot delete reward. There are active redemptions for this reward.");
                            }
                            
                            $stmt = $this->legacy()->prepare("DELETE FROM rewards WHERE reward_id = ?");
                            if (!$stmt) {
                                throw new \Exception("Database error occurred.");
                            }
                            $stmt->bind_param("i", $reward_id);
                            if ($stmt->execute() && $stmt->affected_rows > 0) {
                                // Delete associated image file if it exists
                                if (!empty($reward_data['image'])) {
                                    $imagePath = '../uploads/' . $reward_data['image'];
                                    if (file_exists($imagePath)) {
                                        unlink($imagePath);
                                    }
                                }
                                
                                $success_message = "Reward deleted successfully!";
                                error_log("Admin {$_SESSION['user_id']} deleted reward: {$reward_id}");
                            } else {
                                throw new \Exception("Failed to delete reward or reward not found.");
                            }
                            break;
                    }
                } catch (\Exception $e) {
                    $error_message = $e->getMessage();
                    error_log("Reward management error: " . $e->getMessage());
                }
            }
        }
        
        // Get all rewards with enhanced error handling
        try {
            $stmt = $this->legacy()->prepare("SELECT r.*, 
                (SELECT COUNT(*) FROM rewardredemptions rr WHERE rr.reward_id = r.reward_id AND rr.status = 'Pending') as pending_redemptions,
                (SELECT COUNT(*) FROM rewardredemptions rr WHERE rr.reward_id = r.reward_id AND rr.status = 'Approved') as total_redemptions
                FROM rewards r ORDER BY r.points_required ASC");
            if (!$stmt) {
                throw new \Exception("Database error while fetching rewards.");
            }
            $stmt->execute();
            $rewards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (\Exception $e) {
            error_log("Error fetching rewards: " . $e->getMessage());
            $rewards = [];
            if (empty($error_message)) {
                $error_message = "Unable to load rewards data. Please refresh the page.";
            }
        }
        
        // Get reward statistics with enhanced calculations
        try {
            $total_rewards = count($rewards);
            $total_stock = array_sum(array_column($rewards, 'stock'));
            $avg_points = $total_rewards > 0 ? round(array_sum(array_column($rewards, 'points_required')) / $total_rewards) : 0;
            
            // Get low stock count (stock <= 5)
            $low_stock_count = count(array_filter($rewards, function($reward) {
                return $reward['stock'] <= 5 && $reward['stock'] > 0;
            }));
            
            // Get out of stock count
            $out_of_stock_count = count(array_filter($rewards, function($reward) {
                return $reward['stock'] == 0;
            }));
        } catch (\Exception $e) {
            error_log("Error calculating statistics: " . $e->getMessage());
            $total_rewards = $total_stock = $avg_points = $low_stock_count = $out_of_stock_count = 0;
        }
        
        // Get recent redemptions with enhanced error handling
        try {
            $stmt = $this->legacy()->prepare("
                SELECT rr.*, r.name as reward_name, r.points_required, u.first_name, u.last_name 
                FROM rewardredemptions rr 
                JOIN rewards r ON rr.reward_id = r.reward_id 
                JOIN users u ON rr.user_id = u.user_id 
                ORDER BY rr.created_at DESC 
                LIMIT 5
            ");
            if (!$stmt) {
                throw new \Exception("Database error while fetching redemptions.");
            }
            $stmt->execute();
            $recent_redemptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (\Exception $e) {
            error_log("Error fetching recent redemptions: " . $e->getMessage());
            $recent_redemptions = [];
        }

        $data = get_defined_vars();

        $this->view('admin.rewards', $data);
    }
}