<?php
require_once '../config/database.php';
require_once '../includes/system_functions.php';
require '../includes/auth_res.php'; // Ensure only resident can access

// Get user data for sidebar
$user_id = $_SESSION['user_id'];

// Fetch user requests with recyclable type information
$stmt = prepare_statement($conn, "
    SELECT pr.request_id, pr.weight_kg, pr.quantity_pcs, pr.preferred_date, pr.status, pr.created_at, pr.points_earned,
           rt.name as recyclable_type, rt.points_per_kg, rt.points_per_unit, rt.unit, rt.image,
           CONCAT(u.first_name, ' ', u.last_name) as worker_name
    FROM pickuprequests pr
    JOIN recyclabletypes rt ON pr.type_id = rt.type_id
    LEFT JOIN users u ON pr.assigned_worker_id = u.user_id
    WHERE pr.resident_id = ?
    ORDER BY pr.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$requests = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Pickup Requests - BoteBank</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="assets/css/my_request.css">

    <!-- Pass PHP data to JavaScript -->
    <script>
        window.csrfToken = <?php echo json_encode(\App\Core\Csrf::token()); ?>;

        // Create an array to store request location data for the map
        const requestLocations = [
            <?php 
            $map_data = $conn->query("SELECT latitude, longitude, status FROM pickuprequests WHERE resident_id=" . $_SESSION['user_id']);
            while($loc = $map_data->fetch_assoc()){
                if ($loc['latitude'] && $loc['longitude']) {
                    echo "{ latitude: {$loc['latitude']}, longitude: {$loc['longitude']}, status: '" . htmlspecialchars($loc['status']) . "' },";
                }
            }
            ?>
        ];
    </script>
</head>
<body class="bg-gray-50">
    <?php include 'resident_sidebar.php'; ?>

<!-- Main Content -->
<div class="ml-64 p-8 bg-gradient-to-br from-gray-50 via-white to-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Enhanced Header -->
        <div class="bg-gradient-to-r from-white to-gray-50 rounded-3xl shadow-xl p-8 mb-10 relative overflow-hidden">
            <div class="shimmer absolute inset-0"></div>
            <div class="relative z-10 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="floating-animation">
                        <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mr-6 shadow-lg">
                            <i data-lucide="list-checks" class="w-8 h-8 text-white"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent mb-2">
                            My Pickup Requests
                        </h1>
                        <p class="text-xl text-gray-600">Track and manage all your recyclable pickup requests</p>
                        <div class="mt-3 flex items-center space-x-6 text-sm text-gray-500">
                            <div class="flex items-center">
                                <i data-lucide="clock" class="w-4 h-4 mr-2"></i>
                                <span>Real-time tracking</span>
                            </div>
                            <div class="flex items-center">
                                <i data-lucide="bell" class="w-4 h-4 mr-2"></i>
                                <span>Status updates</span>
                            </div>
                            <div class="flex items-center">
                                <i data-lucide="star" class="w-4 h-4 mr-2"></i>
                                <span>Rewards tracking</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="bg-white rounded-2xl p-4 shadow-lg border border-gray-100">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-600"><?php echo $requests->num_rows; ?></p>
                            <p class="text-sm text-gray-500">Total Requests</p>
                        </div>
                    </div>
                    <a href="resident_new_request.php" class="bg-gradient-to-r from-green-500 via-green-600 to-emerald-600 hover:from-green-600 hover:via-green-700 hover:to-emerald-700 text-white px-8 py-4 rounded-2xl font-semibold transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:scale-105 flex items-center space-x-3">
                        <i data-lucide="plus-circle" class="w-5 h-5"></i>
                        <span>New Request</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <?php 
        // Get accurate statistics from database
        $stats_stmt = prepare_statement($conn, "
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                COALESCE(SUM(CASE WHEN status = 'Completed' AND points_earned IS NOT NULL THEN points_earned ELSE 0 END), 0) as total_points
            FROM pickuprequests pr
            WHERE pr.resident_id = ?
        ");
        $stats_stmt->bind_param("i", $user_id);
        $stats_stmt->execute();
        $stats_result = $stats_stmt->get_result()->fetch_assoc();
        
        $pending_count = $stats_result['pending_count'] ?? 0;
        $completed_count = $stats_result['completed_count'] ?? 0;
        $cancelled_count = $stats_result['cancelled_count'] ?? 0;
        $total_points = $stats_result['total_points'] ?? 0;
        ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="bg-gradient-to-br from-yellow-500 via-yellow-600 to-orange-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold"><?php echo $pending_count; ?></p>
                        <p class="text-yellow-100 text-lg">Pending</p>
                    </div>
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i data-lucide="clock" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-green-500 via-green-600 to-emerald-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold"><?php echo $completed_count; ?></p>
                        <p class="text-green-100 text-lg">Completed</p>
                    </div>
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-red-500 via-red-600 to-pink-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold"><?php echo $cancelled_count; ?></p>
                        <p class="text-red-100 text-lg">Cancelled</p>
                    </div>
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i data-lucide="x-circle" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold"><?php echo number_format($total_points); ?></p>
                        <p class="text-blue-100 text-lg">Total Points</p>
                    </div>
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i data-lucide="coins" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Requests List -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-gray-50 to-white px-8 py-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                            <i data-lucide="history" class="w-5 h-5 text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Request History</h2>
                            <p class="text-gray-600 mt-1">View and manage your pickup requests</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <select class="px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Scheduled">Scheduled</option>
                            <option value="Completed">Completed</option>
                        </select>
                        <button class="p-2 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors" onclick="refreshRequests()">
                            <i data-lucide="refresh-cw" class="w-5 h-5 text-gray-600"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if ($requests->num_rows > 0): ?>
                <div class="divide-y divide-gray-100" id="requestsList">
                    <?php $card_index = 0; ?>
                    <?php while ($request = $requests->fetch_assoc()): ?>
                        <div class="request-card p-8 hover:bg-gradient-to-r hover:from-gray-50 hover:to-white transition-all duration-300 card-enter" 
                             style="animation-delay: <?php echo $card_index * 0.1; ?>s"
                             data-status="<?php echo htmlspecialchars($request['status']); ?>"
                             data-request-id="<?php echo $request['request_id']; ?>">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <!-- Status and ID -->
                                    <div class="flex items-center space-x-4 mb-4">
                                        <div class="flex items-center space-x-3">
                                            <?php
                                            $status_colors = [
                                                'Pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                                'Scheduled' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                'In Progress' => 'bg-purple-100 text-purple-800 border-purple-200',
                                                'Completed' => 'bg-green-100 text-green-800 border-green-200'
                                            ];
                                            $status_icons = [
                                                'Pending' => 'clock',
                                                'Scheduled' => 'calendar',
                                                'In Progress' => 'truck',
                                                'Completed' => 'check-circle'
                                            ];
                                            ?>
                                            <div class="px-4 py-2 rounded-xl font-semibold text-sm border-2 flex items-center space-x-2 <?php echo $status_colors[$request['status']] ?? 'bg-gray-100 text-gray-800 border-gray-200'; ?>">
                                                <i data-lucide="<?php echo $status_icons[$request['status']] ?? 'circle'; ?>" class="w-4 h-4"></i>
                                                <span><?php echo htmlspecialchars($request['status']); ?></span>
                                            </div>
                                            <div class="bg-gray-100 px-3 py-1 rounded-full text-sm font-medium text-gray-600">
                                                ID: #<?php echo str_pad($request['request_id'], 4, '0', STR_PAD_LEFT); ?>
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Created <?php echo date('M j, Y \a\t g:i A', strtotime($request['created_at'])); ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Main Content -->
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                        <!-- Material Info -->
                                        <div class="lg:col-span-2">
                                            <div class="flex items-center mb-4">
                                                <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-600 rounded-xl flex items-center justify-center mr-4 overflow-hidden">
                                                    <?php if (!empty($request['image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($request['image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($request['recyclable_type']); ?>"
                                                             class="w-full h-full object-cover rounded-xl"
                                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                        <i data-lucide="recycle" class="w-6 h-6 text-white" style="display:none;"></i>
                                                    <?php else: ?>
                                                        <i data-lucide="recycle" class="w-6 h-6 text-white"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <h3 class="text-xl font-bold text-gray-900">
                                                        <?php echo htmlspecialchars($request['recyclable_type']); ?>
                                                    </h3>
                                                    <p class="text-gray-600">Recyclable Material</p>
                                                </div>
                                            </div>
                                            
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="bg-gray-50 rounded-xl p-4">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-gray-600 text-sm font-medium">Amount</span>
                                                        <i data-lucide="package" class="w-4 h-4 text-gray-400"></i>
                                                    </div>
                                                    <p class="text-2xl font-bold text-gray-900">
                                                        <?php 
                                                        if ($request['unit'] === 'kg') {
                                                            echo number_format($request['weight_kg'], 1) . ' kg';
                                                        } else {
                                                            echo ($request['quantity_pcs'] ?? 'N/A') . ' pcs';
                                                        }
                                                        ?>
                                                    </p>
                                                    <p class="text-gray-500 text-sm">
                                                        <?php echo htmlspecialchars($request['unit'] === 'kg' ? 'Weight collected' : 'Quantity collected'); ?>
                                                    </p>
                                                </div>
                                                
                                                <div class="bg-green-50 rounded-xl p-4">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-green-700 text-sm font-medium">Points Earned</span>
                                                        <i data-lucide="coins" class="w-4 h-4 text-green-500"></i>
                                                    </div>
                                                    <p class="text-2xl font-bold text-green-600">
                                                        <?php 
                                                        if ($request['status'] === 'Completed' && $request['points_earned'] !== null) {
                                                            echo number_format($request['points_earned']);
                                                        } else {
                                                            echo '--';
                                                        }
                                                        ?>
                                                    </p>
                                                    <p class="text-green-500 text-sm">points</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Schedule and Worker Info -->
                                        <div class="space-y-4">
                                            <div class="bg-blue-50 rounded-xl p-4">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-blue-700 text-sm font-medium">Preferred Date</span>
                                                    <i data-lucide="calendar" class="w-4 h-4 text-blue-500"></i>
                                                </div>
                                                <p class="font-semibold text-blue-900">
                                                    <?php echo date('M j, Y', strtotime($request['preferred_date'])); ?>
                                                </p>
                                                <p class="text-blue-600 text-sm">
                                                    <?php echo date('g:i A', strtotime($request['preferred_date'])); ?>
                                                </p>
                                            </div>
                                            
                                            <?php if ($request['worker_name']): ?>
                                            <div class="bg-purple-50 rounded-xl p-4">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-purple-700 text-sm font-medium">Assigned Worker</span>
                                                    <i data-lucide="user" class="w-4 h-4 text-purple-500"></i>
                                                </div>
                                                <p class="font-semibold text-purple-900">
                                                    <?php echo htmlspecialchars($request['worker_name']); ?>
                                                </p>
                                                <p class="text-purple-600 text-sm">Collection Agent</p>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex flex-col items-end space-y-3 ml-6">
                                    <?php if ($request['status'] == 'Pending'): ?>
                                        <button onclick="cancelRequest(<?php echo $request['request_id']; ?>)" 
                                                class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-xl font-medium transition-colors flex items-center space-x-2">
                                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                                            <span>Cancel</span>
                                        </button>
                                    <?php elseif ($request['status'] == 'Completed'): ?>
                                        <div class="px-4 py-2 bg-green-100 text-green-700 rounded-xl font-medium flex items-center space-x-2">
                                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                                            <span>Completed</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <button onclick="viewDetails(<?php echo $request['request_id']; ?>)"
                                            class="px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-xl font-medium transition-colors flex items-center space-x-2">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                        <span>Details</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Progress Bar for non-completed requests -->
                            <?php if ($request['status'] != 'Completed'): ?>
                            <div class="mt-6">
                                <div class="flex justify-between text-sm text-gray-600 mb-2">
                                    <span>Progress</span>
                                    <span>
                                        <?php 
                                        $progress = $request['status'] == 'Pending' ? 25 : 
                                                   ($request['status'] == 'Scheduled' ? 50 : 75);
                                        echo $progress; 
                                        ?>%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="progress-bar h-2 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php $card_index++; ?>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-16">
                    <div class="floating-animation">
                        <div class="w-32 h-32 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="inbox" class="w-16 h-16 text-gray-400"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">No requests yet</h3>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto">
                        Start your recycling journey by creating your first pickup request and earn valuable points for your contributions.
                    </p>
                    <a href="resident_new_request.php" class="inline-flex items-center space-x-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white px-8 py-4 rounded-2xl font-semibold transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:scale-105">
                        <i data-lucide="plus-circle" class="w-6 h-6"></i>
                        <span>Create Your First Request</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Include the my_request.js file -->
<script src="assets/js/my_request.js"></script>
</body>
</html>
