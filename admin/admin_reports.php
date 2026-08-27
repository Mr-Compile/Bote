<?php
require_once '../config/database.php';
require '../includes/auth_admin.php'; // Ensure only admins can access

// Get date range from query parameters or set defaults
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month

// Overall Statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_requests,
        SUM(CASE WHEN status = 'Completed' THEN weight_kg ELSE 0 END) as total_weight
    FROM pickuprequests 
    WHERE created_at BETWEEN ? AND ?
");

if ($stmt) {
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $overall_stats = $stmt->get_result()->fetch_assoc();
} else {
    $overall_stats = ['total_requests' => 0, 'completed_requests' => 0, 'total_weight' => 0];
}

// Recyclable Types Performance
$stmt = $conn->prepare("
    SELECT 
        rt.name AS type_name,
        COUNT(pr.request_id) as request_count,
        SUM(CASE WHEN pr.status = 'Completed' THEN pr.weight_kg ELSE 0 END) as total_weight
    FROM recyclabletypes rt
    LEFT JOIN pickuprequests pr 
        ON rt.type_id = pr.type_id 
        AND pr.created_at BETWEEN ? AND ?
    GROUP BY rt.type_id, rt.name
    ORDER BY total_weight DESC
");

if ($stmt) {
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $type_performance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $type_performance = [];
}

// Worker Performance
$stmt = prepare_statement($conn, "
    SELECT 
        CONCAT(u.first_name, ' ', u.last_name) as worker_name,
        COUNT(pr.request_id) as assigned_requests,
        SUM(CASE WHEN pr.status = 'Completed' THEN 1 ELSE 0 END) as completed_requests,
        SUM(CASE WHEN pr.status = 'Completed' THEN pr.actual_weight ELSE 0 END) as total_weight,
        ROUND(AVG(CASE WHEN pr.status = 'Completed' THEN 
            TIMESTAMPDIFF(HOUR, pr.created_at, pr.updated_at) 
        END), 1) as avg_completion_hours
    FROM users u
    LEFT JOIN pickuprequests pr ON u.user_id = pr.assigned_worker_id 
        AND pr.created_at BETWEEN ? AND ?
    WHERE u.role = 'worker'
    GROUP BY u.user_id, u.first_name, u.last_name
    ORDER BY completed_requests DESC
");
if ($stmt) {
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $worker_performance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $worker_performance = [];
}

// Top Residents
$stmt = prepare_statement($conn, "
    SELECT 
        CONCAT(u.first_name, ' ', u.last_name) as resident_name,
        COUNT(pr.request_id) as total_requests,
        SUM(CASE WHEN pr.status = 'Completed' THEN pr.actual_weight ELSE 0 END) as total_weight,
        SUM(CASE WHEN pr.status = 'Completed' THEN pr.points_earned ELSE 0 END) as total_points
    FROM users u
    LEFT JOIN pickuprequests pr ON u.user_id = pr.resident_id 
        AND pr.created_at BETWEEN ? AND ?
    WHERE u.role = 'resident'
    GROUP BY u.user_id, u.first_name, u.last_name
    HAVING total_requests > 0
    ORDER BY total_points DESC
    LIMIT 10
");
if ($stmt) {
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $top_residents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $top_residents = [];
}

// Daily Collection Data for Chart
$stmt = prepare_statement($conn, "
    SELECT 
        DATE(created_at) as collection_date,
        COUNT(*) as requests_count,
        SUM(CASE WHEN status = 'completed' THEN actual_weight ELSE 0 END) as total_weight
    FROM pickuprequests 
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY collection_date
");
if ($stmt) {
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $daily_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $daily_data = [];
}

// Status Distribution
$stmt = prepare_statement($conn, "
    SELECT 
        status,
        COUNT(*) as count
    FROM pickuprequests 
    WHERE created_at BETWEEN ? AND ?
    GROUP BY status
");
if ($stmt) {
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $status_distribution = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $status_distribution = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - BoteBank Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="../assets/js/theme.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-bote-stone-50">
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-bote-stone-900 mb-2">Reports & Analytics</h1>
                <p class="text-bote-stone-600">System performance and insights</p>
            </div>

            <!-- Date Range Filter -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <form method="GET" class="flex items-center space-x-4">
                    <div>
                        <label class="block text-sm font-medium text-bote-stone-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" value="<?php echo e($start_date); ?>" 
                               class="px-3 py-2 border border-bote-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-bote-stone-700 mb-1">End Date</label>
                        <input type="date" name="end_date" value="<?php echo e($end_date); ?>" 
                               class="px-3 py-2 border border-bote-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500">
                    </div>
                    <div class="pt-6">
                        <button type="submit" 
                                class="bg-bote-green-600 hover:bg-bote-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Update Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Overall Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-bote-info-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="clipboard-list" class="w-6 h-6 text-bote-info-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($overall_stats['total_requests']); ?></p>
                            <p class="text-sm text-bote-stone-600">Total Requests</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-bote-green-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="check-circle" class="w-6 h-6 text-bote-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($overall_stats['completed_requests']); ?></p>
                            <p class="text-sm text-bote-stone-600">Completed</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="weight" class="w-6 h-6 text-orange-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-bote-stone-900"><?php echo number_format($overall_stats['total_weight'], 1); ?>kg</p>
                            <p class="text-sm text-bote-stone-600">Total Weight</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-bote-green-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="star" class="w-6 h-6 text-bote-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-bote-stone-900"><?php echo number_format($overall_stats['total_points'] ?? 0); ?></p>
                            <p class="text-sm text-bote-stone-600">Points Awarded</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Daily Collections Chart -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-bote-stone-900 mb-4">Daily Collections</h3>
                    <canvas id="dailyChart" width="400" height="200"></canvas>
                </div>
                
                <!-- Status Distribution Chart -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-bote-stone-900 mb-4">Request Status Distribution</h3>
                    <canvas id="statusChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Performance Tables Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Recyclable Types Performance -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-bote-stone-200">
                        <h3 class="text-lg font-semibold text-bote-stone-900">Recyclable Types Performance</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($type_performance as $type): ?>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-medium text-bote-stone-900"><?php echo htmlspecialchars($type['type_name']); ?></div>
                                        <div class="text-sm text-bote-stone-500"><?php echo e($type['request_count']); ?> requests</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-bote-stone-900"><?php echo number_format($type['total_weight'], 1); ?>kg</div>
                                        <div class="text-sm text-bote-green-600"><?php echo number_format($type['total_points'] ?? 0); ?> pts</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Worker Performance -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-bote-stone-200">
                        <h3 class="text-lg font-semibold text-bote-stone-900">Worker Performance</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($worker_performance as $worker): ?>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-medium text-bote-stone-900"><?php echo htmlspecialchars($worker['worker_name']); ?></div>
                                        <div class="text-sm text-bote-stone-500">
                                            <?php echo e($worker['completed_requests']); ?>/<?php echo e($worker['assigned_requests']); ?> completed
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-bote-stone-900"><?php echo number_format($worker['total_weight'], 1); ?>kg</div>
                                        <div class="text-sm text-orange-600">
                                            <?php echo e($worker['avg_completion_hours'] ? $worker['avg_completion_hours'] . 'h avg' : 'N/A'); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Residents -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-bote-stone-200">
                    <h3 class="text-lg font-semibold text-bote-stone-900">Top Residents</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-bote-stone-200">
                        <thead class="bg-bote-stone-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Rank</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Resident</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Requests</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Weight (kg)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Points</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-bote-stone-200">
                            <?php foreach ($top_residents as $index => $resident): ?>
                                <tr class="hover:bg-bote-stone-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-full flex items-center justify-center">
                                                <span class="text-white font-bold text-sm"><?php echo e($index + 1); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-bote-stone-900">
                                            <?php echo htmlspecialchars($resident['resident_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-bote-stone-900">
                                        <?php echo e($resident['total_requests']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-bote-stone-900">
                                        <?php echo number_format($resident['total_weight'], 1); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-bote-green-100 text-bote-green-800">
                                            <?php echo number_format($resident['total_points']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php
$reports_data = ['daily' => $daily_data, 'status' => $status_distribution];
$json_reports_data = htmlspecialchars(json_encode($reports_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
?>
<script type="application/json" id="admin-reports-data"><?php echo $json_reports_data; ?></script>
<script type="module" src="assets/js/admin_reports.js"></script>
</body>
</html>
