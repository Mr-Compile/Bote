<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class RecyclableTypesController extends BaseController
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
        
        // Handle CRUD operations with enhanced error handling and image upload
        $success_message = '';
        $error_message = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            try {
                // Validate database connection
                if (!$conn || !$this->checkDbHealth()) {
                    throw new \Exception("Database connection error. Please try again.");
                }
        
                switch ($_POST['action']) {
                    case 'add_type':
                        $name = trim($_POST['name']);
                        $unit = $_POST['unit']; 
                        $points_per_kg = ($unit === 'kg') ? (float)$_POST['points_per_kg'] : null;
                        $points_per_unit = ($unit === 'pcs') ? (float)$_POST['points_per_unit'] : null;
                        $image_path = null;
        
                        // Validation
                        if (empty($name)) {
                            throw new \Exception("Name is required.");
                        }
                        if ($unit === 'kg' && $points_per_kg <= 0) {
                            throw new \Exception("Points per kg must be greater than 0.");
                        }
                        if ($unit === 'pcs' && $points_per_unit <= 0) {
                            throw new \Exception("Points per unit must be greater than 0.");
                        }
        
                        // Check for duplicate name
                        $check_stmt = $this->legacy()->prepare("SELECT type_id FROM recyclabletypes WHERE name = ?");
                        if (!$check_stmt) {
                            throw new \Exception("Database error while checking for duplicates.");
                        }
                        $check_stmt->bind_param("s", $name);
                        $check_stmt->execute();
                        if ($check_stmt->get_result()->num_rows > 0) {
                            throw new \Exception("A recyclable type with this name already exists.");
                        }
        
                        // Handle image upload
                        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                            $uploader = new \BoteBankUploadHandler();
                            $upload_result = $uploader->uploadFile($_FILES['image'], 'recyclables', 'recyclable_' . time());
                            
                            if ($upload_result['success']) {
                                $image_path = $upload_result['path'];
                            } else {
                                throw new \Exception("Image upload failed: " . $upload_result['error']);
                            }
                        }
        
                        $stmt = $this->legacy()->prepare("INSERT INTO recyclabletypes (name, unit, points_per_kg, points_per_unit, image) VALUES (?, ?, ?, ?, ?)");
                        if (!$stmt) {
                            throw new \Exception("Database error while preparing statement.");
                        }
                        $stmt->bind_param("ssdds", $name, $unit, $points_per_kg, $points_per_unit, $image_path);
                        if ($stmt->execute()) {
                            $success_message = "Recyclable type added successfully!";
                            error_log("Admin {$_SESSION['user_id']} added new recyclable type: {$name}");
                        } else {
                            throw new \Exception("Failed to add recyclable type to database.");
                        }
                        break;
        
                case 'update_type':
                    $type_id = (int)$_POST['type_id'];
                    $name = trim($_POST['name']);
                    $unit = $_POST['unit'];
                    $points_per_kg = ($unit === 'kg') ? (float)$_POST['points_per_kg'] : null;
                    $points_per_unit = ($unit === 'pcs') ? (float)$_POST['points_per_unit'] : null;
                    $image_path = $_POST['current_image'] ?? null; // Keep current image by default
        
                    // Validation
                    if ($type_id <= 0) {
                        throw new \Exception("Invalid type ID.");
                    }
                    if (empty($name)) {
                        throw new \Exception("Name is required.");
                    }
                    if ($unit === 'kg' && $points_per_kg <= 0) {
                        throw new \Exception("Points per kg must be greater than 0.");
                    }
                    if ($unit === 'pcs' && $points_per_unit <= 0) {
                        throw new \Exception("Points per unit must be greater than 0.");
                    }
        
                    // Check if recyclable type exists and name is not duplicate (except current)
                    $check_stmt = $this->legacy()->prepare("SELECT type_id FROM recyclabletypes WHERE name = ? AND type_id != ?");
                    if (!$check_stmt) {
                        throw new \Exception("Database error while checking for duplicates.");
                    }
                    $check_stmt->bind_param("si", $name, $type_id);
                    $check_stmt->execute();
                    if ($check_stmt->get_result()->num_rows > 0) {
                        throw new \Exception("A recyclable type with this name already exists.");
                    }
        
                    // Handle image upload
                    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $uploader = new \BoteBankUploadHandler();
                        
                        // Delete old image if it exists
                        if ($image_path) {
                            $uploader->deleteFile(basename($image_path), 'recyclables');
                        }
                        
                        $upload_result = $uploader->uploadFile($_FILES['image'], 'recyclables', 'recyclable_' . time());
                        
                        if ($upload_result['success']) {
                            $image_path = $upload_result['path'];
                        } else {
                            throw new \Exception("Image upload failed: " . $upload_result['error']);
                        }
                    }
        
                    $stmt = $this->legacy()->prepare("UPDATE recyclabletypes SET name = ?, unit = ?, points_per_kg = ?, points_per_unit = ?, image = ? WHERE type_id = ?");
                    if (!$stmt) {
                        throw new \Exception("Database error while preparing statement.");
                    }
                    $stmt->bind_param("ssddsi", $name, $unit, $points_per_kg, $points_per_unit, $image_path, $type_id);
                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $success_message = "Recyclable type updated successfully!";
                        error_log("Admin {$_SESSION['user_id']} updated recyclable type: {$type_id}");
                    } else {
                        throw new \Exception("Failed to update recyclable type or no changes made.");
                    }
                    break;
        
                case 'delete_type':
                    $type_id = (int)$_POST['type_id'];
        
                    if ($type_id <= 0) {
                        throw new \Exception("Invalid type ID.");
                    }
        
                    // Check if recyclable type has active pickup requests
                    $check_stmt = $this->legacy()->prepare("SELECT COUNT(*) as count FROM pickuprequests WHERE type_id = ? AND status IN ('Pending', 'Scheduled')");
                    if (!$check_stmt) {
                        throw new \Exception("Database error while checking for dependencies.");
                    }
                    $check_stmt->bind_param("i", $type_id);
                    $check_stmt->execute();
                    $request_count = $check_stmt->get_result()->fetch_assoc()['count'];
        
                    if ($request_count > 0) {
                        throw new \Exception("Cannot delete recyclable type with active pickup requests.");
                    }
        
                    // Get image path before deletion
                    $image_stmt = $this->legacy()->prepare("SELECT image FROM recyclabletypes WHERE type_id = ?");
                    if ($image_stmt) {
                        $image_stmt->bind_param("i", $type_id);
                        $image_stmt->execute();
                        $result = $image_stmt->get_result();
                        $image_path = $result->num_rows > 0 ? $result->fetch_assoc()['image'] : null;
                    }
        
                    $stmt = $this->legacy()->prepare("DELETE FROM recyclabletypes WHERE type_id = ?");
                    if (!$stmt) {
                        throw new \Exception("Database error while preparing statement.");
                    }
                    $stmt->bind_param("i", $type_id);
                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        // Delete associated image
                        if ($image_path) {
                            $uploader = new \BoteBankUploadHandler();
                            $uploader->deleteFile(basename($image_path), 'recyclables');
                        }
                        $success_message = "Recyclable type deleted successfully!";
                        error_log("Admin {$_SESSION['user_id']} deleted recyclable type: {$type_id}");
                    } else {
                        throw new \Exception("Failed to delete recyclable type or type not found.");
                    }
                    break;
                }
            } catch (\Exception $e) {
                $error_message = $e->getMessage();
                error_log("Recyclable type management error: " . $e->getMessage());
            }
        }
        
        // Get all recyclable types with enhanced error handling
        try {
            $stmt = $this->legacy()->prepare("SELECT * FROM recyclabletypes ORDER BY name");
            if (!$stmt) {
                throw new \Exception("Database error while fetching recyclable types.");
            }
            $stmt->execute();
            $recyclable_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (\Exception $e) {
            error_log("Error fetching recyclable types: " . $e->getMessage());
            $recyclable_types = [];
            if (empty($error_message)) {
                $error_message = "Unable to load recyclable types data. Please refresh the page.";
            }
        }
        
        // Get statistics with enhanced error handling
        try {
            $total_types = count($recyclable_types);
            
            // Average points calculation
            $avg_points = 0;
            if ($total_types > 0) {
                $total_points = 0;
                $point_count = 0;
                foreach ($recyclable_types as $type) {
                    if ($type['unit'] === 'kg' && $type['points_per_kg'] !== null) {
                        $total_points += (float)$type['points_per_kg'];
                        $point_count++;
                    } elseif ($type['unit'] === 'pcs' && $type['points_per_unit'] !== null) {
                        $total_points += (float)$type['points_per_unit'];
                        $point_count++;
                    }
                }
                $avg_points = $point_count > 0 ? round($total_points / $point_count, 2) : 0;
            }
            
            // Active types (with images)
            $active_types = count(array_filter($recyclable_types, function($type) {
                return !empty($type['image']);
            }));
            
            // Get usage statistics
            $stmt = $this->legacy()->prepare("
                SELECT rt.type_id, rt.name, COUNT(pr.request_id) as usage_count
                FROM recyclabletypes rt
                LEFT JOIN pickuprequests pr ON rt.type_id = pr.type_id
                GROUP BY rt.type_id, rt.name
                ORDER BY usage_count DESC
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                $most_used = $result->num_rows > 0 ? $result->fetch_assoc() : null;
            } else {
                $most_used = null;
            }
            
        } catch (\Exception $e) {
            error_log("Error calculating statistics: " . $e->getMessage());
            $total_types = $avg_points = $active_types = 0;
            $most_used = null;
        }

        $data = get_defined_vars();

        $this->view('admin.recyclable_types', $data);
    }
}