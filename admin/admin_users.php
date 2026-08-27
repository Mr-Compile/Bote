<?php
require_once '../config/database.php';
require '../includes/auth_admin.php'; // Ensure only admins can access

// Check database health before proceeding
if (!check_db_health($conn)) {
    die("Database connection issue. Please try again later.");
}

// Handle user operations with enhanced error handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add_worker':
                    $first_name = trim($_POST['first_name']);
                    $last_name = trim($_POST['last_name']);
                    $email = trim($_POST['email']);
                    $phone = trim($_POST['phone']);
                    $address = trim($_POST['address'] ?? '');
                    $purok_id = !empty($_POST['purok_id']) ? (int)$_POST['purok_id'] : NULL;
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    
                    // Enhanced validation
                    if (empty($first_name) || empty($last_name) || empty($email) || empty($address)) {
                        throw new Exception("All required fields must be filled.");
                    }
                    
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("Invalid email format.");
                    }
                    
                    if (strlen($_POST['password']) < 6) {
                        throw new Exception("Password must be at least 6 characters long.");
                    }
                    
                    // Check if email already exists
                    $stmt = prepare_statement($conn, "SELECT user_id FROM users WHERE email = ?", ['email' => $email]);
                    if (!$stmt) {
                        throw new Exception("Database error occurred.");
                    }
                    
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    
                    if ($stmt->get_result()->num_rows > 0) {
                        $error_message = "Email already exists!";
                    } else {
                        // Insert new worker
                        $stmt = prepare_statement($conn, "INSERT INTO users (first_name, last_name, email, phone, address, password, role, purok_id) VALUES (?, ?, ?, ?, ?, ?, 'worker', ?)");
                        if (!$stmt) {
                            throw new Exception("Database error occurred.");
                        }
                        
                        $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $phone, $address, $password, $purok_id);
                        if ($stmt->execute()) {
                            $success_message = "Worker added successfully!";
                            // Log the action
                            error_log("Admin {$_SESSION['user_id']} added new worker: {$email}");
                        } else {
                            throw new Exception("Failed to add worker.");
                        }
                    }
                    break;
                    
                case 'delete_user':
                    $user_id = (int)$_POST['user_id'];
                    
                    if ($user_id <= 0) {
                        throw new Exception("Invalid user ID.");
                    }
                    
                    // Prevent deleting the current admin
                    if ($user_id === $_SESSION['user_id']) {
                        throw new Exception("You cannot delete your own account.");
                    }
                    
                    $stmt = prepare_statement($conn, "DELETE FROM users WHERE user_id = ? AND role != 'admin'");
                    if (!$stmt) {
                        throw new Exception("Database error occurred.");
                    }
                    
                    $stmt->bind_param("i", $user_id);
                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $success_message = "User deleted successfully!";
                        error_log("Admin {$_SESSION['user_id']} deleted user: {$user_id}");
                    } else {
                        $error_message = "Failed to delete user or user not found.";
                    }
                    break;
                    
                case 'update_role':
                    $user_id = (int)$_POST['user_id'];
                    $new_role = trim($_POST['new_role']);
                    
                    if ($user_id <= 0) {
                        throw new Exception("Invalid user ID.");
                    }
                    
                    if (!in_array($new_role, ['resident', 'worker', 'admin'])) {
                        throw new Exception("Invalid role specified.");
                    }
                    
                    // Prevent changing own role
                    if ($user_id === $_SESSION['user_id']) {
                        throw new Exception("You cannot change your own role.");
                    }
                    
                    $stmt = prepare_statement($conn, "UPDATE users SET role = ? WHERE user_id = ?");
                    if (!$stmt) {
                        throw new Exception("Database error occurred.");
                    }
                    
                    $stmt->bind_param("si", $new_role, $user_id);
                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $success_message = "User role updated successfully!";
                        error_log("Admin {$_SESSION['user_id']} updated role for user {$user_id} to {$new_role}");
                    } else {
                        $error_message = "Failed to update user role or no changes made.";
                    }
                    break;
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            error_log("User management error: " . $e->getMessage());
        }
    }
}

