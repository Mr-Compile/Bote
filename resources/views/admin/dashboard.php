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
                <div class="stat-card bg-white rounded-xl p-6 card-shadow card-hover border-l-4 border-bote-green-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-green-400 to-bote-green-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="users" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo number_format($dashboard_stats['total_residents']); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="home" class="w-3 h-3 mr-1"></i>
                                    Total Residents
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Workers -->
                <div class="stat-card bg-white rounded-xl p-6 card-shadow card-hover border-l-4 border-bote-info-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-info-400 to-bote-info-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="truck" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo number_format($dashboard_stats['total_workers']); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="hard-hat" class="w-3 h-3 mr-1"></i>
                                    Active Workers
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Pickups -->
                <div class="stat-card bg-white rounded-xl p-6 card-shadow card-hover border-l-4 border-bote-green-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-green-400 to-bote-green-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="package" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo number_format($dashboard_stats['total_pickups']); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
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
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo number_format($dashboard_stats['points_distributed']); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="award" class="w-3 h-3 mr-1"></i>
                                    Points Earned
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Residents -->
                <div class="stat-card bg-white rounded-xl p-6 card-shadow card-hover border-l-4 border-bote-green-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-green-400 to-bote-green-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="user-plus" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo number_format($dashboard_stats['new_residents_month']); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="calendar-plus" class="w-3 h-3 mr-1"></i>
                                    New This Month
                                    <?php if ($dashboard_stats['new_residents_month'] > 0): ?>
                                        <span class="ml-2 px-2 py-1 bg-bote-green-100 text-bote-green-800 text-xs rounded-full pulse-animation">New</span>
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
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo number_format($dashboard_stats['available_rewards']); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
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
                        <h2 class="text-xl font-bold text-bote-stone-900 flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg flex items-center justify-center mr-3">
                                <i data-lucide="trophy" class="w-5 h-5 text-white"></i>
                            </div>
                            Eco-Leaderboard
                        </h2>
                        <div class="flex space-x-2">
                            <button id="btn-residents"
                                class="px-4 py-2 bg-gradient-to-r from-bote-green-500 to-bote-green-600 text-white rounded-xl text-sm font-medium transition-all duration-200 flex items-center">
                                <i data-lucide="users" class="w-4 h-4 mr-2"></i>
                                Residents
                            </button>
                            <button id="btn-puroks"
                                class="px-4 py-2 bg-bote-stone-100 text-bote-stone-600 hover:bg-bote-stone-200 rounded-xl text-sm font-medium transition-all duration-200 flex items-center">
                                <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i>
                                Puroks
                            </button>
                        </div>
                    </div>

                    <!-- Residents Leaderboard -->
                    <div id="leaderboard-residents" class="space-y-4">
                        <?php if (empty($top_residents)): ?>
                            <div class="text-center py-12">
                                <div class="w-16 h-16 bg-bote-stone-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i data-lucide="users" class="w-8 h-8 text-bote-stone-400"></i>
                                </div>
                                <h3 class="text-lg font-medium text-bote-stone-900 mb-2">No Resident Data</h3>
                                <p class="text-bote-stone-500">No resident activity data available yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($top_residents as $index => $resident):
                                $position_icons = ['🥇', '🥈', '🥉', '4', '5'];
                                $position_colors = [
                                    'bg-gradient-to-r from-yellow-50 to-yellow-100 border-yellow-300 shadow-yellow-100',
                                    'bg-gradient-to-r from-bote-stone-50 to-bote-stone-100 border-bote-stone-300 shadow-bote-stone-100',
                                    'bg-gradient-to-r from-orange-50 to-orange-100 border-orange-300 shadow-orange-100',
                                    'bg-gradient-to-r from-bote-info-50 to-bote-info-100 border-bote-info-300 shadow-bote-info-100',
                                    'bg-gradient-to-r from-bote-green-50 to-bote-green-100 border-bote-green-300 shadow-purple-100'
                                ];
                                $text_colors = ['text-yellow-700', 'text-bote-stone-700', 'text-orange-700', 'text-bote-info-700', 'text-bote-green-700'];
                            ?>
                                <div class="leaderboard-card flex items-center justify-between p-6 <?php echo e($position_colors[$index]); ?> rounded-xl border shadow-lg">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-md mr-4">
                                            <span class="text-2xl"><?php echo e($position_icons[$index]); ?></span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-bote-stone-900 text-lg">
                                                <?php echo e($resident['first_name'] . ' ' . $resident['last_name']); ?>
                                            </p>
                                            <p class="text-sm <?php echo e($text_colors[$index]); ?> flex items-center">
                                                <i data-lucide="map-pin" class="w-3 h-3 mr-1"></i>
                                                <?php echo e($resident['address'] ?: 'Address not set'); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-2xl font-bold text-bote-green-600">
                                            <?php echo number_format($resident['total_points']); ?>
                                        </span>
                                        <p class="text-sm text-bote-stone-600">points</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Puroks Leaderboard -->
                    <div id="leaderboard-puroks" class="space-y-4 hidden">
                        <?php if (empty($top_puroks)): ?>
                            <div class="text-center py-12">
                                <div class="w-16 h-16 bg-bote-stone-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i data-lucide="map-pin" class="w-8 h-8 text-bote-stone-400"></i>
                                </div>
                                <h3 class="text-lg font-medium text-bote-stone-900 mb-2">No Purok Data</h3>
                                <p class="text-bote-stone-500">No purok activity data available yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($top_puroks as $index => $purok):
                                $position_icons = ['🥇', '🥈', '🥉', '4', '5'];
                                $position_colors = [
                                    'bg-gradient-to-r from-yellow-50 to-yellow-100 border-yellow-300',
                                    'bg-gradient-to-r from-bote-stone-50 to-bote-stone-100 border-bote-stone-300',
                                    'bg-gradient-to-r from-orange-50 to-orange-100 border-orange-300',
                                    'bg-gradient-to-r from-bote-info-50 to-bote-info-100 border-bote-info-300',
                                    'bg-gradient-to-r from-bote-green-50 to-bote-green-100 border-bote-green-300'
                                ];
                            ?>
                                <div class="leaderboard-card flex items-center justify-between p-6 <?php echo e($position_colors[$index]); ?> rounded-xl border shadow-lg">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-md mr-4">
                                            <span class="text-2xl"><?php echo e($position_icons[$index]); ?></span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-bote-stone-900 text-lg">
                                                <?php echo e($purok['purok_name']); ?>
                                            </p>
                                            <p class="text-sm text-bote-stone-600 flex items-center">
                                                <i data-lucide="users" class="w-3 h-3 mr-1"></i>
                                                <?php echo e($purok['resident_count']); ?> residents
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-2xl font-bold text-bote-green-600">
                                            <?php echo number_format($purok['total_points']); ?>
                                        </span>
                                        <p class="text-sm text-bote-stone-600">points</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Enhanced Pickup Requests Overview -->
                <div class="bg-white rounded-2xl p-6 card-shadow card-hover">
                    <h2 class="text-xl font-bold text-bote-stone-900 mb-6 flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-br from-bote-info-400 to-bote-info-600 rounded-lg flex items-center justify-center mr-3">
                            <i data-lucide="bar-chart-3" class="w-5 h-5 text-white"></i>
                        </div>
                        Pickup Requests Overview
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="text-center p-6 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl border border-yellow-200 shadow-lg card-hover">
                            <div class="w-12 h-12 bg-yellow-500 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="clock" class="w-6 h-6 text-white"></i>
                            </div>
                            <p class="text-3xl font-bold text-yellow-600 mb-1"><?php echo e($pickup_overview['pending']); ?></p>
                            <p class="text-sm font-medium text-yellow-700">Pending Requests</p>
                        </div>
                        <div class="text-center p-6 bg-gradient-to-br from-bote-info-50 to-bote-info-100 rounded-xl border border-bote-info-200 shadow-lg card-hover">
                            <div class="w-12 h-12 bg-bote-green-500 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="calendar-check" class="w-6 h-6 text-white"></i>
                            </div>
                            <p class="text-3xl font-bold text-bote-info-600 mb-1"><?php echo e($pickup_overview['scheduled']); ?></p>
                            <p class="text-sm font-medium text-bote-info-700">Scheduled</p>
                        </div>
                        <div class="text-center p-6 bg-gradient-to-br from-bote-green-50 to-bote-green-100 rounded-xl border border-bote-green-200 shadow-lg card-hover">
                            <div class="w-12 h-12 bg-bote-green-500 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                            </div>
                            <p class="text-3xl font-bold text-bote-green-600 mb-1"><?php echo e($pickup_overview['completed_today']); ?></p>
                            <p class="text-sm font-medium text-bote-green-700">Completed Today</p>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="pickupChart"></canvas>
                    </div>
                </div>

                <!-- Enhanced Analytics -->
                <div class="bg-white rounded-2xl p-6 card-shadow card-hover">
                    <h2 class="text-xl font-bold text-bote-stone-900 mb-6 flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-br from-bote-green-400 to-bote-green-600 rounded-lg flex items-center justify-center mr-3">
                            <i data-lucide="trending-up" class="w-5 h-5 text-white"></i>
                        </div>
                        Points Analytics
                        <span class="ml-auto text-sm font-normal text-bote-stone-500">Last 6 months</span>
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
                    <h2 class="text-xl font-bold text-bote-stone-900 mb-6 flex items-center">
                        <span class="text-orange-600 mr-2">🏆</span>
                        Reward Management
                    </h2>
                    <div class="space-y-4">
                        <?php if (empty($rewards_status)): ?>
                            <div class="text-center py-4 text-bote-stone-500">
                                <p>No rewards configured yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($rewards_status as $reward):
                                $status_colors = [
                                    'In Stock' => 'bg-bote-green-100 text-bote-green-800',
                                    'Low Stock' => 'bg-yellow-100 text-yellow-800',
                                    'Out of Stock' => 'bg-red-100 text-red-800'
                                ];
                            ?>
                                <div class="border border-bote-stone-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="font-semibold text-bote-stone-900"><?php echo e($reward['name']); ?></h3>
                                        <span class="<?php echo e($status_colors[$reward['stock_status']]); ?> text-xs px-2 py-1 rounded-full">
                                            <?php echo e($reward['stock_status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-bote-stone-600 mb-2"><?php echo number_format($reward['points_required']); ?> points required</p>
                                    <p class="text-xs text-bote-stone-500">Stock: <?php echo e($reward['stock']); ?> units</p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4 pt-4 border-t border-bote-stone-200">
                        <h4 class="font-medium text-bote-stone-900 mb-2">Recent Redemptions</h4>
                        <div class="text-sm text-bote-stone-600 space-y-1">
                            <?php if (empty($recent_redemptions)): ?>
                                <p class="text-bote-stone-500">No recent redemptions</p>
                            <?php else: ?>
                                <?php foreach ($recent_redemptions as $redemption): ?>
                                    <p>🎉 <?php echo e($redemption['first_name'] . ' ' . substr($redemption['last_name'], 0, 1) . '.'); ?> redeemed <?php echo e($redemption['reward_name']); ?></p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Collection Routes & Stops -->
                <div class="bg-white rounded-xl p-6 card-shadow">
                    <h2 class="text-xl font-bold text-bote-stone-900 mb-6 flex items-center">
                        <span class="text-bote-info-600 mr-2">🚛</span>
                        Collection Routes
                    </h2>
                    <div class="space-y-4">
                        <?php if (empty($collection_routes)): ?>
                            <div class="text-center py-4 text-bote-stone-500">
                                <p>No collection routes for today</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($collection_routes as $route):
                                $completion_rate = $route['total_stops'] > 0 ? ($route['completed_stops'] / $route['total_stops']) * 100 : 0;
                                $status = $completion_rate == 100 ? 'Completed' : ($completion_rate > 0 ? 'Active' : 'Scheduled');
                                $status_colors = [
                                    'Active' => 'bg-bote-green-100 text-bote-green-800',
                                    'Scheduled' => 'bg-bote-info-100 text-bote-info-800',
                                    'Completed' => 'bg-bote-stone-100 text-bote-stone-800'
                                ];
                            ?>
                                <div class="border border-bote-stone-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="font-semibold text-bote-stone-900">Route #<?php echo e($route['route_id']); ?></h3>
                                        <span class="<?php echo e($status_colors[$status]); ?> text-xs px-2 py-1 rounded-full"><?php echo e($status); ?></span>
                                    </div>
                                    <p class="text-sm text-bote-stone-600 mb-1">Worker: <?php echo e($route['worker_name']); ?></p>
                                    <p class="text-xs text-bote-stone-500">
                                        <?php if ($status == 'Completed'): ?>
                                            All stops completed | <?php echo e($route['completed_stops']); ?> pickups
                                        <?php elseif ($status == 'Active'): ?>
                                            Pending stops: <?php echo e($route['total_stops'] - $route['completed_stops']); ?> | Completed: <?php echo e($route['completed_stops']); ?>
                                        <?php else: ?>
                                            Pending stops: <?php echo e($route['total_stops']); ?> | Starts: <?php echo date('g:i A', strtotime($route['generated_on'])); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications Log -->
                <div class="bg-white rounded-xl p-6 card-shadow">
                    <h2 class="text-xl font-bold text-bote-stone-900 mb-6 flex items-center">
                        <span class="text-red-600 mr-2">🔔</span>
                        Recent Notifications
                    </h2>
                    <div class="space-y-3">
                        <?php if (empty($recent_notifications)): ?>
                            <div class="text-center py-4 text-bote-stone-500">
                                <p>No recent notifications</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_notifications as $notification):
                                $event_icons = [
                                    'Pickup Reminder' => '⏰',
                                    'Collection Confirmation' => '✅',
                                    'Points Update' => '⭐',
                                    'Leaderboard Update' => '🏆',
                                    'Reward Redemption' => '🎁'
                                ];
                                $event_colors = [
                                    'Pickup Reminder' => 'bg-bote-info-50 border-bote-info-200',
                                    'Collection Confirmation' => 'bg-bote-green-50 border-bote-green-200',
                                    'Points Update' => 'bg-yellow-50 border-yellow-200',
                                    'Leaderboard Update' => 'bg-bote-green-50 border-bote-green-200',
                                    'Reward Redemption' => 'bg-bote-green-50 border-bote-green-200'
                                ];
                                $icon = $event_icons[$notification['trigger_event']] ?? '🔔';
                                $color = $event_colors[$notification['trigger_event']] ?? 'bg-bote-stone-50 border-bote-stone-200';
                                $text_color = str_replace(['50', '200'], ['600', '600'], $color);
                                $text_color = str_replace(['bg-', 'border-'], '', explode(' ', $text_color)[0]);
                            ?>
                                <div class="flex items-start space-x-3 p-3 <?php echo e($color); ?> rounded-lg border">
                                    <span class="text-<?php echo e($text_color); ?> text-sm"><?php echo e($icon); ?></span>
                                    <div class="flex-1">
                                        <p class="text-sm text-bote-stone-900"><?php echo e(substr($notification['message'], 0, 60) . (strlen($notification['message']) > 60 ? '...' : '')); ?></p>
                                        <p class="text-xs text-bote-stone-500">
                                            Sent <?php echo date('M j, g:i A', strtotime($notification['sent_at'])); ?> to <?php echo e($notification['first_name'] . ' ' . $notification['last_name']); ?>
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

<script type="application/json" id="admin-dashboard-data">
    <?php echo $json_dashboard_data; ?>
</script>
