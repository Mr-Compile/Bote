
<?php
require_once '../config/database.php';
require_once '../includes/system_functions.php';

// Ensure system settings table exists
ensureSystemSettingsTable($conn);

// Get user data from database
$user_id = $_SESSION['user_id'];
$stmt = prepare_statement($conn, "SELECT first_name, last_name, email, phone, profile_photo FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Get system settings
$system_name = getSystemSetting($conn, 'barangay_name', 'BoteBank');
$system_logo = getSystemSetting($conn, 'barangay_logo', '');

// Get user profile display info
$profile_display = getUserProfileDisplay($user_data);

// Get worker statistics
$stats = [];

// Total assigned pickups
$stmt = prepare_statement($conn, "SELECT COUNT(*) as assigned_pickups FROM pickuprequests WHERE assigned_worker_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['assigned_pickups'] = $stmt->get_result()->fetch_assoc()['assigned_pickups'];

// Pending pickups
$stmt = prepare_statement($conn, "SELECT COUNT(*) as pending_pickups FROM pickuprequests WHERE assigned_worker_id = ? AND status = 'Scheduled'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['pending_pickups'] = $stmt->get_result()->fetch_assoc()['pending_pickups'];

// Completed pickups
$stmt = prepare_statement($conn, "SELECT COUNT(*) as completed_pickups FROM pickuprequests WHERE assigned_worker_id = ? AND status = 'Completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['completed_pickups'] = $stmt->get_result()->fetch_assoc()['completed_pickups'];

// Active routes
$stmt = prepare_statement($conn, "SELECT COUNT(*) as active_routes FROM collectionroutes WHERE worker_id = ? AND DATE(generated_on) = CURDATE()");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['active_routes'] = $stmt->get_result()->fetch_assoc()['active_routes'];

// Today's pickup weight total
$stmt = prepare_statement($conn, "SELECT COALESCE(SUM(weight_kg), 0) as today_weight FROM pickuprequests WHERE assigned_worker_id = ? AND status = 'Completed' AND DATE(created_at) = CURDATE()");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['today_weight'] = $stmt->get_result()->fetch_assoc()['today_weight'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Dashboard - BoteBank</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="../assets/js/theme.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script type="module" src="assets/js/worker_sidebar.js"></script>
    <style>
        .sidebar-active {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        .sidebar-active i {
            color: white;
        }
        .notification-badge {
            animation: pulse 2s infinite;
        }
        .route-glow {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body class="bg-bote-stone-50 font-sans">
    <!-- Sidebar -->
    <div class="fixed left-0 top-0 h-full w-64 bg-white shadow-xl flex flex-col z-50">
        <!-- Header Section -->
        <div class="bg-gradient-to-br from-bote-info-600 via-bote-green-600 to-bote-green-700 p-6 relative overflow-hidden">
            <div class="absolute inset-0 bg-black bg-opacity-10"></div>
            <div class="relative z-10">
                <div class="flex items-center space-x-3 mb-2">
                    <?php if ($system_logo && file_exists("../uploads/" . $system_logo)): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($system_logo); ?>" 
                             alt="System Logo" 
                             class="w-10 h-10 rounded-xl object-cover bg-white bg-opacity-20 p-1">
                    <?php else: ?>
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                            <i data-lucide="truck" class="w-6 h-6 text-white"></i>
                        </div>
                    <?php endif; ?>
                    <h1 class="text-xl font-bold text-white"><?php echo htmlspecialchars($system_name); ?></h1>
                </div>
                <p class="text-bote-info-100 text-sm font-medium">Worker Portal</p>
            </div>
        </div>

        <!-- User Info -->
        <div class="px-6 py-4 border-b border-bote-stone-100 bg-gradient-to-r from-bote-info-50 to-bote-green-50">
            <div class="flex items-center space-x-3">
                <?php if ($profile_display['type'] === 'photo'): ?>
                    <img src="../uploads/profiles/thumbs/<?php echo htmlspecialchars($profile_display['value']); ?>" 
                         alt="Profile Photo" 
                         class="w-12 h-12 rounded-full object-cover ring-2 ring-bote-info-200">
                <?php else: ?>
                    <div class="w-12 h-12 bg-gradient-to-br from-bote-info-500 to-bote-green-600 rounded-full flex items-center justify-center ring-2 ring-bote-info-200">
                        <span class="text-white font-bold text-lg">
                            <?php echo htmlspecialchars($profile_display['value']); ?>
                        </span>
                    </div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-bote-stone-800 truncate">
                        <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>
                    </p>
                    <p class="text-sm text-bote-info-600 font-medium">Collection Worker</p>
                    <p class="text-xs text-bote-stone-500 truncate"><?php echo htmlspecialchars($user_data['email']); ?></p>
                </div>
            </div>
        </div>


        <!-- Quick Stats -->
        <div class="px-6 py-3 bg-bote-stone-50 border-b border-bote-stone-100">
            <div class="grid grid-cols-2 gap-2 text-center">
                <div class="bg-white rounded-lg p-2 shadow-sm">
                    <div class="text-sm font-bold text-bote-info-600"><?php echo e($stats['assigned_pickups']); ?></div>
                    <div class="text-xs text-bote-stone-500">Total Jobs</div>
                </div>
                <div class="bg-white rounded-lg p-2 shadow-sm">
                    <div class="text-sm font-bold text-orange-500"><?php echo e($stats['pending_pickups']); ?></div>
                    <div class="text-xs text-bote-stone-500">Pending</div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
            <a href="worker_dashboard.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700">
                <div class="w-8 h-8 rounded-lg bg-bote-green-100 group-hover:bg-bote-green-200 flex items-center justify-center transition-colors">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 text-bote-green-600"></i>
                </div>
                <span class="font-medium">Dashboard</span>
            </a>
            
            <a href="worker_collection_routes.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700">
                <div class="w-8 h-8 rounded-lg bg-bote-info-100 group-hover:bg-bote-info-200 flex items-center justify-center transition-colors">
                    <i data-lucide="map" class="w-4 h-4 text-bote-info-600"></i>
                </div>
                <span class="font-medium">Collection Routes</span>
                <?php if ($stats['active_routes'] > 0): ?>
                    <span class="ml-auto bg-bote-info-100 text-bote-info-700 text-xs px-2 py-1 rounded-full"><?php echo e($stats['active_routes']); ?></span>
                <?php endif; ?>
            </a>
            
            <a href="worker_assigned_pickups.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700">
                <div class="w-8 h-8 rounded-lg bg-orange-100 group-hover:bg-orange-200 flex items-center justify-center transition-colors">
                    <i data-lucide="truck" class="w-4 h-4 text-orange-600"></i>
                </div>
                <span class="font-medium"> Pickup Request</span>
                <?php if ($stats['pending_pickups'] > 0): ?>
                    <span class="ml-auto bg-orange-100 text-orange-700 text-xs px-2 py-1 rounded-full notification-badge"><?php echo e($stats['pending_pickups']); ?></span>
                <?php endif; ?>
            </a>
            
            <a href="worker_completed_collections.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700">
                <div class="w-8 h-8 rounded-lg bg-bote-green-100 group-hover:bg-bote-green-200 flex items-center justify-center transition-colors">
                    <i data-lucide="check-circle" class="w-4 h-4 text-bote-green-600"></i>
                </div>
                <span class="font-medium">Completed Collections</span>
                <span class="ml-auto text-xs text-bote-green-600 font-bold"><?php echo e($stats['completed_pickups']); ?></span>
            </a>

            <a href="worker_daily_reports.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700">
                <div class="w-8 h-8 rounded-lg bg-bote-green-100 group-hover:bg-bote-green-200 flex items-center justify-center transition-colors">
                    <i data-lucide="clipboard-list" class="w-4 h-4 text-bote-green-600"></i>
                </div>
                <span class="font-medium">Daily Reports</span>
            </a>

            <a href="worker_verification.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700">
                <div class="w-8 h-8 rounded-lg bg-red-100 group-hover:bg-red-200 flex items-center justify-center transition-colors">
                    <i data-lucide="shield-check" class="w-4 h-4 text-red-600"></i>
                </div>
                <span class="font-medium">Worker Verification</span>
            </a>

            <div class="border-t border-bote-stone-200 my-4"></div>
            
            <a href="worker_profile.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700">
                <div class="w-8 h-8 rounded-lg bg-bote-stone-100 group-hover:bg-bote-stone-200 flex items-center justify-center transition-colors">
                    <i data-lucide="user" class="w-4 h-4 text-bote-stone-600"></i>
                </div>
                <span class="font-medium">My Profile</span>
            </a>

            <a href="worker_help.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700">
                <div class="w-8 h-8 rounded-lg bg-bote-info-100 group-hover:bg-bote-info-200 flex items-center justify-center transition-colors">
                    <i data-lucide="headphones" class="w-4 h-4 text-bote-info-600"></i>
                </div>
                <span class="font-medium">Support</span>
            </a>
        </nav>

        <!-- Status Indicator -->
        <div class="px-6 py-3 border-t border-bote-stone-100 bg-bote-green-50">
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-bote-green-500 rounded-full animate-pulse"></div>
                <span class="text-sm text-bote-green-700 font-medium">Active Status</span>
            </div>
        </div>

        <!-- Logout Button -->
        <div class="p-4 border-t border-bote-stone-100">
            <button onclick="showLogoutModal()" class="w-full flex items-center justify-center space-x-2 p-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 rounded-xl text-white transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span class="font-medium">Logout</span>
            </button>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="workerLogoutModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="log-out" class="w-8 h-8 text-red-600"></i>
                </div>
                <h3 class="text-xl font-bold text-bote-stone-900 mb-2">End Work Session</h3>
                <p class="text-bote-stone-600 mb-6">Are you sure you want to logout? Make sure all your routes are completed.</p>
                <div class="flex space-x-4">
                    <button onclick="hideLogoutModal()" class="flex-1 px-4 py-2 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-800 rounded-xl font-medium transition-colors">
                        Cancel
                    </button>
                    <button onclick="confirmLogout()" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium transition-colors">
                        End Session
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