// Handle sorting and filtering parameters
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created_at';
$sort_order = isset($_GET['sort_order']) && $_GET['sort_order'] === 'asc' ? 'ASC' : 'DESC';
$role_filter = isset($_GET['role_filter']) && !empty($_GET['role_filter']) ? $_GET['role_filter'] : '';

// Validate sort column to prevent SQL injection
$allowed_columns = ['first_name', 'last_name', 'email', 'role', 'created_at', 'purok_name'];
if (!in_array($sort_by, $allowed_columns)) {
    $sort_by = 'created_at';
}

// Build the WHERE clause for role filtering
$where_clause = "";
$params = [];
$param_types = "";

if (!empty($role_filter) && in_array($role_filter, ['admin', 'worker', 'resident'])) {
    $where_clause = "WHERE u.role = ?";
    $params[] = $role_filter;
    $param_types = "s";
}

// Build the ORDER BY clause
$order_clause = "";
if ($sort_by === 'first_name' || $sort_by === 'last_name') {
    $order_clause = "ORDER BY u.first_name {$sort_order}, u.last_name {$sort_order}";
} else if ($sort_by === 'purok_name') {
    $order_clause = "ORDER BY p.name {$sort_order}";
} else {
    $order_clause = "ORDER BY u.{$sort_by} {$sort_order}";
}

// Get all users with enhanced error handling and sorting
try {
    $query = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone, u.address, u.profile_photo, u.role, u.created_at, p.name as purok_name 
              FROM users u 
              LEFT JOIN puroks p ON u.purok_id = p.purok_id 
              {$where_clause} 
              {$order_clause}";
    
    $stmt = prepare_statement($conn, $query);
    if (!$stmt) {
        throw new Exception("Failed to prepare users query.");
    }
    
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate total users excluding admins
    $total_users = array_reduce($users, function($carry, $user) {
        return $carry + ($user['role'] !== 'admin' ? 1 : 0);
    }, 0);

    // Get user statistics with enhanced queries
    $stats = ['resident' => 0, 'worker' => 0, 'admin' => 0];
    $stmt = prepare_statement($conn, "SELECT role, COUNT(*) as count FROM users GROUP BY role");
    if ($stmt) {
        $stmt->execute();
        $role_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($role_stats as $stat) {
            $stats[$stat['role']] = $stat['count'];
        }
    }
    
    // Get additional statistics
    $recent_users_count = 0;
    $stmt = prepare_statement($conn, "SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    if ($stmt) {
        $stmt->execute();
        $recent_users_count = $stmt->get_result()->fetch_assoc()['count'];
    }
    
} catch (Exception $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = [];
    $stats = ['resident' => 0, 'worker' => 0, 'admin' => 0];
    $total_users = 0;
    $recent_users_count = 0;
    $error_message = "Failed to load user data. Please refresh the page.";
}

