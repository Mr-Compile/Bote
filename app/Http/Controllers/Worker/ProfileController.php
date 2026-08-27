<?php

declare(strict_types=1);

namespace App\Http\Controllers\Worker;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class ProfileController extends BaseController
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
        // Get user data for sidebar
        $user_id = $_SESSION['user_id'];
        
        // Fetch user data
        $stmt = $this->legacy()->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            $errors = [];
            
            // Validate required fields
            if (empty($first_name)) $errors[] = "First name is required";
            if (empty($last_name)) $errors[] = "Last name is required";
            if (empty($email)) $errors[] = "Email is required";
            if (empty($phone)) $errors[] = "Phone is required";
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            }
            
            // Check if email is already taken by another user
            $stmt = $this->legacy()->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = "Email is already taken";
            }
            
            // Password validation if changing password
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $errors[] = "Current password is required to change password";
                } elseif (!password_verify($current_password, $user['password'])) {
                    $errors[] = "Current password is incorrect";
                } elseif (strlen($new_password) < 6) {
                    $errors[] = "New password must be at least 6 characters";
                } elseif ($new_password !== $confirm_password) {
                    $errors[] = "New passwords do not match";
                }
            }
            
            if (empty($errors)) {
                try {
                    if (!empty($new_password)) {
                        // Update with new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $this->legacy()->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, password = ? WHERE user_id = ?");
                        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $hashed_password, $user_id);
                    } else {
                        // Update without changing password
                        $stmt = $this->legacy()->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?");
                        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $user_id);
                    }
                    
                    if ($stmt->execute()) {
                        $_SESSION['first_name'] = $first_name;
                        $_SESSION['last_name'] = $last_name;
                        $success_message = "Profile updated successfully!";
                        // Refresh user data
                        $stmt = $this->legacy()->prepare("SELECT * FROM users WHERE user_id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $user = $stmt->get_result()->fetch_assoc();
                    } else {
                        $errors[] = "Failed to update profile";
                    }
                } catch (\Exception $e) {
                    $errors[] = "An error occurred: " . $e->getMessage();
                }
            }
        }
        
        // Get worker performance stats
        $stmt = $this->legacy()->prepare("
            SELECT 
                COUNT(*) as total_collections,
                SUM(pr.weight_kg) as total_weight
            FROM pickuprequests pr
            WHERE pr.assigned_worker_id = ? AND pr.status = 'Completed'
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $worker_stats = $stmt->get_result()->fetch_assoc();

        $data = get_defined_vars();

        $this->view('worker.profile', $data);
    }
}