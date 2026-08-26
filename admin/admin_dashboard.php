<?php
require_once '../config/database.php';
require_once '../includes/system_functions.php';
require '../includes/auth_admin.php'; // Ensure only admins can access

// Check database health before proceeding
if (!check_db_health($conn)) {
    die("Database connection issue. Please try again later.");
}

// Ensure system settings table exists
ensureSystemSettingsTable($conn);

// Get dashboard statistics from database with enhanced error handling
$dashboard_stats = [];

try {
    // Total Residents with enhanced query
    $stmt = prepare_statement($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'resident'", ['context' => 'count_residents']);
    if ($stmt) {
        $stmt->execute();
        $dashboard_stats['total_residents'] = $stmt->get_result()->fetch_assoc()['count'];
    } else {
        $dashboard_stats['total_residents'] = 0;
    }

    // Total Workers
    $stmt = prepare_statement($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'worker'", ['context' => 'count_workers']);
    if ($stmt) {
        $stmt->execute();
        $dashboard_stats['total_workers'] = $stmt->get_result()->fetch_assoc()['count'];
    } else {
        $dashboard_stats['total_workers'] = 0;
    }

    // Total Pickups
    $stmt = prepare_statement($conn, "SELECT COUNT(*) as count FROM pickuprequests", ['context' => 'count_pickups']);
    if ($stmt) {
        $stmt->execute();
        $dashboard_stats['total_pickups'] = $stmt->get_result()->fetch_assoc()['count'];
    } else {
        $dashboard_stats['total_pickups'] = 0;
    }

    // Total Points Distributed
    $stmt = prepare_statement($conn, "SELECT COALESCE(SUM(points), 0) as total FROM pointsledger WHERE transaction_type = 'Earned'", ['context' => 'total_points']);
    if ($stmt) {
        $stmt->execute();
        $dashboard_stats['points_distributed'] = $stmt->get_result()->fetch_assoc()['total'];
    } else {
        $dashboard_stats['points_distributed'] = 0;
    }

    // Get new registrations this month
    $stmt = prepare_statement($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'resident' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    if ($stmt) {
        $stmt->execute();
        $dashboard_stats['new_residents_month'] = $stmt->get_result()->fetch_assoc()['count'];
    } else {
        $dashboard_stats['new_residents_month'] = 0;
    }

    // Get total rewards available
    $stmt = prepare_statement($conn, "SELECT COUNT(*) as count, SUM(stock) as total_stock FROM rewards WHERE stock > 0");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $dashboard_stats['available_rewards'] = $result['count'] ?: 0;
        $dashboard_stats['total_reward_stock'] = $result['total_stock'] ?: 0;
    } else {
        $dashboard_stats['available_rewards'] = 0;
        $dashboard_stats['total_reward_stock'] = 0;
    }

} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    // Set default values on error
    $dashboard_stats = array_merge([
        'total_residents' => 0,
        'total_workers' => 0,
        'total_pickups' => 0,
        'points_distributed' => 0,
        'new_residents_month' => 0,
        'available_rewards' => 0,
        'total_reward_stock' => 0
    ], $dashboard_stats);
}

// Get top performers for leaderboard with enhanced error handling
try {
    $stmt = prepare_statement($conn, "
        SELECT u.user_id, u.first_name, u.last_name, u.address, 
               COALESCE(SUM(CASE WHEN pl.transaction_type = 'Earned' THEN pl.points ELSE -pl.points END), 0) as total_points
        FROM users u 
        LEFT JOIN pointsledger pl ON u.user_id = pl.user_id 
        WHERE u.role = 'resident' 
        GROUP BY u.user_id, u.first_name, u.last_name, u.address
        ORDER BY total_points DESC 
        LIMIT 5
    ", ['context' => 'top_residents']);
    
    if ($stmt) {
        $stmt->execute();
        $top_residents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $top_residents = [];
    }

    // Get top puroks for leaderboard
    $stmt = prepare_statement($conn, "
        SELECT p.purok_id, p.name as purok_name,
               COALESCE(SUM(CASE WHEN pl.transaction_type = 'Earned' THEN pl.points ELSE -pl.points END), 0) as total_points,
               COUNT(DISTINCT u.user_id) as resident_count
        FROM puroks p
        LEFT JOIN users u ON p.purok_id = u.purok_id AND u.role = 'resident'
        LEFT JOIN pointsledger pl ON u.user_id = pl.user_id
        GROUP BY p.purok_id, p.name
        HAVING resident_count > 0
        ORDER BY total_points DESC
        LIMIT 5
    ");
    
    if ($stmt) {
        $stmt->execute();
        $top_puroks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $top_puroks = [];
    }

} catch (Exception $e) {
    error_log("Leaderboard error: " . $e->getMessage());
    $top_residents = [];
    $top_puroks = [];
}



// Get pickup requests overview
$pickup_overview = [];
$statuses = ['Pending', 'Scheduled', 'Completed'];
foreach ($statuses as $status) {
    $stmt = prepare_statement($conn, "SELECT COUNT(*) as count FROM pickuprequests WHERE status = ?");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $pickup_overview[strtolower($status)] = $stmt->get_result()->fetch_assoc()['count'];
}

// Get completed pickups today
$stmt = prepare_statement($conn, "SELECT COUNT(*) as count FROM pickuprequests WHERE status = 'Completed' AND DATE(created_at) = CURDATE()");
$stmt->execute();
$pickup_overview['completed_today'] = $stmt->get_result()->fetch_assoc()['count'];

// Get current rewards status
$stmt = prepare_statement($conn, "
    SELECT r.*, 
           CASE 
               WHEN r.stock = 0 THEN 'Out of Stock'
               WHEN r.stock <= 10 THEN 'Low Stock'
               ELSE 'In Stock'
           END as stock_status
    FROM rewards r 
    ORDER BY r.stock ASC 
    LIMIT 3
");
$stmt->execute();
$rewards_status = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get collection routes status with actual stops count
$stmt = prepare_statement($conn, "
    SELECT cr.route_id, cr.generated_on,
           CONCAT(w.first_name, ' ', w.last_name) as worker_name,
           COUNT(rs.stop_id) as total_stops,
           COUNT(CASE WHEN pr.status = 'Completed' THEN 1 END) as completed_stops
    FROM collectionroutes cr
    JOIN users w ON cr.worker_id = w.user_id
    LEFT JOIN routestops rs ON cr.route_id = rs.route_id
    LEFT JOIN pickuprequests pr ON rs.request_id = pr.request_id
    WHERE DATE(cr.generated_on) = CURDATE()
    GROUP BY cr.route_id, cr.generated_on, w.first_name, w.last_name
    ORDER BY cr.generated_on DESC
    LIMIT 3
");
$stmt->execute();
$collection_routes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent redemptions
$stmt = prepare_statement($conn, "
    SELECT rr.*, r.name as reward_name, u.first_name, u.last_name
    FROM rewardredemptions rr
    JOIN rewards r ON rr.reward_id = r.reward_id
    JOIN users u ON rr.user_id = u.user_id
    ORDER BY rr.created_at DESC
    LIMIT 3
");
$stmt->execute();
$recent_redemptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent notifications (using pickup requests and reward redemptions as notification sources)
$stmt = prepare_statement($conn, "
    SELECT 
        'Pickup Request' as trigger_event,
        CONCAT('New pickup request from ', u.first_name, ' ', u.last_name) as message,
        pr.created_at as sent_at,
        u.first_name,
        u.last_name
    FROM pickuprequests pr
    JOIN users u ON pr.resident_id = u.user_id
    WHERE pr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    UNION ALL
    
    SELECT 
        'Reward Redemption' as trigger_event,
        CONCAT(u.first_name, ' ', u.last_name, ' redeemed ', r.name) as message,
        rr.created_at as sent_at,
        u.first_name,
        u.last_name
    FROM rewardredemptions rr
    JOIN users u ON rr.user_id = u.user_id
    JOIN rewards r ON rr.reward_id = r.reward_id
    WHERE rr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    ORDER BY sent_at DESC
    LIMIT 5
");
$stmt->execute();
$recent_notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get system settings
$system_name = getSystemSetting($conn, 'barangay_name', 'BoteBank');

// Get monthly analytics data for charts
$stmt = prepare_statement($conn, "
    SELECT 
        MONTH(created_at) as month,
        YEAR(created_at) as year,
        MONTHNAME(created_at) as month_name,
        SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE 0 END) as points_earned,
        SUM(CASE WHEN transaction_type = 'Redeemed' THEN points ELSE 0 END) as points_redeemed
    FROM pointsledger 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY year, month
");
$stmt->execute();
$analytics_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($system_name); ?> - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen font-sans">
    <?php include 'admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="ml-64">
        <!-- Enhanced Header Section -->
        <div class="gradient-bg shadow-lg">
            <div class="max-w-7xl mx-auto px-6 py-8">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                            <i data-lucide="gauge" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">Admin Dashboard</h1>
                            <p class="text-white text-opacity-80 flex items-center">
                                <i data-lucide="calendar" class="w-4 h-4 mr-2"></i>
                                Welcome back! Here's what's happening today.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="flex items-center space-x-3">
                        <button onclick="refreshDashboard()" class="flex items-center space-x-2 px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white rounded-lg transition-all duration-200">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                            <span>Refresh</span>
                        </button>
                        <div class="text-white text-opacity-80 text-sm">
                            <i data-lucide="clock" class="w-4 h-4 inline mr-1"></i>
                            <?php echo date('M j, Y g:i A'); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Enhanced Quick Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                    <!-- Total Residents -->
                    <div class="stat-card bg-white rounded-xl p-6 card-shadow card-hover border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center">
                                    <i data-lucide="users" class="w-6 h-6 text-white"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($dashboard_stats['total_residents']); ?></p>
                                    <p class="text-sm text-gray-600 flex items-center">
                                        <i data-lucide="home" class="w-3 h-3 mr-1"></i>
                                        Total Residents
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Workers -->
                    <div class="stat-card bg-white rounded-xl p-6 card-shadow card-hover border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center">
                                    <i data-lucide="truck" class="w-6 h-6 text-white"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($dashboard_stats['total_workers']); ?></p>
                                    <p class="text-sm text-gray-600 flex items-center">
                                        <i data-lucide="hard-hat" class="w-3 h-3 mr-1"></i>
                                        Active Workers
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Pickups -->
                    <div class="stat-card bg-white rounded-xl p-6 card-shadow card-hover border-l-4 border-emerald-500">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-xl flex items-center justify-center">
                                    <i data-lucide="package" class="w-6 h-6 text-white"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($dashboard_stats['total_pickups']); ?></p>
                                    <p class="text-sm text-gray-600 flex items-center">
                                        <i data-lucide="recycle" class="w-3 h-3 mr-1"></i>
                                        Total Pickups
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Points Distributed -->
                    <div class="stat-card bg-white rounded-xl p-6 card-shadow card-hover border-l-4 border-yellow-500">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-xl flex items-center justify-center">
                                    <i data-lucide="star" class="w-6 h-6 text-white"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($dashboard_stats['points_distributed']); ?></p>
                                    <p class="text-sm text-gray-600 flex items-center">
                                        <i data-lucide="award" class="w-3 h-3 mr-1"></i>
                                        Points Earned
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- New Residents -->
                    <div class="stat-card bg-white rounded-xl p-6 card-shadow card-hover border-l-4 border-purple-500">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center">
                                    <i data-lucide="user-plus" class="w-6 h-6 text-white"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($dashboard_stats['new_residents_month']); ?></p>
                                    <p class="text-sm text-gray-600 flex items-center">
                                        <i data-lucide="calendar-plus" class="w-3 h-3 mr-1"></i>
                                        New This Month
                                        <?php if ($dashboard_stats['new_residents_month'] > 0): ?>
                                            <span class="ml-2 px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full pulse-animation">New</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Available Rewards -->
                    <div class="stat-card bg-white rounded-xl p-6 card-shadow card-hover border-l-4 border-orange-500">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center">
                                    <i data-lucide="gift" class="w-6 h-6 text-white"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($dashboard_stats['available_rewards']); ?></p>
                                    <p class="text-sm text-gray-600 flex items-center">
                                        <i data-lucide="package-2" class="w-3 h-3 mr-1"></i>
                                        Rewards Available
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <!-- Main Dashboard Content -->
    <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Enhanced Eco-Leaderboard -->
                <div class="bg-white rounded-2xl p-6 card-shadow card-hover">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg flex items-center justify-center mr-3">
                                <i data-lucide="trophy" class="w-5 h-5 text-white"></i>
                            </div>
                            Eco-Leaderboard
                        </h2>
                        <div class="flex space-x-2">
                            <button id="btn-residents" 
                                class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl text-sm font-medium transition-all duration-200 flex items-center">
                                <i data-lucide="users" class="w-4 h-4 mr-2"></i>
                                Residents
                            </button>
                            <button id="btn-puroks" 
                                class="px-4 py-2 bg-gray-100 text-gray-600 hover:bg-gray-200 rounded-xl text-sm font-medium transition-all duration-200 flex items-center">
                                <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i>
                                Puroks
                            </button>
                        </div>
                    </div>

                    <!-- Residents Leaderboard -->
                    <div id="leaderboard-residents" class="space-y-4">
                        <?php if (empty($top_residents)): ?>
                            <div class="text-center py-12">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i data-lucide="users" class="w-8 h-8 text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No Resident Data</h3>
                                <p class="text-gray-500">No resident activity data available yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($top_residents as $index => $resident): 
                                $position_icons = ['👑', '🥈', '🥉', '🏅', '⭐'];
                                $position_colors = [
                                    'bg-gradient-to-r from-yellow-50 to-yellow-100 border-yellow-300 shadow-yellow-100',
                                    'bg-gradient-to-r from-gray-50 to-gray-100 border-gray-300 shadow-gray-100',
                                    'bg-gradient-to-r from-orange-50 to-orange-100 border-orange-300 shadow-orange-100',
                                    'bg-gradient-to-r from-blue-50 to-blue-100 border-blue-300 shadow-blue-100',
                                    'bg-gradient-to-r from-purple-50 to-purple-100 border-purple-300 shadow-purple-100'
                                ];
                                $text_colors = ['text-yellow-700', 'text-gray-700', 'text-orange-700', 'text-blue-700', 'text-purple-700'];
                            ?>
                                <div class="leaderboard-card flex items-center justify-between p-6 <?php echo $position_colors[$index]; ?> rounded-xl border shadow-lg">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-md mr-4">
                                            <span class="text-2xl"><?php echo $position_icons[$index]; ?></span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 text-lg">
                                                <?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']); ?>
                                            </p>
                                            <p class="text-sm <?php echo $text_colors[$index]; ?> flex items-center">
                                                <i data-lucide="map-pin" class="w-3 h-3 mr-1"></i>
                                                <?php echo htmlspecialchars($resident['address'] ?: 'Address not set'); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-2xl font-bold text-green-600">
                                            <?php echo number_format($resident['total_points']); ?>
                                        </span>
                                        <p class="text-sm text-gray-600">points</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Puroks Leaderboard -->
                    <div id="leaderboard-puroks" class="space-y-4 hidden">
                        <?php if (empty($top_puroks)): ?>
                            <div class="text-center py-12">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i data-lucide="map-pin" class="w-8 h-8 text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No Purok Data</h3>
                                <p class="text-gray-500">No purok activity data available yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($top_puroks as $index => $purok): 
                                $position_icons = ['👑', '🥈', '🥉', '🏅', '⭐'];
                                $position_colors = [
                                    'bg-gradient-to-r from-yellow-50 to-yellow-100 border-yellow-300',
                                    'bg-gradient-to-r from-gray-50 to-gray-100 border-gray-300',
                                    'bg-gradient-to-r from-orange-50 to-orange-100 border-orange-300',
                                    'bg-gradient-to-r from-blue-50 to-blue-100 border-blue-300',
                                    'bg-gradient-to-r from-purple-50 to-purple-100 border-purple-300'
                                ];
                            ?>
                                <div class="leaderboard-card flex items-center justify-between p-6 <?php echo $position_colors[$index]; ?> rounded-xl border shadow-lg">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-md mr-4">
                                            <span class="text-2xl"><?php echo $position_icons[$index]; ?></span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 text-lg">
                                                <?php echo htmlspecialchars($purok['purok_name']); ?>
                                            </p>
                                            <p class="text-sm text-gray-600 flex items-center">
                                                <i data-lucide="users" class="w-3 h-3 mr-1"></i>
                                                <?php echo $purok['resident_count']; ?> residents
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-2xl font-bold text-green-600">
                                            <?php echo number_format($purok['total_points']); ?>
                                        </span>
                                        <p class="text-sm text-gray-600">points</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>


                <!-- Enhanced Pickup Requests Overview -->
                <div class="bg-white rounded-2xl p-6 card-shadow card-hover">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center mr-3">
                            <i data-lucide="bar-chart-3" class="w-5 h-5 text-white"></i>
                        </div>
                        Pickup Requests Overview
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="text-center p-6 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl border border-yellow-200 shadow-lg card-hover">
                            <div class="w-12 h-12 bg-yellow-500 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="clock" class="w-6 h-6 text-white"></i>
                            </div>
                            <p class="text-3xl font-bold text-yellow-600 mb-1"><?php echo $pickup_overview['pending']; ?></p>
                            <p class="text-sm font-medium text-yellow-700">Pending Requests</p>
                        </div>
                        <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200 shadow-lg card-hover">
                            <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="calendar-check" class="w-6 h-6 text-white"></i>
                            </div>
                            <p class="text-3xl font-bold text-blue-600 mb-1"><?php echo $pickup_overview['scheduled']; ?></p>
                            <p class="text-sm font-medium text-blue-700">Scheduled</p>
                        </div>
                        <div class="text-center p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border border-green-200 shadow-lg card-hover">
                            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                            </div>
                            <p class="text-3xl font-bold text-green-600 mb-1"><?php echo $pickup_overview['completed_today']; ?></p>
                            <p class="text-sm font-medium text-green-700">Completed Today</p>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="pickupChart"></canvas>
                    </div>
                </div>

                <!-- Enhanced Analytics -->
                <div class="bg-white rounded-2xl p-6 card-shadow card-hover">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                            <i data-lucide="trending-up" class="w-5 h-5 text-white"></i>
                        </div>
                        Points Analytics
                        <span class="ml-auto text-sm font-normal text-gray-500">Last 6 months</span>
                    </h2>
                    <div class="chart-container">
                        <canvas id="analyticsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-8">
                
                <!-- Reward Management -->
                <div class="bg-white rounded-xl p-6 card-shadow">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <span class="text-orange-600 mr-2">🎁</span>
                        Reward Management
                    </h2>
                    <div class="space-y-4">
                        <?php if (empty($rewards_status)): ?>
                            <div class="text-center py-4 text-gray-500">
                                <p>No rewards configured yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($rewards_status as $reward): 
                                $status_colors = [
                                    'In Stock' => 'bg-green-100 text-green-800',
                                    'Low Stock' => 'bg-yellow-100 text-yellow-800',
                                    'Out of Stock' => 'bg-red-100 text-red-800'
                                ];
                            ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($reward['name']); ?></h3>
                                        <span class="<?php echo $status_colors[$reward['stock_status']]; ?> text-xs px-2 py-1 rounded-full">
                                            <?php echo $reward['stock_status']; ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2"><?php echo number_format($reward['points_required']); ?> points required</p>
                                    <p class="text-xs text-gray-500">Stock: <?php echo $reward['stock']; ?> units</p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <h4 class="font-medium text-gray-900 mb-2">Recent Redemptions</h4>
                        <div class="text-sm text-gray-600 space-y-1">
                            <?php if (empty($recent_redemptions)): ?>
                                <p class="text-gray-500">No recent redemptions</p>
                            <?php else: ?>
                                <?php foreach ($recent_redemptions as $redemption): ?>
                                    <p>• <?php echo htmlspecialchars($redemption['first_name'] . ' ' . substr($redemption['last_name'], 0, 1) . '.'); ?> redeemed <?php echo htmlspecialchars($redemption['reward_name']); ?></p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Collection Routes & Stops -->
                <div class="bg-white rounded-xl p-6 card-shadow">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <span class="text-indigo-600 mr-2">🗺️</span>
                        Collection Routes
                    </h2>
                    <div class="space-y-4">
                        <?php if (empty($collection_routes)): ?>
                            <div class="text-center py-4 text-gray-500">
                                <p>No collection routes for today</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($collection_routes as $route): 
                                $completion_rate = $route['total_stops'] > 0 ? ($route['completed_stops'] / $route['total_stops']) * 100 : 0;
                                $status = $completion_rate == 100 ? 'Completed' : ($completion_rate > 0 ? 'Active' : 'Scheduled');
                                $status_colors = [
                                    'Active' => 'bg-green-100 text-green-800',
                                    'Scheduled' => 'bg-blue-100 text-blue-800',
                                    'Completed' => 'bg-gray-100 text-gray-800'
                                ];
                            ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="font-semibold text-gray-900">Route #<?php echo $route['route_id']; ?></h3>
                                        <span class="<?php echo $status_colors[$status]; ?> text-xs px-2 py-1 rounded-full"><?php echo $status; ?></span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-1">Worker: <?php echo htmlspecialchars($route['worker_name']); ?></p>
                                    <p class="text-xs text-gray-500">
                                        <?php if ($status == 'Completed'): ?>
                                            All stops completed | <?php echo $route['completed_stops']; ?> pickups
                                        <?php elseif ($status == 'Active'): ?>
                                            Pending stops: <?php echo $route['total_stops'] - $route['completed_stops']; ?> | Completed: <?php echo $route['completed_stops']; ?>
                                        <?php else: ?>
                                            Pending stops: <?php echo $route['total_stops']; ?> | Starts: <?php echo date('g:i A', strtotime($route['generated_on'])); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications Log -->
                <div class="bg-white rounded-xl p-6 card-shadow">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <span class="text-red-600 mr-2">🔔</span>
                        Recent Notifications
                    </h2>
                    <div class="space-y-3">
                        <?php if (empty($recent_notifications)): ?>
                            <div class="text-center py-4 text-gray-500">
                                <p>No recent notifications</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_notifications as $notification): 
                                $event_icons = [
                                    'Pickup Reminder' => '📱',
                                    'Collection Confirmation' => '✅',
                                    'Points Update' => '⭐',
                                    'Leaderboard Update' => '🏆',
                                    'Reward Redemption' => '🎁'
                                ];
                                $event_colors = [
                                    'Pickup Reminder' => 'bg-blue-50 border-blue-200',
                                    'Collection Confirmation' => 'bg-green-50 border-green-200',
                                    'Points Update' => 'bg-yellow-50 border-yellow-200',
                                    'Leaderboard Update' => 'bg-purple-50 border-purple-200',
                                    'Reward Redemption' => 'bg-purple-50 border-purple-200'
                                ];
                                $icon = $event_icons[$notification['trigger_event']] ?? '📱';
                                $color = $event_colors[$notification['trigger_event']] ?? 'bg-gray-50 border-gray-200';
                                $text_color = str_replace(['50', '200'], ['600', '600'], $color);
                                $text_color = str_replace(['bg-', 'border-'], '', explode(' ', $text_color)[0]);
                            ?>
                                <div class="flex items-start space-x-3 p-3 <?php echo $color; ?> rounded-lg border">
                                    <span class="text-<?php echo $text_color; ?> text-sm"><?php echo $icon; ?></span>
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars(substr($notification['message'], 0, 60) . (strlen($notification['message']) > 60 ? '...' : '')); ?></p>
                                        <p class="text-xs text-gray-500">
                                            Sent <?php echo date('M j, g:i A', strtotime($notification['sent_at'])); ?> to <?php echo htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
    const pickupOverview = {
        pending: <?php echo $pickup_overview['pending']; ?>,
        scheduled: <?php echo $pickup_overview['scheduled']; ?>,
        completed_today: <?php echo $pickup_overview['completed_today']; ?>
    };

    const analyticsData = {
        labels: [
            <?php 
                if (!empty($analytics_data)) {
                    echo "'" . implode("', '", array_map(function($item) { return substr($item['month_name'], 0, 3); }, $analytics_data)) . "'";
                } else {
                    echo "'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'";
                }
            ?>
        ],
        points_earned: [
            <?php 
                if (!empty($analytics_data)) {
                    echo implode(', ', array_map(function($item) { return $item['points_earned'] ?: 0; }, $analytics_data));
                } else {
                    echo '0, 0, 0, 0, 0, 0';
                }
            ?>
        ],
        points_redeemed: [
            <?php 
                if (!empty($analytics_data)) {
                    echo implode(', ', array_map(function($item) { return $item['points_redeemed'] ?: 0; }, $analytics_data));
                } else {
                    echo '0, 0, 0, 0, 0, 0';
                }
            ?>
        ]
    };
</script>

<!-- Load your external JS -->
<script src="assets/js/dashboard.js"></script>

    </div>
<script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'976cf7a0b6355dce',t:'MTc1NjQ4MDE0My4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>