// Get all puroks for the dropdown
try {
    $stmt = prepare_statement($conn, "SELECT purok_id, name FROM puroks ORDER BY name ASC");
    if ($stmt) {
        $stmt->execute();
        $puroks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $puroks = [];
    }
} catch (Exception $e) {
    error_log("Error fetching puroks: " . $e->getMessage());
    $puroks = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - BoteBank Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="../assets/js/theme.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
        <link rel="stylesheet" href="assets/css/users.css">
</head>
<body class="bg-gradient-to-br from-bote-stone-50 to-bote-stone-100 min-h-screen">
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Enhanced Header -->
            <div class="mb-8">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center shadow-lg">
                        <i data-lucide="users" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-bote-stone-900">User Management</h1>
                        <p class="text-bote-stone-600 flex items-center">
                            <i data-lucide="shield-check" class="w-4 h-4 mr-2"></i>
                            Manage system users and their roles
                            <span class="ml-3 px-2 py-1 bg-bote-green-100 text-bote-green-800 text-xs rounded-full">
                                ? Sorting & Filtering Enabled
                            </span>
                        </p>
                    </div>
                </div>
                
                <!-- Breadcrumb -->
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="admin_dashboard.php" class="inline-flex items-center text-sm font-medium text-bote-stone-700 hover:text-bote-info-600">
                                <i data-lucide="home" class="w-4 h-4 mr-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i data-lucide="chevron-right" class="w-4 h-4 text-bote-stone-400"></i>
                                <span class="ml-1 text-sm font-medium text-bote-stone-500 md:ml-2">User Management</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>

            <!-- Enhanced Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="bg-gradient-to-r from-bote-green-400 to-bote-green-500 border-0 text-white px-6 py-4 rounded-xl mb-6 shadow-lg notification flex items-center">
                    <i data-lucide="check-circle" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium"><?php echo htmlspecialchars($success_message); ?></span>
                    <button onclick="hideParent(this, 1)" class="ml-auto">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-gradient-to-r from-red-400 to-red-500 border-0 text-white px-6 py-4 rounded-xl mb-6 shadow-lg notification flex items-center">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium"><?php echo htmlspecialchars($error_message); ?></span>
                    <button onclick="hideParent(this, 1)" class="ml-auto">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Enhanced Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Residents Card -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-bote-green-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-green-400 to-bote-green-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="users" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($stats['resident'] ?? 0); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="home" class="w-3 h-3 mr-1"></i>
                                    Residents
                                </p>
                            </div>
                        </div>
                        <div class="text-bote-green-500">
                            <i data-lucide="trending-up" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Workers Card -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="truck" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($stats['worker'] ?? 0); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="hard-hat" class="w-3 h-3 mr-1"></i>
                                    Workers
                                </p>
                            </div>
                        </div>
                        <div class="text-orange-500">
                            <i data-lucide="activity" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Total Users Card -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-bote-green-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-green-400 to-bote-green-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="user-plus" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($total_users); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="globe" class="w-3 h-3 mr-1"></i>
                                    Total Users
                                </p>
                            </div>
                        </div>
                        <div class="text-bote-green-500">
                            <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Users Card -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-bote-info-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-info-400 to-bote-info-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="user-check" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($recent_users_count); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="calendar" class="w-3 h-3 mr-1"></i>
                                    New (30 days)
                                    <?php if ($recent_users_count > 0): ?>
                                        <span class="ml-2 px-2 py-1 bg-bote-info-100 text-bote-info-800 text-xs rounded-full badge-pulse">New</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="text-bote-info-500">
                            <i data-lucide="clock" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Users Table -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden card-hover">
                <div class="px-6 py-4 border-b border-bote-stone-200 bg-gradient-to-r from-bote-stone-50 to-white">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-3">
                            <h2 class="text-xl font-semibold text-bote-stone-900 flex items-center">
                                <i data-lucide="database" class="w-5 h-5 mr-2 text-bote-stone-600"></i>
                                All Users
                            </h2>
                            <span class="px-3 py-1 bg-bote-info-100 text-bote-info-800 text-sm rounded-full font-medium">
                                <?php echo count($users); ?> Total
                            </span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <!-- Role Filter -->
                            <div class="relative">
                                <select id="roleFilter" onchange="applyFilters()" 
                                        class="pl-10 pr-8 py-2 border border-bote-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent bg-white text-sm">
                                    <option value="">All Roles</option>
                                    <option value="admin" <?php echo e($role_filter === 'admin' ? 'selected' : ''); ?>>? Admin</option>
                                    <option value="worker" <?php echo e($role_filter === 'worker' ? 'selected' : ''); ?>>? Worker</option>
                                    <option value="resident" <?php echo e($role_filter === 'resident' ? 'selected' : ''); ?>>? Resident</option>
                                </select>
                                <i data-lucide="filter" class="w-4 h-4 text-bote-stone-400 absolute left-3 top-3"></i>
                            </div>
                            
                            <!-- Search functionality -->
                            <div class="relative">
                                <input type="text" id="userSearch" placeholder="Search users..." 
                                       class="pl-10 pr-4 py-2 border border-bote-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent">
                                <i data-lucide="search" class="w-4 h-4 text-bote-stone-400 absolute left-3 top-3"></i>
                            </div>
                            
                            <button onclick="showAddWorkerModal()" 
                                    class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white px-6 py-2 rounded-lg font-medium transition-all duration-200 flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                <i data-lucide="user-plus" class="w-4 h-4 mr-2"></i>
                                Add Worker
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-bote-stone-200" id="usersTable">
                        <thead class="bg-bote-stone-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">
                                    <div class="flex items-center space-x-2 cursor-pointer hover:text-bote-stone-700 sortable-header <?php echo e($sort_by === 'first_name' ? 'active' : ''); ?>" onclick="sortTable('first_name')">
                                        <i data-lucide="user" class="w-4 h-4"></i>
                                        <span>User</span>
                                        <i data-lucide="chevrons-up-down" class="w-3 h-3 sort-icon" id="sort-first_name"></i>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">
                                    <div class="flex items-center space-x-2 cursor-pointer hover:text-bote-stone-700 sortable-header <?php echo e($sort_by === 'email' ? 'active' : ''); ?>" onclick="sortTable('email')">
                                        <i data-lucide="mail" class="w-4 h-4"></i>
                                        <span>Contact</span>
                                        <i data-lucide="chevrons-up-down" class="w-3 h-3 sort-icon" id="sort-email"></i>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">
                                    <div class="flex items-center space-x-2 cursor-pointer hover:text-bote-stone-700 sortable-header <?php echo e($sort_by === 'role' ? 'active' : ''); ?>" onclick="sortTable('role')">
                                        <i data-lucide="shield" class="w-4 h-4"></i>
                                        <span>Role</span>
                                        <i data-lucide="chevrons-up-down" class="w-3 h-3 sort-icon" id="sort-role"></i>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">
                                    <div class="flex items-center space-x-2 cursor-pointer hover:text-bote-stone-700 sortable-header <?php echo e($sort_by === 'created_at' ? 'active' : ''); ?>" onclick="sortTable('created_at')">
                                        <i data-lucide="calendar" class="w-4 h-4"></i>
                                        <span>Joined</span>
                                        <i data-lucide="chevrons-up-down" class="w-3 h-3 sort-icon" id="sort-created_at"></i>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">
                                    <div class="flex items-center space-x-2">
                                        <i data-lucide="settings" class="w-4 h-4"></i>
                                        <span>Actions</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-bote-stone-200">
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <i data-lucide="users" class="w-12 h-12 text-bote-stone-400 mb-4"></i>
                                            <h3 class="text-lg font-medium text-bote-stone-900 mb-2">No users found</h3>
                                            <p class="text-bote-stone-500">There are no users in the system yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-bote-stone-50 transition-colors duration-200 table-row-hover">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <?php if (!empty($user['profile_photo']) && file_exists('../uploads/profiles/' . $user['profile_photo'])): ?>
                                                    <div class="w-10 h-10 rounded-full overflow-hidden shadow-md border-2 border-bote-stone-200">
                                                        <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_photo']); ?>" 
                                                             alt="Profile" class="w-full h-full object-cover">
                                                    </div>
                                                <?php else: ?>
                                                    <div class="w-10 h-10 bg-gradient-to-br from-bote-info-500 to-bote-green-600 rounded-full flex items-center justify-center shadow-md">
                                                        <span class="text-white font-bold text-sm">
                                                            <?php echo e(strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1))); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-bote-stone-900 flex items-center">
                                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                        <?php if ($user['user_id'] === $_SESSION['user_id']): ?>
                                                            <span class="ml-2 px-2 py-1 bg-bote-info-100 text-bote-info-800 text-xs rounded-full">You</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-sm text-bote-stone-500 flex items-center">
                                                        <i data-lucide="hash" class="w-3 h-3 mr-1"></i>
                                                        ID: <?php echo e($user['user_id']); ?>
                                                    </div>
                                                    <?php if (!empty($user['address'])): ?>
                                                        <div class="text-xs text-bote-stone-400 flex items-center mt-1">
                                                            <i data-lucide="map-pin" class="w-3 h-3 mr-1"></i>
                                                            <?php echo htmlspecialchars(substr($user['address'], 0, 30) . (strlen($user['address']) > 30 ? '...' : '')); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="space-y-1">
                                                <div class="text-sm text-bote-stone-900 flex items-center">
                                                    <i data-lucide="mail" class="w-3 h-3 mr-2 text-bote-stone-400"></i>
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                </div>
                                                <div class="text-sm text-bote-stone-500 flex items-center">
                                                    <i data-lucide="phone" class="w-3 h-3 mr-2 text-bote-stone-400"></i>
                                                    <?php echo htmlspecialchars($user['phone']); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full 
                                                <?php 
                                                switch($user['role']) {
                                                    case 'admin': echo 'bg-gradient-to-r from-bote-info-100 to-bote-info-200 text-bote-info-800'; break;
                                                    case 'worker': echo 'bg-gradient-to-r from-orange-100 to-orange-200 text-orange-800'; break;
                                                    case 'resident': echo 'bg-gradient-to-r from-bote-green-100 to-bote-green-200 text-bote-green-800'; break;
                                                    default: echo 'bg-gradient-to-r from-bote-stone-100 to-bote-stone-200 text-bote-stone-800';
                                                }
                                                ?>">
                                                <?php 
                                                $role_icon = '';
                                                switch($user['role']) {
                                                    case 'admin': $role_icon = 'crown'; break;
                                                    case 'worker': $role_icon = 'hard-hat'; break;
                                                    case 'resident': $role_icon = 'home'; break;
                                                    default: $role_icon = 'user';
                                                }
                                                ?>
                                                <i data-lucide="<?php echo e($role_icon); ?>" class="w-3 h-3 mr-1"></i>
                                                <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-bote-stone-500">
                                            <div class="flex items-center">
                                                <i data-lucide="calendar-days" class="w-3 h-3 mr-2"></i>
                                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                            </div>
                                            <div class="text-xs text-bote-stone-400 mt-1">
                                                <?php echo date('g:i A', strtotime($user['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-2">
                                                <?php if ($user['role'] !== 'admin' || $user['user_id'] !== $_SESSION['user_id']): ?>
                                                    <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                                            class="text-bote-info-600 hover:text-bote-info-900 p-2 hover:bg-bote-info-50 rounded-lg transition-colors duration-200" 
                                                            title="Edit User">
                                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                                    </button>
                                                    <button onclick="confirmDelete(<?php echo e($user['user_id']); ?>)" 
                                                            class="text-red-600 hover:text-red-900 p-2 hover:bg-red-50 rounded-lg transition-colors duration-200" 
                                                            title="Delete User">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-bote-stone-400 text-xs flex items-center">
                                                        <i data-lucide="lock" class="w-3 h-3 mr-1"></i>
                                                        Protected
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Table Footer with Stats -->
                <div class="px-6 py-3 bg-bote-stone-50 border-t border-bote-stone-200">
                    <div class="flex items-center justify-between text-sm text-bote-stone-600">
                        <div class="flex items-center space-x-4">
                            <span class="flex items-center">
                                <i data-lucide="users" class="w-4 h-4 mr-1"></i>
                                Showing <?php echo count($users); ?> users
                                <?php if (!empty($role_filter)): ?>
                                    <span class="ml-2 px-2 py-1 bg-bote-info-100 text-bote-info-800 text-xs rounded-full">
                                        Filtered by: <?php echo htmlspecialchars(ucfirst($role_filter)); ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                            <span class="flex items-center text-xs">
                                <i data-lucide="sort-desc" class="w-3 h-3 mr-1"></i>
                                Sorted by: <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $sort_by))); ?> (<?php echo htmlspecialchars($sort_order); ?>)
                            </span>
                        </div>
                        <div class="flex items-center space-x-4 text-xs">
                            <span class="flex items-center">
                                <div class="w-2 h-2 bg-bote-green-500 rounded-full mr-2"></div>
                                <?php echo e($stats['resident'] ?? 0); ?> Residents
                            </span>
                            <span class="flex items-center">
                                <div class="w-2 h-2 bg-orange-500 rounded-full mr-2"></div>
                                <?php echo e($stats['worker'] ?? 0); ?> Workers
                            </span>
                            <span class="flex items-center">
                                <div class="w-2 h-2 bg-bote-green-500 rounded-full mr-2"></div>
                                <?php echo e($stats['admin'] ?? 0); ?> Admins
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Enhanced Add Worker Modal -->
<div id="addWorkerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-4xl w-full mx-4 shadow-2xl modal-enter">
        <!-- Modal Header -->
        <div class="gradient-bg px-6 py-4 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <i data-lucide="user-plus" class="w-5 h-5 text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Add New Worker</h3>
                </div>
                <button onclick="hideAddWorkerModal()" class="text-white hover:text-bote-stone-200 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6">
            <form method="POST" id="addWorkerForm" class="grid grid-cols-2 gap-6">
                <?php echo \App\Core\Csrf::field(); ?>
                <input type="hidden" name="action" value="add_worker">

                <!-- First Name -->
                <div>
                    <label class="block text-sm font-medium text-bote-stone-700 mb-2 flex items-center">
                        <i data-lucide="user" class="w-4 h-4 mr-2"></i>
                        First Name
                    </label>
                    <input type="text" name="first_name" required 
                           class="w-full px-4 py-2 border border-bote-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500 transition-all duration-200"
                           placeholder="Enter first name">
                </div>

                <!-- Last Name -->
                <div>
                    <label class="block text-sm font-medium text-bote-stone-700 mb-2 flex items-center">
                        <i data-lucide="user" class="w-4 h-4 mr-2"></i>
                        Last Name
                    </label>
                    <input type="text" name="last_name" required 
                           class="w-full px-4 py-2 border border-bote-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500 transition-all duration-200"
                           placeholder="Enter last name">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-bote-stone-700 mb-2 flex items-center">
                        <i data-lucide="mail" class="w-4 h-4 mr-2"></i>
                        Email Address
                    </label>
                    <input type="email" name="email" required 
                           class="w-full px-4 py-2 border border-bote-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500 transition-all duration-200"
                           placeholder="worker@example.com">
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-medium text-bote-stone-700 mb-2 flex items-center">
                        <i data-lucide="phone" class="w-4 h-4 mr-2"></i>
                        Phone Number
                    </label>
                    <input type="tel" name="phone" required 
                           class="w-full px-4 py-2 border border-bote-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500 transition-all duration-200"
                           placeholder="+63 XXX XXX XXXX">
                </div>

                <!-- Address -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-bote-stone-700 mb-2 flex items-center">
                        <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i>
                        Address
                    </label>
                    <textarea name="address" rows="2" required
                              class="w-full px-4 py-2 border border-bote-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500 transition-all duration-200 resize-none"
                              placeholder="Enter complete address"></textarea>
                </div>

                <!-- Purok -->
                <div>
                    <label class="block text-sm font-medium text-bote-stone-700 mb-2 flex items-center">
                        <i data-lucide="map" class="w-4 h-4 mr-2"></i>
                        Purok (Optional)
                    </label>
                    <select name="purok_id" 
                            class="w-full px-4 py-2 border border-bote-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500 transition-all duration-200">
                        <option value="">Select Purok</option>
                        <?php foreach ($puroks as $purok): ?>
                            <option value="<?php echo e($purok['purok_id']); ?>">
                                <?php echo htmlspecialchars($purok['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-bote-stone-700 mb-2 flex items-center">
                        <i data-lucide="key" class="w-4 h-4 mr-2"></i>
                        Password
                    </label>
                    <div class="relative">
                        <input type="password" name="password" required minlength="6" id="passwordField"
                               class="w-full px-4 py-2 border border-bote-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500 transition-all duration-200 pr-12"
                               placeholder="Minimum 6 characters">
                        <button type="button" onclick="togglePassword('passwordField', this)" 
                                class="absolute right-3 top-2.5 text-bote-stone-400 hover:text-bote-stone-600">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="mt-2 flex items-center space-x-4 text-xs">
                        <span class="flex items-center text-bote-green-600">
                            <i data-lucide="check-circle" class="w-3 h-3 mr-1"></i>
                            Minimum 6 characters
                        </span>
                        <span class="flex items-center text-bote-info-600">
                            <i data-lucide="info" class="w-3 h-3 mr-1"></i>
                            Auto-generated option available
                        </span>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="col-span-2 flex space-x-4 mt-4">
                    <button type="button" onclick="hideAddWorkerModal()" 
                            class="flex-1 px-4 py-2 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-800 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                        <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white rounded-lg font-medium transition-all duration-200 flex items-center justify-center shadow-lg">
                        <i data-lucide="user-plus" class="w-4 h-4 mr-2"></i>
                        Add Worker
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


    <!-- Enhanced Edit User Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-md w-full mx-4 shadow-2xl modal-enter">
            <!-- Modal Header -->
            <div class="gradient-bg px-6 py-4 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <i data-lucide="edit" class="w-5 h-5 text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Edit User Role</h3>
                    </div>
                    <button onclick="hideEditModal()" class="text-white hover:text-bote-stone-200 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-6">
                <form method="POST">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-bote-stone-700 mb-2 flex items-center">
                            <i data-lucide="user" class="w-4 h-4 mr-2"></i>
                            User Information
                        </label>
                        <div class="p-4 bg-bote-stone-50 rounded-lg border">
                            <p id="edit_user_name" class="text-bote-stone-900 font-medium"></p>
                            <p id="edit_user_email" class="text-sm text-bote-stone-500 mt-1"></p>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-bote-stone-700 mb-2 flex items-center">
                            <i data-lucide="shield" class="w-4 h-4 mr-2"></i>
                            New Role
                        </label>
                        <select name="new_role" id="edit_role" 
                                class="w-full px-4 py-2 border border-bote-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all duration-200">
                            <option value="resident" class="flex items-center">
                                ? Resident
                            </option>
                            <option value="worker" class="flex items-center">
                                ? Worker
                            </option>
                            <option value="admin" class="flex items-center">
                                ? Admin
                            </option>
                        </select>
                        <div class="mt-2 text-xs text-yellow-600 flex items-center">
                            <i data-lucide="alert-triangle" class="w-3 h-3 mr-1"></i>
                            Role changes take effect immediately
                        </div>
                    </div>
                    
                    <div class="flex space-x-4">
                        <button type="button" onclick="hideEditModal()" 
                                class="flex-1 px-4 py-2 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-800 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                            <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 px-4 py-2 bg-gradient-to-r from-bote-info-500 to-bote-info-600 hover:from-bote-info-600 hover:to-bote-info-700 text-white rounded-lg font-medium transition-all duration-200 flex items-center justify-center shadow-lg">
                            <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                            Update Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Enhanced Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-md w-full mx-4 shadow-2xl modal-enter">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Confirm Deletion</h3>
                    </div>
                    <button onclick="hideDeleteModal()" class="text-white hover:text-bote-stone-200 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="trash-2" class="w-8 h-8 text-red-600"></i>
                    </div>
                    
                    <h3 class="text-lg font-bold text-bote-stone-900 mb-2">Delete User Account</h3>
                    <p class="text-bote-stone-600 mb-6">
                        Are you sure you want to delete this user? This action cannot be undone and will permanently remove all associated data.
                    </p>
                    
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <div class="flex items-center">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-yellow-400 mr-3"></i>
                            <p class="text-sm text-yellow-700">
                                <strong>Warning:</strong> This will also remove all pickup requests, points, and transaction history for this user.
                            </p>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <?php echo \App\Core\Csrf::field(); ?>
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        
                        <div class="flex space-x-4">
                            <button type="button" onclick="hideDeleteModal()" 
                                    class="flex-1 px-4 py-2 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-800 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                                <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="flex-1 px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-lg font-medium transition-all duration-200 flex items-center justify-center shadow-lg">
                                <i data-lucide="trash-2" class="w-4 h-4 mr-2"></i>
                                Delete User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/lucide@latest"></script>
    <?php
$users_sort_data = ['column' => $sort_by, 'order' => strtolower($sort_order)];
$json_users_sort_data = htmlspecialchars(json_encode($users_sort_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
?>
<script type="application/json" id="admin-users-data"><?php echo $json_users_sort_data; ?></script>
<script type="module" src="assets/js/users.js"></script>
<script type="module" src="assets/js/admin_users.js"></script>

</body>
</html>
