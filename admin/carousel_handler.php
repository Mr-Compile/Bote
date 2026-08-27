<?php
require_once '../config/database.php';
require_once '../includes/auth_admin.php';
require_once '../includes/upload_handler.php';

// Set content type for JSON response
header('Content-Type: application/json');

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Log incoming request data for debugging
    error_log('Carousel handler request: ' . json_encode($_POST));
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add':
            case 'update':
                $slide_id = isset($_POST['slide_id']) ? (int)$_POST['slide_id'] : 0;
                $title = trim($_POST['title'] ?? '');
                $subtitle = trim($_POST['subtitle'] ?? '');
                $button_text = trim($_POST['button_text'] ?? '');
                $button_link = trim($_POST['button_link'] ?? '');
                $display_order = (int)($_POST['display_order'] ?? 0);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                // Validation
                if (empty($title)) {
                    throw new Exception("Title is required.");
                }
                
                // Handle image upload
                $image = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploader = new BoteBankUploadHandler();
                    $upload_result = $uploader->handleImageUpload($_FILES['image'], 'carousel');
                    
                    if ($upload_result['success']) {
                        $image = $upload_result['filename'];
                        
                        // Delete old image if updating
                        if ($slide_id > 0) {
                            $stmt = prepare_statement($conn, "SELECT image FROM carousel_slides WHERE slide_id = ?");
                            $stmt->bind_param("i", $slide_id);
                            $stmt->execute();
                            $old_image = $stmt->get_result()->fetch_assoc()['image'] ?? null;
                            
                            if ($old_image && $old_image !== $image) {
                                $uploader->deleteFile('carousel/' . $old_image);
                            }
                        }
                    } else {
                        throw new Exception("Image upload failed: " . $upload_result['error']);
                    }
                }
                
                if ($slide_id > 0) {
                    // Update existing slide
                    if ($image) {
                        $stmt = prepare_statement($conn, 
                            "UPDATE carousel_slides SET title = ?, subtitle = ?, image = ?, button_text = ?, button_link = ?, display_order = ?, is_active = ? WHERE slide_id = ?");
                        $stmt->bind_param("sssssiis", $title, $subtitle, $image, $button_text, $button_link, $display_order, $is_active, $slide_id);
                    } else {
                        $stmt = prepare_statement($conn, 
                            "UPDATE carousel_slides SET title = ?, subtitle = ?, button_text = ?, button_link = ?, display_order = ?, is_active = ? WHERE slide_id = ?");
                        $stmt->bind_param("ssssiis", $title, $subtitle, $button_text, $button_link, $display_order, $is_active, $slide_id);
                    }
                    
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = "Slide updated successfully!";
                    } else {
                        throw new Exception("Failed to update slide.");
                    }
                } else {
                    // Add new slide
                    $stmt = prepare_statement($conn, 
                        "INSERT INTO carousel_slides (title, subtitle, image, button_text, button_link, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssii", $title, $subtitle, $image, $button_text, $button_link, $display_order, $is_active);
                    
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = "Slide added successfully!";
                        $response['slide_id'] = $conn->insert_id;
                    } else {
                        throw new Exception("Failed to add slide.");
                    }
                }
                break;
                
            case 'delete':
                $slide_id = (int)($_POST['slide_id'] ?? 0);
                
                if ($slide_id <= 0) {
                    throw new Exception("Invalid slide ID.");
                }
                
                // Get image to delete
                $stmt = prepare_statement($conn, "SELECT image FROM carousel_slides WHERE slide_id = ?");
                $stmt->bind_param("i", $slide_id);
                $stmt->execute();
                $image = $stmt->get_result()->fetch_assoc()['image'] ?? null;
                
                // Delete slide
                $stmt = prepare_statement($conn, "DELETE FROM carousel_slides WHERE slide_id = ?");
                $stmt->bind_param("i", $slide_id);
                
                if ($stmt->execute()) {
                    // Delete image file
                    if ($image) {
                        $uploader = new BoteBankUploadHandler();
                        $uploader->deleteFile('carousel/' . $image);
                    }
                    
                    $response['success'] = true;
                    $response['message'] = "Slide deleted successfully!";
                } else {
                    throw new Exception("Failed to delete slide.");
                }
                break;
                
            case 'toggle':
                $slide_id = (int)($_POST['slide_id'] ?? 0);
                
                if ($slide_id <= 0) {
                    throw new Exception("Invalid slide ID.");
                }
                
                $stmt = prepare_statement($conn, "UPDATE carousel_slides SET is_active = !is_active WHERE slide_id = ?");
                $stmt->bind_param("i", $slide_id);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Slide status updated!";
                } else {
                    throw new Exception("Failed to update slide status.");
                }
                break;
                
            default:
                throw new Exception("Invalid action.");
        }
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Make sure we're properly encoding JSON
echo json_encode($response, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
exit;
?>
