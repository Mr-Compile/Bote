<?php
require_once '../config/database.php';
require '../includes/auth_admin.php'; // Ensure only admins can access
require_once '../includes/system_functions.php';

// Check database health before proceeding
if (!check_db_health($conn)) {
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
                        throw new Exception("Purok name is required.");
                    }
                    
                    if (strlen($name) > 100) {
                        throw new Exception("Purok name cannot exceed 100 characters.");
                    }
                    
                    // Check if purok name already exists
                    $stmt = prepare_statement($conn, "SELECT purok_id FROM puroks WHERE name = ?", ['context' => 'check_purok_exists']);
                    if (!$stmt) {
                        throw new Exception("Database error occurred.");
                    }
                    
                    $stmt->bind_param("s", $name);
                    $stmt->execute();
                    
                    if ($stmt->get_result()->num_rows > 0) {
                        $error_message = "Purok name already exists!";
                    } else {
                        // Insert new purok
                        $stmt = prepare_statement($conn, "INSERT INTO puroks (name, description) VALUES (?, ?)");
                        if (!$stmt) {
                            throw new Exception("Database error occurred.");
                        }
                        
                        $stmt->bind_param("ss", $name, $description);
                        if ($stmt->execute()) {
                            $success_message = "Purok added successfully!";
                            error_log("Admin {$_SESSION['user_id']} added new purok: {$name}");
                        } else {
                            throw new Exception("Failed to add purok.");
                        }
                    }
                    break;
                    
                case 'edit_purok':
                    $purok_id = (int)$_POST['purok_id'];
                    $name = trim($_POST['name']);
                    $description = trim($_POST['description']);
                    
                    // Enhanced validation
                    if ($purok_id <= 0) {
                        throw new Exception("Invalid purok ID.");
                    }
                    
                    if (empty($name)) {
                        throw new Exception("Purok name is required.");
                    }
                    
                    if (strlen($name) > 100) {
                        throw new Exception("Purok name cannot exceed 100 characters.");
                    }
                    
                    // Check if purok name already exists (excluding current purok)
                    $stmt = prepare_statement($conn, "SELECT purok_id FROM puroks WHERE name = ? AND purok_id != ?");
                    if (!$stmt) {
                        throw new Exception("Database error occurred.");
                    }
                    
                    $stmt->bind_param("si", $name, $purok_id);
                    $stmt->execute();
                    
                    if ($stmt->get_result()->num_rows > 0) {
                        $error_message = "Purok name already exists!";
                    } else {
                        // Update purok
                        $stmt = prepare_statement($conn, "UPDATE puroks SET name = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE purok_id = ?");
                        if (!$stmt) {
                            throw new Exception("Database error occurred.");
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
                        throw new Exception("Invalid purok ID.");
                    }
                    
                    // Check if purok has any users assigned
                    $stmt = prepare_statement($conn, "SELECT COUNT(*) as count FROM users WHERE purok_id = ?");
                    if (!$stmt) {
                        throw new Exception("Database error occurred.");
                    }
                    
                    $stmt->bind_param("i", $purok_id);
                    $stmt->execute();
                    $user_count = $stmt->get_result()->fetch_assoc()['count'];
                    
                    if ($user_count > 0) {
                        $error_message = "Cannot delete purok. There are $user_count users assigned to this purok.";
                    } else {
                        $stmt = prepare_statement($conn, "DELETE FROM puroks WHERE purok_id = ?");
                        if (!$stmt) {
                            throw new Exception("Database error occurred.");
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
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            error_log("Purok management error: " . $e->getMessage());
        }
    }
}

