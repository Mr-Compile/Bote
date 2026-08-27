<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\TypeCaster;
use App\Http\Controllers\BaseController;

final class PuroksController extends BaseController
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
        if (!$this->checkDbHealth()) {
            die("Database connection issue. Please try again later.");
        }
        
        // Handle purok operations with enhanced error handling
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action'])) {
                try {
                    switch ($_POST['action']) {
                        case 'add_purok':
                            $name = trim($_POST['name']);
                            $description = trim($_POST['description']);
                            
                            // Enhanced validation
                            if (empty($name)) {
                                throw new \Exception("Purok name is required.");
                            }
                            
                            if (strlen($name) > 100) {
                                throw new \Exception("Purok name cannot exceed 100 characters.");
                            }
                            
                            // Check if purok name already exists
                            $stmt = $this->legacy()->prepare("SELECT purok_id FROM puroks WHERE name = ?", ['context' => 'check_purok_exists']);
                            if (!$stmt) {
                                throw new \Exception("Database error occurred.");
                            }
                            
                            $stmt->bind_param("s", $name);
                            $stmt->execute();
                            
                            if ($stmt->get_result()->num_rows > 0) {
                                $error_message = "Purok name already exists!";
                            } else {
                                // Insert new purok
                                $stmt = $this->legacy()->prepare("INSERT INTO puroks (name, description) VALUES (?, ?)");
                                if (!$stmt) {
                                    throw new \Exception("Database error occurred.");
                                }
                                
                                $stmt->bind_param("ss", $name, $description);
                                if ($stmt->execute()) {
                                    $success_message = "Purok added successfully!";
                                    error_log("Admin {$_SESSION['user_id']} added new purok: {$name}");
                                } else {
                                    throw new \Exception("Failed to add purok.");
                                }
                            }
                            break;
                            
                        case 'edit_purok':
                            $purok_id = (int)$_POST['purok_id'];
                            $name = trim($_POST['name']);
                            $description = trim($_POST['description']);
                            
                            // Enhanced validation
                            if ($purok_id <= 0) {
                                throw new \Exception("Invalid purok ID.");
                            }
                            
                            if (empty($name)) {
                                throw new \Exception("Purok name is required.");
                            }
                            
                            if (strlen($name) > 100) {
                                throw new \Exception("Purok name cannot exceed 100 characters.");
                            }
                            
                            // Check if purok name already exists (excluding current purok)
                            $stmt = $this->legacy()->prepare("SELECT purok_id FROM puroks WHERE name = ? AND purok_id != ?");
                            if (!$stmt) {
                                throw new \Exception("Database error occurred.");
                            }
                            
                            $stmt->bind_param("si", $name, $purok_id);
                            $stmt->execute();
                            
                            if ($stmt->get_result()->num_rows > 0) {
                                $error_message = "Purok name already exists!";
                            } else {
                                // Update purok
                                $stmt = $this->legacy()->prepare("UPDATE puroks SET name = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE purok_id = ?");
                                if (!$stmt) {
                                    throw new \Exception("Database error occurred.");
                                }
                                
                                $stmt->bind_param("ssi", $name, $description, $purok_id);
                                if ($stmt->execute() && $stmt->affected_rows > 0) {
                                    $success_message = "Purok updated successfully!";
                                    error_log("Admin {$_SESSION['user_id']} updated purok {$purok_id}: {$name}");
                                } else {
                                    $error_message = "Failed to update purok or no changes made.";
                                }
                            }
                            break;
                            
                        case 'delete_purok':
                            $purok_id = (int)$_POST['purok_id'];
                            
                            if ($purok_id <= 0) {
                                throw new \Exception("Invalid purok ID.");
                            }
                            
                            // Check if purok has any users assigned
                            $stmt = $this->legacy()->prepare("SELECT COUNT(*) as count FROM users WHERE purok_id = ?");
                            if (!$stmt) {
                                throw new \Exception("Database error occurred.");
                            }
                            
                            $stmt->bind_param("i", $purok_id);
                            $stmt->execute();
                            $user_count = $stmt->get_result()->fetch_assoc()['count'];
                            
                            if ($user_count > 0) {
                                $error_message = "Cannot delete purok. There are $user_count users assigned to this purok.";
                            } else {
                                $stmt = $this->legacy()->prepare("DELETE FROM puroks WHERE purok_id = ?");
                                if (!$stmt) {
                                    throw new \Exception("Database error occurred.");
                                }
                                
                                $stmt->bind_param("i", $purok_id);
                                if ($stmt->execute() && $stmt->affected_rows > 0) {
                                    $success_message = "Purok deleted successfully!";
                                    error_log("Admin {$_SESSION['user_id']} deleted purok: {$purok_id}");
                                } else {
                                    $error_message = "Failed to delete purok or purok not found.";
                                }
                            }
                            break;
                    }
                } catch (\Exception $e) {
                    $error_message = $e->getMessage();
                    error_log("Purok management error: " . $e->getMessage());
                }
            }
        }
        
        // Get all puroks with user counts and enhanced error handling
        try {
            $stmt = $this->legacy()->prepare("
                SELECT p.*, COUNT(u.user_id) as user_count 
                FROM puroks p 
                LEFT JOIN users u ON p.purok_id = u.purok_id 
                GROUP BY p.purok_id 
                ORDER BY p.name ASC
            ", ['context' => 'get_all_puroks']);
            
            if ($stmt) {
                $stmt->execute();
                $puroks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else {
                $puroks = [];
                $error_message = "Failed to load purok data. Please refresh the page.";
            }
        
            // Get purok statistics
            $total_puroks = count($puroks);
            
            $stmt = $this->legacy()->prepare("SELECT COUNT(DISTINCT purok_id) as active_puroks FROM users WHERE purok_id IS NOT NULL");
            if ($stmt) {
                $stmt->execute();
                $active_puroks = $stmt->get_result()->fetch_assoc()['active_puroks'];
            } else {
                $active_puroks = 0;
            }
        
            $stmt = $this->legacy()->prepare("SELECT COUNT(*) as total_residents FROM users WHERE role = 'resident' AND purok_id IS NOT NULL");
            if ($stmt) {
                $stmt->execute();
                $total_residents = $stmt->get_result()->fetch_assoc()['total_residents'];
            } else {
                $total_residents = 0;
            }
            
            // Get recent purok activity
            $stmt = $this->legacy()->prepare("SELECT COUNT(*) as new_puroks FROM puroks WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
            if ($stmt) {
                $stmt->execute();
                $new_puroks_month = $stmt->get_result()->fetch_assoc()['new_puroks'];
            } else {
                $new_puroks_month = 0;
            }
        
        } catch (\Exception $e) {
            error_log("Error fetching puroks: " . $e->getMessage());
            $puroks = [];
            $total_puroks = 0;
            $active_puroks = 0;
            $total_residents = 0;
            $new_puroks_month = 0;
            $error_message = "Failed to load purok data. Please refresh the page.";
        }

        $data = get_defined_vars();

        $this->view('admin.puroks', $data);
    }
}