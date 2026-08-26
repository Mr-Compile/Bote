<?php
require_once '../config/database.php';
require '../includes/auth_worker.php'; // Ensure only worker can access

// Get user data for sidebar
$user_id = $_SESSION['user_id'];

// Get the selected date from query parameter, default to today
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validate date format
if (!DateTime::createFromFormat('Y-m-d', $selected_date)) {
    $selected_date = date('Y-m-d');
}

// Fetch pickup requests assigned to this worker for the selected date
$stmt = prepare_statement($conn, "
    SELECT pr.request_id, pr.resident_id, pr.type_id, pr.weight_kg, pr.preferred_date, pr.status,
           pr.latitude, pr.longitude, pr.pickup_type, pr.point_id, pr.actual_weight, pr.points_earned,
           pr.created_at, pr.updated_at, pr.quantity_pcs,
           u.first_name, u.last_name, u.address, u.phone,
           rt.name as material_type, rt.unit, rt.points_per_kg, rt.points_per_unit,
           cp.name as collection_point_name
    FROM pickuprequests pr
    JOIN users u ON pr.resident_id = u.user_id
    JOIN recyclabletypes rt ON pr.type_id = rt.type_id
    LEFT JOIN collection_points cp ON pr.point_id = cp.point_id
    WHERE pr.assigned_worker_id = ? 
    AND DATE(pr.preferred_date) = ?
    ORDER BY pr.preferred_date ASC, pr.status ASC
");
$stmt->bind_param("is", $user_id, $selected_date);
$stmt->execute();
$today_pickups = $stmt->get_result();

// Get pickup statistics for the selected date
$stmt = prepare_statement($conn, "
    SELECT 
        COUNT(*) as total_pickups,
        COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_pickups,
        COUNT(CASE WHEN status = 'Scheduled' THEN 1 END) as scheduled_pickups,
        COUNT(CASE WHEN status = 'In Progress' THEN 1 END) as in_progress_pickups,
        COALESCE(SUM(CASE WHEN status = 'Completed' THEN actual_weight END), 0) as total_weight_collected,
        COALESCE(SUM(CASE WHEN status = 'Completed' THEN points_earned END), 0) as total_points_awarded
    FROM pickuprequests 
    WHERE assigned_worker_id = ? 
    AND DATE(preferred_date) = ?
");
$stmt->bind_param("is", $user_id, $selected_date);
$stmt->execute();
$pickup_stats = $stmt->get_result()->fetch_assoc();

// Get available dates with pickup requests for this worker (for date selector)
$stmt = prepare_statement($conn, "
    SELECT DISTINCT DATE(preferred_date) as pickup_date, 
           COUNT(*) as pickup_count,
           COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_count
    FROM pickuprequests 
    WHERE assigned_worker_id = ? 
    AND DATE(preferred_date) >= CURDATE() - INTERVAL 7 DAY
    AND DATE(preferred_date) <= CURDATE() + INTERVAL 30 DAY
    GROUP BY DATE(preferred_date)
    ORDER BY pickup_date ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$available_dates = $stmt->get_result();

// Fetch collection routes for the worker for the selected date
$stmt = prepare_statement($conn, "
    SELECT cr.route_id, cr.generated_on, cr.route_geometry, COUNT(rs.stop_id) as total_stops,
           COUNT(CASE WHEN pr.status = 'Completed' THEN 1 END) as completed_stops,
           SUM(CASE WHEN pr.status != 'Completed' THEN 1 ELSE 0 END) as pending_stops
    FROM collectionroutes cr
    LEFT JOIN routestops rs ON cr.route_id = rs.route_id
    LEFT JOIN pickuprequests pr ON rs.request_id = pr.request_id
    WHERE cr.worker_id = ? AND DATE(cr.generated_on) = ?
    GROUP BY cr.route_id
    ORDER BY cr.generated_on DESC
    LIMIT 1
");
$stmt->bind_param("is", $user_id, $selected_date);
$stmt->execute();
$routes = $stmt->get_result();

// Check if worker has scheduled pickups without a route
$stmt = prepare_statement($conn, "
    SELECT COUNT(*) as unrouted_pickups
    FROM pickuprequests pr
    WHERE pr.assigned_worker_id = ? AND pr.status = 'Scheduled'
    AND pr.request_id NOT IN (
        SELECT COALESCE(rs.request_id, 0) 
        FROM collectionroutes cr 
        LEFT JOIN routestops rs ON cr.route_id = rs.route_id 
        WHERE cr.worker_id = ? AND DATE(cr.generated_on) = CURDATE()
    )
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$unrouted_result = $stmt->get_result()->fetch_assoc();
$has_unrouted_pickups = $unrouted_result['unrouted_pickups'] > 0;

include 'worker_sidebar.php';
?>

<!-- Main Content -->
<div class="ml-64 p-8 bg-gray-50 min-h-screen">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Collection Routes</h1>
                    <p class="text-gray-600">Your daily collection routes with pickup requests and collection points</p>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Date Selector -->
                    <div class="flex items-center space-x-2">
                        <label for="dateSelector" class="text-sm font-medium text-gray-700">Date:</label>
                        <select id="dateSelector" onchange="changeDate()" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm">
                            <?php 
                            $date_options = [];
                            
                            // Add current selection if not in available dates
                            $current_found = false;
                            $available_dates->data_seek(0);
                            while ($date_row = $available_dates->fetch_assoc()) {
                                if ($date_row['pickup_date'] === $selected_date) {
                                    $current_found = true;
                                }
                                $date_options[] = $date_row;
                            }
                            
                            if (!$current_found) {
                                array_unshift($date_options, [
                                    'pickup_date' => $selected_date,
                                    'pickup_count' => $pickup_stats['total_pickups'],
                                    'completed_count' => $pickup_stats['completed_pickups']
                                ]);
                            }
                            
                            // Add today if not present
                            $today = date('Y-m-d');
                            $today_found = false;
                            foreach ($date_options as $option) {
                                if ($option['pickup_date'] === $today) {
                                    $today_found = true;
                                    break;
                                }
                            }
                            
                            if (!$today_found) {
                                array_unshift($date_options, [
                                    'pickup_date' => $today,
                                    'pickup_count' => 0,
                                    'completed_count' => 0
                                ]);
                            }
                            
                            // Sort by date
                            usort($date_options, function($a, $b) {
                                return strcmp($a['pickup_date'], $b['pickup_date']);
                            });
                            
                            foreach ($date_options as $option):
                                $date_label = date('M j, Y', strtotime($option['pickup_date']));
                                if ($option['pickup_date'] === $today) {
                                    $date_label .= ' (Today)';
                                } elseif ($option['pickup_date'] === date('Y-m-d', strtotime('+1 day'))) {
                                    $date_label .= ' (Tomorrow)';
                                }
                                
                                $status_indicator = '';
                                if ($option['pickup_count'] > 0) {
                                    if ($option['completed_count'] === $option['pickup_count']) {
                                        $status_indicator = ' ✓';
                                    } elseif ($option['completed_count'] > 0) {
                                        $status_indicator = ' ◐';
                                    } else {
                                        $status_indicator = ' ○';
                                    }
                                }
                                ?>
                                <option value="<?php echo $option['pickup_date']; ?>" 
                                        <?php echo $option['pickup_date'] === $selected_date ? 'selected' : ''; ?>>
                                    <?php echo $date_label . $status_indicator; ?>
                                    <?php if ($option['pickup_count'] > 0): ?>
                                        (<?php echo $option['pickup_count']; ?> pickups)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="bg-blue-100 border border-blue-300 text-blue-800 px-4 py-2 rounded-lg text-sm font-medium">
                        <i data-lucide="route" class="w-4 h-4 inline mr-2"></i>
                        Auto-optimized routes with street routing enabled
                    </div>
                    <button onclick="viewAllPickups()" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-3 rounded-xl font-medium transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center space-x-2">
                        <i data-lucide="map-pin" class="w-5 h-5"></i>
                        <span>View My Pickups</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Route Map -->
        <div class="bg-white rounded-2xl shadow-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-green-50">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                        <i data-lucide="map" class="w-5 h-5 mr-2 text-green-600"></i>
                        Route Map View
                    </h2>
                    <!-- Route Navigation Controls -->
                    <div id="routeNavigation" class="hidden flex items-center space-x-4 bg-white px-4 py-2 rounded-lg border border-gray-300">
                        <span class="text-sm font-medium text-gray-700">Stop Navigation:</span>
                        <button id="prevStopBtn" onclick="navigateToPreviousStop()" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded-md text-sm font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <i data-lucide="chevron-left" class="w-4 h-4"></i> Prev
                        </button>
                        <span id="currentStopInfo" class="text-sm font-bold text-blue-600 px-2 py-1 bg-blue-50 rounded">
                            Stop 1 of 1
                        </span>
                        <button id="nextStopBtn" onclick="navigateToNextStop()" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-md text-sm font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            Next <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </button>
                        <button id="resetViewBtn" onclick="resetMapView()" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md text-sm font-medium transition-all">
                            <i data-lucide="home" class="w-4 h-4"></i> Reset
                        </button>
                        <!-- Route Summary -->
                        <div id="routeSummary" class="ml-4 pl-4 border-l border-gray-300 text-xs text-gray-600 hidden md:flex items-center space-x-3">
                            <div class="flex items-center space-x-1">
                                <i data-lucide="map-pin" class="w-3 h-3"></i>
                                <span id="totalStops">0 stops</span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <i data-lucide="home" class="w-3 h-3 text-blue-500"></i>
                                <span id="pickupCount">0 pickups</span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <i data-lucide="recycle" class="w-3 h-3 text-green-500"></i>
                                <span id="collectionCount">0 points</span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <i data-lucide="clock" class="w-3 h-3 text-purple-500"></i>
                                <span id="estimatedTime">0 min</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div id="routeMap" style="height: 500px; width: 100%;" class="rounded-lg border border-gray-200"></div>
                <div class="legend-section mt-4">
                    <div class="flex flex-wrap gap-6">
                        <!-- Enhanced Marker Legend -->
                        <div class="flex items-center space-x-2">
                            <div class="legend-marker w-8 h-8 bg-red-500 rounded-full border-3 border-white shadow-xl flex items-center justify-center relative">
                                <i data-lucide="flag-triangle-right" class="w-4 h-4 text-white"></i>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-red-600 rounded-full border border-white"></div>
                            </div>
                            <span class="text-sm text-gray-700 font-semibold">Starting Point (Barangay Office)</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="legend-marker w-8 h-8 bg-blue-500 rounded-full border-3 border-white shadow-xl flex items-center justify-center relative">
                                <i data-lucide="home" class="w-4 h-4 text-white"></i>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-blue-600 rounded-full border border-white flex items-center justify-center">
                                    <span class="text-xs text-white font-bold">#</span>
                                </div>
                            </div>
                            <span class="text-sm text-gray-700 font-semibold">Home Pickup Requests</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="legend-marker w-8 h-8 bg-green-500 rounded-full border-3 border-white shadow-xl flex items-center justify-center relative">
                                <i data-lucide="recycle" class="w-4 h-4 text-white"></i>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-600 rounded-full border border-white flex items-center justify-center">
                                    <i data-lucide="warehouse" class="w-2 h-2 text-white"></i>
                                </div>
                            </div>
                            <span class="text-sm text-gray-700 font-semibold">Collection Points</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="legend-marker w-8 h-8 bg-gray-500 rounded-full border-3 border-white shadow-xl flex items-center justify-center relative">
                                <i data-lucide="check-circle" class="w-4 h-4 text-white"></i>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full border border-white"></div>
                            </div>
                            <span class="text-sm text-gray-700 font-semibold">Completed Stops</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="legend-marker w-8 h-8 bg-yellow-500 rounded-full border-4 border-yellow-200 shadow-2xl flex items-center justify-center animate-pulse relative">
                                <i data-lucide="navigation" class="w-4 h-4 text-white"></i>
                                <div class="absolute -inset-2 border-2 border-yellow-400 rounded-full animate-ping"></div>
                            </div>
                            <span class="text-sm text-gray-700 font-bold">Current Active Stop</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="legend-marker w-8 h-8 bg-purple-500 rounded-full border-3 border-white shadow-xl flex items-center justify-center relative">
                                <i data-lucide="clock" class="w-4 h-4 text-white"></i>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-purple-600 rounded-full border border-white"></div>
                            </div>
                            <span class="text-sm text-gray-700 font-semibold">Scheduled/Pending</span>
                        </div>
                    </div>
                    
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Pickup Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Total Pickups</p>
                        <p class="text-3xl font-bold"><?php echo $pickup_stats['total_pickups']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-400 bg-opacity-50 rounded-xl flex items-center justify-center">
                        <i data-lucide="package" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium">Completed</p>
                        <p class="text-3xl font-bold"><?php echo $pickup_stats['completed_pickups']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-400 bg-opacity-50 rounded-xl flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100 text-sm font-medium">Scheduled</p>
                        <p class="text-3xl font-bold"><?php echo $pickup_stats['scheduled_pickups']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-orange-400 bg-opacity-50 rounded-xl flex items-center justify-center">
                        <i data-lucide="clock" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Weight Collected</p>
                        <p class="text-3xl font-bold"><?php echo number_format($pickup_stats['total_weight_collected'], 1); ?><span class="text-lg"> kg</span></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-400 bg-opacity-50 rounded-xl flex items-center justify-center">
                        <i data-lucide="weight" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Pickup Requests -->
        <div class="bg-white rounded-2xl shadow-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-green-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                        <i data-lucide="map-pin" class="w-5 h-5 mr-2 text-green-600"></i>
                        Pickup Requests for <?php echo date('F j, Y', strtotime($selected_date)); ?>
                        <?php if ($selected_date === date('Y-m-d')): ?>
                            <span class="text-sm text-green-600 ml-2">(Today)</span>
                        <?php elseif ($selected_date === date('Y-m-d', strtotime('+1 day'))): ?>
                            <span class="text-sm text-blue-600 ml-2">(Tomorrow)</span>
                        <?php endif; ?>
                    </h2>
                    <div class="flex items-center space-x-3">
                        <button onclick="sortPickupsByTime('earliest')" 
                                id="sortEarliestBtn"
                                class="sort-btn active bg-green-500 text-white px-3 py-1 rounded-lg text-sm hover:bg-green-600 transition-colors">
                            Earliest First
                        </button>
                        <button onclick="sortPickupsByTime('latest')" 
                                id="sortLatestBtn"
                                class="sort-btn bg-gray-200 text-gray-700 px-3 py-1 rounded-lg text-sm hover:bg-gray-300 transition-colors">
                            Latest First
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if ($today_pickups->num_rows > 0): ?>
                <div class="divide-y divide-gray-200" id="pickupRequestsContainer">
                    <?php while ($pickup = $today_pickups->fetch_assoc()): ?>
                        <?php 
                        $status_colors = [
                            'Scheduled' => 'bg-blue-100 text-blue-800',
                            'In Progress' => 'bg-orange-100 text-orange-800',
                            'Completed' => 'bg-green-100 text-green-800'
                        ];
                        $status_icons = [
                            'Scheduled' => 'clock',
                            'In Progress' => 'truck',
                            'Completed' => 'check-circle'
                        ];
                        ?>
                        <div class="pickup-request-item p-6 hover:bg-gray-50 transition-colors" 
                             data-preferred-time="<?php echo strtotime($pickup['preferred_date']); ?>"
                             data-status="<?php echo $pickup['status']; ?>">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-4 mb-3">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                                <i data-lucide="user" class="w-5 h-5 text-green-600"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-900">
                                                    <?php echo htmlspecialchars($pickup['first_name'] . ' ' . $pickup['last_name']); ?>
                                                </h3>
                                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($pickup['address']); ?></p>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $status_colors[$pickup['status']]; ?>">
                                            <i data-lucide="<?php echo $status_icons[$pickup['status']]; ?>" class="w-4 h-4 mr-1"></i>
                                            <?php echo $pickup['status']; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
                                        <div>
                                            <span class="text-gray-500">Material:</span>
                                            <span class="font-medium ml-1 text-green-600"><?php echo htmlspecialchars($pickup['material_type']); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Weight:</span>
                                            <span class="font-medium ml-1"><?php echo $pickup['weight_kg']; ?> kg</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Preferred Time:</span>
                                            <span class="font-medium ml-1"><?php echo date('g:i A', strtotime($pickup['preferred_date'])); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Type:</span>
                                            <span class="font-medium ml-1"><?php echo $pickup['pickup_type'] === 'CollectionPoint' ? 'Collection Point' : 'Home Pickup'; ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($pickup['status'] === 'Completed'): ?>
                                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-3">
                                            <div class="flex items-center text-green-800 text-sm">
                                                <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i>
                                                <span class="font-medium">Completed on <?php echo date('M j, g:i A', strtotime($pickup['updated_at'])); ?></span>
                                                <?php if ($pickup['actual_weight']): ?>
                                                    <span class="ml-4">Actual Weight: <?php echo $pickup['actual_weight']; ?> kg</span>
                                                <?php endif; ?>
                                                <?php if ($pickup['points_earned']): ?>
                                                    <span class="ml-4">Points Earned: <?php echo $pickup['points_earned']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex flex-col space-y-2 ml-4">
                                    <?php if ($pickup['status'] !== 'Completed'): ?>
                                        <button onclick="showCompletePickupModal(<?php echo $pickup['request_id']; ?>, '<?php echo addslashes($pickup['first_name'] . ' ' . $pickup['last_name']); ?>', '<?php echo addslashes($pickup['material_type']); ?>', '<?php echo $pickup['unit']; ?>', <?php echo $pickup['weight_kg']; ?>, <?php echo $pickup['quantity_pcs'] ?: 0; ?>)" 
                                                class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg text-sm font-medium transition-all duration-200 flex items-center">
                                            <i data-lucide="check" class="w-4 h-4 mr-2"></i>
                                            Complete
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="showOnMapWithLoading(this, <?php echo $pickup['latitude']; ?>, <?php echo $pickup['longitude']; ?>, '<?php echo addslashes($pickup['first_name'] . ' ' . $pickup['last_name']); ?>')" 
                                            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-medium transition-all duration-300 flex items-center group transform hover:scale-105 hover:shadow-lg">
                                        <i data-lucide="map-pin" class="w-4 h-4 mr-2 transition-transform group-hover:scale-110"></i>
                                        <span class="button-text">Show on Map</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="calendar-x" class="w-12 h-12 text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        No pickup requests for <?php echo date('F j, Y', strtotime($selected_date)); ?>
                    </h3>
                    <p class="text-gray-500 mb-6">
                        <?php if ($selected_date === date('Y-m-d')): ?>
                            You don't have any assigned pickup requests for today
                        <?php elseif ($selected_date < date('Y-m-d')): ?>
                            No pickup requests found for this past date
                        <?php else: ?>
                            No pickup requests scheduled for this date yet
                        <?php endif; ?>
                    </p>
                    <a href="worker_assigned_pickups.php" class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-medium transition-colors">
                        <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
                        View All Assignments
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Your Collection Routes -->
        
    </div>
</div>

<!-- Route Details Modal -->
<div id="routeModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-[9999]">
    <div class="bg-white rounded-2xl p-8 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto transform transition-all duration-300 scale-95 shadow-2xl">
        <div class="flex items-center justify-between mb-6 sticky top-0 bg-white pb-4 border-b border-gray-200">
            <h3 class="text-2xl font-bold text-gray-900">Route Details</h3>
            <button onclick="hideRouteModal()" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-100 rounded-full transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div id="routeContent" class="space-y-6">
            <!-- Route details will be loaded here -->
        </div>
    </div>
</div>

<!-- Complete Pickup Modal -->
<div id="completePickupModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-[9999]">
    <div class="bg-white rounded-2xl p-8 max-w-lg w-full mx-4 transform transition-all duration-300 scale-95 shadow-2xl">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                <i data-lucide="check-circle" class="w-6 h-6 text-green-600 mr-2"></i>
                Complete Pickup
            </h3>
            <button onclick="hideCompletePickupModal()" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-100 rounded-full transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <form id="completePickupForm" onsubmit="submitPickupCompletion(event)">
            <input type="hidden" id="completeRequestId" name="request_id">
            
            <div id="pickupDetails" class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200">
                <!-- Pickup details will be populated here -->
            </div>
            
            <div class="space-y-4">
                <div id="weightSection" class="hidden">
                    <label for="actualWeight" class="block text-sm font-medium text-gray-700 mb-2">
                        Actual Weight (kg) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="actualWeight" 
                           name="actual_weight" 
                           step="0.01" 
                           min="0.01"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                           placeholder="Enter actual weight">
                </div>
                
                <div id="quantitySection" class="hidden">
                    <label for="actualQuantity" class="block text-sm font-medium text-gray-700 mb-2">
                        Actual Quantity (pieces) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="actualQuantity" 
                           name="actual_quantity" 
                           min="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                           placeholder="Enter actual quantity">
                </div>
                
                <div>
                    <label for="completionNotes" class="block text-sm font-medium text-gray-700 mb-2">
                        Completion Notes (Optional)
                    </label>
                    <textarea id="completionNotes" 
                              name="notes" 
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                              placeholder="Any additional notes about the pickup..."></textarea>
                </div>
            </div>
            
            <div class="flex space-x-3 mt-6">
                <button type="button" 
                        onclick="hideCompletePickupModal()" 
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 flex items-center justify-center">
                    <i data-lucide="check" class="w-4 h-4 mr-2"></i>
                    Complete Pickup
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();
    
    // Initialize map
    let map;
    let markers = [];
    let routeLines = [];
    let currentRoute = null;
    let currentStopIndex = 0;
    let routeStops = [];
    let stopMarkers = [];

    // Date selector functionality
    function changeDate() {
        const selectedDate = document.getElementById('dateSelector').value;
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('date', selectedDate);
        window.location.href = currentUrl.toString();
    }

    // Complete pickup modal functions
    function showCompletePickupModal(requestId, residentName, materialType, unit, estimatedWeight, estimatedQuantity) {
        document.getElementById('completeRequestId').value = requestId;
        
        // Populate pickup details
        const detailsHtml = `
            <h4 class="font-semibold text-gray-900 mb-2">Pickup Details</h4>
            <div class="space-y-2 text-sm">
                <p><span class="text-gray-600">Resident:</span> <span class="font-medium">${residentName}</span></p>
                <p><span class="text-gray-600">Material:</span> <span class="font-medium">${materialType}</span></p>
                <p><span class="text-gray-600">Unit:</span> <span class="font-medium">${unit}</span></p>
                <p><span class="text-gray-600">Estimated ${unit === 'kg' ? 'Weight' : 'Quantity'}:</span> <span class="font-medium">${unit === 'kg' ? estimatedWeight + ' kg' : estimatedQuantity + ' pcs'}</span></p>
            </div>
        `;
        document.getElementById('pickupDetails').innerHTML = detailsHtml;
        
        // Show appropriate input fields based on unit
        const weightSection = document.getElementById('weightSection');
        const quantitySection = document.getElementById('quantitySection');
        const weightInput = document.getElementById('actualWeight');
        const quantityInput = document.getElementById('actualQuantity');
        
        if (unit === 'kg') {
            weightSection.classList.remove('hidden');
            quantitySection.classList.add('hidden');
            weightInput.required = true;
            quantityInput.required = false;
            weightInput.value = estimatedWeight;
        } else {
            weightSection.classList.add('hidden');
            quantitySection.classList.remove('hidden');
            weightInput.required = false;
            quantityInput.required = true;
            quantityInput.value = estimatedQuantity;
        }
        
        // Clear notes
        document.getElementById('completionNotes').value = '';
        
        // Show modal
        const modal = document.getElementById('completePickupModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            modal.querySelector('.bg-white').classList.remove('scale-95');
            modal.querySelector('.bg-white').classList.add('scale-100');
        }, 10);
    }

    function hideCompletePickupModal() {
        const modal = document.getElementById('completePickupModal');
        modal.querySelector('.bg-white').classList.remove('scale-100');
        modal.querySelector('.bg-white').classList.add('scale-95');
        document.body.style.overflow = '';
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }

    function submitPickupCompletion(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 mr-2 animate-spin"></i>Completing...';
        submitBtn.disabled = true;
        
        // Convert to JSON for the existing completion endpoint
        const data = {
            action: 'complete_pickup',
            request_id: parseInt(formData.get('request_id')),
            actual_weight: formData.get('actual_weight') ? parseFloat(formData.get('actual_weight')) : null,
            actual_quantity: formData.get('actual_quantity') ? parseInt(formData.get('actual_quantity')) : null,
            notes: formData.get('notes')
        };
        
        fetch('worker_assigned_pickups.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.text())
        .then(result => {
            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            // Check if completion was successful (page reload indicates success)
            if (result.includes('success') || result.includes('completed')) {
                hideCompletePickupModal();
                showNotification('Pickup completed successfully!', 'success');
                
                // Reload page to reflect changes
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification('Failed to complete pickup. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            showNotification('An error occurred. Please try again.', 'error');
        });
    }

    // Sorting functions for pickup requests
    function sortPickupsByTime(order) {
        const container = document.getElementById('pickupRequestsContainer');
        const items = Array.from(container.children);
        
        // Update button states
        document.querySelectorAll('.sort-btn').forEach(btn => {
            btn.classList.remove('active', 'bg-green-500', 'text-white');
            btn.classList.add('bg-gray-200', 'text-gray-700');
        });
        
        const activeBtn = document.getElementById(order === 'earliest' ? 'sortEarliestBtn' : 'sortLatestBtn');
        activeBtn.classList.remove('bg-gray-200', 'text-gray-700');
        activeBtn.classList.add('active', 'bg-green-500', 'text-white');
        
        // Sort items
        items.sort((a, b) => {
            const timeA = parseInt(a.dataset.preferredTime);
            const timeB = parseInt(b.dataset.preferredTime);
            
            if (order === 'earliest') {
                return timeA - timeB;
            } else {
                return timeB - timeA;
            }
        });
        
        // Re-append sorted items
        items.forEach(item => container.appendChild(item));
    }

    // Wrapper function to handle loading state for show on map
    function showOnMapWithLoading(button, lat, lng, residentName) {
        // Add loading state to button
        const originalContent = button.innerHTML;
        button.classList.add('button-loading');
        button.disabled = true;
        button.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 mr-2 animate-spin"></i><span class="button-text">Navigating...</span>';
        
        // Call the actual function
        showOnMap(lat, lng, residentName);
        
        // Reset button after animation
        setTimeout(() => {
            button.classList.remove('button-loading');
            button.disabled = false;
            button.innerHTML = originalContent;
            
            // Re-initialize lucide icons
            lucide.createIcons();
        }, 2000);
    }

    // Show pickup location on map
    function showOnMap(lat, lng, residentName) {
        if (map) {
            // Scroll to map section smoothly
            const mapSection = document.getElementById('routeMap').parentElement.parentElement;
            mapSection.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
            
            // Add visual indicator that we're navigating to location
            showNotification(`Navigating to ${residentName}'s location...`, 'info');
            
            // Wait for scroll animation then show location
            setTimeout(() => {
                // Center map on the location with appropriate zoom
                map.setView([lat, lng], 17, {
                    animate: true,
                    pan: {
                        animate: true,
                        duration: 1.5
                    },
                    zoom: {
                        animate: true
                    }
                });
                
                // Remove any existing temporary markers
                map.eachLayer(layer => {
                    if (layer.options && layer.options.className && layer.options.className.includes('temp-location-marker')) {
                        map.removeLayer(layer);
                    }
                });
                
                // Add an enhanced temporary marker with pulsing animation
                const tempMarker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: 'temp-location-marker',
                        html: `
                            <div class="relative">
                                <div class="w-12 h-12 bg-red-500 rounded-full border-4 border-white shadow-2xl flex items-center justify-center animate-bounce" style="animation: bounce 1s infinite;">
                                    <i class="fas fa-map-pin text-white text-lg"></i>
                                </div>
                                <div class="absolute inset-0 w-12 h-12 bg-red-400 rounded-full animate-ping opacity-75"></div>
                                <div class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-red-500"></div>
                            </div>
                        `,
                        iconSize: [48, 48],
                        iconAnchor: [24, 48],
                        className: 'temp-location-marker-container'
                    }),
                    zIndexOffset: 1000
                }).addTo(map);
                
                // Enhanced popup with more details
                tempMarker.bindPopup(`
                    <div class="p-4 min-w-48">
                        <div class="flex items-center space-x-2 mb-3">
                            <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-red-600 text-lg">${residentName}</h3>
                                <p class="text-xs text-gray-500">Pickup Location</p>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600 space-y-1">
                            <p><strong>Coordinates:</strong> ${lat.toFixed(6)}, ${lng.toFixed(6)}</p>
                            <p><strong>Status:</strong> <span class="text-blue-600">Selected for Navigation</span></p>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <button onclick="centerMapOnLocation(${lat}, ${lng})" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm font-medium transition-colors">
                                <i class="fas fa-crosshairs mr-2"></i>Re-center
                            </button>
                        </div>
                    </div>
                `, {
                    maxWidth: 300,
                    className: 'custom-popup'
                }).openPopup();
                
                // Add hover effects for all existing pickup markers
                highlightNearbyPickups(lat, lng);
                
                // Auto-remove marker after 15 seconds with fade out
                setTimeout(() => {
                    if (tempMarker && map.hasLayer(tempMarker)) {
                        // Fade out animation
                        const markerElement = tempMarker.getElement();
                        if (markerElement) {
                            markerElement.style.transition = 'opacity 1s ease-out';
                            markerElement.style.opacity = '0';
                            setTimeout(() => {
                                map.removeLayer(tempMarker);
                            }, 1000);
                        } else {
                            map.removeLayer(tempMarker);
                        }
                    }
                }, 15000);
                
            }, 800); // Wait for scroll animation
        } else {
            showNotification('Map not yet loaded. Please wait and try again.', 'warning');
        }
    }
    
    // Helper function to re-center map
    function centerMapOnLocation(lat, lng) {
        if (map) {
            map.setView([lat, lng], 18, {
                animate: true,
                pan: { animate: true, duration: 1 }
            });
        }
    }
    
    // Helper function to highlight nearby pickups
    function highlightNearbyPickups(targetLat, targetLng) {
        markers.forEach(marker => {
            if (marker.getLatLng) {
                const markerPos = marker.getLatLng();
                const distance = getDistance(
                    { lat: targetLat, lng: targetLng },
                    { lat: markerPos.lat, lng: markerPos.lng }
                );
                
                // Highlight markers within 500 meters
                if (distance < 0.5) {
                    const markerElement = marker.getElement();
                    if (markerElement && !markerElement.classList.contains('temp-location-marker-container')) {
                        // Add pulsing highlight effect
                        markerElement.style.transition = 'all 0.3s ease';
                        markerElement.style.transform = 'scale(1.2)';
                        markerElement.style.filter = 'drop-shadow(0 0 10px rgba(59, 130, 246, 0.8))';
                        
                        // Remove highlight after 5 seconds
                        setTimeout(() => {
                            markerElement.style.transform = 'scale(1)';
                            markerElement.style.filter = 'none';
                        }, 5000);
                    }
                }
            }
        });
    }

    // Notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
        
        const colors = {
            success: 'bg-green-600 text-white',
            error: 'bg-red-600 text-white',
            info: 'bg-blue-600 text-white',
            warning: 'bg-yellow-600 text-white'
        };
        
        const icons = {
            success: 'check-circle',
            error: 'x-circle',
            info: 'info',
            warning: 'alert-triangle'
        };
        
        notification.className += ` ${colors[type]}`;
        notification.innerHTML = `
            <div class="flex items-center space-x-2">
                <i data-lucide="${icons[type]}" class="w-5 h-5"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Trigger animation
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Auto remove
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 4000);
        
        // Re-create lucide icons
        lucide.createIcons();
    }

    // Close modal when clicking outside
    document.getElementById('completePickupModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideCompletePickupModal();
        }
    });

    // Initialize Leaflet map
    function initializeMap() {
        // Default center to Philippines if no barangay coordinates
        const defaultLat = 6.4216;
        const defaultLng = 124.7810;
        
        map = L.map('routeMap').setView([defaultLat, defaultLng], 13);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Load current routes and pickup requests
        loadRouteData();
    }

    // Load route data and display on map
    function loadRouteData() {
        const selectedDate = '<?php echo $selected_date; ?>';
        fetch(`get_map_data.php?worker_id=${<?php echo $user_id; ?>}&date=${selectedDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                clearMapData();
                
                // Add starting point (barangay office or admin location)
                if (data.admin_location) {
                    const startMarker = L.marker([data.admin_location.lat, data.admin_location.lng], {
                        icon: L.divIcon({
                            className: 'custom-marker start-marker',
                            html: '<div class="w-8 h-8 bg-green-500 rounded-full border-3 border-white shadow-lg flex items-center justify-center"><i class="fas fa-home text-white text-sm"></i></div>',
                            iconSize: [32, 32],
                            iconAnchor: [16, 16]
                        })
                    }).addTo(map);
                    
                    startMarker.bindPopup(`
                        <div class="p-3">
                            <h3 class="font-semibold text-green-600 text-lg">Starting Point</h3>
                            <p class="text-sm text-gray-600">All routes begin and end here</p>
                            <p class="text-xs text-gray-500 mt-2">Barangay Office Location</p>
                        </div>
                    `);
                    
                    markers.push(startMarker);
                    map.setView([data.admin_location.lat, data.admin_location.lng], 13);
                }
                
                // Add selected date's pickup request markers for this worker
                if (data.today_pickups && data.today_pickups.length > 0) {
                    data.today_pickups.forEach(pickup => {
                        const statusColors = {
                            'Scheduled': { color: 'blue', bg: 'bg-blue-500' },
                            'In Progress': { color: 'orange', bg: 'bg-orange-500' },
                            'Completed': { color: 'green', bg: 'bg-green-500' }
                        };
                        
                        const statusInfo = statusColors[pickup.status] || { color: 'blue', bg: 'bg-blue-500' };
                        
                        // Different marker style for completed pickups
                        const markerClass = pickup.status === 'Completed' ? 'completed-pickup-marker' : 'active-pickup-marker';
                        const iconHtml = pickup.status === 'Completed' 
                            ? `<div class="w-8 h-8 ${statusInfo.bg} rounded-full border-3 border-white shadow-lg flex items-center justify-center opacity-70"><i class="fas fa-check text-white text-sm"></i></div>`
                            : `<div class="w-8 h-8 ${statusInfo.bg} rounded-full border-3 border-white shadow-lg flex items-center justify-center"><i class="fas fa-home text-white text-sm"></i></div>`;
                        
                        const marker = L.marker([pickup.lat, pickup.lng], {
                            icon: L.divIcon({
                                className: `custom-marker pickup-marker ${markerClass}`,
                                html: iconHtml,
                                iconSize: [32, 32],
                                iconAnchor: [16, 16]
                            })
                        }).addTo(map);
                        
                        const estimatedPoints = pickup.unit === 'kg' 
                            ? Math.floor(pickup.weight_kg * pickup.points_per_kg)
                            : Math.floor(pickup.quantity_pcs * pickup.points_per_unit);
                        
                        const completionInfo = pickup.status === 'Completed' 
                            ? `<div class="mt-2 p-2 bg-green-50 border border-green-200 rounded">
                                 <p class="text-xs text-green-800 font-medium">✓ Completed on ${new Date(pickup.updated_at).toLocaleDateString()}</p>
                                 ${pickup.actual_weight ? `<p class="text-xs text-green-700">Actual Weight: ${pickup.actual_weight} kg</p>` : ''}
                                 ${pickup.points_earned ? `<p class="text-xs text-green-700">Points Earned: ${pickup.points_earned}</p>` : ''}
                               </div>`
                            : `<div class="mt-2">
                                 <button onclick="showCompletePickupModal(${pickup.request_id}, '${pickup.resident_name.replace(/'/g, "\\'")}', '${pickup.material_type.replace(/'/g, "\\'")}', '${pickup.unit}', ${pickup.weight_kg}, ${pickup.quantity_pcs || 0})" 
                                         class="w-full bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs font-medium transition-colors">
                                   Complete Pickup
                                 </button>
                               </div>`;
                        
                        marker.bindPopup(`
                            <div class="p-3 min-w-64">
                                <h3 class="font-semibold text-${statusInfo.color}-600 text-lg mb-2">${pickup.resident_name}</h3>
                                <div class="space-y-1 text-sm mb-3">
                                    <p><span class="font-medium">Address:</span> ${pickup.address}</p>
                                    <p><span class="font-medium">Material:</span> ${pickup.material_type}</p>
                                    <p><span class="font-medium">Weight:</span> ${pickup.weight_kg} kg</p>
                                    <p><span class="font-medium">Est. Points:</span> ${estimatedPoints}</p>
                                    <p><span class="font-medium">Status:</span> <span class="text-${statusInfo.color}-600">${pickup.status}</span></p>
                                    <p><span class="font-medium">Preferred Time:</span> ${new Date(pickup.preferred_date).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</p>
                                </div>
                                ${completionInfo}
                            </div>
                        `);
                        
                        markers.push(marker);
                    });
                }
                
                // Add collection points markers
                if (data.collection_points) {
                    data.collection_points.forEach(point => {
                        const icon = L.divIcon({
                            html: `<div class="collection-point-marker bg-green-600 text-white rounded-lg w-8 h-6 flex items-center justify-center text-xs font-bold shadow-lg">
                                     <i class="fas fa-warehouse"></i>
                                   </div>`,
                            className: 'custom-marker',
                            iconSize: [32, 24],
                            iconAnchor: [16, 12]
                        });
                        
                        const marker = L.marker([point.lat, point.lng], { icon: icon }).addTo(map);
                        
                        marker.bindPopup(`
                            <div class="p-3 min-w-64">
                                <h4 class="font-bold text-gray-900 mb-2 flex items-center">
                                    <i class="fas fa-warehouse text-green-600 mr-2"></i>
                                    ${point.name}
                                </h4>
                                <div class="space-y-1 text-sm">
                                    <p><strong>Type:</strong> Collection Point</p>
                                    <p><strong>Purok:</strong> ${point.purok_name || 'N/A'}</p>
                                    <p><strong>Description:</strong> ${point.description || 'No description'}</p>
                                    <div class="street-routing-indicator">
                                        <i class="fas fa-route mr-1"></i>Collection Point
                                    </div>
                                </div>
                            </div>
                        `);
                        
                        markers.push(marker);
                    });
                }
                
                // Draw optimized routes if available
                if (data.routes && data.routes.length > 0) {
                    data.routes.forEach(route => {
                        drawOptimizedRoute(route);
                    });
                }
                
                // Fit map to show all markers with padding
                if (markers.length > 0) {
                    const group = new L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.1));
                } else {
                    // If no markers, show a message on the map
                    showNotification(`No pickup requests found for ${selectedDate}`, 'info');
                }
            } else {
                showNotification('Failed to load map data: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error loading map data:', error);
            showNotification('Failed to load map data', 'error');
        });
    }

    // Draw optimized route on map with street routing
    function drawOptimizedRoute(route) {
        if (route.stops && route.stops.length > 1) {
            // Store current route data for navigation
            currentRoute = route;
            routeStops = route.stops;
            currentStopIndex = 0;
            
            // Show navigation controls
            showRouteNavigation();
            
            // If we have street geometry, use it; otherwise fall back to direct lines
            const streetGeometry = route.street_geometry;
            
            if (streetGeometry && streetGeometry.segments && streetGeometry.segments.length > 0) {
                // Draw actual street routes
                drawStreetRoutingPath(streetGeometry, route);
            } else {
                // Fallback to direct line routing
                drawDirectLineRoute(route);
            }
            
            // Add numbered markers for pickup stops
            route.stops.forEach((stop, index) => {
                let markerColor, iconClass, stopType, bgColor;
                
                if (stop.type === 'collection_point') {
                    markerColor = 'green';
                    iconClass = 'recycle';
                    stopType = 'Collection Point';
                    bgColor = 'bg-green-500';
                } else if (stop.type === 'pickup') {
                    markerColor = 'blue';
                    iconClass = 'home';
                    stopType = 'Home Pickup';
                    bgColor = 'bg-blue-500';
                } else {
                    markerColor = 'red';
                    iconClass = 'flag-triangle-right';
                    stopType = 'Starting Point';
                    bgColor = 'bg-red-500';
                }
                
                // Create enhanced marker with stop number and type indicator
                const isCurrentStop = index === currentStopIndex;
                const currentStopClass = isCurrentStop ? 'current-active-stop' : '';
                const zIndexStyle = isCurrentStop ? 'z-index: 2000;' : 'z-index: 1000;';
                const scaleClass = isCurrentStop ? 'scale-125' : '';
                const glowEffect = isCurrentStop ? 'box-shadow: 0 0 25px rgba(255, 193, 7, 0.8), 0 0 50px rgba(255, 193, 7, 0.4);' : '';
                
                const markerHtml = `
                    <div class="route-marker-container relative ${currentStopClass}" style="${zIndexStyle} ${glowEffect}">
                        <div class="w-10 h-10 ${bgColor} rounded-full border-3 border-white shadow-xl flex items-center justify-center relative marker-${index} ${scaleClass} transition-all duration-300">
                            <i data-lucide="${iconClass}" class="w-5 h-5 text-white"></i>
                            <div class="absolute -top-2 -right-2 w-6 h-6 bg-white rounded-full border-2 border-${markerColor}-500 flex items-center justify-center">
                                <span class="text-xs text-${markerColor}-700 font-bold">${index}</span>
                            </div>
                            ${isCurrentStop ? '<div class="absolute inset-0 border-4 border-yellow-400 rounded-full animate-pulse"></div>' : ''}
                        </div>
                        ${stop.status === 'Completed' ? 
                            '<div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white flex items-center justify-center"><i data-lucide="check" class="w-2 h-2 text-white"></i></div>' : 
                            ''
                        }
                    </div>
                `;
                
                const numberMarker = L.divIcon({
                    className: 'route-number-marker custom-marker',
                    html: markerHtml,
                    iconSize: [40, 40],
                    iconAnchor: [20, 20]
                });
                
                const stopMarker = L.marker([stop.lat, stop.lng], { icon: numberMarker }).addTo(map);
                
                // Enhanced popup with more information
                const statusBadge = stop.status === 'Completed' ? 
                    '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"><i data-lucide="check-circle" class="w-3 h-3 mr-1"></i>Completed</span>' :
                    '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"><i data-lucide="clock" class="w-3 h-3 mr-1"></i>Scheduled</span>';
                
                const estimatedTime = index * 15; // Rough estimate: 15 minutes per stop
                const timeFromNow = new Date(Date.now() + estimatedTime * 60000).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                
                const popupContent = `
                    <div class="p-4 min-w-64">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 ${bgColor} rounded-full flex items-center justify-center">
                                    <i data-lucide="${iconClass}" class="w-4 h-4 text-white"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900">Stop ${index}</div>
                                    <div class="text-xs text-gray-500">${stopType}</div>
                                </div>
                            </div>
                            ${statusBadge}
                        </div>
                        
                        <div class="space-y-2 mb-3">
                            <div class="text-sm font-semibold text-gray-800">${stop.resident_name || stop.name || 'Stop ' + index}</div>
                            ${stop.material_type ? `<div class="text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded"><i data-lucide="package" class="w-3 h-3 inline mr-1"></i>Material: ${stop.material_type}</div>` : ''}
                            ${stop.address ? `<div class="text-xs text-gray-600"><i data-lucide="map-pin" class="w-3 h-3 inline mr-1"></i>${stop.address}</div>` : ''}
                        </div>
                        
                        <div class="border-t pt-2 space-y-1">
                            <div class="text-xs text-gray-500"><i data-lucide="clock" class="w-3 h-3 inline mr-1"></i>Est. arrival: ${timeFromNow}</div>
                            <div class="text-xs text-gray-500"><i data-lucide="navigation" class="w-3 h-3 inline mr-1"></i>Lat: ${stop.lat.toFixed(4)}, Lng: ${stop.lng.toFixed(4)}</div>
                        </div>
                        
                        <div class="flex space-x-2 mt-3">
                            <button onclick="centerOnStop(${index})" class="flex-1 bg-blue-500 text-white px-3 py-2 rounded text-xs hover:bg-blue-600 transition-colors">
                                <i data-lucide="focus" class="w-3 h-3 inline mr-1"></i>Center Map
                            </button>
                           
                        </div>
                    </div>
                `;
                
                stopMarker.bindPopup(popupContent);
                stopMarkers.push(stopMarker);
                markers.push(stopMarker);
            });
            
            // Add route information control
            addRouteInfoControl(route);
            
            // Update navigation controls
            updateNavigationControls();
            
            // Ensure current stop is brought to front
            setTimeout(() => {
                updateMarkerZIndexes();
            }, 100);
        }
    }

    // Draw street routing path using OSRM geometry
    function drawStreetRoutingPath(streetGeometry, route) {
        if (!streetGeometry || !streetGeometry.segments || streetGeometry.segments.length === 0) {
            console.log('No street geometry available, falling back to direct lines');
            drawDirectLineRoute(route);
            return;
        }
        
        streetGeometry.segments.forEach((segment, index) => {
            if (segment.coordinates && segment.coordinates.length > 1) {
                // Convert coordinates to Leaflet format [lat, lng]
                const latLngCoords = segment.coordinates.map(coord => [coord[1], coord[0]]);
                
                const isReturnSegment = segment.is_return || false;
                const isFallback = segment.fallback || false;
                
                // Choose colors and styles based on segment type
                let routeColor, routeWeight, routeOpacity, routeDashArray;
                
                if (isReturnSegment) {
                    routeColor = '#16a34a'; // Green for return
                    routeWeight = isFallback ? 3 : 4;
                    routeOpacity = 0.8;
                    routeDashArray = '8, 12'; // Longer dashes for return
                } else {
                    routeColor = '#dc2626'; // Red for pickup route
                    routeWeight = isFallback ? 3 : 5;
                    routeOpacity = 0.9;
                    routeDashArray = isFallback ? '15, 10' : '10, 5';
                }
                
                const routeLine = L.polyline(latLngCoords, {
                    color: routeColor,
                    weight: routeWeight,
                    opacity: routeOpacity,
                    dashArray: routeDashArray,
                    className: isFallback ? 'fallback-route' : 'street-route'
                }).addTo(map);
                
                // Add popup with segment information
                const distance = segment.distance ? (segment.distance / 1000).toFixed(2) : 'Unknown';
                const duration = segment.duration ? Math.round(segment.duration / 60) : 'Unknown';
                
                routeLine.bindPopup(`
                    <div class="p-3">
                        <h4 class="font-semibold ${isReturnSegment ? 'text-green-600' : 'text-red-600'} mb-2">
                            <i class="fas fa-route mr-2"></i>
                            ${isReturnSegment ? 'Return Journey' : `Route Segment ${index + 1}`}
                        </h4>
                        <div class="space-y-1 text-sm">
                            <p><strong>From:</strong> ${segment.from_stop || 'Previous Stop'}</p>
                            <p><strong>To:</strong> ${segment.to_stop || 'Next Stop'}</p>
                            <p><strong>Distance:</strong> ${distance} km</p>
                            <p><strong>Est. Time:</strong> ${duration} min</p>
                            ${isFallback ? 
                                '<p class="text-xs text-yellow-600 mt-2"><i class="fas fa-exclamation-triangle mr-1"></i>Direct route (street data unavailable)</p>' : 
                                '<p class="text-xs text-green-600 mt-2"><i class="fas fa-check-circle mr-1"></i>Actual street routing</p>'
                            }
                        </div>
                    </div>
                `);
                
                routeLines.push(routeLine);
            }
        });
        
        // Add route statistics
        if (streetGeometry.total_distance > 0) {
            const totalDistanceKm = (streetGeometry.total_distance / 1000).toFixed(2);
            const totalDurationHours = Math.round(streetGeometry.total_duration / 3600 * 100) / 100;
            
            const routeStatsControl = L.control({position: 'topleft'});
            routeStatsControl.onAdd = function(map) {
                const div = L.DomUtil.create('div', 'route-stats-control');
                div.innerHTML = `
                    <div class="bg-white p-3 rounded-lg shadow-lg border-l-4 border-blue-500">
                        <h4 class="font-semibold text-gray-900 mb-2 flex items-center">
                            <i class="fas fa-chart-line mr-2 text-blue-600"></i>
                            Route Statistics
                        </h4>
                        <div class="space-y-1 text-sm text-gray-600">
                            <p><strong>Total Distance:</strong> ${totalDistanceKm} km</p>
                            <p><strong>Total Duration:</strong> ${totalDurationHours}h</p>
                            <p><strong>Routing Method:</strong> Street Navigation</p>
                        </div>
                        <div class="mt-2 text-xs text-green-600">
                            <i class="fas fa-route mr-1"></i>Optimized path with street routing
                        </div>
                    </div>
                `;
                return div;
            };
            routeStatsControl.addTo(map);
        }
    }

    // Fallback to direct line routing
    function drawDirectLineRoute(route) {
        const routeCoords = route.stops.map(stop => [stop.lat, stop.lng]);
        
        // Add return path to starting point
        if (routeCoords.length > 0) {
            routeCoords.push(routeCoords[0]); // Return to start
        }
        
        // Create main route line (red dashed for pickup route)
        const outboundRoute = L.polyline(routeCoords.slice(0, -1), {
            color: '#dc2626',
            weight: 4,
            opacity: 0.8,
            dashArray: '10, 5'
        }).addTo(map);
        
        // Create return route line (green solid line)
        if (routeCoords.length > 1) {
            const returnRoute = L.polyline([routeCoords[routeCoords.length-2], routeCoords[routeCoords.length-1]], {
                color: '#16a34a',
                weight: 4,
                opacity: 0.8,
                dashArray: '5, 10'
            }).addTo(map);
            routeLines.push(returnRoute);
        }
        
        routeLines.push(outboundRoute);
    }

    // Add route information control to map
    function addRouteInfoControl(route) {
        if (route.total_distance || route.stops) {
            const infoControl = L.control({position: 'topright'});
            infoControl.onAdd = function(map) {
                const div = L.DomUtil.create('div', 'route-info-control');
                
                const pickupCount = route.stops ? route.stops.filter(s => s.type === 'pickup').length : 0;
                const collectionCount = route.stops ? route.stops.filter(s => s.type === 'collection_point').length : 0;
                const totalStops = pickupCount + collectionCount;
                
                div.innerHTML = `
                    <div class="bg-white p-4 rounded-lg shadow-lg border border-gray-200">
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-route mr-2 text-blue-600"></i>
                            Route Summary
                        </h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Distance:</span>
                                <span class="font-medium text-gray-900">${route.total_distance ? route.total_distance.toFixed(2) : 'Calculating...'} km</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Est. Duration:</span>
                                <span class="font-medium text-gray-900">${route.estimated_time || 'Calculating...'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Home Pickups:</span>
                                <span class="font-medium text-blue-600">${pickupCount}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Collection Points:</span>
                                <span class="font-medium text-green-600">${collectionCount}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Stops:</span>
                                <span class="font-medium text-gray-900">${totalStops}</span>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <div class="flex items-center space-x-2 text-xs">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span class="text-green-600 font-medium">Street Navigation Enabled</span>
                            </div>
                            <div class="flex items-center space-x-2 text-xs mt-1">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                <span class="text-blue-600 font-medium">Auto-Optimized Route</span>
                            </div>
                        </div>
                    </div>
                `;
                return div;
            };
            infoControl.addTo(map);
        }
    }

    // Clear existing map data
    function clearMapData() {
        markers.forEach(marker => map.removeLayer(marker));
        routeLines.forEach(line => map.removeLayer(line));
        markers = [];
        routeLines = [];
        stopMarkers = [];
        routeStops = [];
        currentStopIndex = 0;
        hideRouteNavigation();
    }

    // Calculate optimal route using nearest neighbor algorithm
    function calculateOptimalRoute(barangay, pickups) {
        if (!pickups || pickups.length === 0) return [];
        
        const remaining = [...pickups];
        const route = [barangay];
        let current = barangay;
        
        while (remaining.length > 0) {
            let nearestIndex = 0;
            let nearestDistance = getDistance(current, remaining[0]);
            
            for (let i = 1; i < remaining.length; i++) {
                const distance = getDistance(current, remaining[i]);
                if (distance < nearestDistance) {
                    nearestDistance = distance;
                    nearestIndex = i;
                }
            }
            
            current = remaining[nearestIndex];
            route.push(current);
            remaining.splice(nearestIndex, 1);
        }
        
        return route;
    }

    // Calculate distance between two points using Haversine formula
    function getDistance(point1, point2) {
        const R = 6371; // Earth's radius in km
        const dLat = (point2.lat - point1.lat) * Math.PI / 180;
        const dLng = (point2.lng - point1.lng) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(point1.lat * Math.PI / 180) * Math.cos(point2.lat * Math.PI / 180) *
                Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    // View all worker's pickups on the map
    function viewAllPickups() {
        // Focus map on worker's pickups and highlight them
        fetch('get_map_data.php?focus=worker_pickups')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.worker_pickups.length > 0) {
                clearMapData();
                loadRouteData();
                
                // Create a popup showing pickup summary
                setTimeout(() => {
                    const totalPickups = data.worker_pickups.length;
                    const scheduledPickups = data.worker_pickups.filter(p => p.status === 'Scheduled').length;
                    const completedPickups = data.worker_pickups.filter(p => p.status === 'Completed').length;
                    
                    const summaryPopup = L.popup()
                        .setLatLng(map.getCenter())
                        .setContent(`
                            <div class="p-3">
                                <h3 class="font-semibold text-blue-600 mb-2">Your Pickup Summary</h3>
                                <p class="text-sm text-gray-600">Total Pickups: <span class="font-medium">${totalPickups}</span></p>
                                <p class="text-sm text-gray-600">Scheduled: <span class="font-medium text-blue-600">${scheduledPickups}</span></p>
                                <p class="text-sm text-gray-600">Completed: <span class="font-medium text-green-600">${completedPickups}</span></p>
                                ${scheduledPickups > 0 ? '<p class="text-xs text-orange-600 mt-2">💡 Generate an optimal route to save fuel!</p>' : ''}
                            </div>
                        `)
                        .openOn(map);
                }, 1000);
            } else {
                alert('No pickups found for your account');
            }
        })
        .catch(error => {
            console.error('Error loading pickups:', error);
        });
    }
    function viewRouteDetails(routeId) {
        // Load route details via AJAX
        fetch(`get_route_details.php?route_id=${routeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('routeContent').innerHTML = data.html;
                showRouteModal();
                
                // Highlight route on map if available
                highlightRouteOnMap(routeId);
            } else {
                alert('Failed to load route details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading route details');
        });
    }

    function highlightRouteOnMap(routeId) {
        // Fetch route coordinates and highlight on map
        fetch(`get_route_coordinates.php?route_id=${routeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.coordinates) {
                // Clear existing highlights
                clearMapData();
                
                // Redraw with highlighted route
                loadRouteData();
                
                // Add special highlighting for selected route
                setTimeout(() => {
                    data.coordinates.forEach((coord, index) => {
                        const highlightMarker = L.circleMarker([coord.lat, coord.lng], {
                            radius: 8,
                            color: '#ff0000',
                            weight: 3,
                            fillColor: '#ff0000',
                            fillOpacity: 0.5
                        }).addTo(map);
                        
                        markers.push(highlightMarker);
                    });
                }, 500);
            }
        })
        .catch(error => console.error('Error highlighting route:', error));
    }

    function startRoute(routeId) {
        // Show route details in modal instead of separate page
        viewRouteDetails(routeId);
    }

    function showRouteModal() {
        const modal = document.getElementById('routeModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
        setTimeout(() => {
            modal.querySelector('div > div').classList.remove('scale-95');
            modal.querySelector('div > div').classList.add('scale-100');
        }, 10);
    }

    function hideRouteModal() {
        const modal = document.getElementById('routeModal');
        modal.querySelector('div > div').classList.remove('scale-100');
        modal.querySelector('div > div').classList.add('scale-95');
        document.body.style.overflow = ''; // Restore scrolling
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }

    // Close modal when clicking outside
    document.getElementById('routeModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideRouteModal();
        }
    });

    // Initialize map when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Add Leaflet CSS and JS
        const leafletCSS = document.createElement('link');
        leafletCSS.rel = 'stylesheet';
        leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(leafletCSS);
        
        const leafletJS = document.createElement('script');
        leafletJS.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        leafletJS.onload = function() {
            initializeMap();
        };
        document.head.appendChild(leafletJS);
        
        // Add Font Awesome for icons
        const fontAwesome = document.createElement('link');
        fontAwesome.rel = 'stylesheet';
        fontAwesome.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css';
        document.head.appendChild(fontAwesome);
    });

    // Route Navigation Functions
    function showRouteNavigation() {
        document.getElementById('routeNavigation').classList.remove('hidden');
    }

    function hideRouteNavigation() {
        document.getElementById('routeNavigation').classList.add('hidden');
    }

    function updateNavigationControls() {
        if (!routeStops || routeStops.length === 0) return;
        
        const prevBtn = document.getElementById('prevStopBtn');
        const nextBtn = document.getElementById('nextStopBtn');
        const currentStopInfo = document.getElementById('currentStopInfo');
        
        // Update stop info
        const currentStop = routeStops[currentStopIndex];
        const stopType = currentStop?.type === 'pickup' ? 'Pickup' : currentStop?.type === 'collection_point' ? 'Collection' : 'Start';
        currentStopInfo.innerHTML = `Stop ${currentStopIndex + 1} of ${routeStops.length} <span class="text-xs">(${stopType})</span>`;
        
        // Update button states
        prevBtn.disabled = currentStopIndex === 0;
        nextBtn.disabled = currentStopIndex === routeStops.length - 1;
        
        // Update button text with icons
        if (currentStopIndex === 0) {
            nextBtn.innerHTML = '<i data-lucide="play" class="w-4 h-4"></i> Start <i data-lucide="chevron-right" class="w-4 h-4"></i>';
        } else if (currentStopIndex === routeStops.length - 1) {
            nextBtn.innerHTML = '<i data-lucide="flag" class="w-4 h-4"></i> Finish';
        } else {
            nextBtn.innerHTML = 'Next <i data-lucide="chevron-right" class="w-4 h-4"></i>';
        }
        
        // Update route summary
        updateRouteSummary();
        
        // Refresh lucide icons
        lucide.createIcons();
    }
    
    function updateRouteSummary() {
        if (!routeStops) return;
        
        const totalStops = routeStops.length;
        const pickupCount = routeStops.filter(stop => stop.type === 'pickup').length;
        const collectionCount = routeStops.filter(stop => stop.type === 'collection_point').length;
        const estimatedTotalTime = totalStops * 15; // 15 minutes per stop estimate
        
        document.getElementById('totalStops').textContent = `${totalStops} stops`;
        document.getElementById('pickupCount').textContent = `${pickupCount} pickups`;
        document.getElementById('collectionCount').textContent = `${collectionCount} points`;
        document.getElementById('estimatedTime').textContent = `${estimatedTotalTime} min`;
        
        // Show the summary
        document.getElementById('routeSummary').classList.remove('hidden');
    }

    function navigateToNextStop() {
        if (!routeStops || currentStopIndex >= routeStops.length - 1) return;
        
        currentStopIndex++;
        centerOnStop(currentStopIndex);
        updateNavigationControls();
        highlightCurrentStop();
        updateMarkerZIndexes();
    }

    function navigateToPreviousStop() {
        if (!routeStops || currentStopIndex <= 0) return;
        
        currentStopIndex--;
        centerOnStop(currentStopIndex);
        updateNavigationControls();
        highlightCurrentStop();
        updateMarkerZIndexes();
    }

    function centerOnStop(stopIndex) {
        if (!routeStops || stopIndex < 0 || stopIndex >= routeStops.length) return;
        
        const stop = routeStops[stopIndex];
        currentStopIndex = stopIndex;
        
        // Center map on the stop
        map.setView([stop.lat, stop.lng], 16, {
            animate: true,
            pan: {
                animate: true,
                duration: 1
            }
        });
        
        // Open popup for current stop
        if (stopMarkers[stopIndex]) {
            stopMarkers[stopIndex].openPopup();
        }
        
        updateNavigationControls();
        highlightCurrentStop();
        updateMarkerZIndexes();
    }

    // Update marker z-indexes to bring current stop to front
    function updateMarkerZIndexes() {
        stopMarkers.forEach((marker, index) => {
            if (marker && marker._icon) {
                const container = marker._icon.querySelector('.route-marker-container');
                if (container) {
                    if (index === currentStopIndex) {
                        // Current stop gets highest z-index
                        container.style.zIndex = '2000';
                        container.style.position = 'relative';
                        // Add current stop styling
                        container.classList.add('current-active-stop');
                        const markerDiv = container.querySelector(`div[class*="marker-${index}"]`);
                        if (markerDiv) {
                            markerDiv.classList.add('scale-125', 'animate-pulse');
                            markerDiv.style.borderColor = '#fbbf24'; // yellow border
                            markerDiv.style.borderWidth = '4px';
                        }
                    } else {
                        // Other stops get normal z-index
                        container.style.zIndex = '1000';
                        // Remove current stop styling
                        container.classList.remove('current-active-stop');
                        const markerDiv = container.querySelector(`div[class*="marker-${index}"]`);
                        if (markerDiv) {
                            markerDiv.classList.remove('scale-125', 'animate-pulse');
                            markerDiv.style.borderColor = 'white';
                            markerDiv.style.borderWidth = '3px';
                        }
                    }
                }
            }
        });
    }

    function highlightCurrentStop() {
        // Remove previous highlighting
        stopMarkers.forEach((marker, index) => {
            if (marker._icon) {
                marker._icon.style.transform = 'scale(1)';
                marker._icon.style.zIndex = '1000';
                marker._icon.classList.remove('current-stop-active', 'pulse-animation');
                
                // Remove any existing glow effects
                const container = marker._icon.querySelector('.route-marker-container');
                if (container) {
                    container.style.boxShadow = '';
                    container.style.animation = '';
                }
            }
        });
        
        // Highlight current stop with enhanced effects
        if (stopMarkers[currentStopIndex] && stopMarkers[currentStopIndex]._icon) {
            const currentIcon = stopMarkers[currentStopIndex]._icon;
            const container = currentIcon.querySelector('.route-marker-container');
            
            // Apply enhanced styling
            currentIcon.style.transform = 'scale(1.4)';
            currentIcon.style.zIndex = '1001';
            currentIcon.classList.add('current-stop-active', 'pulse-animation');
            
            if (container) {
                // Add glowing effect
                container.style.boxShadow = '0 0 30px rgba(255, 193, 7, 0.8), 0 0 60px rgba(255, 193, 7, 0.4)';
                container.style.animation = 'currentStopPulse 2s infinite';
                
                // Add a rotating border effect
                const marker = container.querySelector(`div[class*="marker-${currentStopIndex}"]`);
                if (marker) {
                    marker.style.animation = 'currentStopRotate 3s linear infinite';
                }
            }
            
            // Auto-remove effects after a few seconds
            setTimeout(() => {
                if (stopMarkers[currentStopIndex] && stopMarkers[currentStopIndex]._icon) {
                    const icon = stopMarkers[currentStopIndex]._icon;
                    icon.classList.remove('pulse-animation');
                    
                    const cont = icon.querySelector('.route-marker-container');
                    if (cont) {
                        cont.style.animation = 'none';
                        const markerDiv = cont.querySelector(`div[class*="marker-${currentStopIndex}"]`);
                        if (markerDiv) {
                            markerDiv.style.animation = 'none';
                        }
                    }
                }
            }, 3000);
        }
    }

    function resetMapView() {
        if (markers.length > 0) {
            const group = new L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.1), {
                animate: true,
                pan: {
                    animate: true,
                    duration: 1
                }
            });
            
            // Reset to first stop
            currentStopIndex = 0;
            updateNavigationControls();
            
            // Remove highlighting
            stopMarkers.forEach((marker) => {
                if (marker._icon) {
                    marker._icon.style.transform = 'scale(1)';
                    marker._icon.style.zIndex = '1000';
                }
            });
        }
    }

    // Clear existing map data
    function clearMapData() {
        markers.forEach(marker => map.removeLayer(marker));
        routeLines.forEach(line => map.removeLayer(line));
        markers = [];
        routeLines = [];
        stopMarkers = [];
        routeStops = [];
        currentStopIndex = 0;
        hideRouteNavigation();
    }
