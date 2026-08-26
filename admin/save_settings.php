<?php
require_once '../config/database.php';
require '../includes/auth_admin.php'; // Ensure only admins can access
require_once '../includes/system_functions.php';
require_once '../includes/upload_handler.php';

// Ensure system settings table exists
ensureSystemSettingsTable($conn);

// Initialize upload handler
$uploadHandler = new BoteBankUploadHandler();

// Function to save or update system setting
function saveSystemSetting($conn, $key, $value) {
    $stmt = prepare_statement($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    if ($stmt) {
        $stmt->bind_param("sss", $key, $value, $value);
        return $stmt->execute();
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = $_POST['tab'] ?? '';
    $success = false;
    
    try {
        switch ($tab) {
            case 'profile':
                // Handle admin profile updates (personal data and photo)
                $first_name = trim($_POST['first_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $address = trim($_POST['address'] ?? '');
                $admin_latitude = trim($_POST['admin_latitude'] ?? '');
                $admin_longitude = trim($_POST['admin_longitude'] ?? '');

                // Validation
                if (empty($first_name) || empty($last_name)) {
                    throw new Exception("First name and last name are required.");
                }
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Please enter a valid email address.");
                }
                if (empty($phone)) {
                    throw new Exception("Phone number is required.");
                }

                // Validate coordinates if provided
                if ((!empty($admin_latitude) && !is_numeric($admin_latitude)) || (!empty($admin_longitude) && !is_numeric($admin_longitude))) {
                    throw new Exception("Invalid coordinates for admin location.");
                }

                $user_id = $_SESSION['user_id'];
                
                // Get current profile photo
                $current_photo = '';
                $stmt = prepare_statement($conn, "SELECT profile_photo FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                if ($result) $current_photo = $result['profile_photo'];

                // Handle profile photo upload
                if (isset($_FILES['admin_profile_photo']) && $_FILES['admin_profile_photo']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = $uploadHandler->uploadFile($_FILES['admin_profile_photo'], 'profile');
                    if ($upload_result['success']) {
                        // Delete old photo if exists
                        if (!empty($current_photo) && $current_photo !== $upload_result['filename']) {
                            $uploadHandler->deleteFile($current_photo, 'profile');
                        }
                        $current_photo = $upload_result['filename'];
                    } else {
                        throw new Exception("Profile photo upload failed: " . $upload_result['error']);
                    }
                }

                // Update admin profile data
                $stmt = prepare_statement($conn, "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, profile_photo = ?, latitude = ?, longitude = ? WHERE user_id = ?");
                $stmt->bind_param("ssssssssi", $first_name, $last_name, $email, $phone, $address, $current_photo, $admin_latitude, $admin_longitude, $user_id);
                
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    throw new Exception("Failed to update profile.");
                }
                break;
                
            case 'barangay':
                // Handle barangay information
                saveSystemSetting($conn, 'barangay_name', $_POST['barangay_name'] ?? '');
                saveSystemSetting($conn, 'barangay_contact', $_POST['barangay_contact'] ?? '');
                saveSystemSetting($conn, 'barangay_email', $_POST['barangay_email'] ?? '');
                saveSystemSetting($conn, 'barangay_address', $_POST['barangay_address'] ?? '');
                // Save barangay coordinates
                saveSystemSetting($conn, 'barangay_latitude', $_POST['barangay_latitude'] ?? '');
                saveSystemSetting($conn, 'barangay_longitude', $_POST['barangay_longitude'] ?? '');

                // Handle banner settings
                saveSystemSetting($conn, 'banner_title', $_POST['banner_title'] ?? '');
                saveSystemSetting($conn, 'banner_subtitle', $_POST['banner_subtitle'] ?? '');
                saveSystemSetting($conn, 'banner_enabled', isset($_POST['banner_enabled']) ? '1' : '0');

                // Handle logo upload
                if (isset($_FILES['barangay_logo']) && $_FILES['barangay_logo']['size'] > 0) {
                    $uploadResult = $uploadHandler->uploadSystemFile($_FILES['barangay_logo'], 'logos');
                    if ($uploadResult['success']) {
                        saveSystemSetting($conn, 'barangay_logo', $uploadResult['filename']);
                    } else {
                        throw new Exception('Logo upload failed: ' . $uploadResult['error']);
                    }
                }

                // Handle banner upload
                if (isset($_FILES['system_banner']) && $_FILES['system_banner']['size'] > 0) {
                    $uploadResult = $uploadHandler->uploadSystemFile($_FILES['system_banner'], 'banners');
                    if ($uploadResult['success']) {
                        saveSystemSetting($conn, 'system_banner', $uploadResult['filename']);
                    } else {
                        throw new Exception('Banner upload failed: ' . $uploadResult['error']);
                    }
                }
                
                // Handle login image upload
                if (isset($_FILES['login_image']) && $_FILES['login_image']['size'] > 0) {
                    // Ensure auth directory exists
                    $auth_dir = '../uploads/system/auth/';
                    if (!is_dir($auth_dir)) {
                        if (!mkdir($auth_dir, 0755, true)) {
                            throw new Exception('Failed to create login image directory');
                        }
                    }
                    
                    $uploadResult = $uploadHandler->uploadSystemFile($_FILES['login_image'], 'auth');
                    if ($uploadResult['success']) {
                        saveSystemSetting($conn, 'login_image', $uploadResult['filename']);
                    } else {
                        throw new Exception('Login image upload failed: ' . $uploadResult['error']);
                    }
                }

                $success = true;
                break;
                
            case 'points':
                // Handle points and rewards settings
                saveSystemSetting($conn, 'max_points_per_day', $_POST['max_points_per_day'] ?? '1000');
                saveSystemSetting($conn, 'min_redemption_points', $_POST['min_redemption_points'] ?? '100');
                
                // Update recyclable types points
                foreach ($_POST as $key => $value) {
                    if (strpos($key, 'points_') === 0 && is_numeric(substr($key, 7))) {
                        $type_id = substr($key, 7);
                        $stmt = prepare_statement($conn, "UPDATE recyclabletypes SET points_per_kg = ? WHERE type_id = ?");
                        if ($stmt) {
                            $stmt->bind_param("ii", $value, $type_id);
                            $stmt->execute();
                        }
                    }
                }
                
                $success = true;
                break;
                
            case 'notifications':
                // Handle notification settings
                saveSystemSetting($conn, 'email_notification_enabled', isset($_POST['email_notification_enabled']) ? '1' : '0');
                saveSystemSetting($conn, 'sms_notification_enabled', isset($_POST['sms_notification_enabled']) ? '1' : '0');
                saveSystemSetting($conn, 'points_notification_template', $_POST['points_notification_template'] ?? '');
                saveSystemSetting($conn, 'reward_notification_template', $_POST['reward_notification_template'] ?? '');
                
                $success = true;
                break;
                
            case 'security':
                // Handle security settings
                saveSystemSetting($conn, 'password_min_length', $_POST['password_min_length'] ?? '8');
                saveSystemSetting($conn, 'session_timeout', $_POST['session_timeout'] ?? '30');
                saveSystemSetting($conn, 'password_require_special', isset($_POST['password_require_special']) ? '1' : '0');
                
                $success = true;
                break;
                
            case 'password':
                // Handle password change
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if ($new_password !== $confirm_password) {
                    throw new Exception('New password and confirmation password do not match.');
                }
                
                if (strlen($new_password) < 8) {
                    throw new Exception('Password must be at least 8 characters long.');
                }
                
                // Verify current password
                $user_id = $_SESSION['user_id'];
                $stmt = prepare_statement($conn, "SELECT password FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                if (!password_verify($current_password, $user['password'])) {
                    throw new Exception('Current password is incorrect.');
                }
                
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = prepare_statement($conn, "UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                $stmt->execute();
                
                $success = true;
                break;
                
            default:
                throw new Exception('Invalid tab specified.');
        }
        
        if ($success) {
            // Redirect with success message
            header('Location: admin_settings.php?success=1&tab=' . urlencode($tab));
            exit;
        }
        
    } catch (Exception $e) {
        // Redirect with error message
        header('Location: admin_settings.php?error=' . urlencode($e->getMessage()) . '&tab=' . urlencode($tab));
        exit;
    }
} else {
    // Redirect if not POST request
    header('Location: admin_settings.php');
    exit;
}
?>
