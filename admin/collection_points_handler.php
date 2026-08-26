<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_admin.php';
require_once '../includes/system_functions.php';

header('Content-Type: application/json');

// Function to validate and sanitize input
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to validate coordinates
function validate_coordinates($lat, $lng) {
    // Basic validation for Philippine coordinates
    // Philippines: roughly 4°N to 21°N, 116°E to 127°E
    if ($lat < 4 || $lat > 21 || $lng < 116 || $lng > 127) {
        return false;
    }
    return true;
}

// Function to get all puroks for the form
function get_puroks($conn) {
    try {
        $query = "SELECT purok_id, name FROM puroks ORDER BY name";
        $result = execute_query($conn, $query);
        
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    } catch (Exception $e) {
        error_log("Error fetching puroks: " . $e->getMessage());
        return [];
    }
}

// Function to get all collection points for map display
function get_collection_points($conn) {
    try {
        $query = "SELECT cp.*, p.name as purok_name, 
                  LOWER(cp.status) as status_lower,
                  cp.capacity_kg as capacity
                 FROM collection_points cp 
                 LEFT JOIN puroks p ON cp.purok_id = p.purok_id 
                 ORDER BY cp.name";
        $result = execute_query($conn, $query);
        
        if ($result) {
            $points = $result->fetch_all(MYSQLI_ASSOC);
            // Convert status to lowercase for frontend consistency
            foreach ($points as &$point) {
                $point['status'] = $point['status_lower'];
                unset($point['status_lower']);
            }
            return $points;
        }
        return [];
    } catch (Exception $e) {
        error_log("Error fetching collection points: " . $e->getMessage());
        return [];
    }
}

// Function to get a single collection point by ID
function get_collection_point($conn, $point_id) {
    try {
        $query = "SELECT cp.*, p.name as purok_name,
                  LOWER(cp.status) as status_lower,
                  cp.capacity_kg as capacity
                 FROM collection_points cp 
                 LEFT JOIN puroks p ON cp.purok_id = p.purok_id 
                 WHERE cp.point_id = ?";
        $result = execute_query($conn, $query, [$point_id], 'i');
        
        if ($result && $result->num_rows > 0) {
            $point = $result->fetch_assoc();
            // Convert status to lowercase for frontend consistency
            $point['status'] = $point['status_lower'];
            unset($point['status_lower']);
            return $point;
        }
        return null;
    } catch (Exception $e) {
        error_log("Error fetching collection point: " . $e->getMessage());
        return null;
    }
}

