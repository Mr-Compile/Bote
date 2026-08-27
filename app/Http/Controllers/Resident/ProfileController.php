<?php

declare(strict_types=1);

namespace App\Http\Controllers\Resident;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class ProfileController extends BaseController
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
            $address = trim($_POST['address']);
            $latitude = $_POST['latitude'] ?? null;
            $longitude = $_POST['longitude'] ?? null;
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            $errors = [];
            
            // Validate required fields
            if (empty($first_name)) $errors[] = "First name is required";
            if (empty($last_name)) $errors[] = "Last name is required";
            if (empty($email)) $errors[] = "Email is required";
            if (empty($phone)) $errors[] = "Phone is required";
            if (empty($address)) $errors[] = "Address is required";
            
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
                        // Update with new password and coordinates
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $this->legacy()->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, latitude = ?, longitude = ?, password = ? WHERE user_id = ?");
                        $stmt->bind_param("ssssssdsi", $first_name, $last_name, $email, $phone, $address, $latitude, $longitude, $hashed_password, $user_id);
                    } else {
                        // Update without changing password but include coordinates
                        $stmt = $this->legacy()->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, latitude = ?, longitude = ? WHERE user_id = ?");
                        $stmt->bind_param("sssssddi", $first_name, $last_name, $email, $phone, $address, $latitude, $longitude, $user_id);
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
        
        $profile_data = [
            'latitude' => $user['latitude'] ? (float) $user['latitude'] : 6.4217,
            'longitude' => $user['longitude'] ? (float) $user['longitude'] : 124.7801,
            'hasCoordinates' => !empty($user['latitude']),
        ];
        $json_profile_data = json_encode($profile_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $data = get_defined_vars();

        $this->view('resident.profile', $data);
    }
}