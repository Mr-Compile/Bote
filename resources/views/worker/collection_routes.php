<!-- Main Content -->
<div class="p-8 bg-bote-stone-50 min-h-screen">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-bote-stone-900 mb-2">Collection Routes</h1>
                    <p class="text-bote-stone-600">Your daily collection routes with pickup requests and collection points</p>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Date Selector -->
                    <div class="flex items-center space-x-2">
                        <label for="dateSelector" class="text-sm font-medium text-bote-stone-700">Date:</label>
                        <select id="dateSelector" onchange="changeDate()" class="px-3 py-2 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 text-sm">
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
                                <option value="<?php echo e($option['pickup_date']); ?>" 
                                        <?php echo e($option['pickup_date'] === $selected_date ? 'selected' : ''); ?>>
                                    <?php echo e($date_label . $status_indicator); ?>
                                    <?php if ($option['pickup_count'] > 0): ?>
                                        (<?php echo e($option['pickup_count']); ?> pickups)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="bg-bote-info-100 border border-bote-info-300 text-bote-info-800 px-4 py-2 rounded-lg text-sm font-medium">
                        <i data-lucide="route" class="w-4 h-4 inline mr-2"></i>
                        Auto-optimized routes with street routing enabled
                    </div>
                    <button onclick="viewAllPickups()" class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white px-6 py-3 rounded-xl font-medium transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center space-x-2">
                        <i data-lucide="map-pin" class="w-5 h-5"></i>
                        <span>View My Pickups</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Route Map -->
        <div class="bg-white rounded-2xl shadow-lg mb-8">
            <div class="px-6 py-4 border-b border-bote-stone-200 bg-bote-green-50">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-bote-stone-900 flex items-center">
                        <i data-lucide="map" class="w-5 h-5 mr-2 text-bote-green-600"></i>
                        Route Map View
                    </h2>
                    <!-- Route Navigation Controls -->
                    <div id="routeNavigation" class="hidden flex items-center space-x-4 bg-white px-4 py-2 rounded-lg border border-bote-stone-300">
                        <span class="text-sm font-medium text-bote-stone-700">Stop Navigation:</span>
                        <button id="prevStopBtn" onclick="navigateToPreviousStop()" class="bg-bote-stone-500 hover:bg-bote-stone-600 text-white px-3 py-1 rounded-md text-sm font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <i data-lucide="chevron-left" class="w-4 h-4"></i> Prev
                        </button>
                        <span id="currentStopInfo" class="text-sm font-bold text-bote-info-600 px-2 py-1 bg-bote-info-50 rounded">
                            Stop 1 of 1
                        </span>
                        <button id="nextStopBtn" onclick="navigateToNextStop()" class="bg-bote-green-500 hover:bg-bote-green-600 text-white px-3 py-1 rounded-md text-sm font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            Next <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </button>
                        <button id="resetViewBtn" onclick="resetMapView()" class="bg-bote-green-500 hover:bg-bote-green-600 text-white px-3 py-1 rounded-md text-sm font-medium transition-all">
                            <i data-lucide="home" class="w-4 h-4"></i> Reset
                        </button>
                        <!-- Route Summary -->
                        <div id="routeSummary" class="ml-4 pl-4 border-l border-bote-stone-300 text-xs text-bote-stone-600 hidden md:flex items-center space-x-3">
                            <div class="flex items-center space-x-1">
                                <i data-lucide="map-pin" class="w-3 h-3"></i>
                                <span id="totalStops">0 stops</span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <i data-lucide="home" class="w-3 h-3 text-bote-info-500"></i>
                                <span id="pickupCount">0 pickups</span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <i data-lucide="recycle" class="w-3 h-3 text-bote-green-500"></i>
                                <span id="collectionCount">0 points</span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <i data-lucide="clock" class="w-3 h-3 text-bote-green-500"></i>
                                <span id="estimatedTime">0 min</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div id="routeMap" style="height: 500px; width: 100%;" class="rounded-lg border border-bote-stone-200"></div>
                <div class="legend-section mt-4">
                    <div class="flex flex-wrap gap-6">
                        <!-- Enhanced Marker Legend -->
                        <div class="flex items-center space-x-2">
                            <div class="legend-marker w-8 h-8 bg-red-500 rounded-full border-3 border-white shadow-xl flex items-center justify-center relative">
                                <i data-lucide="flag-triangle-right" class="w-4 h-4 text-white"></i>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-red-600 rounded-full border border-white"></div>
                            </div>
                            <span class="text-sm text-bote-stone-700 font-semibold">Starting Point (Barangay Office)</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="legend-marker w-8 h-8 bg-bote-green-500 rounded-full border-3 border-white shadow-xl flex items-center justify-center relative">
                                <i data-lucide="home" class="w-4 h-4 text-white"></i>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-bote-green-600 rounded-full border border-white flex items-center justify-center">
                                    <span class="text-xs text-white font-bold">#</span>
                                </div>
                            </div>
                            <span class="text-sm text-bote-stone-700 font-semibold">Home Pickup Requests</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="legend-marker w-8 h-8 bg-bote-green-500 rounded-full border-3 border-white shadow-xl flex items-center justify-center relative">
                                <i data-lucide="recycle" class="w-4 h-4 text-white"></i>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-bote-green-600 rounded-full border border-white flex items-center justify-center">
                                    <i data-lucide="warehouse" class="w-2 h-2 text-white"></i>
                                </div>
                            </div>
                            <span class="text-sm text-bote-stone-700 font-semibold">Collection Points</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="legend-marker w-8 h-8 bg-bote-stone-500 rounded-full border-3 border-white shadow-xl flex items-center justify-center relative">
                                <i data-lucide="check-circle" class="w-4 h-4 text-white"></i>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-bote-green-500 rounded-full border border-white"></div>
                            </div>
                            <span class="text-sm text-bote-stone-700 font-semibold">Completed Stops</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="legend-marker w-8 h-8 bg-yellow-500 rounded-full border-4 border-yellow-200 shadow-2xl flex items-center justify-center animate-pulse relative">
                                <i data-lucide="navigation" class="w-4 h-4 text-white"></i>
                                <div class="absolute -inset-2 border-2 border-yellow-400 rounded-full animate-ping"></div>
                            </div>
                            <span class="text-sm text-bote-stone-700 font-bold">Current Active Stop</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="legend-marker w-8 h-8 bg-bote-green-500 rounded-full border-3 border-white shadow-xl flex items-center justify-center relative">
                                <i data-lucide="clock" class="w-4 h-4 text-white"></i>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-bote-green-600 rounded-full border border-white"></div>
                            </div>
                            <span class="text-sm text-bote-stone-700 font-semibold">Scheduled/Pending</span>
                        </div>
                    </div>
                    
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Pickup Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-bote-info-500 to-bote-info-600 rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-bote-info-100 text-sm font-medium">Total Pickups</p>
                        <p class="text-3xl font-bold"><?php echo e($pickup_stats['total_pickups']); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-bote-info-400 bg-opacity-50 rounded-xl flex items-center justify-center">
                        <i data-lucide="package" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-bote-green-100 text-sm font-medium">Completed</p>
                        <p class="text-3xl font-bold"><?php echo e($pickup_stats['completed_pickups']); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-bote-green-400 bg-opacity-50 rounded-xl flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100 text-sm font-medium">Scheduled</p>
                        <p class="text-3xl font-bold"><?php echo e($pickup_stats['scheduled_pickups']); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-orange-400 bg-opacity-50 rounded-xl flex items-center justify-center">
                        <i data-lucide="clock" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-bote-green-100 text-sm font-medium">Weight Collected</p>
                        <p class="text-3xl font-bold"><?php echo number_format($pickup_stats['total_weight_collected'], 1); ?><span class="text-lg"> kg</span></p>
                    </div>
                    <div class="w-12 h-12 bg-bote-green-400 bg-opacity-50 rounded-xl flex items-center justify-center">
                        <i data-lucide="weight" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Pickup Requests -->
        <div class="bg-white rounded-2xl shadow-lg mb-8">
            <div class="px-6 py-4 border-b border-bote-stone-200 bg-gradient-to-r from-bote-green-50 to-bote-green-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-bote-stone-900 flex items-center">
                        <i data-lucide="map-pin" class="w-5 h-5 mr-2 text-bote-green-600"></i>
                        Pickup Requests for <?php echo date('F j, Y', strtotime($selected_date)); ?>
                        <?php if ($selected_date === date('Y-m-d')): ?>
                            <span class="text-sm text-bote-green-600 ml-2">(Today)</span>
                        <?php elseif ($selected_date === date('Y-m-d', strtotime('+1 day'))): ?>
                            <span class="text-sm text-bote-info-600 ml-2">(Tomorrow)</span>
                        <?php endif; ?>
                    </h2>
                    <div class="flex items-center space-x-3">
                        <button onclick="sortPickupsByTime('earliest')" 
                                id="sortEarliestBtn"
                                class="sort-btn active bg-bote-green-500 text-white px-3 py-1 rounded-lg text-sm hover:bg-bote-green-600 transition-colors">
                            Earliest First
                        </button>
                        <button onclick="sortPickupsByTime('latest')" 
                                id="sortLatestBtn"
                                class="sort-btn bg-bote-stone-200 text-bote-stone-700 px-3 py-1 rounded-lg text-sm hover:bg-bote-stone-300 transition-colors">
                            Latest First
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if ($today_pickups->num_rows > 0): ?>
                <div class="divide-y divide-bote-stone-200" id="pickupRequestsContainer">
                    <?php while ($pickup = $today_pickups->fetch_assoc()): ?>
                        <?php 
                        $status_colors = [
                            'Scheduled' => 'bg-bote-info-100 text-bote-info-800',
                            'In Progress' => 'bg-orange-100 text-orange-800',
                            'Completed' => 'bg-bote-green-100 text-bote-green-800'
                        ];
                        $status_icons = [
                            'Scheduled' => 'clock',
                            'In Progress' => 'truck',
                            'Completed' => 'check-circle'
                        ];
                        ?>
                        <div class="pickup-request-item p-6 hover:bg-bote-stone-50 transition-colors" 
                             data-preferred-time="<?php echo strtotime($pickup['preferred_date']); ?>"
                             data-status="<?php echo htmlspecialchars($pickup['status']); ?>">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-4 mb-3">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-10 h-10 bg-bote-green-100 rounded-full flex items-center justify-center">
                                                <i data-lucide="user" class="w-5 h-5 text-bote-green-600"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-semibold text-bote-stone-900">
                                                    <?php echo htmlspecialchars($pickup['first_name'] . ' ' . $pickup['last_name']); ?>
                                                </h3>
                                                <p class="text-sm text-bote-stone-600"><?php echo htmlspecialchars($pickup['address']); ?></p>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo e($status_colors[$pickup['status']]); ?>">
                                            <i data-lucide="<?php echo e($status_icons[$pickup['status']]); ?>" class="w-4 h-4 mr-1"></i>
                                            <?php echo htmlspecialchars($pickup['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
                                        <div>
                                            <span class="text-bote-stone-500">Material:</span>
                                            <span class="font-medium ml-1 text-bote-green-600"><?php echo htmlspecialchars($pickup['material_type']); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-bote-stone-500">Weight:</span>
                                            <span class="font-medium ml-1"><?php echo e($pickup['weight_kg']); ?> kg</span>
                                        </div>
                                        <div>
                                            <span class="text-bote-stone-500">Preferred Time:</span>
                                            <span class="font-medium ml-1"><?php echo date('g:i A', strtotime($pickup['preferred_date'])); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-bote-stone-500">Type:</span>
                                            <span class="font-medium ml-1"><?php echo htmlspecialchars($pickup['pickup_type'] === 'CollectionPoint' ? 'Collection Point' : 'Home Pickup'); ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($pickup['status'] === 'Completed'): ?>
                                        <div class="bg-bote-green-50 border border-bote-green-200 rounded-lg p-3 mb-3">
                                            <div class="flex items-center text-bote-green-800 text-sm">
                                                <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i>
                                                <span class="font-medium">Completed on <?php echo date('M j, g:i A', strtotime($pickup['updated_at'])); ?></span>
                                                <?php if ($pickup['actual_weight']): ?>
                                                    <span class="ml-4">Actual Weight: <?php echo e($pickup['actual_weight']); ?> kg</span>
                                                <?php endif; ?>
                                                <?php if ($pickup['points_earned']): ?>
                                                    <span class="ml-4">Points Earned: <?php echo e($pickup['points_earned']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex flex-col space-y-2 ml-4">
                                    <?php if ($pickup['status'] !== 'Completed'): ?>
                                        <button onclick="showCompletePickupModal(<?php echo (int) $pickup['request_id']; ?>, <?php echo json_encode($pickup['first_name'] . ' ' . $pickup['last_name'], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS); ?>, <?php echo json_encode($pickup['material_type'], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS); ?>, <?php echo json_encode($pickup['unit'], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS); ?>, <?php echo (float) $pickup['weight_kg']; ?>, <?php echo (int) $pickup['quantity_pcs']; ?>)"
                                                class="px-4 py-2 bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white rounded-lg text-sm font-medium transition-all duration-200 flex items-center">
                                            <i data-lucide="check" class="w-4 h-4 mr-2"></i>
                                            Complete
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="showOnMapWithLoading(this, <?php echo (float) $pickup['latitude']; ?>, <?php echo (float) $pickup['longitude']; ?>, <?php echo json_encode($pickup['first_name'] . ' ' . $pickup['last_name'], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS); ?>)"
                                            class="px-4 py-2 bg-bote-green-500 hover:bg-bote-green-600 text-white rounded-lg text-sm font-medium transition-all duration-300 flex items-center group transform hover:scale-105 hover:shadow-lg">
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
                    <div class="w-24 h-24 bg-bote-stone-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="calendar-x" class="w-12 h-12 text-bote-stone-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-bote-stone-900 mb-2">
                        No pickup requests for <?php echo date('F j, Y', strtotime($selected_date)); ?>
                    </h3>
                    <p class="text-bote-stone-500 mb-6">
                        <?php if ($selected_date === date('Y-m-d')): ?>
                            You don't have any assigned pickup requests for today
                        <?php elseif ($selected_date < date('Y-m-d')): ?>
                            No pickup requests found for this past date
                        <?php else: ?>
                            No pickup requests scheduled for this date yet
                        <?php endif; ?>
                    </p>
                    <a href="worker_assigned_pickups.php" class="inline-flex items-center px-6 py-3 bg-bote-green-600 hover:bg-bote-green-700 text-white rounded-xl font-medium transition-colors">
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
        <div class="flex items-center justify-between mb-6 sticky top-0 bg-white pb-4 border-b border-bote-stone-200">
            <h3 class="text-2xl font-bold text-bote-stone-900">Route Details</h3>
            <button onclick="hideRouteModal()" class="text-bote-stone-400 hover:text-bote-stone-600 p-2 hover:bg-bote-stone-100 rounded-full transition-colors">
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
            <h3 class="text-xl font-bold text-bote-stone-900 flex items-center">
                <i data-lucide="check-circle" class="w-6 h-6 text-bote-green-600 mr-2"></i>
                Complete Pickup
            </h3>
            <button onclick="hideCompletePickupModal()" class="text-bote-stone-400 hover:text-bote-stone-600 p-2 hover:bg-bote-stone-100 rounded-full transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <form id="completePickupForm" onsubmit="submitPickupCompletion(event)">
            <?php echo \App\Core\Csrf::field(); ?>
            <input type="hidden" id="completeRequestId" name="request_id">
            
            <div id="pickupDetails" class="mb-6 p-4 bg-bote-green-50 rounded-lg border border-bote-green-200">
                <!-- Pickup details will be populated here -->
            </div>
            
            <div class="space-y-4">
                <div id="weightSection" class="hidden">
                    <label for="actualWeight" class="block text-sm font-medium text-bote-stone-700 mb-2">
                        Actual Weight (kg) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="actualWeight" 
                           name="actual_weight" 
                           step="0.01" 
                           min="0.01"
                           class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500"
                           placeholder="Enter actual weight">
                </div>
                
                <div id="quantitySection" class="hidden">
                    <label for="actualQuantity" class="block text-sm font-medium text-bote-stone-700 mb-2">
                        Actual Quantity (pieces) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="actualQuantity" 
                           name="actual_quantity" 
                           min="1"
                           class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500"
                           placeholder="Enter actual quantity">
                </div>
                
                <div>
                    <label for="completionNotes" class="block text-sm font-medium text-bote-stone-700 mb-2">
                        Completion Notes (Optional)
                    </label>
                    <textarea id="completionNotes" 
                              name="notes" 
                              rows="3"
                              class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500"
                              placeholder="Any additional notes about the pickup..."></textarea>
                </div>
            </div>
            
            <div class="flex space-x-3 mt-6">
                <button type="button" 
                        onclick="hideCompletePickupModal()" 
                        class="flex-1 px-4 py-2 border border-bote-stone-300 text-bote-stone-700 rounded-lg hover:bg-bote-stone-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="flex-1 bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 flex items-center justify-center">
                    <i data-lucide="check" class="w-4 h-4 mr-2"></i>
                    Complete Pickup
                </button>
            </div>
        </form>
    </div>
</div>

<script type="application/json" id="collection-routes-data">
<?php echo htmlspecialchars(json_encode(['selectedDate' => $selected_date, 'workerId' => (int) $user_id], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>
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
    
    .pickup-request-item:hover .bg-bote-green-500 {
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