</script>

<style>
    /* Enhanced custom marker styles */
    .custom-marker {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }
    
    .route-number-marker {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }
    
    .route-marker-container {
        transition: all 0.3s ease;
    }
    
    .route-marker-container:hover {
        transform: scale(1.1);
    }
    
    /* Enhanced legend styles */
    .legend-marker {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        transition: all 0.3s ease;
        position: relative;
    }
    
    .legend-marker:hover {
        transform: scale(1.2);
        box-shadow: 0 6px 16px rgba(0,0,0,0.25);
    }
    
    .legend-marker::before {
        content: '';
        position: absolute;
        inset: -2px;
        border-radius: inherit;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
        animation: legend-shine 3s infinite;
        pointer-events: none;
    }
    
    @keyframes legend-shine {
        0%, 100% { opacity: 0; transform: rotate(0deg); }
        50% { opacity: 1; transform: rotate(45deg); }
    }
    
    .legend-route-line {
        position: relative;
        overflow: hidden;
    }
    
    .legend-route-line::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        animation: legend-shimmer 3s infinite;
    }
    
    @keyframes legend-shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    /* Leaflet popup customization */
    .leaflet-popup-content-wrapper {
        border-radius: 12px !important;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
        border: 1px solid #e5e7eb;
    }
    
    .leaflet-popup-content {
        margin: 12px 16px !important;
        line-height: 1.5 !important;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
        font-size: 14px !important;
    }
    
    .leaflet-popup-tip {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    }
    
    /* Map marker animations */
    .start-marker .w-6,
    .start-marker .w-8 {
        animation: pulse-green 2s infinite;
    }
    
    @keyframes pulse-green {
        0%, 100% { 
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            transform: scale(1);
        }
        50% { 
            box-shadow: 0 0 0 8px rgba(34, 197, 94, 0);
            transform: scale(1.05);
        }
    }
    
    .pickup-marker .w-5,
    .pickup-marker .w-6 {
        transition: all 0.3s ease;
    }
    
    .pickup-marker:hover .w-5,
    .pickup-marker:hover .w-6 {
        transform: scale(1.2);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    
    /* Enhanced route marker pulse animation */
    .pulse-animation {
        animation: enhanced-pulse-marker 2s infinite;
    }
    
    .current-stop-active {
        filter: drop-shadow(0 0 20px rgba(255, 193, 7, 0.8));
    }
    
    @keyframes enhanced-pulse-marker {
        0%, 100% { 
            transform: scale(1.4);
            filter: drop-shadow(0 0 20px rgba(255, 193, 7, 0.8));
        }
        50% { 
            transform: scale(1.6);
            filter: drop-shadow(0 0 40px rgba(255, 193, 7, 1)) drop-shadow(0 0 60px rgba(255, 193, 7, 0.6));
        }
    }
    
    @keyframes currentStopPulse {
        0%, 100% { 
            box-shadow: 0 0 30px rgba(255, 193, 7, 0.8), 0 0 60px rgba(255, 193, 7, 0.4);
        }
        50% { 
            box-shadow: 0 0 50px rgba(255, 193, 7, 1), 0 0 100px rgba(255, 193, 7, 0.6), 0 0 150px rgba(255, 193, 7, 0.3);
        }
    }
    
    @keyframes currentStopRotate {
        0% { 
            transform: rotate(0deg);
            border-color: currentColor;
        }
        25% { 
            border-color: #fbbf24;
        }
        50% { 
            transform: rotate(180deg);
            border-color: #f59e0b;
        }
        75% { 
            border-color: #d97706;
        }
        100% { 
            transform: rotate(360deg);
            border-color: currentColor;
        }
    }
    
    /* Route line styles */
    .street-route {
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        transition: all 0.3s ease;
    }
    
    .street-route:hover {
        filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
        opacity: 1 !important;
        stroke-width: 6 !important;
    }
    
    .fallback-route {
        filter: drop-shadow(0 1px 2px rgba(251, 191, 36, 0.5));
        opacity: 0.7;
    }
    
    .fallback-route:hover {
        opacity: 0.9;
    }
    
    /* Route info control styling */
    .route-info-control,
    .route-stats-control {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }
    
    .route-info-control .fa-route,
    .route-stats-control .fa-chart-line {
        animation: route-pulse 2s infinite;
    }
    
    @keyframes route-pulse {
        0%, 100% { 
            color: #2563eb;
            transform: scale(1);
        }
        50% { 
            color: #1d4ed8;
            transform: scale(1.1);
        }
    }
    
    /* Collection point marker styling */
    .collection-point-marker {
        transition: all 0.3s ease;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }
    
    .collection-point-marker:hover {
        transform: scale(1.1);
        filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
    }
    
    /* Street routing indicator */
    .street-routing-indicator {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 2px 8px;
        border-radius: 8px;
        font-size: 0.7rem;
        font-weight: 500;
        display: inline-block;
        margin-top: 4px;
        box-shadow: 0 1px 3px rgba(16, 185, 129, 0.3);
    }
    
    /* Map container styling */
    #routeMap {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    
    /* Legend section styling */
    .legend-section {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px;
    }
    
    /* Current active stop styles */
    .current-active-stop {
        position: relative !important;
        z-index: 2000 !important;
        transform: scale(1.1);
        transition: all 0.3s ease-in-out;
    }
    
    .current-active-stop::before {
        content: '';
        position: absolute;
        inset: -8px;
        border: 3px solid #fbbf24;
        border-radius: 50%;
        animation: currentStopGlow 2s infinite;
        pointer-events: none;
        z-index: -1;
    }
    
    .current-active-stop::after {
        content: '';
        position: absolute;
        inset: -4px;
        border: 2px solid rgba(251, 191, 36, 0.5);
        border-radius: 50%;
        animation: currentStopPulse 1.5s infinite;
        pointer-events: none;
        z-index: -1;
    }
    
    @keyframes currentStopGlow {
        0%, 100% {
            box-shadow: 0 0 15px rgba(251, 191, 36, 0.8);
            transform: scale(1);
        }
        50% {
            box-shadow: 0 0 25px rgba(251, 191, 36, 1), 0 0 40px rgba(251, 191, 36, 0.6);
            transform: scale(1.05);
        }
    }
    
    @keyframes currentStopPulse {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: 0.6;
            transform: scale(1.15);
        }
    }
    
    /* Ensure current stop marker stays on top */
    .leaflet-marker-icon.current-stop-marker {
        z-index: 2000 !important;
    }
    
    /* Override Leaflet's default marker layering */
    .leaflet-marker-icon {
        transition: z-index 0.3s ease, transform 0.3s ease;
    }
    
    .leaflet-marker-pane .current-active-stop {
        z-index: 2000 !important;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .legend-marker {
            margin: 2px;
        }
        
        .leaflet-popup-content {
            font-size: 12px !important;
        }
        
        #routeMap {
            height: 350px !important;
        }
    }
    
    /* Enhanced temporary marker animations */
    .temp-location-marker-container {
        z-index: 1000 !important;
    }
    
    .temp-location-marker .animate-bounce {
        animation: enhanced-bounce 1.5s ease-in-out infinite;
    }
    
    @keyframes enhanced-bounce {
        0%, 20%, 53%, 80%, 100% {
            transform: translate3d(0, 0, 0) scale(1);
        }
        40%, 43% {
            transform: translate3d(0, -8px, 0) scale(1.1);
        }
        70% {
            transform: translate3d(0, -4px, 0) scale(1.05);
        }
        90% {
            transform: translate3d(0, -2px, 0) scale(1.02);
        }
    }
    
    /* Custom popup styling */
    .custom-popup .leaflet-popup-content-wrapper {
        border-radius: 12px !important;
        box-shadow: 0 8px 32px rgba(0,0,0,0.2) !important;
        border: 2px solid #ef4444;
        background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%);
    }
    
    .custom-popup .leaflet-popup-tip {
        background: #ef4444 !important;
        border: 1px solid #dc2626 !important;
    }
    
    /* Hover effects for pickup request cards */
    .pickup-request-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        border-left: 4px solid #10b981;
    }
    
    .pickup-request-item:hover .bg-blue-500 {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
        transform: scale(1.05);
    }
    
    .pickup-request-item:hover .bg-gradient-to-r {
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    }
    
    /* Map container enhancements */
    #routeMap {
        transition: all 0.3s ease;
    }
    
    #routeMap:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    
    /* Enhanced marker hover effects */
    .leaflet-marker-icon:hover {
        transform: scale(1.1) !important;
        transition: transform 0.3s ease !important;
        filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3)) !important;
    }
    
    /* Smooth scroll enhancement */
    html {
        scroll-behavior: smooth;
    }
    
    /* Loading state for buttons */
    .button-loading {
        position: relative;
        color: transparent !important;
    }
    
    .button-loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: button-spin 1s ease-in-out infinite;
    }
    
    @keyframes button-spin {
        to { transform: rotate(360deg); }
    }
</style>
