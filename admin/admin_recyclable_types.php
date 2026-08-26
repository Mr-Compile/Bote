<?php
require_once '../config/database.php';
require '../includes/auth_admin.php'; // Ensure only admins can access
require_once '../includes/upload_handler.php'; // Include upload handler

// Handle CRUD operations with enhanced error handling and image upload
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        // Validate database connection
        if (!$conn || !check_db_health($conn)) {
            throw new Exception("Database connection error. Please try again.");
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
                    throw new Exception("Name is required.");
                }
                if ($unit === 'kg' && $points_per_kg <= 0) {
                    throw new Exception("Points per kg must be greater than 0.");
                }
                if ($unit === 'pcs' && $points_per_unit <= 0) {
                    throw new Exception("Points per unit must be greater than 0.");
                }

                // Check for duplicate name
                $check_stmt = prepare_statement($conn, "SELECT type_id FROM recyclabletypes WHERE name = ?");
                if (!$check_stmt) {
                    throw new Exception("Database error while checking for duplicates.");
                }
                $check_stmt->bind_param("s", $name);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    throw new Exception("A recyclable type with this name already exists.");
                }

                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $uploader = new BoteBankUploadHandler();
                    $upload_result = $uploader->uploadFile($_FILES['image'], 'recyclables', 'recyclable_' . time());
                    
                    if ($upload_result['success']) {
                        $image_path = $upload_result['path'];
                    } else {
                        throw new Exception("Image upload failed: " . $upload_result['error']);
                    }
                }

                $stmt = prepare_statement($conn, "INSERT INTO recyclabletypes (name, unit, points_per_kg, points_per_unit, image) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Database error while preparing statement.");
                }
                $stmt->bind_param("ssdds", $name, $unit, $points_per_kg, $points_per_unit, $image_path);
                if ($stmt->execute()) {
                    $success_message = "Recyclable type added successfully!";
                    error_log("Admin {$_SESSION['user_id']} added new recyclable type: {$name}");
                } else {
                    throw new Exception("Failed to add recyclable type to database.");
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
                throw new Exception("Invalid type ID.");
            }
            if (empty($name)) {
                throw new Exception("Name is required.");
            }
            if ($unit === 'kg' && $points_per_kg <= 0) {
                throw new Exception("Points per kg must be greater than 0.");
            }
            if ($unit === 'pcs' && $points_per_unit <= 0) {
                throw new Exception("Points per unit must be greater than 0.");
            }

            // Check if recyclable type exists and name is not duplicate (except current)
            $check_stmt = prepare_statement($conn, "SELECT type_id FROM recyclabletypes WHERE name = ? AND type_id != ?");
            if (!$check_stmt) {
                throw new Exception("Database error while checking for duplicates.");
            }
            $check_stmt->bind_param("si", $name, $type_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                throw new Exception("A recyclable type with this name already exists.");
            }

            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploader = new BoteBankUploadHandler();
                
                // Delete old image if it exists
                if ($image_path) {
                    $uploader->deleteFile(basename($image_path), 'recyclables');
                }
                
                $upload_result = $uploader->uploadFile($_FILES['image'], 'recyclables', 'recyclable_' . time());
                
                if ($upload_result['success']) {
                    $image_path = $upload_result['path'];
                } else {
                    throw new Exception("Image upload failed: " . $upload_result['error']);
                }
            }

            $stmt = prepare_statement($conn, "UPDATE recyclabletypes SET name = ?, unit = ?, points_per_kg = ?, points_per_unit = ?, image = ? WHERE type_id = ?");
            if (!$stmt) {
                throw new Exception("Database error while preparing statement.");
            }
            $stmt->bind_param("ssddsi", $name, $unit, $points_per_kg, $points_per_unit, $image_path, $type_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success_message = "Recyclable type updated successfully!";
                error_log("Admin {$_SESSION['user_id']} updated recyclable type: {$type_id}");
            } else {
                throw new Exception("Failed to update recyclable type or no changes made.");
            }
            break;

        case 'delete_type':
            $type_id = (int)$_POST['type_id'];

            if ($type_id <= 0) {
                throw new Exception("Invalid type ID.");
            }

            // Check if recyclable type has active pickup requests
            $check_stmt = prepare_statement($conn, "SELECT COUNT(*) as count FROM pickuprequests WHERE type_id = ? AND status IN ('Pending', 'Scheduled')");
            if (!$check_stmt) {
                throw new Exception("Database error while checking for dependencies.");
            }
            $check_stmt->bind_param("i", $type_id);
            $check_stmt->execute();
            $request_count = $check_stmt->get_result()->fetch_assoc()['count'];

            if ($request_count > 0) {
                throw new Exception("Cannot delete recyclable type with active pickup requests.");
            }

            // Get image path before deletion
            $image_stmt = prepare_statement($conn, "SELECT image FROM recyclabletypes WHERE type_id = ?");
            if ($image_stmt) {
                $image_stmt->bind_param("i", $type_id);
                $image_stmt->execute();
                $result = $image_stmt->get_result();
                $image_path = $result->num_rows > 0 ? $result->fetch_assoc()['image'] : null;
            }

            $stmt = prepare_statement($conn, "DELETE FROM recyclabletypes WHERE type_id = ?");
            if (!$stmt) {
                throw new Exception("Database error while preparing statement.");
            }
            $stmt->bind_param("i", $type_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                // Delete associated image
                if ($image_path) {
                    $uploader = new BoteBankUploadHandler();
                    $uploader->deleteFile(basename($image_path), 'recyclables');
                }
                $success_message = "Recyclable type deleted successfully!";
                error_log("Admin {$_SESSION['user_id']} deleted recyclable type: {$type_id}");
            } else {
                throw new Exception("Failed to delete recyclable type or type not found.");
            }
            break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Recyclable type management error: " . $e->getMessage());
    }
}

