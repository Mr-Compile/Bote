<?php
require_once '../config/database.php';
require_once '../includes/auth_res.php';
require_once '../includes/system_functions.php';

// Ensure system settings table exists
ensureSystemSettingsTable($conn);

// Get system settings
$system_name = getSystemSetting($conn, 'barangay_name', 'BoteBank');

// Get time period from URL parameter (weekly or monthly)
$period = isset($_GET['period']) ? $_GET['period'] : 'weekly';
$valid_periods = ['weekly', 'monthly'];
if (!in_array($period, $valid_periods)) {
    $period = 'weekly';
}

// Calculate date range based on period
if ($period === 'weekly') {
    $start_date = date('Y-m-d', strtotime('-7 days'));
    $period_label = 'Weekly';
} else {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $period_label = 'Monthly';
}

// Get individual leaderboard data
$individual_query = "
    SELECT 
        u.user_id,
        u.first_name,
        u.last_name,
        u.profile_photo,
        COALESCE(SUM(CASE WHEN pl.transaction_type = 'Earned' THEN pl.points ELSE 0 END), 0) as total_points
    FROM users u
    LEFT JOIN pointsledger pl ON u.user_id = pl.user_id AND pl.created_at >= ?
    WHERE u.role = 'resident'
    GROUP BY u.user_id, u.first_name, u.last_name, u.profile_photo
    ORDER BY total_points DESC, u.first_name ASC
    LIMIT 50
";

$individual_leaderboard = [];
$stmt = prepare_statement($conn, $individual_query);
if ($stmt) {
    $stmt->bind_param("s", $start_date);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $individual_leaderboard = $result->fetch_all(MYSQLI_ASSOC);
        
        // Get request counts separately to avoid Cartesian product
        foreach ($individual_leaderboard as $key => $user) {
            $request_query = "
                SELECT 
                    COUNT(DISTINCT CASE WHEN pr.status = 'Completed' THEN pr.request_id END) + 
                    COUNT(DISTINCT CASE WHEN dd.status = 'Completed' THEN dd.dropoff_id END) as total_requests
                FROM users u
                LEFT JOIN pickuprequests pr ON u.user_id = pr.resident_id AND pr.created_at >= ?
                LEFT JOIN direct_dropoffs dd ON u.user_id = dd.resident_id AND dd.created_at >= ?
                WHERE u.user_id = ?
            ";
            $request_stmt = prepare_statement($conn, $request_query);
            if ($request_stmt) {
                $request_stmt->bind_param("ssi", $start_date, $start_date, $user['user_id']);
                if ($request_stmt->execute()) {
                    $request_result = $request_stmt->get_result();
                    $request_data = $request_result->fetch_assoc();
                    $individual_leaderboard[$key]['total_requests'] = $request_data['total_requests'] ?? 0;
                } else {
                    $individual_leaderboard[$key]['total_requests'] = 0;
                }
                $request_stmt->close();
            } else {
                $individual_leaderboard[$key]['total_requests'] = 0;
            }
        }
    } else {
        error_log("Error executing individual leaderboard query: " . $stmt->error);
    }
    $stmt->close(); 
} else {
    error_log("Error preparing individual leaderboard query");
}

// Get purok leaderboard data
$purok_query = "
    SELECT 
        p.purok_id,
        p.name as purok_name,
        COALESCE(SUM(CASE WHEN pl.transaction_type = 'Earned' THEN pl.points ELSE 0 END), 0) as total_points,
        COUNT(DISTINCT u.user_id) as active_members
    FROM puroks p
    LEFT JOIN users u ON p.purok_id = u.purok_id AND u.role = 'resident'
    LEFT JOIN pointsledger pl ON u.user_id = pl.user_id AND pl.created_at >= ?
    GROUP BY p.purok_id, p.name
    ORDER BY total_points DESC, p.name ASC
";

