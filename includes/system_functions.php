<?php
// System Functions - Helper functions for system settings and common operations

/**
 * Get system setting value from database
 * @param mysqli $conn Database connection
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value or default
 */
function getSystemSetting($conn, $key, $default = '') {
    $stmt = prepare_statement($conn, "SELECT setting_value FROM system_settings WHERE setting_key = ?");
    if ($stmt) {
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['setting_value'];
        }
    }
    return $default;
}

/**
 * Get all system settings as an associative array
 * @param mysqli $conn Database connection
 * @return array Array of setting_key => setting_value
 */
function getAllSystemSettings($conn) {
    $settings = [];
    $stmt = prepare_statement($conn, "SELECT setting_key, setting_value FROM system_settings");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings;
}

/**
 * Update system setting
 * @param mysqli $conn Database connection
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @return bool Success status
 */
function updateSystemSetting($conn, $key, $value) {
    $stmt = prepare_statement($conn, "
        INSERT INTO system_settings (setting_key, setting_value) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP
    ");
    if ($stmt) {
        $stmt->bind_param("ss", $key, $value);
        return $stmt->execute();
    }
    return false;
}

/**
 * Set system setting (alias for updateSystemSetting for consistency)
 * @param mysqli $conn Database connection
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @return bool Success status
 */
function setSystemSetting($conn, $key, $value) {
    return updateSystemSetting($conn, $key, $value);
}

/**
 * Get user's total points
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return int Total points
 */
function getUserTotalPoints($conn, $user_id) {
    $stmt = prepare_statement($conn, "
        SELECT COALESCE(SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE -points END), 0) as total_points
        FROM pointsledger 
        WHERE user_id = ?
    ");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return (int)$row['total_points'];
        }
    }
    return 0;
}

/**
 * Get user's profile photo path or generate initials
 * @param array $user_data User data array
 * @return array Array with 'type' (photo|initials) and 'value' (path|letters)
 */
function getUserProfileDisplay($user_data) {
    if (!empty($user_data['profile_photo']) && file_exists("../uploads/profiles/thumbs/" . $user_data['profile_photo'])) {
        return [
            'type' => 'photo',
            'value' => $user_data['profile_photo']
        ];
    } else {
        $initials = strtoupper(substr($user_data['first_name'], 0, 1) . substr($user_data['last_name'], 0, 1));
        return [
            'type' => 'initials',
            'value' => $initials
        ];
    }
}

/**
 * Format date for display
 * @param string $date Date string
 * @param string $format Date format (default: 'M j, Y')
 * @return string Formatted date
 */
function formatDisplayDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 * @param string $datetime Datetime string
 * @param string $format Datetime format (default: 'M j, Y g:i A')
 * @return string Formatted datetime
 */
function formatDisplayDateTime($datetime, $format = 'M j, Y g:i A') {
    return date($format, strtotime($datetime));
}



/**
 * Check if system settings table exists and create if needed
 * @param mysqli $conn Database connection
 * @return bool Success status
 */
function ensureSystemSettingsTable($conn) {
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE 'system_settings'");
    if ($result->num_rows == 0) {
        // Table doesn't exist, create it
        $sql = "
        CREATE TABLE `system_settings` (
          `setting_id` int(11) NOT NULL AUTO_INCREMENT,
          `setting_key` varchar(100) NOT NULL,
          `setting_value` text DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`setting_id`),
          UNIQUE KEY `setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`) VALUES
        ('barangay_name', 'BoteBank'),
        ('barangay_contact', ''),
        ('barangay_email', ''),
        ('barangay_address', ''),
        ('barangay_logo', ''),
        ('system_banner', ''),
        ('banner_title', 'Welcome to BoteBank'),
        ('banner_subtitle', 'Turn your recyclables into rewards and help build a greener community'),
        ('banner_enabled', '1'),
        ('login_image', ''),
        ('max_points_per_day', '1000'),
        ('min_redemption_points', '100'),
        ('points_notification_template', 'You have earned {points} points for your recyclable submission!'),
        ('reward_notification_template', 'Your reward claim for {reward_name} has been processed!'),
        ('email_notification_enabled', '1'),
        ('sms_notification_enabled', '1'),
        ('password_min_length', '8'),
        ('password_require_special', '1'),
        ('session_timeout', '30');
        ";
        
        return $conn->multi_query($sql);
    }
    
    return true;
}

/**
 * Safely convert coordinate values for database storage
 * @param string|float $value The coordinate value
 * @return float|null Returns float value or null for empty/invalid values
 */
function sanitizeCoordinate($value) {
    if (empty($value) || $value === '' || $value === '0' || $value === '0.0000000') {
        return null;
    }
    
    $floatValue = (float)$value;
    
    // Validate latitude range
    if (abs($floatValue) <= 90) {
        return $floatValue;
    }
    
    // Validate longitude range  
    if (abs($floatValue) <= 180) {
        return $floatValue;
    }
    
    return null;
}

/**
 * Format coordinates for display
 * @param float|null $value The coordinate value from database
 * @return string Formatted coordinate value
 */
function formatCoordinate($value) {
    if ($value === null || $value === '') {
        return '';
    }
    return number_format((float)$value, 7, '.', '');
}

/**
 * Validate latitude value
 * @param mixed $lat Latitude value to validate
 * @return bool True if valid latitude
 */
function isValidLatitude($lat) {
    if (!is_numeric($lat)) {
        return false;
    }
    $lat = (float)$lat;
    return $lat >= -90 && $lat <= 90;
}

/**
 * Validate longitude value
 * @param mixed $lng Longitude value to validate
 * @return bool True if valid longitude
 */
function isValidLongitude($lng) {
    if (!is_numeric($lng)) {
        return false;
    }
    $lng = (float)$lng;
    return $lng >= -180 && $lng <= 180;
}
?>