// Get all puroks with user counts and enhanced error handling
try {
    $stmt = prepare_statement($conn, "
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
    
    $stmt = prepare_statement($conn, "SELECT COUNT(DISTINCT purok_id) as active_puroks FROM users WHERE purok_id IS NOT NULL");
    if ($stmt) {
        $stmt->execute();
        $active_puroks = $stmt->get_result()->fetch_assoc()['active_puroks'];
    } else {
        $active_puroks = 0;
    }

    $stmt = prepare_statement($conn, "SELECT COUNT(*) as total_residents FROM users WHERE role = 'resident' AND purok_id IS NOT NULL");
    if ($stmt) {
        $stmt->execute();
        $total_residents = $stmt->get_result()->fetch_assoc()['total_residents'];
    } else {
        $total_residents = 0;
    }
    
    // Get recent purok activity
    $stmt = prepare_statement($conn, "SELECT COUNT(*) as new_puroks FROM puroks WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    if ($stmt) {
        $stmt->execute();
        $new_puroks_month = $stmt->get_result()->fetch_assoc()['new_puroks'];
    } else {
        $new_puroks_month = 0;
    }

} catch (Exception $e) {
    error_log("Error fetching puroks: " . $e->getMessage());
    $puroks = [];
    $total_puroks = 0;
    $active_puroks = 0;
    $total_residents = 0;
    $new_puroks_month = 0;
    $error_message = "Failed to load purok data. Please refresh the page.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purok Management - BoteBank Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="assets/css/purok.css" >
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Enhanced Header -->
            <div class="mb-8">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center shadow-lg">
                        <i data-lucide="map-pin" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Purok Management</h1>
                        <p class="text-gray-600 flex items-center">
                            <i data-lucide="home" class="w-4 h-4 mr-2"></i>
                            Manage purok areas and their assignments
                        </p>
                    </div>
                </div>
                
                <!-- Breadcrumb -->
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="admin_dashboard.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                                <i data-lucide="home" class="w-4 h-4 mr-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400"></i>
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Purok Management</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>

            <!-- Enhanced Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="bg-gradient-to-r from-green-400 to-green-500 border-0 text-white px-6 py-4 rounded-xl mb-6 shadow-lg notification flex items-center">
                    <i data-lucide="check-circle" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium"><?php echo htmlspecialchars($success_message); ?></span>
                    <button onclick="this.parentElement.style.display='none'" class="ml-auto">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-gradient-to-r from-red-400 to-red-500 border-0 text-white px-6 py-4 rounded-xl mb-6 shadow-lg notification flex items-center">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium"><?php echo htmlspecialchars($error_message); ?></span>
                    <button onclick="this.parentElement.style.display='none'" class="ml-auto">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Enhanced Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Total Puroks -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="map" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-gray-900"><?php echo $total_puroks; ?></p>
                                <p class="text-sm text-gray-600 flex items-center">
                                    <i data-lucide="layers" class="w-3 h-3 mr-1"></i>
                                    Total Puroks
                                </p>
                            </div>
                        </div>
                        <div class="text-blue-500">
                            <i data-lucide="trending-up" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Active Puroks -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-gray-900"><?php echo $active_puroks; ?></p>
                                <p class="text-sm text-gray-600 flex items-center">
                                    <i data-lucide="activity" class="w-3 h-3 mr-1"></i>
                                    Active Puroks
                                </p>
                            </div>
                        </div>
                        <div class="text-green-500">
                            <i data-lucide="zap" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Total Residents -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="users" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-gray-900"><?php echo $total_residents; ?></p>
                                <p class="text-sm text-gray-600 flex items-center">
                                    <i data-lucide="user-check" class="w-3 h-3 mr-1"></i>
                                    Total Residents
                                </p>
                            </div>
                        </div>
                        <div class="text-purple-500">
                            <i data-lucide="user-plus" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Newest Purok -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="plus-circle" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-gray-900"><?php echo $new_puroks_month; ?></p>
                                <p class="text-sm text-gray-600 flex items-center">
                                    <i data-lucide="calendar" class="w-3 h-3 mr-1"></i>
                                    New (30 days)
                                    <?php if ($new_puroks_month > 0): ?>
                                        <span class="ml-2 px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full badge-pulse">New</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="text-orange-500">
                            <i data-lucide="calendar-plus" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Puroks Table -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mr-3">
                                <i data-lucide="map" class="w-4 h-4 text-white"></i>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900">Purok Management</h2>
                        </div>
                        <button onclick="showAddPurokModal()" 
                                class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-2.5 rounded-xl font-medium transition-all duration-300 flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                            Add New Purok
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <i data-lucide="map-pin" class="w-3 h-3 mr-2"></i>
                                        Purok Info
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <i data-lucide="file-text" class="w-3 h-3 mr-2"></i>
                                        Description
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <i data-lucide="users" class="w-3 h-3 mr-2"></i>
                                        Residents
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <i data-lucide="calendar" class="w-3 h-3 mr-2"></i>
                                        Created
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <i data-lucide="settings" class="w-3 h-3 mr-2"></i>
                                        Actions
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php foreach ($puroks as $purok): ?>
                                <tr class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                                                <span class="text-white font-bold text-sm">
                                                    <?php echo strtoupper(substr($purok['name'], 0, 2)); ?>
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-semibold text-gray-900 flex items-center">
                                                    <i data-lucide="map-pin" class="w-3 h-3 mr-1 text-blue-500"></i>
                                                    <?php echo htmlspecialchars($purok['name']); ?>
                                                </div>
                                                <div class="text-xs text-gray-500 flex items-center mt-1">
                                                    <i data-lucide="hash" class="w-2 h-2 mr-1"></i>
                                                    ID: <?php echo $purok['purok_id']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs">
                                            <?php echo htmlspecialchars($purok['description'] ?: 'No description available'); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full shadow-sm
                                                <?php echo $purok['user_count'] > 0 ? 'bg-gradient-to-r from-green-100 to-green-200 text-green-800 border border-green-300' : 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-600 border border-gray-300'; ?>">
                                                <i data-lucide="users" class="w-3 h-3 mr-1"></i>
                                                <?php echo $purok['user_count']; ?> resident<?php echo $purok['user_count'] != 1 ? 's' : ''; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center text-sm text-gray-500">
                                            <i data-lucide="calendar" class="w-3 h-3 mr-1"></i>
                                            <?php echo date('M j, Y', strtotime($purok['created_at'])); ?>
                                        </div>
                                        <div class="text-xs text-gray-400 mt-1">
                                            <?php echo date('g:i A', strtotime($purok['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-2">
                                            <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($purok)); ?>)" 
                                                    class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white text-xs font-medium rounded-lg transition-all duration-200 transform hover:scale-105 shadow-sm hover:shadow-md">
                                                <i data-lucide="edit" class="w-3 h-3 mr-1"></i>
                                                Edit
                                            </button>
                                            <?php if ($purok['user_count'] == 0): ?>
                                                <button onclick="confirmDelete(<?php echo $purok['purok_id']; ?>)" 
                                                        class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white text-xs font-medium rounded-lg transition-all duration-200 transform hover:scale-105 shadow-sm hover:shadow-md">
                                                    <i data-lucide="trash-2" class="w-3 h-3 mr-1"></i>
                                                    Delete
                                                </button>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-500 text-xs font-medium rounded-lg cursor-not-allowed" 
                                                      title="Cannot delete purok with residents">
                                                    <i data-lucide="lock" class="w-3 h-3 mr-1"></i>
                                                    Protected
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Add Purok Modal -->
    <div id="addPurokModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300 modal-enter">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-t-3xl px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                            <i data-lucide="plus-circle" class="w-5 h-5 text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Add New Purok</h3>
                    </div>
                    <button onclick="hideAddPurokModal()" class="text-white hover:text-gray-200 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-8">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="add_purok">
                    
                    <div class="space-y-2">
                        <label class="flex items-center text-sm font-semibold text-gray-700">
                            <i data-lucide="map-pin" class="w-4 h-4 mr-2 text-green-500"></i>
                            Purok Name
                        </label>
                        <input type="text" name="name" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 hover:border-gray-400"
                               placeholder="e.g. Purok 1 - Mabini">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="flex items-center text-sm font-semibold text-gray-700">
                            <i data-lucide="file-text" class="w-4 h-4 mr-2 text-green-500"></i>
                            Description
                        </label>
                        <textarea name="description" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 hover:border-gray-400 resize-none"
                                  placeholder="Optional description of the purok area"></textarea>
                    </div>
                    
                    <div class="flex space-x-4 pt-4">
                        <button type="button" onclick="hideAddPurokModal()" 
                                class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-all duration-200 flex items-center justify-center">
                            <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-xl font-semibold transition-all duration-200 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                            Add Purok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Enhanced Edit Purok Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300 modal-enter">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-t-3xl px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                            <i data-lucide="edit" class="w-5 h-5 text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Edit Purok</h3>
                    </div>
                    <button onclick="hideEditModal()" class="text-white hover:text-gray-200 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-8">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="edit_purok">
                    <input type="hidden" name="purok_id" id="edit_purok_id">
                    
                    <div class="space-y-2">
                        <label class="flex items-center text-sm font-semibold text-gray-700">
                            <i data-lucide="map-pin" class="w-4 h-4 mr-2 text-blue-500"></i>
                            Purok Name
                        </label>
                        <input type="text" name="name" id="edit_name" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 hover:border-gray-400">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="flex items-center text-sm font-semibold text-gray-700">
                            <i data-lucide="file-text" class="w-4 h-4 mr-2 text-blue-500"></i>
                            Description
                        </label>
                        <textarea name="description" id="edit_description" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 hover:border-gray-400 resize-none"></textarea>
                    </div>
                    
                    <div class="flex space-x-4 pt-4">
                        <button type="button" onclick="hideEditModal()" 
                                class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-all duration-200 flex items-center justify-center">
                            <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl font-semibold transition-all duration-200 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                            Update Purok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Enhanced Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full mx-4 transform transition-all duration-300 modal-enter">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-t-3xl px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Delete Confirmation</h3>
                    </div>
                    <button onclick="hideDeleteModal()" class="text-white hover:text-gray-200 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="trash-2" class="w-10 h-10 text-red-600"></i>
                </div>
                
                <h4 class="text-xl font-bold text-gray-900 mb-3">Delete this Purok?</h4>
                <p class="text-gray-600 mb-8 leading-relaxed">
                    This action cannot be undone. The purok will be permanently removed from the system.
                    <br><br>
                    <span class="font-semibold text-red-600">⚠ Warning:</span> Make sure no residents are assigned to this purok before deletion.
                </p>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="delete_purok">
                    <input type="hidden" name="purok_id" id="delete_purok_id">
                    
                    <div class="flex space-x-4">
                        <button type="button" onclick="hideDeleteModal()" 
                                class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-all duration-200 flex items-center justify-center">
                            <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-xl font-semibold transition-all duration-200 flex items-center justify-center shadow-lg hover:shadow-xl">
                            <i data-lucide="trash-2" class="w-4 h-4 mr-2"></i>
                            Delete Purok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<script src="assets/js/purok.js"></script>
</body>
</html>
