<style>
    .card-shadow {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    .hover-lift {
        transition: all 0.3s ease-out;
    }
    .hover-lift:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    .pulse-glow {
        animation: pulse-animation 2s infinite;
    }
    @keyframes pulse-animation {
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }
    .progress-bar {
        background: linear-gradient(90deg, #3b82f6, #10b981);
        transition: width 1s ease-in-out;
    }
    .pickup-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .pickup-card:hover {
        transform: scale(1.02);
        z-index: 10;
    }
    .next-pickup-badge {
        animation: badge-glow 2s ease-in-out infinite alternate;
    }
    @keyframes badge-glow {
        from { box-shadow: 0 0 5px 1px rgba(234, 179, 8, 0.3); }
        to { box-shadow: 0 0 10px 3px rgba(234, 179, 8, 0.6); }
    }
    .eco-icon {
        position: relative;
        overflow: hidden;
    }
    .eco-icon:after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(135deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.1) 50%, rgba(255,255,255,0) 100%);
        transform: rotate(45deg);
        animation: shine 3s infinite;
    }
    @keyframes shine {
        0% { transform: translateX(-100%) rotate(45deg); }
        100% { transform: translateX(100%) rotate(45deg); }
    }
</style>

<!-- Header Section -->
<div class="bg-white border-b border-bote-stone-200 px-6 py-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <!-- Profile Info -->
                <div class="flex items-center gap-4">
                    <?php if ($profile_display['type'] === 'photo'): ?>
                        <img src="<?php echo $basePath; ?>uploads/profiles/thumbs/<?php echo e($profile_display['value']); ?>"
                             alt="Profile Photo"
                             class="w-16 h-16 rounded-full object-cover ring-4 ring-bote-info-200">
                    <?php else: ?>
                        <div class="w-16 h-16 bg-gradient-to-br from-bote-info-400 to-bote-green-600 rounded-full flex items-center justify-center text-white text-xl font-semibold">
                            <?php echo e($profile_display['value']); ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h1 class="text-2xl font-bold text-bote-stone-900">
                            <?php echo e($user_data['first_name'] . ' ' . $user_data['last_name']); ?>
                        </h1>
                        <div class="text-sm text-bote-stone-600 space-y-1">
                            <p>📧 <?php echo e($user_data['email']); ?></p>
                            <p>📱 <?php echo e($user_data['phone']); ?></p>
                            <p class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-bote-green-500 rounded-full animate-pulse"></span>
                                <span class="text-bote-green-600 font-medium">Active Status</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Today's Performance -->
                <div class="eco-gradient rounded-2xl p-6 text-white text-center lg:text-right">
                    <p class="text-bote-info-100 text-sm font-medium">Today's Collection</p>
                    <p class="text-4xl font-bold mt-1"><?php echo number_format($stats['today_weight'], 1); ?> kg</p>
                    <p class="text-bote-info-100 text-xs mt-1">🚛 Keep up the great work!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard -->
    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Top Row - Performance Summary & Quick Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Performance Summary -->
            <div class="lg:col-span-2 bg-white rounded-xl card-shadow p-6 hover-lift">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-bote-info-100 rounded-lg flex items-center justify-center">
                        <span class="text-bote-info-600 text-xl">📊</span>
                    </div>
                    <h2 class="text-xl font-semibold text-bote-stone-900">Performance Summary</h2>
                </div>
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-4 bg-bote-info-50 rounded-lg">
                        <p class="text-2xl font-bold text-bote-info-600"><?php echo e($stats['assigned_pickups']); ?></p>
                        <p class="text-sm text-bote-stone-600">Total Assigned</p>
                    </div>
                    <div class="text-center p-4 bg-bote-green-50 rounded-lg">
                        <p class="text-2xl font-bold text-bote-green-600"><?php echo e($stats['completed_pickups']); ?></p>
                        <p class="text-sm text-bote-stone-600">Completed</p>
                    </div>
                    <div class="text-center p-4 bg-orange-50 rounded-lg">
                        <p class="text-2xl font-bold text-orange-600"><?php echo e($stats['pending_pickups']); ?></p>
                        <p class="text-sm text-bote-stone-600">Pending</p>
                    </div>
                </div>
                <div class="relative w-full h-4 bg-bote-stone-200 rounded-full">
                    <?php $completion_rate = $stats['assigned_pickups'] > 0 ? ($stats['completed_pickups'] / $stats['assigned_pickups']) * 100 : 0; ?>
                    <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-bote-info-500 to-bote-green-500 rounded-full" style="width: <?php echo e($completion_rate); ?>%"></div>
                </div>
                <p class="text-center text-sm text-bote-stone-600 mt-2"><?php echo number_format($completion_rate, 1); ?>% Completion Rate</p>
            </div>

            <!-- Quick Actions -->
            <div class="space-y-4">
                <div class="bg-gradient-to-br from-white to-bote-stone-50 rounded-xl card-shadow p-6 hover-lift border border-bote-stone-100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-bote-info-500 to-bote-green-600 rounded-lg flex items-center justify-center shadow-md">
                                <i data-lucide="zap" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-bote-stone-900">Quick Actions</h3>
                                <p class="text-sm text-bote-stone-600">Your daily tasks</p>
                            </div>
                        </div>

                        <div class="bg-bote-info-100 text-bote-info-700 px-2 py-1 rounded-md text-xs font-medium">
                            Worker ID: <?php echo e($user_id); ?>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-bote-info-50 rounded-lg border border-bote-info-100 mb-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="calendar-check" class="w-5 h-5 text-bote-info-600"></i>
                            <div>
                                <p class="text-sm font-medium text-bote-stone-800">Today's Status</p>
                                <p class="text-xs text-bote-stone-600"><?php echo date('l, F j, Y'); ?></p>
                            </div>
                        </div>
                        <div>
                            <?php if ($stats['active_routes'] > 0): ?>
                                <span class="px-2 py-1 bg-bote-green-100 text-bote-green-700 rounded-md text-xs font-medium">Route Active</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-md text-xs font-medium">No Active Route</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <a href="worker_collection_routes.php" class="flex flex-col items-center p-3 bg-gradient-to-r from-bote-info-500 to-bote-info-600 text-white rounded-lg hover:from-bote-info-600 hover:to-bote-info-700 transition-all duration-300 shadow-md hover:shadow-lg transform hover:scale-105">
                            <i data-lucide="map" class="w-6 h-6 mb-2"></i>
                            <span class="text-sm font-medium">View Routes</span>
                        </a>

                        <a href="worker_assigned_pickups.php" class="flex flex-col items-center p-3 bg-gradient-to-r from-bote-green-500 to-bote-green-600 text-white rounded-lg hover:from-bote-green-600 hover:to-bote-green-700 transition-all duration-300 shadow-md hover:shadow-lg transform hover:scale-105">
                            <i data-lucide="package-check" class="w-6 h-6 mb-2"></i>
                            <span class="text-sm font-medium">Manage Pickups</span>
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-xl card-shadow p-5 hover-lift border border-bote-stone-100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <i data-lucide="bar-chart-2" class="w-5 h-5 text-bote-green-600"></i>
                            <h3 class="text-base font-bold text-bote-stone-900">Today's Activity</h3>
                        </div>
                        <span class="bg-bote-green-100 text-bote-green-700 px-2 py-1 rounded-md text-xs font-medium">
                            <?php echo date('M j'); ?>
                        </span>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-bote-stone-600">Completed Pickups</span>
                            <span class="font-medium"><?php echo e($stats['today_weight'] > 0 ? number_format($stats['completed_pickups']) : '0'); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-bote-stone-600">Material Collected</span>
                            <span class="font-medium"><?php echo number_format($stats['today_weight'], 1); ?> kg</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-bote-stone-600">Pending Pickups</span>
                            <span class="font-medium"><?php echo e($stats['pending_pickups']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Middle Row - Assigned Pickups & Today's Route -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Assigned Pickup Requests -->
            <div class="bg-white rounded-xl card-shadow p-6 hover-lift">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="clipboard-list" class="w-5 h-5 text-orange-600"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-bote-stone-900">Assigned Pickups</h2>
                    </div>
                    <span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-sm font-medium"><?php echo e($stats['pending_pickups']); ?> Pending</span>
                </div>

                <div class="space-y-4">
                    <?php if (!empty($pending_pickups)): ?>
                        <?php foreach ($pending_pickups as $index => $pickup): ?>
                            <div class="relative bg-white rounded-xl hover:bg-bote-info-50/70 transition-all duration-300 border border-bote-stone-200 shadow-sm hover:shadow-md group overflow-hidden pickup-card">
                                <!-- Top pickup tag -->
                                <?php if ($index === 0): ?>
                                <div class="absolute top-0 left-0 bg-gradient-to-r from-yellow-500 to-amber-600 text-white px-3 py-1 text-xs font-bold rounded-br-lg shadow-sm z-10">NEXT PICKUP</div>
                                <?php endif; ?>

                                <!-- Pickup Status Indicator -->
                                <div class="absolute top-0 right-0 flex items-center gap-1 bg-yellow-100 text-yellow-800 px-3 py-1 text-xs font-medium rounded-bl-lg shadow-sm">
                                    <span class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></span>
                                    Scheduled
                                </div>

                                <div class="flex items-start p-4 pt-8">
                                    <!-- Left: Icon Column -->
                                    <div class="mr-4 relative">
                                        <div class="w-14 h-14 bg-gradient-to-br from-bote-info-100 to-bote-info-200 rounded-xl flex items-center justify-center group-hover:from-bote-info-200 group-hover:to-bote-info-300 transition-all duration-300 shadow-inner">
                                            <i data-lucide="package" class="w-7 h-7 text-bote-info-600"></i>
                                        </div>
                                        <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-bote-green-500 rounded-full flex items-center justify-center text-white text-xs font-bold border-2 border-white"><?php echo e($index + 1); ?></div>
                                    </div>

                                    <!-- Middle: Details Column -->
                                    <div class="flex-grow">
                                        <h3 class="font-semibold text-bote-stone-900 group-hover:text-bote-info-700 transition-colors">
                                            <?php echo e($pickup['first_name'] . ' ' . $pickup['last_name']); ?>
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-2">
                                            <span class="inline-flex items-center text-sm text-bote-stone-600">
                                                <i data-lucide="package-open" class="w-4 h-4 mr-1.5 text-bote-info-500"></i>
                                                <span>
                                                    <?php echo e($pickup['recyclable_type']); ?>
                                                    <span class="font-medium"><?php echo number_format($pickup['weight_kg'], 1); ?> kg</span>
                                                </span>
                                            </span>
                                            <span class="inline-flex items-center text-sm text-bote-stone-600">
                                                <i data-lucide="calendar" class="w-4 h-4 mr-1.5 text-orange-500"></i>
                                                <?php
                                                    $pickup_date = new DateTime($pickup['preferred_date']);
                                                    $now = new DateTime();
                                                    $diff = $now->diff($pickup_date);
                                                    $date_formatted = date('M j, g:i A', strtotime($pickup['preferred_date']));

                                                    if ($pickup_date < $now) {
                                                        echo '<span class="text-red-600 font-medium">' . $date_formatted . ' (Overdue)</span>';
                                                    } elseif ($diff->days == 0) {
                                                        echo '<span class="text-bote-green-600 font-medium">' . $date_formatted . ' (Today)</span>';
                                                    } else {
                                                        echo $date_formatted;
                                                    }
                                                ?>
                                            </span>
                                            <?php if (!empty($pickup['phone'])): ?>
                                            <span class="inline-flex items-center text-sm text-bote-stone-600">
                                                <i data-lucide="phone" class="w-4 h-4 mr-1.5 text-bote-green-500"></i>
                                                <?php echo e($pickup['phone']); ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Right: Action Button -->
                                    <div class="ml-2">
                                        <a href="complete_pickup.php?id=<?php echo e($pickup['request_id']); ?>"
                                           class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white text-sm font-medium rounded-lg transition-all shadow-sm hover:shadow group-hover:scale-105">
                                            <i data-lucide="check-circle" class="w-4 h-4 mr-1.5"></i>
                                            Complete Pickup
                                        </a>
                                    </div>
                                </div>

                                <!-- Bottom quick action bar -->
                                <div class="flex items-center justify-between border-t border-bote-stone-200 bg-bote-stone-50 px-4 py-2 text-sm">
                                    <a href="tel:<?php echo e($pickup['phone']); ?>" class="inline-flex items-center text-bote-info-600 hover:text-bote-info-800">
                                        <i data-lucide="phone-call" class="w-4 h-4 mr-1.5"></i>
                                        Call Resident
                                    </a>
                                    <a href="https://maps.google.com/?q=<?php echo e($pickup['latitude']); ?>,<?php echo e($pickup['longitude']); ?>" target="_blank" class="inline-flex items-center text-bote-info-600 hover:text-bote-info-800">
                                        <i data-lucide="map-pin" class="w-4 h-4 mr-1.5"></i>
                                        View Location
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (count($pending_pickups) > 2): ?>
                            <div class="text-center bg-bote-info-50 rounded-lg py-3 border border-bote-info-100">
                                <span class="text-bote-info-700 font-medium"><?php echo count($pending_pickups) - 2; ?> more pickups scheduled</span>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-10 bg-bote-stone-50/70 rounded-xl border border-bote-stone-100">
                            <div class="w-20 h-20 bg-gradient-to-br from-bote-info-50 to-bote-green-50 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-inner border border-bote-stone-100">
                                <i data-lucide="package-check" class="w-10 h-10 text-bote-green-400"></i>
                            </div>
                            <div class="bg-white w-max mx-auto px-5 py-2 rounded-full shadow-sm border border-bote-stone-100 mb-3">
                                <span class="text-bote-green-600 font-medium">All caught up for today!</span>
                            </div>
                            <p class="text-bote-stone-600 mb-5">You have no pending pickups at the moment</p>
                            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                                <a href="worker_assigned_pickups.php" class="inline-flex items-center justify-center px-5 py-2.5 bg-bote-info-100 text-bote-info-700 hover:bg-bote-info-200 rounded-lg text-sm font-medium transition-all hover:scale-105">
                                    <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>
                                    Check for new pickups
                                </a>
                                <a href="generate_route.php" class="inline-flex items-center justify-center px-5 py-2.5 bg-bote-green-100 text-bote-green-700 hover:bg-bote-green-200 rounded-lg text-sm font-medium transition-all hover:scale-105">
                                    <i data-lucide="map" class="w-4 h-4 mr-2"></i>
                                    Generate collection route
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <a href="worker_assigned_pickups.php" class="block w-full mt-5 py-3 bg-bote-info-50 hover:bg-bote-info-100 border border-bote-info-200 rounded-lg text-bote-info-700 hover:text-bote-info-800 text-sm font-medium text-center transition-all duration-200 hover:shadow-md">
                    <span class="inline-flex items-center">
                        <i data-lucide="list" class="w-4 h-4 mr-2"></i>
                        View All Assigned Pickups
                        <i data-lucide="chevron-right" class="w-4 h-4 ml-2"></i>
                    </span>
                </a>
            </div>

            <!-- Today's Route Overview -->
            <div class="bg-white rounded-xl card-shadow p-6 hover-lift">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-bote-green-100 rounded-lg flex items-center justify-center">
                        <i data-lucide="map" class="w-5 h-5 text-bote-green-600"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-bote-stone-900">Today's Route</h2>
                </div>

                <?php if ($today_route && !empty($route_stops)): ?>
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <div class="text-sm text-bote-stone-600">
                                <i data-lucide="map-pin" class="inline w-4 h-4 mr-1 text-bote-info-500"></i>
                                <?php echo count($route_stops); ?> stops
                            </div>
                            <div class="text-sm text-bote-stone-600">
                                <i data-lucide="clock" class="inline w-4 h-4 mr-1 text-bote-green-500"></i>
                                Estimated: <?php echo e($stats['route_time'] ?? 'N/A'); ?>
                            </div>
                            <div class="text-sm text-bote-stone-600">
                                <i data-lucide="navigation" class="inline w-4 h-4 mr-1 text-orange-500"></i>
                                <?php echo e(isset($stats['route_distance']) ? number_format($stats['route_distance'], 1) : '0'); ?> km
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <?php
                        // Display up to 3 stops from the route
                        $counter = 0;
                        $displayed_stops = min(count($route_stops), 3);
                        foreach (array_slice($route_stops, 0, $displayed_stops) as $index => $stop):
                            // Determine if it's a collection point or a pickup request
                            $is_collection_point = !empty($stop['point_id']);
                            $completed = isset($stop['request_status']) && $stop['request_status'] === 'Completed';

                            if ($is_collection_point) {
                                $name = $stop['point_name'] ?? 'Collection Point';
                                $details = 'Drop-off Location';
                                $bg_class = 'bg-bote-green-50';
                                $border_class = 'border-bote-green-500';
                                $number_bg = 'bg-bote-green-500';
                            } else {
                                $name = $stop['first_name'] . ' ' . $stop['last_name'];
                                $details = 'Pickup Request • ' . number_format($stop['weight_kg'], 1) . ' kg';
                                if ($completed) {
                                    $bg_class = 'bg-bote-green-50';
                                    $border_class = 'border-bote-green-500';
                                    $number_bg = 'bg-bote-green-500';
                                } else {
                                    $bg_class = 'bg-bote-info-50';
                                    $border_class = 'border-bote-info-500';
                                    $number_bg = 'bg-bote-info-500';
                                }
                            }
                            $counter++;
                        ?>
                            <div class="flex items-center p-3 <?php echo e($bg_class); ?> rounded-lg border-l-4 <?php echo e($border_class); ?>">
                                <div class="<?php echo e($number_bg); ?> text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">
                                    <?php echo e($counter); ?>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-bote-stone-800"><?php echo e($name); ?></div>
                                    <div class="text-sm text-bote-stone-600"><?php echo e($details); ?></div>
                                </div>
                                <div class="<?php echo e($completed ? 'text-bote-green-500' : 'text-bote-stone-400'); ?>">
                                    <?php echo $completed ? '<i data-lucide="check-circle" class="w-5 h-5"></i>' : '<i data-lucide="circle" class="w-5 h-5"></i>'; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (count($route_stops) > 3): ?>
                            <div class="text-center text-bote-stone-500 text-sm py-2">
                                + <?php echo count($route_stops) - 3; ?> more stops
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <a href="worker_collection_routes.php" class="flex-1 text-center bg-bote-green-600 text-white py-2 px-4 rounded-lg hover:bg-bote-green-700 transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="map" class="w-5 h-5"></i>
                            <span>View Route</span>
                        </a>
                        <a href="get_route_coordinates.php?route_id=<?php echo e($today_route['route_id']); ?>" target="_blank" class="flex-1 text-center bg-bote-green-600 text-white py-2 px-4 rounded-lg hover:bg-bote-green-700 transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="navigation" class="w-5 h-5"></i>
                            <span>Navigate</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-bote-stone-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="map-off" class="w-8 h-8 text-bote-stone-400"></i>
                        </div>
                        <p class="text-bote-stone-500 mb-4">No route generated for today</p>
                        <a href="worker_collection_routes.php" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-bote-info-500 to-bote-info-600 text-white rounded-xl hover:from-bote-info-600 hover:to-bote-info-700 transition-all font-semibold shadow-lg">
                            <i data-lucide="map" class="w-4 h-4"></i>
                            Generate Route
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bottom Row - Notifications & Impact -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Notifications -->
            <div class="bg-white rounded-xl card-shadow p-6 hover-lift">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i data-lucide="bell" class="w-5 h-5 text-yellow-600"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-bote-stone-900">Recent Notifications</h2>
                </div>
                <div class="space-y-4">
                    <?php if (!empty($recent_notifications)): ?>
                        <?php foreach ($recent_notifications as $index => $notification):
                            // Determine notification type and message based on status
                            if ($notification['status'] === 'Scheduled') {
                                $bg_class = 'bg-bote-info-50';
                                $dot_class = 'bg-bote-info-500';
                                $message = "New pickup scheduled from " . $notification['first_name'] . ' ' . $notification['last_name'] . ". " .
                                          $notification['recyclable_type'] . " (" . number_format($notification['weight_kg'], 1) . " kg)";
                            } elseif ($notification['status'] === 'Completed') {
                                $bg_class = 'bg-bote-green-50';
                                $dot_class = 'bg-bote-green-500';
                                $message = "Pickup completed for " . $notification['first_name'] . ' ' . $notification['last_name'] . ". " .
                                          $notification['recyclable_type'] . " (" . number_format($notification['weight_kg'], 1) . " kg)";
                            } else {
                                $bg_class = 'bg-yellow-50';
                                $dot_class = 'bg-yellow-500';
                                $message = "Status update for request #" . $notification['request_id'] . ": " . $notification['status'];
                            }

                            // Calculate time difference
                            $created_time = strtotime($notification['created_at']);
                            $now = time();
                            $diff = $now - $created_time;

                            if ($diff < 3600) {
                                $time_ago = floor($diff / 60) . " minutes ago";
                            } elseif ($diff < 86400) {
                                $time_ago = floor($diff / 3600) . " hours ago";
                            } else {
                                $time_ago = floor($diff / 86400) . " days ago";
                            }
                        ?>
                            <div class="flex gap-3 p-3 <?php echo e($bg_class); ?> rounded-lg">
                                <div class="w-2 h-2 <?php echo e($dot_class); ?> rounded-full mt-2 flex-shrink-0"></div>
                                <div>
                                    <p class="text-sm text-bote-stone-900"><?php echo e($message); ?></p>
                                    <p class="text-xs text-bote-stone-500 mt-1"><?php echo e($time_ago); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-bote-stone-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="bell" class="w-8 h-8 text-bote-stone-400"></i>
                            </div>
                            <p class="text-bote-stone-500">No notifications yet</p>
                        </div>
                    <?php endif; ?>
                </div>
                <a href="worker_dashboard.php" class="block w-full mt-4 text-bote-info-600 hover:text-bote-info-700 text-sm font-medium text-center" onclick="showNotificationsComingSoon(event)">
                    View All Notifications
                </a>
            </div>

            <!-- Your Impact -->
            <div class="bg-white rounded-xl card-shadow p-6 hover-lift">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-bote-green-100 rounded-lg flex items-center justify-center">
                        <i data-lucide="award" class="w-5 h-5 text-bote-green-600"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-bote-stone-900">Your Impact</h2>
                </div>

                <div class="space-y-4">
                    <div class="text-center p-5 bg-gradient-to-r from-bote-info-500 to-bote-green-500 rounded-xl text-white shadow-lg">
                        <div class="flex items-center justify-center gap-3 mb-1">
                            <i data-lucide="recycle" class="w-6 h-6 text-white opacity-75"></i>
                            <div class="text-3xl font-bold"><?php echo number_format($total_weight, 1); ?> kg</div>
                        </div>
                        <div class="text-sm opacity-90">Total Material Collected</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-4 bg-gradient-to-br from-bote-info-50 to-bote-info-100 rounded-lg border border-bote-info-200">
                            <div class="w-10 h-10 bg-bote-green-500 rounded-full flex items-center justify-center mx-auto mb-2 text-white">
                                <i data-lucide="droplets" class="w-5 h-5"></i>
                            </div>
                            <div class="text-lg font-bold text-bote-info-700"><?php echo number_format($plastic_saved, 1); ?></div>
                            <div class="text-xs text-bote-info-800">Plastic Saved (kg)</div>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-bote-green-50 to-bote-green-100 rounded-lg border border-bote-green-200">
                            <div class="w-10 h-10 bg-bote-green-500 rounded-full flex items-center justify-center mx-auto mb-2 text-white">
                                <i data-lucide="cloud" class="w-5 h-5"></i>
                            </div>
                            <div class="text-lg font-bold text-bote-green-700"><?php echo number_format($co2_reduced, 1); ?></div>
                            <div class="text-xs text-bote-green-800">CO₂ Reduced (kg)</div>
                        </div>
                    </div>

                    <div class="p-5 bg-gradient-to-br from-bote-green-50 via-bote-green-50 to-bote-green-100 rounded-xl border border-bote-green-200 shadow-sm relative overflow-hidden">
                        <div class="absolute right-0 bottom-0 opacity-10">
                            <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" class="text-bote-green-800">
                                <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path>
                                <path d="M2 12h20"></path>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                            </svg>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-lg flex items-center justify-center shadow-md">
                                    <i data-lucide="tree" class="w-5 h-5 text-white"></i>
                                </div>
                                <span class="text-base font-semibold text-bote-green-800">Environmental Impact</span>
                            </div>
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <div class="text-2xl font-bold text-bote-green-700"><?php echo number_format($trees_equivalent, 1); ?> <span class="text-xl">trees</span></div>
                                    <p class="text-sm text-bote-green-800">Annual CO₂ absorption equivalent</p>
                                </div>
                                <div class="w-16 h-16 bg-white bg-opacity-50 rounded-full flex items-center justify-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-bote-green-400 to-bote-green-600 rounded-full flex items-center justify-center shadow-lg">
                                        <span class="text-lg font-bold text-white">+<?php echo number_format($stats['completed_pickups']); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full bg-white bg-opacity-50 rounded-full h-3 mt-3">
                                <div class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 h-3 rounded-full shadow-inner"
                                    style="width: <?php echo e(min(100, max(15, $trees_equivalent * 2))); ?>%"></div>
                            </div>
                            <p class="text-xs text-bote-green-800 text-center mt-2 font-medium">
                                <?php echo number_format($stats['completed_pickups']); ?> completed pickup<?php echo e($stats['completed_pickups'] != 1 ? 's' : ''); ?>
                                = <?php echo number_format($total_weight, 1); ?> kg material saved from landfill
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-center gap-2 text-sm text-bote-stone-600 bg-bote-stone-50 py-3 px-4 rounded-lg border border-bote-stone-100">
                        <i data-lucide="info" class="w-4 h-4 text-bote-info-500"></i>
                        <span>Keep recycling! Every 100kg saves approximately <?php echo number_format(100 * 2.5 / 21, 1); ?> trees worth of CO₂</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
