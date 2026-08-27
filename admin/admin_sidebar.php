<?php
require_once '../config/database.php';
require_once '../includes/system_functions.php';

// Ensure system settings table exists
ensureSystemSettingsTable($conn);

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = prepare_statement($conn, "SELECT first_name, last_name, email, phone, profile_photo FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$stmt->close();

// Get system settings
$system_name = getSystemSetting($conn, 'barangay_name', 'BoteBank');
$system_logo = getSystemSetting($conn, 'barangay_logo', '');

// Get user profile display info
$profile_display = getUserProfileDisplay($user_data);

// Get dashboard statistics
$stats = [];

function getStat($conn, $query) {
    $stmt = prepare_statement($conn, $query);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return array_values($result)[0];
}

$stats['total_users']     = getStat($conn, "SELECT COUNT(*) as total FROM users");
$stats['total_requests']  = getStat($conn, "SELECT COUNT(*) as total FROM pickuprequests");
$stats['pending_requests']= getStat($conn, "SELECT COUNT(*) as total FROM pickuprequests WHERE status = 'Pending'");
$stats['total_rewards']   = getStat($conn, "SELECT COUNT(*) as total FROM rewards");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BoteBank</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="../assets/js/theme.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        .sidebar-active {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white !important;
        }
        .sidebar-active i {
            color: white !important;
        }
        .notification-badge {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body class="bg-bote-stone-50 font-sans">

    <!-- Sidebar -->
    <div class="fixed left-0 top-0 h-full w-64 bg-white shadow-xl flex flex-col z-50">
        <!-- Header Section -->
        <div class="bg-gradient-to-br from-bote-green-600 via-bote-green-700 to-bote-green-800 p-6 relative overflow-hidden">
            <div class="absolute inset-0 bg-black bg-opacity-10"></div>
            <div class="relative z-10">
                <div class="flex items-center space-x-3 mb-2">
                    <?php if ($system_logo && file_exists("../uploads/" . $system_logo)): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($system_logo); ?>" 
                             alt="System Logo" 
                             class="w-10 h-10 rounded-lg object-cover bg-white bg-opacity-20 p-1">
                    <?php else: ?>
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <i data-lucide="recycle" class="w-6 h-6 text-white"></i>
                        </div>
                    <?php endif; ?>
                    <h1 class="text-xl font-bold text-white"><?php echo htmlspecialchars($system_name); ?></h1>
                </div>
                <p class="text-bote-green-100 text-sm font-medium">Admin Panel</p>
            </div>
        </div>

        <!-- User Info -->
        <div class="px-6 py-4 border-b border-bote-stone-100 bg-gradient-to-r from-bote-green-50 to-bote-green-50">
            <div class="flex items-center space-x-3">
                <?php if ($profile_display['type'] === 'photo'): ?>
                    <img src="../uploads/profiles/thumbs/<?php echo htmlspecialchars($profile_display['value']); ?>" 
                         alt="Profile Photo" 
                         class="w-12 h-12 rounded-full object-cover ring-2 ring-bote-green-200">
                <?php else: ?>
                    <div class="w-12 h-12 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-full flex items-center justify-center ring-2 ring-bote-green-200">
                        <span class="text-white font-bold text-lg">
                            <?php echo htmlspecialchars($profile_display['value']); ?>
                        </span>
                    </div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-bote-stone-800 truncate">
                        <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>
                    </p>
                    <p class="text-sm text-bote-green-600 font-medium">System Administrator</p>
                    <p class="text-xs text-bote-stone-500 truncate"><?php echo htmlspecialchars($user_data['email']); ?></p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <!-- Navigation -->

<nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
    <a href="admin_dashboard.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700 <?php if(basename($_SERVER['PHP_SELF'])=='admin_dashboard.php') echo 'sidebar-active'; ?>">
        <div class="w-8 h-8 rounded-lg bg-bote-green-100 group-hover:bg-bote-green-200 flex items-center justify-center transition-colors">
            <i data-lucide="layout-dashboard" class="w-4 h-4 text-bote-green-600"></i>
        </div>
        <span class="font-medium">Dashboard</span>
    </a>


    <a href="admin_pickup_requests.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700 <?php if(basename($_SERVER['PHP_SELF'])=='admin_pickup_requests.php') echo 'sidebar-active'; ?>">
        <div class="w-8 h-8 rounded-lg bg-bote-info-100 group-hover:bg-bote-info-200 flex items-center justify-center transition-colors">
            <i data-lucide="plus-circle" class="w-4 h-4 text-bote-info-600"></i>
        </div>
        <span class="font-medium">Pickup Requests</span>
    </a>

    <a href="admin_users.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700 <?php if(basename($_SERVER['PHP_SELF'])=='admin_users.php') echo 'sidebar-active'; ?>">
        <div class="w-8 h-8 rounded-lg bg-orange-100 group-hover:bg-orange-200 flex items-center justify-center transition-colors">
            <i data-lucide="list" class="w-4 h-4 text-orange-600"></i>
        </div>
        <span class="font-medium">Users</span>
    </a>

    <a href="admin_rewards.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700 <?php if(basename($_SERVER['PHP_SELF'])=='admin_rewards.php') echo 'sidebar-active'; ?>">
        <div class="w-8 h-8 rounded-lg bg-bote-green-100 group-hover:bg-bote-green-200 flex items-center justify-center transition-colors">
            <i data-lucide="gift" class="w-4 h-4 text-bote-green-600"></i>
        </div>
        <span class="font-medium">Rewards</span>
    </a>

    <a href="admin_reports.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700 <?php if(basename($_SERVER['PHP_SELF'])=='admin_reports.php') echo 'sidebar-active'; ?>">
        <div class="w-8 h-8 rounded-lg bg-yellow-100 group-hover:bg-yellow-200 flex items-center justify-center transition-colors">
            <i data-lucide="file-text" class="w-4 h-4 text-yellow-600"></i>
        </div>
        <span class="font-medium">Reports</span>
    </a>

    <a href="admin_puroks.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700 <?php if(basename($_SERVER['PHP_SELF'])=='admin_puroks.php') echo 'sidebar-active'; ?>">
        <div class="w-8 h-8 rounded-lg bg-bote-green-100 group-hover:bg-bote-green-200 flex items-center justify-center transition-colors">
            <i data-lucide="map-pin" class="w-4 h-4 text-bote-green-600"></i>
        </div>
        <span class="font-medium">Puroks</span>
    </a>

    <a href="admin_recyclable_types.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700 <?php if(basename($_SERVER['PHP_SELF'])=='admin_recyclable_types.php') echo 'sidebar-active'; ?>">
        <div class="w-8 h-8 rounded-lg bg-bote-info-100 group-hover:bg-bote-info-200 flex items-center justify-center transition-colors">
            <i data-lucide="recycle" class="w-4 h-4 text-bote-info-600"></i>
        </div>
        <span class="font-medium">Recyclable Types</span>
    </a>

<a href="admin_collection_points.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700 <?php if(basename($_SERVER['PHP_SELF'])=='admin_collection_points.php') echo 'sidebar-active'; ?>">
    <div class="w-8 h-8 rounded-lg bg-bote-green-100 group-hover:bg-bote-green-200 flex items-center justify-center transition-colors">
        <i data-lucide="map-pin" class="w-4 h-4 text-bote-green-600"></i>
    </div>
    <span class="font-medium">Collection Points</span>
</a>


    <a href="admin_profile.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700 <?php if(basename($_SERVER['PHP_SELF'])=='admin_profile.php') echo 'sidebar-active'; ?>">
        <div class="w-8 h-8 rounded-lg bg-bote-green-100 group-hover:bg-bote-green-200 flex items-center justify-center transition-colors">
            <i data-lucide="user-circle" class="w-4 h-4 text-bote-green-600"></i>
        </div>
        <span class="font-medium">My Profile</span>
    </a>


    <a href="admin_settings.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700 <?php if(basename($_SERVER['PHP_SELF'])=='admin_settings.php') echo 'sidebar-active'; ?>">
        <div class="w-8 h-8 rounded-lg bg-bote-stone-100 group-hover:bg-bote-stone-200 flex items-center justify-center transition-colors">
            <i data-lucide="settings" class="w-4 h-4 text-bote-stone-600"></i>
        </div>
        <span class="font-medium">Settings</span>
    </a>

    <a href="admin_help.php" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-bote-green-50 transition-all duration-200 text-bote-stone-700 hover:text-bote-green-700 <?php if(basename($_SERVER['PHP_SELF'])=='admin_help.php') echo 'sidebar-active'; ?>">
        <div class="w-8 h-8 rounded-lg bg-bote-info-100 group-hover:bg-bote-info-200 flex items-center justify-center transition-colors">
            <i data-lucide="help-circle" class="w-4 h-4 text-bote-info-600"></i>
        </div>
        <span class="font-medium">Help & Support</span>
    </a>
</nav>


        <!-- Logout Button -->
        <div class="p-4 border-t border-bote-stone-100">
            <button onclick="AdminSidebar.showLogoutModal()" class="w-full flex items-center justify-center space-x-2 p-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 rounded-xl text-white transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span class="font-medium">Logout</span>
            </button>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="adminLogoutModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="log-out" class="w-8 h-8 text-red-600"></i>
                </div>
                <h3 class="text-xl font-bold text-bote-stone-900 mb-2">Confirm Logout</h3>
                <p class="text-bote-stone-600 mb-6">Are you sure you want to log out of your admin account?</p>
                <div class="flex space-x-4">
                    <button onclick="AdminSidebar.hideLogoutModal()" class="flex-1 px-4 py-2 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-800 rounded-xl font-medium transition-colors">
                        Cancel
                    </button>
                    <button onclick="AdminSidebar.confirmLogout()" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium transition-colors">
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </div>

    
<script type="module" src="assets/js/admin_sidebar.js"></script>
</body>
</html>
