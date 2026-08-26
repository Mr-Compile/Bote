<?php
require_once '../config/database.php';
require '../includes/auth_res.php'; // Ensure only resident can access
require '../includes/sms_notifications.php'; // Include SMS notification system

try {
    // Validate required fields
    if (!isset($_POST['type_id']) || !isset($_POST['weight_kg']) || !isset($_POST['preferred_date'])) {
        throw new Exception("Missing required fields");
    }

    $resident_id = $_SESSION['user_id'];
    $type_id = (int)$_POST['type_id'];
    $weight_kg = isset($_POST['weight_kg']) && $_POST['weight_kg'] !== '' ? floatval($_POST['weight_kg']) : 0;
    $quantity_pcs = isset($_POST['quantity_pcs']) && $_POST['quantity_pcs'] !== '' ? (int)$_POST['quantity_pcs'] : null;
    $pickup_type = isset($_POST['pickup_type']) ? $_POST['pickup_type'] : 'Home';
    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? floatval($_POST['latitude']) : null;
    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? floatval($_POST['longitude']) : null;
    $point_id = isset($_POST['point_id']) && $_POST['point_id'] !== '' ? (int)$_POST['point_id'] : null;
    $preferred_date = $_POST['preferred_date'];

    // Get material type info to determine validation rules
    $type_stmt = $conn->prepare("SELECT unit FROM recyclabletypes WHERE type_id = ?");
    $type_stmt->bind_param("i", $type_id);
    $type_stmt->execute();
    $type_result = $type_stmt->get_result();
    $material_type = $type_result->fetch_assoc();
    $type_stmt->close();
    
    if (!$material_type) {
        throw new Exception("Invalid recyclable type selected");
    }
    
    // Validate data based on material unit
    if ($material_type['unit'] === 'kg') {
        if ($weight_kg <= 0) {
            throw new Exception("Weight must be greater than 0");
        }
        if ($weight_kg < 10) {
            throw new Exception("Minimum weight requirement is 10 kilograms");
        }
    } elseif ($material_type['unit'] === 'pcs') {
        if (!$quantity_pcs || $quantity_pcs <= 0) {
            throw new Exception("Quantity must be greater than 0");
        }
        if ($quantity_pcs < 30) {
            throw new Exception("Minimum quantity requirement is 30 pieces");
        }
        // For pieces-based materials, set weight to 0.01 to satisfy database constraint
        $weight_kg = 0.01;
    }


    // Validate pickup type specific requirements
    if ($pickup_type === 'CollectionPoint') {
        if (!$point_id) {
            throw new Exception("Collection point must be selected for collection point pickup");
        }
        // For collection point pickup, get coordinates from collection_points table
        $point_stmt = $conn->prepare("SELECT latitude, longitude FROM collection_points WHERE point_id = ?");
        $point_stmt->bind_param("i", $point_id);
        $point_stmt->execute();
        $point_result = $point_stmt->get_result();
        
        if ($point_coords = $point_result->fetch_assoc()) {
            $latitude = floatval($point_coords['latitude']);
            $longitude = floatval($point_coords['longitude']);
        } else {
            throw new Exception("Invalid collection point selected");
        }
        $point_stmt->close();
        
        // Clear any GPS coordinates from form for collection point pickup
        // We use collection point coordinates instead
        
    } elseif ($pickup_type === 'Home') {
        if (!$latitude || !$longitude) {
            throw new Exception("GPS location is required for home pickup");
        }
        // For home pickup, point_id should be null
        $point_id = null;
    } else {
        throw new Exception("Invalid pickup type selected");
    }

    // Prepare SQL statement
    $sql = "INSERT INTO pickuprequests 
            (resident_id, type_id, weight_kg, quantity_pcs, preferred_date, pickup_type, latitude, longitude, point_id) 
            VALUES (?,?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param("iiddssddi", 
        $resident_id, $type_id, $weight_kg, $quantity_pcs, 
        $preferred_date, $pickup_type, $latitude, $longitude, $point_id
    );

    if ($stmt->execute()) {
        $request_id = $conn->insert_id; // Get the ID of the inserted request
        $stmt->close();
        
        // Send SMS confirmation to resident
        sendPickupConfirmationSMS($request_id);
        
        header("Location: resident_my_requests.php?msg=" . urlencode("Request submitted successfully! We'll contact you within 24 hours."));
        exit();
    } else {
        throw new Exception("Failed to submit request: " . $stmt->error);
    }

} catch (Exception $e) {
    error_log("Error in resident_save_request.php: " . $e->getMessage());
    header("Location: resident_new_request.php?error=" . urlencode($e->getMessage()));
    exit();
}
