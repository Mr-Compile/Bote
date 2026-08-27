<?php
require_once '../config/database.php';
require_once '../includes/system_functions.php';
require '../includes/auth_res.php'; // Ensure only resident can access

// Get user data for sidebar
$user_id = $_SESSION['user_id'];

// Fetch comprehensive history: points, pickup requests, dropoffs, and redemptions
$stmt = prepare_statement($conn, "
    SELECT 
        'points' as activity_type,
        pl.transaction_id as id,
        pl.points,
        pl.transaction_type,
        pl.created_at,
        pr.request_id,
        pr.status as request_status,
        pr.pickup_type,
        pr.preferred_date,
        pr.actual_weight,
        rt.name as recyclable_type,
        rt.image as recyclable_image,
        rt.unit as recyclable_unit,
        pr.quantity_pcs,
        NULL as redemption_id,
        NULL as redemption_status,
        NULL as reward_name,
        NULL as reward_image,
        dd.dropoff_id,
        dd.status as dropoff_status,
        dd.weight_kg as dropoff_weight,
        rt2.name as dropoff_recyclable_type,
        rt2.image as dropoff_recyclable_image,
        COALESCE(cp.name, cp2.name) as collection_point_name
    FROM pointsledger pl
    LEFT JOIN pickuprequests pr ON pl.request_id = pr.request_id
    LEFT JOIN recyclabletypes rt ON pr.type_id = rt.type_id
    LEFT JOIN direct_dropoffs dd ON pl.dropoff_id = dd.dropoff_id
    LEFT JOIN recyclabletypes rt2 ON dd.type_id = rt2.type_id
    LEFT JOIN collection_points cp ON pr.point_id = cp.point_id
    LEFT JOIN collection_points cp2 ON dd.point_id = cp2.point_id
    WHERE pl.user_id = ?
    
    UNION ALL
    
    SELECT 
        'request' as activity_type,
        pr.request_id as id,
        COALESCE(pr.points_earned, 0) as points,
        'Request' as transaction_type,
        pr.created_at,
        pr.request_id,
        pr.status as request_status,
        pr.pickup_type,
        pr.preferred_date,
        pr.actual_weight,
        rt.name as recyclable_type,
        rt.image as recyclable_image,
        rt.unit as recyclable_unit,
        pr.quantity_pcs,
        NULL as redemption_id,
        NULL as redemption_status,
        NULL as reward_name,
        NULL as reward_image,
        NULL as dropoff_id,
        NULL as dropoff_status,
        NULL as dropoff_weight,
        NULL as dropoff_recyclable_type,
        NULL as dropoff_recyclable_image,
        cp.name as collection_point_name
    FROM pickuprequests pr
    LEFT JOIN recyclabletypes rt ON pr.type_id = rt.type_id
    LEFT JOIN collection_points cp ON pr.point_id = cp.point_id
    WHERE pr.resident_id = ? 
    AND NOT EXISTS (
        SELECT 1 FROM pointsledger WHERE user_id = ? AND request_id = pr.request_id
    )
    
    UNION ALL
    
    SELECT 
        'dropoff' as activity_type,
        dd.dropoff_id as id,
        COALESCE(dd.points_earned, 0) as points,
        'Dropoff' as transaction_type,
        dd.created_at,
        NULL as request_id,
        NULL as request_status,
        'DirectDropoff' as pickup_type,
        NULL as preferred_date,
        dd.weight_kg as actual_weight,
        rt.name as recyclable_type,
        rt.image as recyclable_image,
        rt.unit as recyclable_unit,
        dd.quantity_pcs,
        NULL as redemption_id,
        NULL as redemption_status,
        NULL as reward_name,
        NULL as reward_image,
        dd.dropoff_id,
        dd.status as dropoff_status,
        dd.weight_kg as dropoff_weight,
        rt.name as dropoff_recyclable_type,
        rt.image as dropoff_recyclable_image,
        cp.name as collection_point_name
    FROM direct_dropoffs dd
    LEFT JOIN recyclabletypes rt ON dd.type_id = rt.type_id
    LEFT JOIN collection_points cp ON dd.point_id = cp.point_id
    WHERE dd.resident_id = ? 
    AND NOT EXISTS (
        SELECT 1 FROM pointsledger WHERE user_id = ? AND dropoff_id = dd.dropoff_id
    )
    
    UNION ALL
    
    SELECT 
        'redemption' as activity_type,
        rr.redemption_id as id,
        r.points_required * rr.quantity as points,
        'Redeemed' as transaction_type,
        rr.created_at,
        NULL as request_id,
        NULL as request_status,
        NULL as pickup_type,
        NULL as preferred_date,
        NULL as actual_weight,
        NULL as recyclable_type,
        NULL as recyclable_image,
        NULL as recyclable_unit,
        rr.quantity as quantity_pcs,
        rr.redemption_id,
        rr.status as redemption_status,
        r.name as reward_name,
        r.image as reward_image,
        NULL as dropoff_id,
        NULL as dropoff_status,
        NULL as dropoff_weight,
        NULL as dropoff_recyclable_type,
        NULL as dropoff_recyclable_image,
        NULL as collection_point_name
    FROM rewardredemptions rr
    LEFT JOIN rewards r ON rr.reward_id = r.reward_id
    WHERE rr.user_id = ?
    
    ORDER BY created_at DESC
");

if (!$stmt) {
    die("Error preparing statement. Please check the error log.");
}

$stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$transactions = $stmt->get_result();

// Get available points (total earned minus total spent)
$stmt = prepare_statement($conn, "SELECT COALESCE(SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE -points END), 0) as available_points FROM pointsledger WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$available_points = $stmt->get_result()->fetch_assoc()['available_points'];

// Get total points earned
$stmt = prepare_statement($conn, "SELECT COALESCE(SUM(points), 0) as total_earned FROM pointsledger WHERE user_id = ? AND transaction_type = 'Earned'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_earned = $stmt->get_result()->fetch_assoc()['total_earned'];

// Get points earned this month
$stmt = prepare_statement($conn, "SELECT COALESCE(SUM(points), 0) as month_points FROM pointsledger WHERE user_id = ? AND transaction_type = 'Earned' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$month_points = $stmt->get_result()->fetch_assoc()['month_points'];

// Get total points spent on rewards
$stmt = prepare_statement($conn, "SELECT COALESCE(SUM(points), 0) as total_spent FROM pointsledger WHERE user_id = ? AND transaction_type = 'Redeemed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_spent = $stmt->get_result()->fetch_assoc()['total_spent'];

// Get total pickup requests
$stmt = prepare_statement($conn, "SELECT COUNT(*) as total_requests FROM pickuprequests WHERE resident_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_requests = $stmt->get_result()->fetch_assoc()['total_requests'];

// Get total direct dropoffs
$stmt = prepare_statement($conn, "SELECT COUNT(*) as total_dropoffs FROM direct_dropoffs WHERE resident_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_dropoffs = $stmt->get_result()->fetch_assoc()['total_dropoffs'];

// Get total weight recycled
$stmt = prepare_statement($conn, "
    SELECT 
        (SELECT COALESCE(SUM(actual_weight), 0) FROM pickuprequests WHERE resident_id = ? AND status = 'Completed') +
        (SELECT COALESCE(SUM(weight_kg), 0) FROM direct_dropoffs WHERE resident_id = ? AND status = 'Verified') as total_weight
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$total_weight = $stmt->get_result()->fetch_assoc()['total_weight'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity History - BoteBank</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="../assets/js/theme.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="assets/css/my_request.css">
</head>
<body class="bg-bote-stone-50">
    <?php include 'resident_sidebar.php'; ?>

<!-- Main Content -->
<div class="ml-64 p-8 bg-gradient-to-br from-bote-stone-50 via-white to-bote-stone-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Enhanced Header -->
        <div class="bg-gradient-to-r from-white to-bote-stone-50 rounded-3xl shadow-xl p-8 mb-10 relative overflow-hidden">
            <div class="shimmer absolute inset-0"></div>
            <div class="relative z-10 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="floating-animation">
                        <div class="w-16 h-16 bg-gradient-to-r from-bote-green-500 to-bote-info-600 rounded-2xl flex items-center justify-center mr-6 shadow-lg">
                            <i data-lucide="history" class="w-8 h-8 text-white"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold bg-gradient-to-r from-bote-stone-900 to-bote-stone-700 bg-clip-text text-transparent mb-2">
                            Activity History
                        </h1>
                        <p class="text-xl text-bote-stone-600">Track your recycling activities, points, and rewards</p>
                        <div class="mt-3 flex items-center space-x-6 text-sm text-bote-stone-500">
                            <div class="flex items-center">
                                <i data-lucide="recycle" class="w-4 h-4 mr-2"></i>
                                <span>All activities</span>
                            </div>
                            <div class="flex items-center">
                                <i data-lucide="coins" class="w-4 h-4 mr-2"></i>
                                <span>Points tracking</span>
                            </div>
                            <div class="flex items-center">
                                <i data-lucide="gift" class="w-4 h-4 mr-2"></i>
                                <span>Reward redemptions</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-bote-stone-100">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-bote-green-600"><?php echo number_format($available_points); ?></p>
                        <p class="text-sm text-bote-stone-500 mt-1">Available Points</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="bg-gradient-to-br from-bote-green-500 via-bote-green-600 to-bote-green-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold"><?php echo number_format($total_earned); ?></p>
                        <p class="text-bote-green-100 text-lg">Total Earned</p>
                    </div>
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i data-lucide="coins" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-bote-info-500 via-bote-info-600 to-bote-info-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold"><?php echo number_format($month_points); ?></p>
                        <p class="text-bote-info-100 text-lg">This Month</p>
                    </div>
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i data-lucide="calendar" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-bote-green-500 via-bote-green-600 to-bote-green-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold"><?php echo number_format($total_requests + $total_dropoffs); ?></p>
                        <p class="text-bote-green-100 text-lg">Total Activities</p>
                    </div>
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i data-lucide="recycle" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-red-500 via-red-600 to-orange-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold"><?php echo number_format($total_spent); ?></p>
                        <p class="text-red-100 text-lg">Points Spent</p>
                    </div>
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i data-lucide="gift" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Activity History -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-bote-stone-50 to-white px-8 py-6 border-b border-bote-stone-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-bote-green-500 to-bote-info-600 rounded-xl flex items-center justify-center mr-4">
                            <i data-lucide="activity" class="w-5 h-5 text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-bote-stone-900">Complete Activity History</h2>
                            <p class="text-bote-stone-600 mt-1">All your recycling activities, pickups, drop-offs, and redemptions</p>
                        </div>
                    </div>
                    <button class="p-2 bg-bote-stone-100 hover:bg-bote-stone-200 rounded-xl transition-colors" onclick="location.reload()">
                        <i data-lucide="refresh-cw" class="w-5 h-5 text-bote-stone-600"></i>
                    </button>
                </div>
            </div>
            
            <?php if ($transactions->num_rows > 0): ?>
                <div class="divide-y divide-bote-stone-100">
                    <?php 
                    $card_index = 0;
                    while ($transaction = $transactions->fetch_assoc()): 
                        // Determine activity details
                        $activity_type = $transaction['activity_type'];
                        $is_earned = ($transaction['transaction_type'] == 'Earned' || $activity_type == 'request' || $activity_type == 'dropoff');
                        $is_redeemed = ($transaction['transaction_type'] == 'Redeemed' || $activity_type == 'redemption');
                        
                        // Set colors and icons
                        if ($is_redeemed) {
                            $bg_gradient = 'from-red-400 to-red-600';
                            $text_color = 'text-red-600';
                            $icon = 'gift';
                        } elseif ($activity_type == 'dropoff') {
                            $bg_gradient = 'from-bote-info-400 to-bote-info-600';
                            $text_color = 'text-bote-info-600';
                            $icon = 'map-pin';
                        } elseif ($activity_type == 'request') {
                            $bg_gradient = 'from-bote-green-400 to-bote-green-600';
                            $text_color = 'text-bote-green-600';
                            $icon = 'truck';
                        } else {
                            $bg_gradient = $is_earned ? 'from-bote-green-400 to-bote-green-600' : 'from-red-400 to-red-600';
                            $text_color = $is_earned ? 'text-bote-green-600' : 'text-red-600';
                            $icon = $is_earned ? 'coins' : 'gift';
                        }
                        
                        // Get status badge
                        $status = $transaction['request_status'] ?? $transaction['dropoff_status'] ?? $transaction['redemption_status'];
                        $status_colors = [
                            'Pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                            'Scheduled' => 'bg-bote-info-100 text-bote-info-800 border-bote-info-200',
                            'In Progress' => 'bg-bote-green-100 text-bote-green-800 border-bote-green-200',
                            'Completed' => 'bg-bote-green-100 text-bote-green-800 border-bote-green-200',
                            'Verified' => 'bg-bote-green-100 text-bote-green-800 border-bote-green-200',
                            'Cancelled' => 'bg-red-100 text-red-800 border-red-200',
                            'Approved' => 'bg-bote-green-100 text-bote-green-800 border-bote-green-200',
                            'Rejected' => 'bg-red-100 text-red-800 border-red-200'
                        ];
                        $status_icons = [
                            'Pending' => 'clock',
                            'Scheduled' => 'calendar',
                            'In Progress' => 'truck',
                            'Completed' => 'check-circle',
                            'Verified' => 'check-circle',
                            'Cancelled' => 'x-circle',
                            'Approved' => 'check-circle',
                            'Rejected' => 'x-circle'
                        ];
                    ?>
                        <div class="card-enter p-8 hover:bg-gradient-to-r hover:from-bote-stone-50 hover:to-white transition-all duration-300" 
                             style="animation-delay: <?php echo e($card_index * 0.1); ?>s">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-4 flex-1">
                                    <div class="w-14 h-14 bg-gradient-to-br <?php echo e($bg_gradient); ?> rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                                        <i data-lucide="<?php echo e($icon); ?>" class="w-7 h-7 text-white"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-3">
                                            <h3 class="text-xl font-bold text-bote-stone-900">
                                                <?php 
                                                if ($is_redeemed) {
                                                    echo 'Reward Redeemed';
                                                } elseif ($activity_type == 'dropoff') {
                                                    echo 'Direct Drop-off';
                                                } elseif ($activity_type == 'request') {
                                                    echo 'Pickup Request';
                                                } else {
                                                    echo 'Points Earned';
                                                }
                                                ?>
                                            </h3>
                                            <?php if ($status && isset($status_colors[$status])): ?>
                                                <span class="px-3 py-1 text-xs rounded-xl font-semibold border-2 flex items-center space-x-1 <?php echo e($status_colors[$status]); ?>">
                                                    <i data-lucide="<?php echo e($status_icons[$status] ?? 'circle'); ?>" class="w-3 h-3"></i>
                                                    <span><?php echo htmlspecialchars($status); ?></span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Activity Details -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                            <?php if ($transaction['recyclable_type'] || $transaction['dropoff_recyclable_type']): 
                                                $recyclable = $transaction['recyclable_type'] ?? $transaction['dropoff_recyclable_type'];
                                                $recyclable_img = $transaction['recyclable_image'] ?? $transaction['dropoff_recyclable_image'];
                                                $weight = $transaction['actual_weight'] ?? $transaction['dropoff_weight'];
                                                $quantity = $transaction['quantity_pcs'];
                                                $unit = $transaction['recyclable_unit'];
                                            ?>
                                                <div class="bg-bote-stone-50 rounded-xl p-4">
                                                    <div class="flex items-center mb-2">
                                                        <?php if (!empty($recyclable_img)): ?>
                                                        <div class="w-10 h-10 bg-bote-green-100 rounded-lg overflow-hidden mr-3">
                                                            <img src="<?php echo htmlspecialchars($recyclable_img); ?>" 
                                                                 alt="<?php echo htmlspecialchars($recyclable); ?>"
                                                                 class="w-full h-full object-cover"
                                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                            <i data-lucide="recycle" class="w-5 h-5 text-bote-green-600 m-2" style="display:none;"></i>
                                                        </div>
                                                        <?php else: ?>
                                                        <div class="w-10 h-10 bg-bote-green-100 rounded-lg flex items-center justify-center mr-3">
                                                            <i data-lucide="recycle" class="w-5 h-5 text-bote-green-600"></i>
                                                        </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <p class="font-semibold text-bote-stone-900"><?php echo htmlspecialchars($recyclable); ?></p>
                                                            <p class="text-sm text-bote-stone-600">
                                                                <?php if ($weight): ?>
                                                                    <?php echo number_format($weight, 2); ?> kg
                                                                <?php elseif ($quantity && $unit): ?>
                                                                    <?php echo number_format($quantity); ?> <?php echo htmlspecialchars($unit); ?>
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php elseif ($transaction['reward_name']): ?>
                                                <div class="bg-bote-green-50 rounded-xl p-4">
                                                    <div class="flex items-center">
                                                        <?php if (!empty($transaction['reward_image'])): ?>
                                                        <div class="w-10 h-10 bg-bote-green-100 rounded-lg overflow-hidden mr-3">
                                                            <img src="<?php echo htmlspecialchars($transaction['reward_image']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($transaction['reward_name']); ?>"
                                                                 class="w-full h-full object-cover"
                                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                            <i data-lucide="gift" class="w-5 h-5 text-bote-green-600 m-2" style="display:none;"></i>
                                                        </div>
                                                        <?php else: ?>
                                                        <div class="w-10 h-10 bg-bote-green-100 rounded-lg flex items-center justify-center mr-3">
                                                            <i data-lucide="gift" class="w-5 h-5 text-bote-green-600"></i>
                                                        </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <p class="font-semibold text-bote-stone-900"><?php echo htmlspecialchars($transaction['reward_name']); ?></p>
                                                            <?php if ($transaction['quantity_pcs']): ?>
                                                            <p class="text-sm text-bote-stone-600">Quantity: <?php echo e($transaction['quantity_pcs']); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Location/Time Info -->
                                            <div class="bg-bote-info-50 rounded-xl p-4">
                                                <?php if ($transaction['collection_point_name']): ?>
                                                    <div class="flex items-center mb-2">
                                                        <i data-lucide="map-pin" class="w-5 h-5 text-bote-info-600 mr-2"></i>
                                                        <span class="font-semibold text-bote-stone-900"><?php echo htmlspecialchars($transaction['collection_point_name']); ?></span>
                                                    </div>
                                                    <p class="text-sm text-bote-stone-600">Collection Point</p>
                                                <?php elseif ($transaction['pickup_type'] == 'Home'): ?>
                                                    <div class="flex items-center mb-2">
                                                        <i data-lucide="home" class="w-5 h-5 text-bote-info-600 mr-2"></i>
                                                        <span class="font-semibold text-bote-stone-900">Home Pickup</span>
                                                    </div>
                                                    <p class="text-sm text-bote-stone-600">Scheduled pickup</p>
                                                <?php else: ?>
                                                    <div class="flex items-center mb-2">
                                                        <i data-lucide="clock" class="w-5 h-5 text-bote-info-600 mr-2"></i>
                                                        <span class="font-semibold text-bote-stone-900">Activity Date</span>
                                                    </div>
                                                <?php endif; ?>
                                                <p class="text-sm text-bote-stone-600">
                                                    <?php echo date('M j, Y', strtotime($transaction['created_at'])); ?> at 
                                                    <?php echo date('g:i A', strtotime($transaction['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Points Display -->
                                <div class="ml-6 text-right">
                                    <?php if ($transaction['points'] > 0): ?>
                                        <div class="bg-<?php echo e($is_earned ? 'green' : 'red'); ?>-50 rounded-xl p-4 mb-3">
                                            <div class="flex items-center justify-end mb-1">
                                                <i data-lucide="coins" class="w-5 h-5 <?php echo e($text_color); ?> mr-2"></i>
                                                <span class="text-sm text-bote-stone-600">Points</span>
                                            </div>
                                            <div class="text-3xl font-bold <?php echo e($text_color); ?>">
                                                <?php echo e($is_earned ? '+' : '-'); ?><?php echo number_format($transaction['points']); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($transaction['request_id']): ?>
                                        <div class="bg-bote-stone-100 px-3 py-1 rounded-full text-xs font-medium text-bote-stone-600">
                                            Request #<?php echo e(str_pad($transaction['request_id'], 4, '0', STR_PAD_LEFT)); ?>
                                        </div>
                                    <?php elseif ($transaction['redemption_id']): ?>
                                        <div class="bg-bote-stone-100 px-3 py-1 rounded-full text-xs font-medium text-bote-stone-600">
                                            Redemption #<?php echo e(str_pad($transaction['redemption_id'], 4, '0', STR_PAD_LEFT)); ?>
                                        </div>
                                    <?php elseif ($transaction['dropoff_id']): ?>
                                        <div class="bg-bote-stone-100 px-3 py-1 rounded-full text-xs font-medium text-bote-stone-600">
                                            Dropoff #<?php echo e(str_pad($transaction['dropoff_id'], 4, '0', STR_PAD_LEFT)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php 
                    $card_index++;
                    endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-16">
                    <div class="floating-animation">
                        <div class="w-32 h-32 bg-gradient-to-br from-bote-stone-100 to-bote-stone-200 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="history" class="w-16 h-16 text-bote-stone-400"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-bote-stone-900 mb-3">No activity history yet</h3>
                    <p class="text-bote-stone-600 mb-8 max-w-md mx-auto">
                        Start your eco-journey by scheduling a pickup or visiting a drop-off point. Every action earns you points!
                    </p>
                    <div class="flex gap-4 justify-center">
                        <a href="resident_new_request.php" class="inline-flex items-center space-x-3 bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white px-8 py-4 rounded-2xl font-semibold transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:scale-105">
                            <i data-lucide="truck" class="w-6 h-6"></i>
                            <span>Schedule Pickup</span>
                        </a>
                        <a href="resident_direct_dropoff.php" class="inline-flex items-center space-x-3 bg-gradient-to-r from-bote-info-500 to-bote-info-600 hover:from-bote-info-600 hover:to-bote-info-700 text-white px-8 py-4 rounded-2xl font-semibold transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:scale-105">
                            <i data-lucide="map-pin" class="w-6 h-6"></i>
                            <span>Direct Drop-off</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="module" src="assets/js/resident_history.js"></script>
</body>
</html>
