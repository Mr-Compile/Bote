    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="assets/css/my_request.css">

    <script type="application/json" id="my-requests-data">
        <?php echo $json_my_requests_data; ?>
    </script>
<!-- Main Content -->
<div class="p-8 bg-gradient-to-br from-bote-stone-50 via-white to-bote-stone-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Enhanced Header -->
        <div class="bg-gradient-to-r from-white to-bote-stone-50 rounded-3xl shadow-xl p-8 mb-10 relative overflow-hidden">
            <div class="shimmer absolute inset-0"></div>
            <div class="relative z-10 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="floating-animation">
                        <div class="w-16 h-16 bg-gradient-to-r from-bote-green-500 to-bote-green-600 rounded-2xl flex items-center justify-center mr-6 shadow-lg">
                            <i data-lucide="list-checks" class="w-8 h-8 text-white"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold bg-gradient-to-r from-bote-stone-900 to-bote-stone-700 bg-clip-text text-transparent mb-2">
                            My Pickup Requests
                        </h1>
                        <p class="text-xl text-bote-stone-600">Track and manage all your recyclable pickup requests</p>
                        <div class="mt-3 flex items-center space-x-6 text-sm text-bote-stone-500">
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
                    <div class="bg-white rounded-2xl p-4 shadow-lg border border-bote-stone-100">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-bote-green-600"><?php echo e($requests->num_rows); ?></p>
                            <p class="text-sm text-bote-stone-500">Total Requests</p>
                        </div>
                    </div>
                    <a href="resident_new_request.php" class="bg-gradient-to-r from-bote-green-500 via-bote-green-600 to-bote-green-600 hover:from-bote-green-600 hover:via-bote-green-700 hover:to-bote-green-700 text-white px-8 py-4 rounded-2xl font-semibold transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:scale-105 flex items-center space-x-3">
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
                        <p class="text-3xl font-bold"><?php echo e($pending_count); ?></p>
                        <p class="text-yellow-100 text-lg">Pending</p>
                    </div>
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i data-lucide="clock" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-bote-green-500 via-bote-green-600 to-bote-green-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold"><?php echo e($completed_count); ?></p>
                        <p class="text-bote-green-100 text-lg">Completed</p>
                    </div>
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-red-500 via-red-600 to-red-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold"><?php echo e($cancelled_count); ?></p>
                        <p class="text-red-100 text-lg">Cancelled</p>
                    </div>
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i data-lucide="x-circle" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-bote-info-500 via-bote-info-600 to-bote-info-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold"><?php echo number_format($total_points); ?></p>
                        <p class="text-bote-info-100 text-lg">Total Points</p>
                    </div>
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i data-lucide="coins" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Requests List -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-bote-stone-50 to-white px-8 py-6 border-b border-bote-stone-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-bote-info-500 to-bote-info-600 rounded-xl flex items-center justify-center mr-4">
                            <i data-lucide="history" class="w-5 h-5 text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-bote-stone-900">Request History</h2>
                            <p class="text-bote-stone-600 mt-1">View and manage your pickup requests</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <select class="px-4 py-2 border border-bote-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Scheduled">Scheduled</option>
                            <option value="Completed">Completed</option>
                        </select>
                        <button class="p-2 bg-bote-stone-100 hover:bg-bote-stone-200 rounded-xl transition-colors" onclick="refreshRequests()">
                            <i data-lucide="refresh-cw" class="w-5 h-5 text-bote-stone-600"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if ($requests->num_rows > 0): ?>
                <div class="divide-y divide-bote-stone-100" id="requestsList">
                    <?php $card_index = 0; ?>
                    <?php foreach ($requests_data as $request): ?>
                        <div class="request-card p-8 hover:bg-gradient-to-r hover:from-bote-stone-50 hover:to-white transition-all duration-300 card-enter" 
                             style="animation-delay: <?php echo e($card_index * 0.1); ?>s"
                             data-status="<?php echo htmlspecialchars($request['status']); ?>"
                             data-request-id="<?php echo e($request['request_id']); ?>">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <!-- Status and ID -->
                                    <div class="flex items-center space-x-4 mb-4">
                                        <div class="flex items-center space-x-3">
                                            <?php
                                            $status_colors = [
                                                'Pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                                'Scheduled' => 'bg-bote-info-100 text-bote-info-800 border-bote-info-200',
                                                'In Progress' => 'bg-bote-green-100 text-bote-green-800 border-bote-green-200',
                                                'Completed' => 'bg-bote-green-100 text-bote-green-800 border-bote-green-200'
                                            ];
                                            $status_icons = [
                                                'Pending' => 'clock',
                                                'Scheduled' => 'calendar',
                                                'In Progress' => 'truck',
                                                'Completed' => 'check-circle'
                                            ];
                                            ?>
                                            <div class="px-4 py-2 rounded-xl font-semibold text-sm border-2 flex items-center space-x-2 <?php echo e($status_colors[$request['status']] ?? 'bg-bote-stone-100 text-bote-stone-800 border-bote-stone-200'); ?>">
                                                <i data-lucide="<?php echo e($status_icons[$request['status']] ?? 'circle'); ?>" class="w-4 h-4"></i>
                                                <span><?php echo htmlspecialchars($request['status']); ?></span>
                                            </div>
                                            <div class="bg-bote-stone-100 px-3 py-1 rounded-full text-sm font-medium text-bote-stone-600">
                                                ID: #<?php echo e(str_pad($request['request_id'], 4, '0', STR_PAD_LEFT)); ?>
                                            </div>
                                        </div>
                                        <div class="text-xs text-bote-stone-500">
                                            Created <?php echo date('M j, Y \a\t g:i A', strtotime($request['created_at'])); ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Main Content -->
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                        <!-- Material Info -->
                                        <div class="lg:col-span-2">
                                            <div class="flex items-center mb-4">
                                                <div class="w-12 h-12 bg-gradient-to-br from-bote-green-400 to-bote-green-600 rounded-xl flex items-center justify-center mr-4 overflow-hidden">
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
                                                    <h3 class="text-xl font-bold text-bote-stone-900">
                                                        <?php echo htmlspecialchars($request['recyclable_type']); ?>
                                                    </h3>
                                                    <p class="text-bote-stone-600">Recyclable Material</p>
                                                </div>
                                            </div>
                                            
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="bg-bote-stone-50 rounded-xl p-4">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-bote-stone-600 text-sm font-medium">Amount</span>
                                                        <i data-lucide="package" class="w-4 h-4 text-bote-stone-400"></i>
                                                    </div>
                                                    <p class="text-2xl font-bold text-bote-stone-900">
                                                        <?php 
                                                        if ($request['unit'] === 'kg') {
                                                            echo number_format($request['weight_kg'], 1) . ' kg';
                                                        } else {
                                                            echo ($request['quantity_pcs'] ?? 'N/A') . ' pcs';
                                                        }
                                                        ?>
                                                    </p>
                                                    <p class="text-bote-stone-500 text-sm">
                                                        <?php echo htmlspecialchars($request['unit'] === 'kg' ? 'Weight collected' : 'Quantity collected'); ?>
                                                    </p>
                                                </div>
                                                
                                                <div class="bg-bote-green-50 rounded-xl p-4">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-bote-green-700 text-sm font-medium">Points Earned</span>
                                                        <i data-lucide="coins" class="w-4 h-4 text-bote-green-500"></i>
                                                    </div>
                                                    <p class="text-2xl font-bold text-bote-green-600">
                                                        <?php 
                                                        if ($request['status'] === 'Completed' && $request['points_earned'] !== null) {
                                                            echo number_format($request['points_earned']);
                                                        } else {
                                                            echo '--';
                                                        }
                                                        ?>
                                                    </p>
                                                    <p class="text-bote-green-500 text-sm">points</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Schedule and Worker Info -->
                                        <div class="space-y-4">
                                            <div class="bg-bote-info-50 rounded-xl p-4">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-bote-info-700 text-sm font-medium">Preferred Date</span>
                                                    <i data-lucide="calendar" class="w-4 h-4 text-bote-info-500"></i>
                                                </div>
                                                <p class="font-semibold text-bote-info-900">
                                                    <?php echo date('M j, Y', strtotime($request['preferred_date'])); ?>
                                                </p>
                                                <p class="text-bote-info-600 text-sm">
                                                    <?php echo date('g:i A', strtotime($request['preferred_date'])); ?>
                                                </p>
                                            </div>
                                            
                                            <?php if ($request['worker_name']): ?>
                                            <div class="bg-bote-green-50 rounded-xl p-4">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-bote-green-700 text-sm font-medium">Assigned Worker</span>
                                                    <i data-lucide="user" class="w-4 h-4 text-bote-green-500"></i>
                                                </div>
                                                <p class="font-semibold text-bote-green-900">
                                                    <?php echo htmlspecialchars($request['worker_name']); ?>
                                                </p>
                                                <p class="text-bote-green-600 text-sm">Collection Agent</p>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex flex-col items-end space-y-3 ml-6">
                                    <?php if ($request['status'] == 'Pending'): ?>
                                        <button onclick="cancelRequest(<?php echo (int) $request['request_id']; ?>)" 
                                                class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-xl font-medium transition-colors flex items-center space-x-2">
                                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                                            <span>Cancel</span>
                                        </button>
                                    <?php elseif ($request['status'] == 'Completed'): ?>
                                        <div class="px-4 py-2 bg-bote-green-100 text-bote-green-700 rounded-xl font-medium flex items-center space-x-2">
                                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                                            <span>Completed</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <button onclick="viewDetails(<?php echo (int) $request['request_id']; ?>)"
                                            class="px-4 py-2 bg-bote-info-100 hover:bg-bote-info-200 text-bote-info-700 rounded-xl font-medium transition-colors flex items-center space-x-2">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                        <span>Details</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Progress Bar for non-completed requests -->
                            <?php if ($request['status'] != 'Completed'): ?>
                            <div class="mt-6">
                                <div class="flex justify-between text-sm text-bote-stone-600 mb-2">
                                    <span>Progress</span>
                                    <span>
                                        <?php 
                                        $progress = $request['status'] == 'Pending' ? 25 : 
                                                   ($request['status'] == 'Scheduled' ? 50 : 75);
                                        echo $progress; 
                                        ?>%
                                    </span>
                                </div>
                                <div class="w-full bg-bote-stone-200 rounded-full h-2">
                                    <div class="progress-bar h-2 rounded-full" style="width: <?php echo e($progress); ?>%"></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php $card_index++; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-16">
                    <div class="floating-animation">
                        <div class="w-32 h-32 bg-gradient-to-br from-bote-stone-100 to-bote-stone-200 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="inbox" class="w-16 h-16 text-bote-stone-400"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-bote-stone-900 mb-3">No requests yet</h3>
                    <p class="text-bote-stone-600 mb-8 max-w-md mx-auto">
                        Start your recycling journey by creating your first pickup request and earn valuable points for your contributions.
                    </p>
                    <a href="resident_new_request.php" class="inline-flex items-center space-x-3 bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white px-8 py-4 rounded-2xl font-semibold transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:scale-105">
                        <i data-lucide="plus-circle" class="w-6 h-6"></i>
                        <span>Create Your First Request</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Include the my_request.js file -->