$purok_leaderboard = [];
$stmt = prepare_statement($conn, $purok_query);
if ($stmt) {
    $stmt->bind_param("s", $start_date);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $purok_leaderboard = $result->fetch_all(MYSQLI_ASSOC);
        
        // Get request counts separately for each purok to avoid Cartesian product
        foreach ($purok_leaderboard as $key => $purok) {
            $purok_request_query = "
                SELECT 
                    COUNT(DISTINCT CASE WHEN pr.status = 'Completed' THEN pr.request_id END) + 
                    COUNT(DISTINCT CASE WHEN dd.status = 'Completed' THEN dd.dropoff_id END) as total_requests
                FROM puroks p
                LEFT JOIN users u ON p.purok_id = u.purok_id AND u.role = 'resident'
                LEFT JOIN pickuprequests pr ON u.user_id = pr.resident_id AND pr.created_at >= ?
                LEFT JOIN direct_dropoffs dd ON u.user_id = dd.resident_id AND dd.created_at >= ?
                WHERE p.purok_id = ?
            ";
            $purok_request_stmt = prepare_statement($conn, $purok_request_query);
            if ($purok_request_stmt) {
                $purok_request_stmt->bind_param("ssi", $start_date, $start_date, $purok['purok_id']);
                if ($purok_request_stmt->execute()) {
                    $purok_request_result = $purok_request_stmt->get_result();
                    $purok_request_data = $purok_request_result->fetch_assoc();
                    $purok_leaderboard[$key]['total_requests'] = $purok_request_data['total_requests'] ?? 0;
                } else {
                    $purok_leaderboard[$key]['total_requests'] = 0;
                }
                $purok_request_stmt->close();
            } else {
                $purok_leaderboard[$key]['total_requests'] = 0;
            }
        }
    } else {
        error_log("Error executing purok leaderboard query: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Error preparing purok leaderboard query");
}

// Get community stats
$stats_query = "
    SELECT 
        COALESCE(SUM(CASE WHEN pl.transaction_type = 'Earned' THEN pl.points ELSE 0 END), 0) as total_points,
        COUNT(DISTINCT u.user_id) as active_recyclers
    FROM users u
    LEFT JOIN pointsledger pl ON u.user_id = pl.user_id AND pl.created_at >= ?
    WHERE u.role = 'resident'
";

$community_stats = ['total_points' => 0, 'active_recyclers' => 0, 'total_weight' => 0];
$stmt = prepare_statement($conn, $stats_query);
if ($stmt) {
    $stmt->bind_param("s", $start_date);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $fetched_stats = $result->fetch_assoc();
        if ($fetched_stats) {
            $community_stats['total_points'] = $fetched_stats['total_points'];
            $community_stats['active_recyclers'] = $fetched_stats['active_recyclers'];
        }
        
        // Get total weight separately to avoid Cartesian product
        $weight_query = "
            SELECT 
                COALESCE(SUM(pr.weight_kg), 0) + COALESCE(SUM(dd.weight_kg), 0) as total_weight
            FROM users u
            LEFT JOIN pickuprequests pr ON u.user_id = pr.resident_id AND pr.created_at >= ? AND pr.status = 'Completed'
            LEFT JOIN direct_dropoffs dd ON u.user_id = dd.resident_id AND dd.created_at >= ? AND dd.status = 'Completed'
            WHERE u.role = 'resident'
        ";
        $weight_stmt = prepare_statement($conn, $weight_query);
        if ($weight_stmt) {
            $weight_stmt->bind_param("ss", $start_date, $start_date);
            if ($weight_stmt->execute()) {
                $weight_result = $weight_stmt->get_result();
                $weight_data = $weight_result->fetch_assoc();
                $community_stats['total_weight'] = $weight_data['total_weight'] ?? 0;
            }
            $weight_stmt->close();
        }
    } else {
        error_log("Error executing community stats query: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Error preparing community stats query");
}

// Helper function to get user profile display
function getProfileDisplay($user) {
    if (!empty($user['profile_photo'])) {
        $thumb_path = "../uploads/profiles/thumbs/" . $user['profile_photo'];
        $regular_path = "../uploads/profiles/" . $user['profile_photo'];
        
        // Check for thumbnail first, then regular profile photo
        if (file_exists($thumb_path)) {
            return [
                'type' => 'photo',
                'src' => $thumb_path
            ];
        } elseif (file_exists($regular_path)) {
            return [
                'type' => 'photo',
                'src' => $regular_path
            ];
        }
    }
    
    // Generate initials as fallback
    $first_initial = !empty($user['first_name']) ? strtoupper(substr($user['first_name'], 0, 1)) : 'U';
    $last_initial = !empty($user['last_name']) ? strtoupper(substr($user['last_name'], 0, 1)) : 'S';
    
    return [
        'type' => 'initials',
        'initials' => $first_initial . $last_initial
    ];
}

