<?php
require_once '../config/database.php';
require_once '../includes/system_functions.php';
require '../includes/auth_res.php'; // Ensure only resident can access

// Ensure system settings table exists
ensureSystemSettingsTable($conn);

// Get system settings
$system_name = getSystemSetting($conn, 'barangay_name', 'BoteBank');

// Get user data and stats
$user_id = $_SESSION['user_id'];

// Get time period for leaderboard (default to monthly for dashboard)
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$valid_periods = ['weekly', 'monthly', 'all-time'];
if (!in_array($period, $valid_periods)) {
    $period = 'monthly';
}

// Calculate date range based on period
if ($period === 'weekly') {
    $leaderboard_date_filter = " AND pl.created_at >= '" . date('Y-m-d', strtotime('-7 days')) . "'";
    $period_label = 'Weekly';
} elseif ($period === 'monthly') {
    $leaderboard_date_filter = " AND pl.created_at >= '" . date('Y-m-d', strtotime('-30 days')) . "'";
    $period_label = 'Monthly';
} else {
    $leaderboard_date_filter = "";
    $period_label = 'All-Time';
}

$stmt = prepare_statement($conn, "SELECT first_name, last_name, email, phone, address, profile_photo FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Get user total points
$total_points = getUserTotalPoints($conn, $user_id);

// Get user profile display
$profile_display = getUserProfileDisplay($user_data);

// Get detailed user statistics
$stats = [];

// Total points earned vs redeemed
$stmt = prepare_statement($conn, "
    SELECT 
        COALESCE(SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE 0 END), 0) as points_earned,
        COALESCE(SUM(CASE WHEN transaction_type = 'Redeemed' THEN points ELSE 0 END), 0) as points_redeemed
    FROM pointsledger WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$points_data = $stmt->get_result()->fetch_assoc();

// Ensure we have default values if no data is returned
$stats['points_earned'] = isset($points_data['points_earned']) ? $points_data['points_earned'] : 0;
$stats['points_redeemed'] = isset($points_data['points_redeemed']) ? $points_data['points_redeemed'] : 0;

// Request counts - include all possible statuses
$stmt = prepare_statement($conn, "
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_requests,
        SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled_requests,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_requests,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_requests,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_requests,
        COALESCE(SUM(CASE WHEN status = 'Completed' THEN weight_kg ELSE 0 END), 0) as total_weight
    FROM pickuprequests WHERE resident_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$request_data = $stmt->get_result()->fetch_assoc();

// Ensure we have valid data before merging
if ($request_data) {
    $stats = array_merge($stats, $request_data);
} else {
    // Set default values if no data is returned
    $stats['total_requests'] = 0;
    $stats['pending_requests'] = 0;
    $stats['scheduled_requests'] = 0;
    $stats['in_progress_requests'] = 0;
    $stats['completed_requests'] = 0;
    $stats['cancelled_requests'] = 0;
    $stats['total_weight'] = 0;
}

// Recent pickup requests
$stmt = prepare_statement($conn, "
    SELECT pr.*, rt.name as recyclable_type, rt.points_per_kg
    FROM pickuprequests pr
    LEFT JOIN recyclabletypes rt ON pr.type_id = rt.type_id
    WHERE pr.resident_id = ?
    ORDER BY pr.created_at DESC
    LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Recent reward redemptions
$stmt = prepare_statement($conn, "
    SELECT rr.*, r.name as reward_name, r.points_required
    FROM rewardredemptions rr
    LEFT JOIN rewards r ON rr.reward_id = r.reward_id
    WHERE rr.user_id = ?
    ORDER BY rr.created_at DESC
    LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_redemptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get current user's purok_id
$stmt = prepare_statement($conn, "SELECT purok_id FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_purok_result = $stmt->get_result();
$user_purok_data = $user_purok_result->fetch_assoc();
$user_purok_id = $user_purok_data['purok_id'] ?? null;

// Get individual leaderboard (top residents)
$stmt = prepare_statement($conn, "
    SELECT 
        u.user_id,
        u.first_name,
        u.last_name,
        COALESCE(SUM(CASE WHEN pl.transaction_type = 'Earned' THEN pl.points ELSE -pl.points END), 0) as total_points,
        ROW_NUMBER() OVER (ORDER BY COALESCE(SUM(CASE WHEN pl.transaction_type = 'Earned' THEN pl.points ELSE -pl.points END), 0) DESC) as ranking
    FROM users u
    LEFT JOIN pointsledger pl ON u.user_id = pl.user_id" . $leaderboard_date_filter . "
    WHERE u.role = 'resident'
    GROUP BY u.user_id, u.first_name, u.last_name
    HAVING total_points > 0
    ORDER BY total_points DESC
    LIMIT 10");
$stmt->execute();
$leaderboard = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$top_residents = $leaderboard; // For individual ranking

// Get purok leaderboard (top puroks by total points)
$stmt = prepare_statement($conn, "
    SELECT 
        p.purok_id,
        p.name as purok_name,
        COALESCE(SUM(CASE WHEN pl.transaction_type = 'Earned' THEN pl.points ELSE -pl.points END), 0) as total_points
    FROM puroks p
    LEFT JOIN users u ON p.purok_id = u.purok_id
    LEFT JOIN pointsledger pl ON u.user_id = pl.user_id" . $leaderboard_date_filter . "
    WHERE u.role = 'resident'
    GROUP BY p.purok_id, p.name
    HAVING total_points > 0
    ORDER BY total_points DESC
    LIMIT 10");
$stmt->execute();
$top_puroks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Find current user's position in individual leaderboard
$user_position = 0;
foreach ($leaderboard as $position => $user) {
    if ($user['user_id'] == $user_id) {
        $user_position = $position + 1;
        break;
    }
}

// Get recent points transactions for notifications
$stmt = prepare_statement($conn, "
    SELECT pl.*, pr.preferred_date as pickup_date, rt.name as recyclable_type
    FROM pointsledger pl
    LEFT JOIN pickuprequests pr ON pl.request_id = pr.request_id
    LEFT JOIN recyclabletypes rt ON pr.type_id = rt.type_id
    WHERE pl.user_id = ?
    ORDER BY pl.created_at DESC
    LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$dashboard_data = [
    'pointsEarned' => (int) ($stats['points_earned'] ?? 0),
    'pointsRedeemed' => (int) ($stats['points_redeemed'] ?? 0),
    'totalPoints' => (int) $total_points,
];
$json_dashboard_data = json_encode($dashboard_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($system_name); ?> - Resident Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="../assets/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body class="bg-gradient-to-br from-bote-stone-50 via-bote-info-50 to-bote-info-50 min-h-screen">
    <!-- Include Sidebar -->
    <?php include 'resident_sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="ml-64 min-h-screen">
        <!-- Enhanced Header Section -->
        <div class="bg-gradient-to-br from-bote-green-500 via-bote-green-600 to-bote-green-700 shadow-2xl">
            <div class="max-w-7xl mx-auto px-8 py-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
                    <!-- Enhanced Profile Info -->
                    <div class="flex items-center gap-6">
                        <div class="relative">
                            <?php if ($profile_display['type'] === 'photo'): ?>
                                <img src="../uploads/profiles/thumbs/<?php echo htmlspecialchars($profile_display['value']); ?>" 
                                     alt="Profile Photo" 
                                     class="w-20 h-20 rounded-2xl object-cover ring-4 ring-white ring-opacity-30 shadow-2xl">
                            <?php else: ?>
                                <div class="w-20 h-20 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center text-white text-2xl font-bold shadow-2xl backdrop-blur-sm">
                                    <?php echo htmlspecialchars($profile_display['value']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="absolute -bottom-2 -right-2 w-6 h-6 bg-bote-green-400 rounded-full border-2 border-white"></div>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white mb-2">
                                Welcome back, <?php echo htmlspecialchars($user_data['first_name']); ?>! ?
                            </h1>
                            <div class="text-white text-opacity-90 space-y-1">
                                <p class="flex items-center gap-2">
                                    <i data-lucide="mail" class="w-4 h-4"></i>
                                    <?php echo htmlspecialchars($user_data['email']); ?>
                                </p>
                                <p class="flex items-center gap-2">
                                    <i data-lucide="phone" class="w-4 h-4"></i>
                                    <?php echo htmlspecialchars($user_data['phone']); ?>
                                </p>
                                <p class="flex items-center gap-2">
                                    <i data-lucide="map-pin" class="w-4 h-4"></i>
                                    <?php echo htmlspecialchars($user_data['address'] ?: 'Address not set'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Enhanced Points Display -->
                    <div class="points-gradient rounded-3xl p-8 text-white text-center lg:text-right pulse-glow relative overflow-hidden">
                        <div class="absolute inset-0 bg-white bg-opacity-10"></div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-center lg:justify-end gap-3 mb-2">
                                <i data-lucide="coins" class="w-8 h-8"></i>
                                <p class="text-lg font-semibold">EcoPoints Balance</p>
                            </div>
                            <p class="text-5xl font-bold mb-2"><?php echo number_format($total_points); ?></p>
                            <p class="text-bote-green-100 text-sm">Keep collecting to earn more! ?</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Main Dashboard -->
        <div class="max-w-7xl mx-auto px-8 py-8">
            <!-- Top Row - Enhanced Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Requests Card -->
                <div class="stat-card rounded-2xl p-6 card-hover relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-bote-info-400 to-bote-info-600 rounded-full transform translate-x-8 -translate-y-8 opacity-20"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-info-500 to-bote-info-600 rounded-xl flex items-center justify-center shadow-lg">
                                <i data-lucide="recycle" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($stats['total_requests'] ?? 0); ?></p>
                                <p class="text-sm text-bote-stone-600">Total Requests</p>
                            </div>
                        </div>
                        <div class="w-full bg-bote-stone-200 rounded-full h-2">
                            <div class="progress-bar h-2 rounded-full" style="width: <?php echo e(min(100, (($stats['total_requests'] ?? 0) / 10) * 100)); ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Completed Requests Card -->
                <div class="stat-card rounded-2xl p-6 card-hover relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-bote-green-400 to-bote-green-600 rounded-full transform translate-x-8 -translate-y-8 opacity-20"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-xl flex items-center justify-center shadow-lg">
                                <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($stats['completed_requests'] ?? 0); ?></p>
                                <p class="text-sm text-bote-stone-600">Completed</p>
                            </div>
                        </div>
                        <div class="w-full bg-bote-stone-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 h-2 rounded-full" style="width: <?php echo e(($stats['total_requests'] ?? 0) > 0 ? (($stats['completed_requests'] ?? 0) / ($stats['total_requests'] ?? 1)) * 100 : 0); ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Pending Requests Card -->
                <div class="stat-card rounded-2xl p-6 card-hover relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full transform translate-x-8 -translate-y-8 opacity-20"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                                <i data-lucide="clock" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($stats['pending_requests'] ?? 0); ?></p>
                                <p class="text-sm text-bote-stone-600">Pending</p>
                            </div>
                        </div>
                        <?php if (($stats['pending_requests'] ?? 0) > 0): ?>
                            <div class="flex items-center text-orange-600 text-sm">
                                <i data-lucide="alert-circle" class="w-4 h-4 mr-1"></i>
                                Awaiting pickup
                            </div>
                        <?php else: ?>
                            <div class="flex items-center text-bote-green-600 text-sm">
                                <i data-lucide="check" class="w-4 h-4 mr-1"></i>
                                All caught up!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ranking Card -->
                <div class="stat-card rounded-2xl p-6 card-hover relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-full transform translate-x-8 -translate-y-8 opacity-20"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center shadow-lg">
                                <i data-lucide="trophy" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-bote-stone-900">
                                    <?php echo e($user_position > 0 ? "#$user_position" : "N/A"); ?>
                                </p>
                                <p class="text-sm text-bote-stone-600">Barangay Rank</p>
                            </div>
                        </div>
                        <?php if ($user_position > 0 && $user_position <= 3): ?>
                            <div class="flex items-center text-yellow-600 text-sm">
                                <i data-lucide="star" class="w-4 h-4 mr-1"></i>
                                Top performer!
                            </div>
                        <?php elseif ($user_position > 0): ?>
                            <div class="flex items-center text-bote-info-600 text-sm">
                                <i data-lucide="trending-up" class="w-4 h-4 mr-1"></i>
                                Keep it up!
                            </div>
                        <?php else: ?>
                            <div class="flex items-center text-bote-stone-500 text-sm">
                                <i data-lucide="zap" class="w-4 h-4 mr-1"></i>
                                Start earning!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Middle Row - Enhanced Points Overview & Activity Chart -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <!-- Points Summary Chart -->
                <div class="lg:col-span-2 bg-white rounded-3xl shadow-xl p-8 card-hover border border-bote-stone-100">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                <i data-lucide="bar-chart-3" class="w-7 h-7 text-white"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-bote-stone-900">Points Overview</h2>
                                <p class="text-bote-stone-600">Your recycling impact</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-bote-stone-500">Total Balance</p>
                            <p class="text-3xl font-bold text-bote-green-600"><?php echo number_format($total_points); ?></p>
                        </div>
                    </div>
                    
                    <!-- Points Stats Grid -->
                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <div class="text-center p-6 bg-gradient-to-br from-bote-green-50 to-bote-green-50 rounded-2xl border border-bote-green-200">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                                <i data-lucide="trending-up" class="w-6 h-6 text-white"></i>
                            </div>
                            <p class="text-3xl font-bold text-bote-green-600"><?php echo number_format(isset($stats['points_earned']) ? $stats['points_earned'] : 0); ?></p>
                            <p class="text-sm text-bote-stone-600 font-medium">Points Earned</p>
                        </div>
                        <div class="text-center p-6 bg-gradient-to-br from-bote-info-50 to-bote-info-50 rounded-2xl border border-bote-info-200">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-info-500 to-bote-info-600 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                                <i data-lucide="gift" class="w-6 h-6 text-white"></i>
                            </div>
                            <p class="text-3xl font-bold text-bote-info-600"><?php echo number_format(isset($stats['points_redeemed']) ? $stats['points_redeemed'] : 0); ?></p>
                            <p class="text-sm text-bote-stone-600 font-medium">Points Redeemed</p>
                        </div>
                    </div>
                    
                    <!-- Chart Container -->
                    <div class="h-64 relative">
                        <canvas id="pointsChart" 
                                data-points-earned="<?php echo e(isset($stats['points_earned']) ? $stats['points_earned'] : 0); ?>" 
                                data-points-redeemed="<?php echo e(isset($stats['points_redeemed']) ? $stats['points_redeemed'] : 0); ?>" 
                                data-total-points="<?php echo e($total_points); ?>">
                        </canvas>
                    </div>
                </div>

                <!-- Enhanced Quick Actions & Goals -->
                <div class="space-y-6">
                    <!-- Quick Actions Card -->
                    <div class="bg-white rounded-3xl shadow-xl p-6 card-hover border border-bote-stone-100">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-info-500 to-bote-green-600 rounded-xl flex items-center justify-center shadow-lg">
                                <i data-lucide="zap" class="w-6 h-6 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-bote-stone-900">Quick Actions</h3>
                                <p class="text-bote-stone-600 text-sm">Start recycling now</p>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <a href="resident_new_request.php" class="w-full flex items-center gap-3 p-4 bg-gradient-to-r from-bote-green-500 to-bote-green-600 text-white rounded-xl hover:from-bote-green-600 hover:to-bote-green-700 transition-all duration-300 shadow-lg transform hover:scale-105">
                                <i data-lucide="plus" class="w-5 h-5"></i>
                                <span class="font-semibold">Schedule Pickup</span>
                            </a>
                            
                            <a href="resident_direct_dropoff.php" class="w-full flex items-center gap-3 p-4 bg-gradient-to-r from-bote-info-500 to-bote-info-600 text-white rounded-xl hover:from-bote-info-600 hover:to-bote-info-700 transition-all duration-300 shadow-lg transform hover:scale-105">
                                <i data-lucide="map-pin" class="w-5 h-5"></i>
                                <span class="font-semibold">Direct Drop-off</span>
                            </a>
                            
                            <a href="resident_rewards.php" class="w-full flex items-center gap-3 p-4 bg-gradient-to-r from-bote-green-500 to-bote-green-600 text-white rounded-xl hover:from-bote-green-600 hover:to-bote-green-700 transition-all duration-300 shadow-lg transform hover:scale-105">
                                <i data-lucide="gift" class="w-5 h-5"></i>
                                <span class="font-semibold">Browse Rewards</span>
                            </a>
                            
                            <a href="resident_leaderboard.php" class="w-full flex items-center gap-3 p-4 bg-gradient-to-r from-yellow-500 to-orange-600 text-white rounded-xl hover:from-yellow-600 hover:to-orange-700 transition-all duration-300 shadow-lg transform hover:scale-105">
                                <i data-lucide="trophy" class="w-5 h-5"></i>
                                <span class="font-semibold">View Rankings</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Eco Goals Card -->
                    <div class="bg-white rounded-3xl shadow-xl p-6 card-hover border border-bote-stone-100">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-xl flex items-center justify-center shadow-lg">
                                <i data-lucide="target" class="w-6 h-6 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-bote-stone-900">Eco Goals</h3>
                                <p class="text-bote-stone-600 text-sm">Your impact this month</p>
                            </div>
                        </div>
                        
                        <!-- Progress towards next milestone -->
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-bote-stone-600">Next Milestone</span>
                                    <span class="font-semibold text-bote-green-600"><?php echo number_format($total_points); ?>/1000</span>
                                </div>
                                <div class="w-full bg-bote-stone-200 rounded-full h-3">
                                    <div class="progress-bar h-3 rounded-full" style="width: <?php echo e(min(100, ($total_points / 1000) * 100)); ?>%"></div>
                                </div>
                                <p class="text-xs text-bote-stone-500 mt-1">Reach 1000 points for special rewards!</p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3 pt-4 border-t border-bote-stone-100">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-bote-green-600"><?php echo number_format($stats['total_weight'] ?? 0, 1); ?></div>
                                    <div class="text-xs text-bote-stone-500">KG Recycled</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-bote-info-600"><?php echo e($stats['completed_requests'] ?? 0); ?></div>
                                    <div class="text-xs text-bote-stone-500">Collections</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Bottom Row - Enhanced Activity & Leaderboard -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Enhanced Recent Activity -->
                <div class="bg-white rounded-3xl shadow-xl border border-bote-stone-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-bote-info-600 to-bote-green-700 px-8 py-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i data-lucide="activity" class="w-6 h-6 text-white"></i>
                                <h2 class="text-xl font-bold text-white">Recent Activity</h2>
                            </div>
                            <a href="resident_my_requests.php" class="text-bote-info-100 hover:text-white text-sm font-medium transition-colors">
                                View All ?
                            </a>
                        </div>
                        <p class="text-bote-info-100 mt-2">Your latest recycling activities</p>
                    </div>
                    
                    <div class="p-8">
                        <!-- Activity Tabs -->
                        <div class="flex space-x-1 bg-bote-stone-100 p-1 rounded-xl mb-6">
                            <button onclick="showActivityTab('pickups')" id="tab-pickups" class="flex-1 py-3 px-4 text-sm font-medium rounded-lg transition-all duration-200 bg-bote-green-600 text-white">
                                Pickups
                            </button>
                            <button onclick="showActivityTab('rewards')" id="tab-rewards" class="flex-1 py-3 px-4 text-sm font-medium rounded-lg transition-all duration-200 text-bote-stone-600 hover:text-bote-stone-800">
                                Rewards
                            </button>
                            <button onclick="showActivityTab('points')" id="tab-points" class="flex-1 py-3 px-4 text-sm font-medium rounded-lg transition-all duration-200 text-bote-stone-600 hover:text-bote-stone-800">
                                Points
                            </button>
                        </div>
                        
                        <!-- Pickup Requests Tab -->
                        <div id="activity-pickups" class="activity-tab space-y-4">
                            <?php if (!empty($recent_requests)): ?>
                                <?php foreach ($recent_requests as $request): ?>
                                <div class="flex items-center justify-between p-4 bg-bote-stone-50 rounded-xl hover:bg-bote-stone-100 transition-colors card-hover border border-bote-stone-200">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-xl flex items-center justify-center shadow-md">
                                            <i data-lucide="package" class="w-5 h-5 text-white"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-bote-stone-900"><?php echo htmlspecialchars($request['recyclable_type'] ?: 'Unknown Type'); ?></p>
                                            <p class="text-sm text-bote-stone-600"><?php echo number_format($request['weight_kg'], 1); ?> kg � <?php echo date('M j, Y', strtotime($request['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <?php 
                                        $status_class = '';
                                        switch($request['status']) {
                                            case 'Completed': $status_class = 'bg-bote-green-100 text-bote-green-700 border-bote-green-200'; break;
                                            case 'Scheduled': $status_class = 'bg-bote-info-100 text-bote-info-700 border-bote-info-200'; break;
                                            case 'Pending': $status_class = 'bg-yellow-100 text-yellow-700 border-yellow-200'; break;
                                            default: $status_class = 'bg-bote-stone-100 text-bote-stone-700 border-bote-stone-200';
                                        }
                                    ?>
                                    <span class="px-3 py-1 <?php echo e($status_class); ?> text-xs font-semibold rounded-full border">
                                        <?php echo htmlspecialchars($request['status']); ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-12">
                                    <div class="w-16 h-16 bg-bote-stone-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <i data-lucide="package-x" class="w-8 h-8 text-bote-stone-400"></i>
                                    </div>
                                    <p class="text-bote-stone-500 mb-4">No pickup requests yet</p>
                                    <a href="resident_new_request.php" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-bote-green-500 to-bote-green-600 text-white rounded-xl hover:from-bote-green-600 hover:to-bote-green-700 transition-all font-semibold shadow-lg">
                                        <i data-lucide="plus" class="w-4 h-4"></i>
                                        Create First Request
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Reward Redemptions Tab -->
                        <div id="activity-rewards" class="activity-tab space-y-4 hidden">
                            <?php if (!empty($recent_redemptions)): ?>
                                <?php foreach ($recent_redemptions as $redemption): ?>
                                <div class="flex items-center justify-between p-4 bg-bote-stone-50 rounded-xl hover:bg-bote-stone-100 transition-colors card-hover border border-bote-stone-200">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-xl flex items-center justify-center shadow-md">
                                            <i data-lucide="gift" class="w-5 h-5 text-white"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-bote-stone-900"><?php echo htmlspecialchars($redemption['reward_name'] ?: 'Unknown Reward'); ?></p>
                                            <p class="text-sm text-bote-stone-600">Qty: <?php echo e($redemption['quantity']); ?> � <?php echo number_format($redemption['points_required']); ?> points each</p>
                                        </div>
                                    </div>
                                    <?php 
                                        $status_class = '';
                                        switch($redemption['status']) {
                                            case 'Approved': $status_class = 'bg-bote-green-100 text-bote-green-700 border-bote-green-200'; break;
                                            case 'Pending': $status_class = 'bg-yellow-100 text-yellow-700 border-yellow-200'; break;
                                            case 'Rejected': $status_class = 'bg-red-100 text-red-700 border-red-200'; break;
                                            default: $status_class = 'bg-bote-stone-100 text-bote-stone-700 border-bote-stone-200';
                                        }
                                    ?>
                                    <span class="px-3 py-1 <?php echo e($status_class); ?> text-xs font-semibold rounded-full border">
                                        <?php echo htmlspecialchars($redemption['status']); ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-12">
                                    <div class="w-16 h-16 bg-bote-stone-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <i data-lucide="gift" class="w-8 h-8 text-bote-stone-400"></i>
                                    </div>
                                    <p class="text-bote-stone-500 mb-4">No rewards redeemed yet</p>
                                    <a href="resident_rewards.php" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-bote-green-500 to-bote-green-600 text-white rounded-xl hover:from-bote-green-600 hover:to-bote-green-700 transition-all font-semibold shadow-lg">
                                        <i data-lucide="gift" class="w-4 h-4"></i>
                                        Browse Rewards
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Points History Tab -->
                        <div id="activity-points" class="activity-tab space-y-4 hidden">
                            <?php if (!empty($recent_transactions)): ?>
                                <?php foreach ($recent_transactions as $transaction): ?>
                                <div class="flex gap-4 p-4 <?php echo e($transaction['transaction_type'] === 'Earned' ? 'bg-bote-green-50 border-bote-green-200' : 'bg-bote-info-50 border-bote-info-200'); ?> rounded-xl border">
                                    <div class="w-12 h-12 <?php echo e($transaction['transaction_type'] === 'Earned' ? 'bg-bote-green-500' : 'bg-bote-green-500'); ?> rounded-xl flex items-center justify-center shadow-md">
                                        <i data-lucide="<?php echo e($transaction['transaction_type'] === 'Earned' ? 'plus' : 'minus'); ?>" class="w-5 h-5 text-white"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-bote-stone-900">
                                            <?php if ($transaction['transaction_type'] === 'Earned'): ?>
                                                +<?php echo e($transaction['points']); ?> points earned
                                            <?php else: ?>
                                                -<?php echo e($transaction['points']); ?> points redeemed
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-sm text-bote-stone-600">
                                            <?php if ($transaction['transaction_type'] === 'Earned'): ?>
                                                From <?php echo htmlspecialchars($transaction['recyclable_type'] ?: 'recyclable collection'); ?>
                                            <?php else: ?>
                                                Reward redemption
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-xs text-bote-stone-500 mt-1"><?php echo e(formatDisplayDateTime($transaction['created_at'])); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-12">
                                    <div class="w-16 h-16 bg-bote-stone-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <i data-lucide="coins" class="w-8 h-8 text-bote-stone-400"></i>
                                    </div>
                                    <p class="text-bote-stone-500 mb-4">No point transactions yet</p>
                                    <a href="resident_new_request.php" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-bote-green-500 to-bote-green-600 text-white rounded-xl hover:from-bote-green-600 hover:to-bote-green-700 transition-all font-semibold shadow-lg">
                                        <i data-lucide="zap" class="w-4 h-4"></i>
                                        Start Earning Points
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- Enhanced Leaderboard Preview -->
                <div class="bg-white rounded-3xl shadow-xl border border-bote-stone-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-yellow-500 to-orange-600 px-8 py-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i data-lucide="trophy" class="w-6 h-6 text-white"></i>
                                <h2 class="text-xl font-bold text-white">Leaderboard</h2>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="flex bg-white bg-opacity-20 rounded-lg p-1">
                                    <a href="?period=weekly" class="px-3 py-1 rounded text-sm font-medium transition-all <?php echo e($period === 'weekly' ? 'bg-white text-yellow-600' : 'text-yellow-100 hover:text-white'); ?>">Weekly</a>
                                    <a href="?period=monthly" class="px-3 py-1 rounded text-sm font-medium transition-all <?php echo e($period === 'monthly' ? 'bg-white text-yellow-600' : 'text-yellow-100 hover:text-white'); ?>">Monthly</a>
                                    <a href="?period=all-time" class="px-3 py-1 rounded text-sm font-medium transition-all <?php echo e($period === 'all-time' ? 'bg-white text-yellow-600' : 'text-yellow-100 hover:text-white'); ?>">All-Time</a>
                                </div>
                                <a href="resident_leaderboard.php?period=<?php echo htmlspecialchars($period); ?>" class="text-yellow-100 hover:text-white text-sm font-medium transition-colors">
                                    View Full ?
                                </a>
                            </div>
                        </div>
                        <p class="text-yellow-100 mt-2">Top eco-champions (<?php echo htmlspecialchars($period_label); ?>)</p>
                    </div>
                    
                    <div class="p-8">
                        <!-- Individual Ranking -->
                        <div class="mb-8">
                            <h3 class="text-lg font-bold text-bote-stone-900 mb-4 flex items-center gap-2">
                                <i data-lucide="user" class="w-5 h-5 text-bote-green-600"></i>
                                Individual Ranking (<?php echo htmlspecialchars($period_label); ?>)
                            </h3>
                            <div class="space-y-3">
                                <?php if (!empty($top_residents)): ?>
                                    <?php foreach (array_slice($top_residents, 0, 5) as $index => $resident): ?>
                                    <div class="flex items-center justify-between p-4 
                                        <?php if ($resident['user_id'] == $user_id): echo 'bg-gradient-to-r from-bote-green-50 to-bote-green-50 border-2 border-bote-green-200 shadow-lg'; 
                                        elseif ($index == 0): echo 'bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200'; 
                                        else: echo 'bg-bote-stone-50 border border-bote-stone-200'; endif; ?> rounded-xl hover:shadow-md transition-all">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 
                                                <?php if ($index == 0): echo 'bg-gradient-to-br from-yellow-400 to-yellow-500 shadow-lg'; 
                                                elseif ($index == 1): echo 'bg-gradient-to-br from-bote-stone-400 to-bote-stone-500'; 
                                                elseif ($index == 2): echo 'bg-gradient-to-br from-amber-600 to-amber-700'; 
                                                else: echo 'bg-gradient-to-br from-bote-info-500 to-bote-info-600'; endif; ?> 
                                                text-white text-sm font-bold rounded-xl flex items-center justify-center shadow-md">
                                                <?php if ($index < 3): ?>
                                                    <i data-lucide="crown" class="w-5 h-5"></i>
                                                <?php else: ?>
                                                    <?php echo e($index + 1); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <span class="font-bold <?php echo e($resident['user_id'] == $user_id ? 'text-bote-green-700' : 'text-bote-stone-900'); ?>">
                                                    <?php 
                                                    if ($resident['user_id'] == $user_id) {
                                                        echo 'You (' . htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) . ')';
                                                    } else {
                                                        echo htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']); 
                                                    }
                                                    ?>
                                                </span>
                                                <p class="text-sm text-bote-stone-600">Rank #<?php echo e($index + 1); ?></p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-lg font-bold <?php echo e($resident['user_id'] == $user_id ? 'text-bote-green-700' : ($index == 0 ? 'text-yellow-700' : 'text-bote-stone-700')); ?>">
                                                <?php echo number_format($resident['total_points']); ?>
                                            </span>
                                            <p class="text-sm text-bote-stone-500">points</p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-8">
                                        <div class="w-16 h-16 bg-bote-stone-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                            <i data-lucide="users" class="w-8 h-8 text-bote-stone-400"></i>
                                        </div>
                                        <p class="text-bote-stone-500">No rankings available yet</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Purok Ranking -->
                        <div class="border-t border-bote-stone-200 pt-6">
                            <h3 class="text-lg font-bold text-bote-stone-900 mb-4 flex items-center gap-2">
                                <i data-lucide="map" class="w-5 h-5 text-bote-green-600"></i>
                                Purok Ranking (<?php echo htmlspecialchars($period_label); ?>)
                            </h3>
                            <div class="space-y-3">
                                <?php if (!empty($top_puroks)): ?>
                                    <?php foreach (array_slice($top_puroks, 0, 3) as $index => $purok): ?>
                                    <div class="flex items-center justify-between p-4 
                                        <?php if ($purok['purok_id'] == $user_purok_id): echo 'bg-gradient-to-r from-bote-green-50 to-bote-green-50 border-2 border-bote-green-200 shadow-lg'; 
                                        elseif ($index == 0): echo 'bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200'; 
                                        else: echo 'bg-bote-stone-50 border border-bote-stone-200'; endif; ?> rounded-xl hover:shadow-md transition-all">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 
                                                <?php if ($index == 0): echo 'bg-gradient-to-br from-yellow-400 to-yellow-500'; 
                                                elseif ($index == 1): echo 'bg-gradient-to-br from-bote-stone-400 to-bote-stone-500'; 
                                                elseif ($index == 2): echo 'bg-gradient-to-br from-amber-600 to-amber-700'; 
                                                else: echo 'bg-gradient-to-br from-bote-green-500 to-bote-green-600'; endif; ?> 
                                                text-white text-sm font-bold rounded-xl flex items-center justify-center shadow-md">
                                                <?php echo e($index + 1); ?>
                                            </div>
                                            <div>
                                                <span class="font-bold <?php echo e($purok['purok_id'] == $user_purok_id ? 'text-bote-green-700' : 'text-bote-stone-900'); ?>">
                                                    <?php 
                                                    if ($purok['purok_id'] == $user_purok_id) {
                                                        echo 'Your Purok (' . htmlspecialchars($purok['purok_name']) . ')';
                                                    } else {
                                                        echo htmlspecialchars($purok['purok_name']); 
                                                    }
                                                    ?>
                                                </span>
                                                <p class="text-sm text-bote-stone-600">Community rank</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-lg font-bold <?php echo e($purok['purok_id'] == $user_purok_id ? 'text-bote-green-700' : ($index == 0 ? 'text-yellow-700' : 'text-bote-stone-700')); ?>">
                                                <?php echo number_format($purok['total_points']); ?>
                                            </span>
                                            <p class="text-sm text-bote-stone-500">points</p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-8">
                                        <div class="w-16 h-16 bg-bote-stone-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                            <i data-lucide="map-pin" class="w-8 h-8 text-bote-stone-400"></i>
                                        </div>
                                        <p class="text-bote-stone-500">No purok rankings available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="resident-dashboard-data">
        <?php echo $json_dashboard_data; ?>
    </script>
    <script type="module" src="assets/js/dashboard.js"></script>
</body>
</html>
