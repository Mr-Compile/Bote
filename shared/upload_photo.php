<?php
session_start();
require_once '../config/database.php';
require_once '../includes/system_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    \App\Core\Csrf::validateRequest();
} catch (\InvalidArgumentException $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired session token.']);
    exit;
}

if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['profile_photo'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_size = 5 * 1024 * 1024; // 5MB

// Validate file type
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload JPEG, PNG, GIF, or WebP images']);
    exit;
}

// Validate file size
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB']);
    exit;
}

// Validate image
$image_info = getimagesize($file['tmp_name']);
if ($image_info === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid image file']);
    exit;
}

try {
    // Get user's current photo to delete old one
    $stmt = prepare_statement($conn, "SELECT profile_photo FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $current_photo = $stmt->get_result()->fetch_assoc()['profile_photo'];
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $user_id . '_' . time() . '.' . strtolower($extension);
    $upload_path = '../uploads/profiles/' . $filename;
    $thumb_path = '../uploads/profiles/thumbs/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    // Create thumbnail
    createThumbnail($upload_path, $thumb_path, 150, 150);
    
    // Update database
    $stmt = prepare_statement($conn, "UPDATE users SET profile_photo = ? WHERE user_id = ?");
    $stmt->bind_param("si", $filename, $user_id);
    
    if (!$stmt->execute()) {
        // Delete uploaded files if database update fails
        unlink($upload_path);
        if (file_exists($thumb_path)) unlink($thumb_path);
        throw new Exception('Failed to update database');
    }
    
    // Delete old photo files
    if ($current_photo) {
        $old_path = '../uploads/profiles/' . $current_photo;
        $old_thumb = '../uploads/profiles/thumbs/' . $current_photo;
        if (file_exists($old_path)) unlink($old_path);
        if (file_exists($old_thumb)) unlink($old_thumb);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Profile photo updated successfully',
        'photo_url' => 'uploads/profiles/' . $filename,
        'thumb_url' => 'uploads/profiles/thumbs/' . $filename
    ]);
    
} catch (Exception $e) {
    // Clean up uploaded file on error
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    if (isset($thumb_path) && file_exists($thumb_path)) {
        unlink($thumb_path);
    }
    
    echo json_encode(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()]);
}

function createThumbnail($source, $dest, $width, $height) {
    $image_info = getimagesize($source);
    $image_type = $image_info[2];
    
    // Create image resource from source
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $source_image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $source_image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $source_image = imagecreatefromgif($source);
            break;
        case IMAGETYPE_WEBP:
            $source_image = imagecreatefromwebp($source);
            break;
        default:
            throw new Exception('Unsupported image type');
    }
    
    $source_width = imagesx($source_image);
    $source_height = imagesy($source_image);
    
    // Calculate dimensions for square crop
    $crop_size = min($source_width, $source_height);
    $crop_x = ($source_width - $crop_size) / 2;
    $crop_y = ($source_height - $crop_size) / 2;
    
    // Create thumbnail
    $thumbnail = imagecreatetruecolor($width, $height);
    
    // Preserve transparency for PNG and GIF
    if ($image_type == IMAGETYPE_PNG || $image_type == IMAGETYPE_GIF) {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefill($thumbnail, 0, 0, $transparent);
    }
    
    // Copy and resize
    imagecopyresampled(
        $thumbnail, $source_image,
        0, 0, $crop_x, $crop_y,
        $width, $height, $crop_size, $crop_size
    );
    
    // Save thumbnail
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumbnail, $dest, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumbnail, $dest, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumbnail, $dest);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($thumbnail, $dest, 85);
            break;
    }
    
    // Clean up
    imagedestroy($source_image);
    imagedestroy($thumbnail);
}
?>