// Get all recyclable types with enhanced error handling
try {
    $stmt = prepare_statement($conn, "SELECT * FROM recyclabletypes ORDER BY name");
    if (!$stmt) {
        throw new Exception("Database error while fetching recyclable types.");
    }
    $stmt->execute();
    $recyclable_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
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
    $stmt = prepare_statement($conn, "
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
    
} catch (Exception $e) {
    error_log("Error calculating statistics: " . $e->getMessage());
    $total_types = $avg_points = $active_types = 0;
    $most_used = null;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recyclable Types Management - BoteBank Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="assets/css/recyclable_types.css">
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Enhanced Header -->
            <div class="bg-white rounded-3xl shadow-lg p-8 mb-8 border border-gray-100">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i data-lucide="recycle" class="w-8 h-8 text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Recyclable Types</h1>
                            <p class="text-gray-600 mt-1">Manage recyclable materials and their point values</p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Enhanced Success/Error Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-500 p-6 rounded-2xl mb-8 shadow-lg">
                    <div class="flex items-center">
                        <i data-lucide="check-circle" class="w-6 h-6 text-green-600 mr-3"></i>
                        <p class="text-green-800 font-medium"><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-500 p-6 rounded-2xl mb-8 shadow-lg">
                    <div class="flex items-center">
                        <i data-lucide="x-circle" class="w-6 h-6 text-red-600 mr-3"></i>
                        <p class="text-red-800 font-medium"><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

         <!-- Enhanced Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Types -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $total_types; ?></p>
                        <p class="text-sm text-gray-600 mt-1">Total Types</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-green-100 to-green-200 rounded-2xl flex items-center justify-center">
                        <i data-lucide="layers" class="w-8 h-8 text-green-600"></i>
                    </div>
                </div>
            </div>
            
            <!-- Average Points -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $avg_points; ?></p>
                        <p class="text-sm text-gray-600 mt-1">Avg Points</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-100 to-yellow-200 rounded-2xl flex items-center justify-center">
                        <i data-lucide="star" class="w-8 h-8 text-yellow-600"></i>
                    </div>
                </div>
            </div>
            
            <!-- Types with Images -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $active_types; ?></p>
                        <p class="text-sm text-gray-600 mt-1">With Images</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-blue-200 rounded-2xl flex items-center justify-center">
                        <i data-lucide="image" class="w-8 h-8 text-blue-600"></i>
                    </div>
                </div>
            </div>
            
            <!-- Most Used -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-lg font-bold text-gray-900"><?php echo $most_used ? $most_used['name'] : 'N/A'; ?></p>
                        <p class="text-sm text-gray-600 mt-1">Most Used</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-100 to-purple-200 rounded-2xl flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-8 h-8 text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>


            <!-- Enhanced Recyclable Types Table -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                <div class="bg-gradient-to-r from-green-500 to-green-600 px-8 py-6">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <i data-lucide="recycle" class="w-6 h-6 text-white mr-3"></i>
                            <h2 class="text-xl font-bold text-white">Recyclable Types</h2>
                        </div>
                        <button onclick="showAddTypeModal()" 
                                class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-xl font-medium transition-all transform hover:scale-105 flex items-center backdrop-blur-sm border border-white/30">
                            <i data-lucide="plus-circle" class="w-4 h-4 mr-2"></i>
                            Add Type
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type Name</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Type</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($recyclable_types)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mb-6">
                                                <i data-lucide="package-x" class="w-10 h-10 text-gray-400"></i>
                                            </div>
                                            <h3 class="text-xl font-semibold text-gray-900 mb-3">No recyclable types found</h3>
                                            <p class="text-gray-500 mb-6 max-w-md mx-auto">
                                                Get started by adding your first recyclable type with point values and images.
                                            </p>
                                            <button onclick="showAddTypeModal()" 
                                                    class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-3 rounded-xl font-medium transition-all transform hover:scale-105 flex items-center shadow-lg">
                                                <i data-lucide="plus-circle" class="w-5 h-5 mr-2"></i>
                                                Add Your First Type
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                            <?php foreach ($recyclable_types as $type): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <!-- Image Column -->
                                    <td class="px-6 py-4">
                                        <div class="w-16 h-16 rounded-xl overflow-hidden shadow-md">
                                            <?php if (!empty($type['image'])): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($type['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($type['name']); ?>" 
                                                     class="w-full h-full object-cover hover:scale-110 transition-transform duration-300">
                                            <?php else: ?>
                                                <div class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                                    <i data-lucide="image-off" class="w-6 h-6 text-gray-400"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <!-- Type Name Column -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                                            <div>
                                                <span class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($type['name']); ?></span>
                                                <div class="text-sm text-gray-500 flex items-center mt-1">
                                                    <i data-lucide="hash" class="w-3 h-3 mr-1"></i>
                                                    ID: <?php echo $type['type_id']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Points Column -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <i data-lucide="coins" class="w-4 h-4 text-yellow-500 mr-2"></i>
                                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-gradient-to-r from-green-100 to-green-200 text-green-800">
                                                <?php
                                                    if ($type['unit'] === 'kg') {
                                                        echo number_format((float)$type['points_per_kg'], 2) . " pts/kg";
                                                    } else {
                                                        echo number_format((float)$type['points_per_unit'], 2) . " pts/pc";
                                                    }
                                                ?>
                                            </span>
                                        </div>
                                    </td>
                                    <!-- Unit Type Column -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center text-sm text-gray-900 max-w-xs">
                                            <?php if ($type['unit'] === 'kg'): ?>
                                                <i data-lucide="weight" class="w-4 h-4 text-blue-500 mr-2"></i>
                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Weight-based</span>
                                            <?php else: ?>
                                                <i data-lucide="package" class="w-4 h-4 text-purple-500 mr-2"></i>
                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">Count-based</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <!-- Actions Column -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <button onclick="showEditTypeModal(<?php echo htmlspecialchars(json_encode($type)); ?>)" 
                                                    class="inline-flex items-center px-3 py-1 bg-gradient-to-r from-indigo-100 to-indigo-200 hover:from-indigo-200 hover:to-indigo-300 text-indigo-700 rounded-lg text-sm font-medium transition-all transform hover:scale-105">
                                                <i data-lucide="edit-3" class="w-4 h-4 mr-1"></i>
                                                Edit
                                            </button>
                                            <button onclick="confirmDeleteType(<?php echo $type['type_id']; ?>)" 
                                                    class="inline-flex items-center px-3 py-1 bg-gradient-to-r from-red-100 to-red-200 hover:from-red-200 hover:to-red-300 text-red-700 rounded-lg text-sm font-medium transition-all transform hover:scale-105">
                                                <i data-lucide="trash-2" class="w-4 h-4 mr-1"></i>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

                                    <td class="px-6 py-4">
                                        <div class="flex items-center text-sm text-gray-900 max-w-xs">
                                            <?php if ($type['unit'] === 'kg'): ?>
                                                <i data-lucide="weight" class="w-4 h-4 text-blue-500 mr-2"></i>
                                                <span>Weight-based material</span>
                                            <?php else: ?>
                                                <i data-lucide="package" class="w-4 h-4 text-purple-500 mr-2"></i>
                                                <span>Count-based material</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <button onclick="showEditTypeModal(<?php echo htmlspecialchars(json_encode($type)); ?>)" 
                                                    class="inline-flex items-center px-3 py-1 bg-gradient-to-r from-indigo-100 to-indigo-200 hover:from-indigo-200 hover:to-indigo-300 text-indigo-700 rounded-lg text-sm font-medium transition-all transform hover:scale-105">
                                                <i data-lucide="edit-3" class="w-4 h-4 mr-1"></i>
                                                Edit
                                            </button>
                                            <button onclick="confirmDeleteType(<?php echo $type['type_id']; ?>)" 
                                                    class="inline-flex items-center px-3 py-1 bg-gradient-to-r from-red-100 to-red-200 hover:from-red-200 hover:to-red-300 text-red-700 rounded-lg text-sm font-medium transition-all transform hover:scale-105">
                                                <i data-lucide="trash-2" class="w-4 h-4 mr-1"></i>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Add Type Modal -->
    <div id="addTypeModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300 modal-enter">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-t-3xl px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i data-lucide="plus-circle" class="w-6 h-6 text-white mr-3"></i>
                        <h3 class="text-xl font-bold text-white">Add New Recyclable Type</h3>
                    </div>
                    <button onclick="hideAddTypeModal()" class="text-white hover:text-gray-200 transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-8">
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="action" value="add_type">
                    
                    <!-- Image Upload Section -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 mb-3 flex items-center">
                            <i data-lucide="image" class="w-4 h-4 mr-2 text-gray-500"></i>
                            Recyclable Type Image
                        </label>
                        <div class="file-drop-area p-6 border-2 border-dashed border-gray-300 rounded-xl text-center hover:border-green-500 transition-colors cursor-pointer" 
                             onclick="document.getElementById('add_image').click()">
                            <div id="add_image_preview" class="hidden">
                                <img id="add_preview_img" src="" alt="Preview" class="w-24 h-24 object-cover rounded-lg mx-auto mb-3">
                                <p class="text-sm text-gray-600">Click to change image</p>
                            </div>
                            <div id="add_image_placeholder">
                                <i data-lucide="upload" class="w-8 h-8 text-gray-400 mx-auto mb-3"></i>
                                <p class="text-sm text-gray-600 mb-1">Click to upload an image</p>
                                <p class="text-xs text-gray-500">PNG, JPG, GIF up to 5MB</p>
                            </div>
                        </div>
                        <input type="file" id="add_image" name="image" accept="image/*" class="hidden" onchange="previewAddImage(this)">
                    </div>
                
                    <!-- Type Name -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 flex items-center">
                            <i data-lucide="tag" class="w-4 h-4 mr-2 text-gray-500"></i>
                            Type Name
                        </label>
                        <input type="text" name="name" required placeholder="e.g., Plastic Bottles, Paper, Glass"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                    </div>
                    
                    <!-- Unit Selection -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 flex items-center">
                            <i data-lucide="ruler" class="w-4 h-4 mr-2 text-gray-500"></i>
                            Unit Type
                        </label>
                        <select name="unit" id="unit" required 
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                            <option value="kg">Weight-based (kg)</option>
                            <option value="pcs">Count-based (pieces)</option>
                        </select>
                    </div>

                    <!-- Points per KG -->
                    <div class="space-y-2" id="points_kg_field">
                        <label class="block text-sm font-medium text-gray-700 flex items-center">
                            <i data-lucide="coins" class="w-4 h-4 mr-2 text-gray-500"></i>
                            Points per Kilogram
                        </label>
                        <input type="number" name="points_per_kg" step="0.1" min="0" placeholder="10.0"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                    </div>

                    <!-- Points per Unit -->
                    <div class="space-y-2 hidden" id="points_unit_field">
                        <label class="block text-sm font-medium text-gray-700 flex items-center">
                            <i data-lucide="coins" class="w-4 h-4 mr-2 text-gray-500"></i>
                            Points per Piece
                        </label>
                        <input type="number" name="points_per_unit" step="0.01" min="0" placeholder="1.50"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex space-x-4 pt-4">
                        <button type="button" onclick="hideAddTypeModal()" 
                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-medium transition-all">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white py-3 rounded-xl font-medium transition-all transform hover:scale-105 shadow-lg">
                            Add Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Enhanced Edit Type Modal -->
    <div id="editTypeModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300 modal-enter">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-t-3xl px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i data-lucide="edit-3" class="w-6 h-6 text-white mr-3"></i>
                        <h3 class="text-xl font-bold text-white">Edit Recyclable Type</h3>
                    </div>
                    <button onclick="hideEditTypeModal()" class="text-white hover:text-gray-200 transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-8">
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="action" value="update_type">
                    <input type="hidden" name="type_id" id="edit_type_id">
                    <input type="hidden" name="current_image" id="edit_current_image">
                    
                    <!-- Image Upload Section -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 mb-3 flex items-center">
                            <i data-lucide="image" class="w-4 h-4 mr-2 text-gray-500"></i>
                            Update Image
                        </label>
                        <div class="file-drop-area p-6 border-2 border-dashed border-gray-300 rounded-xl text-center hover:border-blue-500 transition-colors cursor-pointer" 
                             onclick="document.getElementById('edit_image').click()">
                            <div id="edit_image_preview" class="hidden">
                                <img id="edit_preview_img" src="" alt="Preview" class="w-24 h-24 object-cover rounded-lg mx-auto mb-3">
                                <p class="text-sm text-gray-600">Click to change image</p>
                            </div>
                            <div id="edit_image_placeholder">
                                <i data-lucide="upload" class="w-8 h-8 text-gray-400 mx-auto mb-3"></i>
                                <p class="text-sm text-gray-600 mb-1">Click to upload new image</p>
                                <p class="text-xs text-gray-500">PNG, JPG, GIF up to 5MB</p>
                            </div>
                        </div>
                        <input type="file" id="edit_image" name="image" accept="image/*" class="hidden" onchange="previewEditImage(this)">
                    </div>
                    
                    <!-- Type Name -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 flex items-center">
                            <i data-lucide="tag" class="w-4 h-4 mr-2 text-gray-500"></i>
                            Type Name
                        </label>
                        <input type="text" name="name" id="edit_name" required placeholder="e.g., Plastic Bottles, Paper, Glass"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    </div>
                    
                    <!-- Unit Selection -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 flex items-center">
                            <i data-lucide="ruler" class="w-4 h-4 mr-2 text-gray-500"></i>
                            Unit Type
                        </label>
                        <select name="unit" id="edit_unit" required 
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <option value="kg">Weight-based (kg)</option>
                            <option value="pcs">Count-based (pieces)</option>
                        </select>
                    </div>

                    <!-- Points per KG -->
                    <div class="space-y-2" id="edit_points_kg_field">
                        <label class="block text-sm font-medium text-gray-700 flex items-center">
                            <i data-lucide="coins" class="w-4 h-4 mr-2 text-gray-500"></i>
                            Points per Kilogram
                        </label>
                        <input type="number" name="points_per_kg" id="edit_points_per_kg" step="0.1" min="0" placeholder="10.0"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    </div>

                    <!-- Points per Unit -->
                    <div class="space-y-2 hidden" id="edit_points_unit_field">
                        <label class="block text-sm font-medium text-gray-700 flex items-center">
                            <i data-lucide="coins" class="w-4 h-4 mr-2 text-gray-500"></i>
                            Points per Piece
                        </label>
                        <input type="number" name="points_per_unit" id="edit_points_per_unit" step="0.01" min="0" placeholder="1.50"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex space-x-4 pt-4">
                        <button type="button" onclick="hideEditTypeModal()" 
                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-medium transition-all">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white py-3 rounded-xl font-medium transition-all transform hover:scale-105 shadow-lg">
                            Update Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Enhanced Delete Confirmation Modal -->
    <div id="deleteTypeModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full mx-4 transform transition-all duration-300 modal-enter">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-t-3xl px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i data-lucide="trash-2" class="w-6 h-6 text-white mr-3"></i>
                        <h3 class="text-xl font-bold text-white">Delete Recyclable Type</h3>
                    </div>
                    <button onclick="hideDeleteTypeModal()" class="text-white hover:text-gray-200 transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="trash-2" class="w-10 h-10 text-red-600"></i>
                </div>
                
                <h4 class="text-xl font-bold text-gray-900 mb-3">Delete this Recyclable Type?</h4>
                <p class="text-gray-600 mb-8 leading-relaxed">
                    This action cannot be undone. The recyclable type and its associated image will be permanently removed.
                </p>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="delete_type">
                    <input type="hidden" name="type_id" id="delete_type_id">
                    
                    <div class="flex space-x-4">
                        <button type="button" onclick="hideDeleteTypeModal()" 
                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-medium transition-all">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white py-3 rounded-xl font-medium transition-all transform hover:scale-105 shadow-lg">
                            Delete Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="assets/js/recyclable_types.js"></script>

</body>
</html>