// Helper function to get badge based on points
function getBadge($points) {
    if ($points >= 3000) return ['name' => '🏆 Eco Champion', 'class' => 'bg-yellow-100 text-yellow-800'];
    if ($points >= 2000) return ['name' => '🥈 Silver Recycler', 'class' => 'bg-bote-stone-100 text-bote-stone-700'];
    if ($points >= 1000) return ['name' => '🥉 Bronze Warrior', 'class' => 'bg-orange-100 text-orange-700'];
    if ($points >= 500) return ['name' => '🛡️ Eco Warrior', 'class' => 'bg-bote-green-100 text-bote-green-700'];
    if ($points >= 250) return ['name' => '🌿 Green Guardian', 'class' => 'bg-bote-info-100 text-bote-info-700'];
    if ($points >= 100) return ['name' => '🌻 Nature Friend', 'class' => 'bg-bote-green-100 text-bote-green-700'];
    if ($points >= 50) return ['name' => '🌱 Eco Starter', 'class' => 'bg-bote-green-100 text-bote-green-700'];
    if ($points >= 10) return ['name' => '🌱 Recycling Beginner', 'class' => 'bg-bote-green-100 text-bote-green-700'];
    return ['name' => '🌍 Earth Helper', 'class' => 'bg-bote-stone-100 text-bote-stone-600'];
}

// Helper function to get purok badge
function getPurokBadge($points) {
    if ($points >= 10000) return ['name' => '🏆 Eco Champion Purok', 'class' => 'bg-yellow-100 text-yellow-800'];
    if ($points >= 7000) return ['name' => '🥈 Silver Community', 'class' => 'bg-bote-stone-100 text-bote-stone-700'];
    if ($points >= 5000) return ['name' => '🥉 Bronze Sustainable', 'class' => 'bg-orange-100 text-orange-700'];
    if ($points >= 3000) return ['name' => '🌿 Green Community', 'class' => 'bg-bote-green-100 text-bote-green-700'];
    if ($points >= 1000) return ['name' => '♻️ Recycling Hub', 'class' => 'bg-bote-info-100 text-bote-info-700'];
    if ($points >= 500) return ['name' => '🌱 Eco Starter', 'class' => 'bg-bote-green-100 text-bote-green-700'];
    if ($points >= 100) return ['name' => '🌱 Growing Community', 'class' => 'bg-bote-green-100 text-bote-green-700'];
    return ['name' => '🏘️ New Community', 'class' => 'bg-bote-stone-100 text-bote-stone-600'];
}

include 'resident_sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($system_name); ?> Leaderboard - BoteBank</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="../assets/js/theme.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-bote-stone-50">

