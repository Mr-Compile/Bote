<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class SettingsController extends BaseController
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
        $success_message = '';
        $error_message = '';
        
        // Handle form submissions with enhanced validation
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Validate database connection
                if (!$conn || !$this->checkDbHealth()) {
                    throw new \Exception("Database connection error. Please try again.");
                }
        
                switch ($_POST['tab']) {
                    case 'barangay':
                        // Handle barangay information updates including image uploads
                        $barangay_name = trim($_POST['barangay_name'] ?? '');
                        $barangay_contact = trim($_POST['barangay_contact'] ?? '');
                        $barangay_email = trim($_POST['barangay_email'] ?? '');
                        $barangay_address = trim($_POST['barangay_address'] ?? '');
                        $banner_title = trim($_POST['banner_title'] ?? '');
                        $banner_subtitle = trim($_POST['banner_subtitle'] ?? '');
                        $banner_enabled = isset($_POST['banner_enabled']) ? '1' : '0';
        
                        // Validation
                        if (empty($barangay_name)) {
                            throw new \Exception("Barangay name is required.");
                        }
                        if (!empty($barangay_email) && !filter_var($barangay_email, FILTER_VALIDATE_EMAIL)) {
                            throw new \Exception("Please enter a valid email address.");
                        }
        
                        // Handle logo upload
                        $current_logo = $this->setting('barangay_logo', '');
                        if (isset($_FILES['barangay_logo']) && $_FILES['barangay_logo']['error'] === UPLOAD_ERR_OK) {
                            $uploader = new \BoteBankUploadHandler();
                            $upload_result = $uploader->handleImageUpload($_FILES['barangay_logo'], 'system/logos');
                            
                            if ($upload_result['success']) {
                                // Delete old logo if exists
                                if (!empty($current_logo) && $current_logo !== $upload_result['filename']) {
                                    $uploader->deleteFile('system/logos/' . $current_logo);
                                }
                                $current_logo = $upload_result['filename'];
                            } else {
                                throw new \Exception("Logo upload failed: " . $upload_result['error']);
                            }
                        }
        
                        // Handle banner upload
                        $current_banner = $this->setting('system_banner', '');
                        if (isset($_FILES['system_banner']) && $_FILES['system_banner']['error'] === UPLOAD_ERR_OK) {
                            $uploader = new \BoteBankUploadHandler();
                            $upload_result = $uploader->handleImageUpload($_FILES['system_banner'], 'system/banners');
                            
                            if ($upload_result['success']) {
                                // Delete old banner if exists
                                if (!empty($current_banner) && $current_banner !== $upload_result['filename']) {
                                    $uploader->deleteFile('system/banners/' . $current_banner);
                                }
                                $current_banner = $upload_result['filename'];
                            } else {
                                throw new \Exception("Banner upload failed: " . $upload_result['error']);
                            }
                        }
        
        
                        // Update all barangay settings
                        $settings_to_update = [
                            'barangay_name' => $barangay_name,
                            'barangay_contact' => $barangay_contact,
                            'barangay_email' => $barangay_email,
                            'barangay_address' => $barangay_address,
                            'barangay_logo' => $current_logo,
                            'system_banner' => $current_banner,
                            'banner_title' => $banner_title,
                            'banner_subtitle' => $banner_subtitle,
                            'banner_enabled' => $banner_enabled
                        ];
                        foreach ($settings_to_update as $key => $value) {
                            $this->setSetting($key, $value);
                        }
                        
                        $success_message = "Barangay information updated successfully!";
                        break;
        
                    case 'password':
                        // Handle password change
                        $current_password = $_POST['current_password'] ?? '';
                        $new_password = $_POST['new_password'] ?? '';
                        $confirm_password = $_POST['confirm_password'] ?? '';
        
                        // Validation
                        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                            throw new \Exception("All password fields are required.");
                        }
                        if ($new_password !== $confirm_password) {
                            throw new \Exception("New password and confirmation do not match.");
                        }
                        if (strlen($new_password) < 8) {
                            throw new \Exception("Password must be at least 8 characters long.");
                        }
        
                        // Verify current password
                        $user_id = $_SESSION['user_id'];
                        $stmt = $this->legacy()->prepare("SELECT password FROM users WHERE user_id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result()->fetch_assoc();
                        
                        if (!password_verify($current_password, $result['password'])) {
                            throw new \Exception("Current password is incorrect.");
                        }
        
                        // Update password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $this->legacy()->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                        $stmt->bind_param("si", $hashed_password, $user_id);
                        
                        if ($stmt->execute()) {
                            $success_message = "Password changed successfully!";
                        } else {
                            throw new \Exception("Failed to update password.");
                        }
                        break;
                }
        
            } catch (\Exception $e) {
                $error_message = $e->getMessage();
                error_log("Settings update error: " . $e->getMessage());
            }
        }
        
        // Ensure system settings table exists
        $this->ensureSystemSettingsTable();
        
        // Get system settings
        $settings = [
            'barangay_name' => $this->setting('barangay_name', 'Barangay Name'),
            'barangay_contact' => $this->setting('barangay_contact', ''),
            'barangay_email' => $this->setting('barangay_email', ''),
            'barangay_address' => $this->setting('barangay_address', ''),
            'barangay_logo' => $this->setting('barangay_logo', ''),
            'system_banner' => $this->setting('system_banner', ''),
            'banner_title' => $this->setting('banner_title', 'Welcome to BoteBank'),
            'banner_subtitle' => $this->setting('banner_subtitle', 'Turn your recyclables into rewards and help build a greener community'),
            'banner_enabled' => $this->setting('banner_enabled', '1'),
            'login_image' => $this->setting('login_image', '')
        ];
        
        // Get carousel slides
        $carousel_slides = [];
        $stmt = $this->legacy()->prepare("SELECT * FROM carousel_slides ORDER BY display_order, slide_id");
        if ($stmt->execute()) {
            $carousel_result = $stmt->get_result();
            while ($row = $carousel_result->fetch_assoc()) {
                $carousel_slides[] = $row;
            }
        }

        $data = get_defined_vars();

        $this->view('admin.settings', $data);
    }
}