// Function to update a collection point
function update_collection_point($conn, $data) {
    try {
        // Validate required fields
        $required_fields = ['point_id', 'name', 'purok_id', 'latitude', 'longitude', 'status'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }

        // Validate coordinates
        if (!validate_coordinates($data['latitude'], $data['longitude'])) {
            throw new Exception("Invalid coordinates. Please ensure coordinates are within the Philippines.");
        }

        // Validate purok exists
        $purok_check = execute_query($conn, "SELECT purok_id FROM puroks WHERE purok_id = ?", [$data['purok_id']], 'i');
        if (!$purok_check || $purok_check->num_rows === 0) {
            throw new Exception("Invalid purok selected");
        }

        // Check if collection point exists
        $point_check = execute_query($conn, "SELECT point_id FROM collection_points WHERE point_id = ?", [$data['point_id']], 'i');
        if (!$point_check || $point_check->num_rows === 0) {
            throw new Exception("Collection point not found");
        }

        // Check if collection point name already exists in the same purok (excluding current point)
        $name_check = execute_query($conn, 
            "SELECT point_id FROM collection_points WHERE name = ? AND purok_id = ? AND point_id != ?", 
            [$data['name'], $data['purok_id'], $data['point_id']], 
            'sii'
        );
        if ($name_check && $name_check->num_rows > 0) {
            throw new Exception("A collection point with this name already exists in the selected purok");
        }

        // Prepare the update query
        $query = "UPDATE collection_points SET 
            name = ?, purok_id = ?, latitude = ?, longitude = ?, description = ?, 
            status = ?, capacity_kg = ?, contact_person = ?, contact_phone = ?, 
            operating_hours = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
            WHERE point_id = ?";

        // Handle capacity - use 0 if null/empty for binding purposes
        $capacity_value = !empty($data['capacity']) ? (float)$data['capacity'] : 0;

        $params = [
            $data['name'],
            $data['purok_id'],
            $data['latitude'],
            $data['longitude'],
            $data['description'] ?? '',
            ucfirst($data['status']), // Convert to title case to match enum values
            $capacity_value,
            $data['contact_person'] ?? '',
            $data['contact_phone'] ?? '',
            $data['operating_hours'] ?? '24/7',
            $data['notes'] ?? '',
            $data['point_id']
        ];

        $types = 'siddssdssssi';

        $stmt = prepare_statement($conn, $query);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement");
        }

        if (!$stmt->bind_param($types, ...$params)) {
            throw new Exception("Failed to bind parameters");
        }

        if (!$stmt->execute()) {
            throw new Exception("Failed to execute query: " . $stmt->error);
        }

        $stmt->close();

        return [
            'success' => true,
            'message' => 'Collection point updated successfully!'
        ];

    } catch (Exception $e) {
        error_log("Error updating collection point: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Function to delete a collection point
function delete_collection_point($conn, $point_id) {
    try {
        // Check if collection point exists
        $point_check = execute_query($conn, "SELECT point_id, name FROM collection_points WHERE point_id = ?", [$point_id], 'i');
        if (!$point_check || $point_check->num_rows === 0) {
            throw new Exception("Collection point not found");
        }
        
        $point_data = $point_check->fetch_assoc();
        $point_name = $point_data['name'];

        // Check if there are any pickup requests associated with this collection point
        $requests_check = execute_query($conn, "SELECT COUNT(*) as count FROM pickuprequests WHERE point_id = ?", [$point_id], 'i');
        if ($requests_check) {
            $requests_data = $requests_check->fetch_assoc();
            if ($requests_data['count'] > 0) {
                throw new Exception("Cannot delete collection point. There are " . $requests_data['count'] . " pickup requests associated with this location.");
            }
        }

        // Delete the collection point
        $delete_stmt = prepare_statement($conn, "DELETE FROM collection_points WHERE point_id = ?");
        if (!$delete_stmt) {
            throw new Exception("Failed to prepare delete statement");
        }

        if (!$delete_stmt->bind_param('i', $point_id)) {
            throw new Exception("Failed to bind parameters");
        }

        if (!$delete_stmt->execute()) {
            throw new Exception("Failed to execute delete query: " . $delete_stmt->error);
        }

        $delete_stmt->close();

        return [
            'success' => true,
            'message' => "Collection point '$point_name' deleted successfully!"
        ];

    } catch (Exception $e) {
        error_log("Error deleting collection point: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
function add_collection_point($conn, $data) {
    try {
        // Validate required fields
        $required_fields = ['name', 'purok_id', 'latitude', 'longitude', 'status'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }

        // Validate coordinates
        if (!validate_coordinates($data['latitude'], $data['longitude'])) {
            throw new Exception("Invalid coordinates. Please ensure coordinates are within the Philippines.");
        }

        // Validate purok exists
        $purok_check = execute_query($conn, "SELECT purok_id FROM puroks WHERE purok_id = ?", [$data['purok_id']], 'i');
        if (!$purok_check || $purok_check->num_rows === 0) {
            throw new Exception("Invalid purok selected");
        }

        // Check if collection point name already exists in the same purok
        $name_check = execute_query($conn, 
            "SELECT point_id FROM collection_points WHERE name = ? AND purok_id = ?", 
            [$data['name'], $data['purok_id']], 
            'si'
        );
        if ($name_check && $name_check->num_rows > 0) {
            throw new Exception("A collection point with this name already exists in the selected purok");
        }

        // Prepare the insert query
        $query = "INSERT INTO collection_points (
            name, purok_id, latitude, longitude, description, status, 
            capacity_kg, contact_person, contact_phone, operating_hours, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Handle capacity - use 0 if null/empty for binding purposes
        $capacity_value = !empty($data['capacity']) ? (float)$data['capacity'] : 0;

        $params = [
            $data['name'],
            $data['purok_id'],
            $data['latitude'],
            $data['longitude'],
            $data['description'] ?? '',
            ucfirst($data['status']), // Convert to title case to match enum values
            $capacity_value,
            $data['contact_person'] ?? '',
            $data['contact_phone'] ?? '',
            $data['operating_hours'] ?? '24/7',
            $data['notes'] ?? ''
        ];

        $types = 'siddssdssss';

        $stmt = prepare_statement($conn, $query);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement");
        }

        if (!$stmt->bind_param($types, ...$params)) {
            throw new Exception("Failed to bind parameters");
        }

        if (!$stmt->execute()) {
            throw new Exception("Failed to execute query: " . $stmt->error);
        }

        $point_id = $conn->insert_id;
        $stmt->close();

        return [
            'success' => true,
            'message' => 'Collection point added successfully!',
            'point_id' => $point_id
        ];

    } catch (Exception $e) {
        error_log("Error adding collection point: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Handle GET requests for fetching data
            $action = $_GET['action'] ?? '';
            
            switch ($action) {
                case 'get_puroks':
                    echo json_encode([
                        'success' => true,
                        'data' => get_puroks($conn)
                    ]);
                    break;
                    
                case 'get_collection_points':
                    echo json_encode([
                        'success' => true,
                        'data' => get_collection_points($conn)
                    ]);
                    break;
                    
                case 'get_collection_point':
                    $point_id = $_GET['id'] ?? 0;
                    if (!$point_id) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Collection point ID is required'
                        ]);
                        break;
                    }
                    
                    $point = get_collection_point($conn, $point_id);
                    if ($point) {
                        echo json_encode([
                            'success' => true,
                            'data' => $point
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Collection point not found'
                        ]);
                    }
                    break;
                    
                default:
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid action specified'
                    ]);
            }
            break;

        case 'POST':
            // Validate CSRF token from JSON body or form data
            try {
                \App\Core\Csrf::validateRequest();
            } catch (\InvalidArgumentException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid or expired session token.'
                ]);
                exit;
            }

            // Handle POST requests for adding/updating/deleting collection points
            $input = json_decode(file_get_contents('php://input'), true);
            
            // If JSON input is not available, try form data
            if (!$input) {
                $input = $_POST;
            }

            $action = $input['action'] ?? 'create';

            switch ($action) {
                case 'create':
                    // Sanitize input data
                    $data = [];
                    $fields = ['name', 'purok_id', 'latitude', 'longitude', 'description', 
                              'status', 'capacity', 'contact_person', 'contact_phone', 
                              'operating_hours', 'notes'];

                    foreach ($fields as $field) {
                        if (isset($input[$field])) {
                            if ($field === 'purok_id') {
                                $data[$field] = (int)$input[$field];
                            } elseif (in_array($field, ['latitude', 'longitude', 'capacity'])) {
                                $data[$field] = !empty($input[$field]) ? (float)$input[$field] : null;
                            } else {
                                $data[$field] = sanitize_input($input[$field]);
                            }
                        }
                    }

                    $result = add_collection_point($conn, $data);
                    echo json_encode($result);
                    break;

                case 'update':
                    // Sanitize input data
                    $data = [];
                    $fields = ['point_id', 'name', 'purok_id', 'latitude', 'longitude', 'description', 
                              'status', 'capacity', 'contact_person', 'contact_phone', 
                              'operating_hours', 'notes'];

                    foreach ($fields as $field) {
                        if (isset($input[$field])) {
                            if (in_array($field, ['point_id', 'purok_id'])) {
                                $data[$field] = (int)$input[$field];
                            } elseif (in_array($field, ['latitude', 'longitude', 'capacity'])) {
                                $data[$field] = !empty($input[$field]) ? (float)$input[$field] : null;
                            } else {
                                $data[$field] = sanitize_input($input[$field]);
                            }
                        }
                    }

                    $result = update_collection_point($conn, $data);
                    echo json_encode($result);
                    break;

                case 'delete':
                    $point_id = (int)($input['point_id'] ?? 0);
                    if (!$point_id) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Collection point ID is required'
                        ]);
                        break;
                    }

                    $result = delete_collection_point($conn, $point_id);
                    echo json_encode($result);
                    break;

                default:
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid action specified'
                    ]);
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            http_response_code(405);
    }

} catch (Exception $e) {
    error_log("Collection points API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred. Please try again.'
    ]);
    http_response_code(500);
}
?>