<!-- Main Content -->
<div class="ml-64 p-8 bg-bote-stone-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-full flex items-center justify-center">
                    <i data-lucide="trophy" class="w-6 h-6 text-white"></i>
                </div>
                <h1 class="text-3xl font-bold text-bote-stone-800"><?php echo htmlspecialchars($system_name); ?> Leaderboard</h1>
            </div>
            <p class="text-bote-stone-600 text-lg">Track recycling performance of individuals and puroks (<?php echo htmlspecialchars($period_label); ?> Rankings)</p>
        </div>

        <!-- Summary Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-bote-stone-800 mb-2">Community Impact (<?php echo htmlspecialchars($period_label); ?>)</h3>
                    <div class="flex items-center gap-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-bote-green-600"><?php echo number_format($community_stats['total_points']); ?></div>
                            <div class="text-sm text-bote-stone-600">Total Points</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-bote-info-600"><?php echo number_format($community_stats['active_recyclers']); ?></div>
                            <div class="text-sm text-bote-stone-600">Active Recyclers</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-bote-green-600"><?php echo number_format($community_stats['total_weight'], 1); ?>kg</div>
                            <div class="text-sm text-bote-stone-600">Waste Recycled</div>
                        </div>
                    </div>
                </div>
                <div class="text-6xl">?</div>
            </div>
        </div>

        <!-- Time Period Toggle -->
        <div class="flex justify-center mb-8">
            <div class="bg-white rounded-lg p-1 shadow-md">
                <a href="?period=<?php echo htmlspecialchars('weekly'); ?>" 
                   class="px-6 py-2 rounded-md font-medium transition-all duration-300 <?php echo e($period === 'weekly' ? 'bg-gradient-to-r from-bote-green-500 to-bote-green-600 text-white' : 'text-bote-stone-600 hover:text-bote-stone-800'); ?>">
                    Weekly Rankings
                </a>
                <a href="?period=monthly" 
                   class="px-6 py-2 rounded-md font-medium transition-all duration-300 <?php echo e($period === 'monthly' ? 'bg-gradient-to-r from-bote-green-500 to-bote-green-600 text-white' : 'text-bote-stone-600 hover:text-bote-stone-800'); ?>">
                    Monthly Rankings
                </a>
            </div>
        </div>

        <!-- Leaderboard Tabs -->
        <div class="flex justify-center mb-8">
            <div class="bg-white rounded-lg p-1 shadow-md">
                <button onclick="showTab('individual')" id="individualTab" class="px-8 py-3 rounded-md font-medium transition-all duration-300 bg-gradient-to-r from-bote-green-500 to-bote-green-600 text-white">
                    Individual Leaderboard
                </button>
                <button onclick="showTab('purok')" id="purokTab" class="px-8 py-3 rounded-md font-medium transition-all duration-300 text-bote-stone-600 hover:text-bote-stone-800">
                    Purok Leaderboard
                </button>
            </div>
        </div>

        <!-- Individual Leaderboard -->
        <div id="individualLeaderboard" class="space-y-4">
            <?php if (!empty($individual_leaderboard)): ?>
                <!-- Individual Rankings Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <i data-lucide="users" class="w-6 h-6"></i>
                            Individual Leaderboard (<?php echo htmlspecialchars($period_label); ?>)
                        </h3>
                        <p class="text-bote-green-100 mt-1">Rankings based on total points earned from recycling activities</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-bote-stone-50 border-b">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold text-bote-stone-900">Rank</th>
                                    <th class="px-6 py-4 text-left font-semibold text-bote-stone-900">Resident</th>
                                    <th class="px-6 py-4 text-center font-semibold text-bote-stone-900">Points Balance</th>
                                    <th class="px-6 py-4 text-center font-semibold text-bote-stone-900">Requests</th>
                                    <th class="px-6 py-4 text-center font-semibold text-bote-stone-900">Achievement</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-bote-stone-200">
                                <?php foreach ($individual_leaderboard as $index => $user): 
                                    $profile = getProfileDisplay($user);
                                    $badge = getBadge($user['total_points']);
                                    $rank = $index + 1;
                                    
                                    // Special styling for top 3
                                    $row_class = "";
                                    $rank_badge = "";
                                    if ($rank === 1) {
                                        $row_class = "bg-gradient-to-r from-yellow-50 to-orange-50";
                                        $rank_badge = "?";
                                    } elseif ($rank === 2) {
                                        $row_class = "bg-bote-stone-50";
                                        $rank_badge = "?";
                                    } elseif ($rank === 3) {
                                        $row_class = "bg-orange-50";
                                        $rank_badge = "?";
                                    }
                                ?>
                                <tr class="hover:bg-bote-stone-50 transition-colors duration-200 <?php echo e($row_class); ?>">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-2xl"><?php echo e($rank_badge); ?></span>
                                            <span class="font-bold text-lg text-bote-stone-800"><?php echo e($rank); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <?php if ($profile['type'] === 'photo'): ?>
                                                <img src="<?php echo htmlspecialchars($profile['src']); ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover ring-2 ring-bote-green-200">
                                            <?php else: ?>
                                                <div class="w-12 h-12 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-full flex items-center justify-center text-white font-bold ring-2 ring-bote-green-200">
                                                    <?php echo htmlspecialchars($profile['initials']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="font-medium text-bote-stone-900"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                                <div class="text-sm text-bote-stone-500">Resident</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-2xl font-bold <?php echo e($user['total_points'] > 0 ? 'text-bote-green-600' : ($user['total_points'] < 0 ? 'text-red-500' : 'text-bote-stone-400')); ?>">
                                            <?php echo number_format($user['total_points']); ?>
                                        </div>
                                        <div class="text-sm text-bote-stone-500">points</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-lg font-semibold text-bote-stone-700"><?php echo e($user['total_requests']); ?></div>
                                        <div class="text-sm text-bote-stone-500">completed</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-block <?php echo e($badge['class']); ?> px-3 py-2 rounded-full text-sm font-medium">
                                            <?php echo htmlspecialchars($badge['name']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                    <div class="text-6xl mb-4">?</div>
                    <h3 class="text-xl font-bold text-bote-stone-800 mb-2">Welcome to the Leaderboard!</h3>
                    <p class="text-bote-stone-600">No residents have been registered yet. Be the first to join and start earning points for recycling!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Purok Leaderboard -->
        <div id="purokLeaderboard" class="space-y-4 hidden">
            <?php if (!empty($purok_leaderboard)): ?>
                <!-- Purok Rankings Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-bote-info-500 to-bote-info-600 px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <i data-lucide="map" class="w-6 h-6"></i>
                            Purok Leaderboard (<?php echo htmlspecialchars($period_label); ?>)
                        </h3>
                        <p class="text-bote-info-100 mt-1">Community rankings based on collective recycling efforts</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-bote-stone-50 border-b">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold text-bote-stone-900">Rank</th>
                                    <th class="px-6 py-4 text-left font-semibold text-bote-stone-900">Purok</th>
                                    <th class="px-6 py-4 text-center font-semibold text-bote-stone-900">Total Points</th>
                                    <th class="px-6 py-4 text-center font-semibold text-bote-stone-900">Members</th>
                                    <th class="px-6 py-4 text-center font-semibold text-bote-stone-900">Requests</th>
                                    <th class="px-6 py-4 text-center font-semibold text-bote-stone-900">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-bote-stone-200">
                                <?php foreach ($purok_leaderboard as $index => $purok): 
                                    $badge = getPurokBadge($purok['total_points']);
                                    $rank = $index + 1;
                                    
                                    // Special styling for top 3
                                    $row_class = "";
                                    $rank_badge = "";
                                    if ($rank === 1) {
                                        $row_class = "bg-gradient-to-r from-yellow-50 to-orange-50";
                                        $rank_badge = "?";
                                    } elseif ($rank === 2) {
                                        $row_class = "bg-bote-stone-50";
                                        $rank_badge = "?";
                                    } elseif ($rank === 3) {
                                        $row_class = "bg-orange-50";
                                        $rank_badge = "?";
                                    }
                                ?>
                                <tr class="hover:bg-bote-stone-50 transition-colors duration-200 <?php echo e($row_class); ?>">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-2xl"><?php echo e($rank_badge); ?></span>
                                            <span class="font-bold text-lg text-bote-stone-800"><?php echo e($rank); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 bg-gradient-to-br from-bote-info-500 to-bote-info-600 rounded-full flex items-center justify-center text-white font-bold ring-2 ring-bote-info-200">
                                                P<?php echo e($purok['purok_id']); ?>
                                            </div>
                                            <div>
                                                <div class="font-medium text-bote-stone-900"><?php echo htmlspecialchars($purok['purok_name']); ?></div>
                                                <div class="text-sm text-bote-stone-500">Community Area</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-2xl font-bold <?php echo e($purok['total_points'] > 0 ? 'text-bote-green-600' : ($purok['total_points'] < 0 ? 'text-red-500' : 'text-bote-stone-400')); ?>">
                                            <?php echo number_format($purok['total_points']); ?>
                                        </div>
                                        <div class="text-sm text-bote-stone-500">total points</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-lg font-semibold text-bote-stone-700"><?php echo e($purok['active_members']); ?></div>
                                        <div class="text-sm text-bote-stone-500">residents</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-lg font-semibold text-bote-stone-700"><?php echo e($purok['total_requests']); ?></div>
                                        <div class="text-sm text-bote-stone-500">completed</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-block <?php echo e($badge['class']); ?> px-3 py-2 rounded-full text-sm font-medium">
                                            <?php echo htmlspecialchars($badge['name']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                    <div class="text-6xl mb-4">??</div>
                    <h3 class="text-xl font-bold text-bote-stone-800 mb-2">Welcome Puroks!</h3>
                    <p class="text-bote-stone-600">No puroks have been set up yet. Contact your administrator to add community areas.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="module" src="assets/js/leaderboard.js"></script>
</body>
</html>
