   <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/pages/admin-rewards.css">
<div class="p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Enhanced Header -->
            <div class="bg-white rounded-3xl shadow-lg p-8 mb-8 border border-bote-stone-100">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i data-lucide="gift" class="w-8 h-8 text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold text-bote-stone-900 mb-2">Rewards Management</h1>
                            <p class="text-bote-stone-600 text-lg">Manage reward catalog and track redemptions</p>
                        </div>
                    </div>
                    <button onclick="showAddModal()" class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white px-8 py-4 rounded-2xl font-semibold transition-all duration-300 flex items-center space-x-3 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <i data-lucide="plus" class="w-5 h-5"></i>
                        <span>Add New Reward</span>
                    </button>
                </div>
            </div>

            <!-- Enhanced Success/Error Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="bg-gradient-to-r from-bote-green-50 to-bote-green-100 border-l-4 border-bote-green-500 p-6 rounded-2xl mb-8 shadow-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-bote-green-500 rounded-full flex items-center justify-center mr-4">
                            <i data-lucide="check" class="w-4 h-4 text-white"></i>
                        </div>
                        <div>
                            <h4 class="text-bote-green-800 font-semibold">Success!</h4>
                            <p class="text-bote-green-700"><?php echo htmlspecialchars($success_message); ?></p>
                        </div>
                        <button onclick="hideParent(this, 2)" class="ml-auto text-bote-green-600 hover:text-bote-green-800">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-500 p-6 rounded-2xl mb-8 shadow-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center mr-4">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-white"></i>
                        </div>
                        <div>
                            <h4 class="text-red-800 font-semibold">Error!</h4>
                            <p class="text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
                        </div>
                        <button onclick="hideParent(this, 2)" class="ml-auto text-red-600 hover:text-red-800">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Enhanced Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                <!-- Total Rewards -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-bote-green-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-green-400 to-bote-green-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="gift" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($total_rewards); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="package" class="w-3 h-3 mr-1"></i>
                                    Total Rewards
                                </p>
                            </div>
                        </div>
                        <div class="text-bote-green-500">
                            <i data-lucide="trending-up" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Total Stock -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-bote-info-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-info-400 to-bote-info-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="package" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($total_stock); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="box" class="w-3 h-3 mr-1"></i>
                                    Total Stock
                                </p>
                            </div>
                        </div>
                        <div class="text-bote-info-500">
                            <i data-lucide="archive" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Average Points -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="coins" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo number_format($avg_points); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="calculator" class="w-3 h-3 mr-1"></i>
                                    Avg Points
                                </p>
                            </div>
                        </div>
                        <div class="text-yellow-500">
                            <i data-lucide="bar-chart" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Low Stock Alert -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="alert-triangle" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($low_stock_count); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="alert-circle" class="w-3 h-3 mr-1"></i>
                                    Low Stock
                                    <?php if ($low_stock_count > 0): ?>
                                        <span class="ml-2 px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full pulse-critical">Alert</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="text-orange-500">
                            <i data-lucide="package-x" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Out of Stock -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-red-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-red-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="x-circle" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($out_of_stock_count); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="ban" class="w-3 h-3 mr-1"></i>
                                    Out of Stock
                                    <?php if ($out_of_stock_count > 0): ?>
                                        <span class="ml-2 px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full pulse-critical">Critical</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="text-red-500">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Enhanced Rewards List -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-bote-stone-100">
                        <div class="px-8 py-6 border-b border-bote-stone-200 bg-gradient-to-r from-bote-green-50 to-bote-green-50">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-lg flex items-center justify-center mr-3">
                                        <i data-lucide="gift" class="w-4 h-4 text-white"></i>
                                    </div>
                                    <h2 class="text-xl font-bold text-bote-stone-900">Reward Catalog</h2>
                                </div>
                                <span class="text-sm text-bote-stone-500"><?php echo count($rewards); ?> reward<?php echo count($rewards) != 1 ? 's' : ''; ?></span>
                            </div>
                        </div>
                        
                        <div class="p-8">
                            <?php if (empty($rewards)): ?>
                                <div class="text-center py-12">
                                    <div class="w-24 h-24 bg-bote-stone-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i data-lucide="gift" class="w-12 h-12 text-bote-stone-400"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-bote-stone-900 mb-2">No Rewards Available</h3>
                                    <p class="text-bote-stone-600 mb-6">Start by adding your first reward to the catalog.</p>
                                    <button onclick="showAddModal()" class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white px-6 py-3 rounded-xl font-medium transition-all duration-200">
                                        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
                                        Add First Reward
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <?php foreach ($rewards as $reward): ?>
                                        <div class="border border-bote-stone-200 rounded-2xl p-6 hover:shadow-lg transition-all duration-300 card-hover">
                                            <div class="flex justify-between items-start mb-4">
                                                <?php if (!empty($reward['image'])): ?>
                                                    <div class="w-16 h-16 rounded-2xl overflow-hidden shadow-lg bg-bote-stone-100">
                                                        <img src="../uploads/<?php echo htmlspecialchars($reward['image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($reward['name']); ?>" 
                                                             class="w-full h-full object-cover">
                                                    </div>
                                                <?php else: ?>
                                                    <div class="w-16 h-16 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                                        <span class="text-white font-bold text-lg">
                                                            <?php echo e(strtoupper(substr($reward['name'], 0, 2))); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="flex space-x-2">
                                                    <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($reward)); ?>)" 
                                                            class="w-8 h-8 bg-bote-info-100 hover:bg-bote-info-200 text-bote-info-600 rounded-lg flex items-center justify-center transition-colors" 
                                                            title="Edit reward">
                                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                                    </button>
                                                    <?php if (($reward['pending_redemptions'] ?? 0) == 0): ?>
                                                        <button onclick="confirmDelete(<?php echo e($reward['reward_id']); ?>)" 
                                                                class="w-8 h-8 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg flex items-center justify-center transition-colors" 
                                                                title="Delete reward">
                                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="w-8 h-8 bg-bote-stone-100 text-bote-stone-400 rounded-lg flex items-center justify-center cursor-not-allowed" 
                                                                title="Cannot delete - has active redemptions">
                                                            <i data-lucide="lock" class="w-4 h-4"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <h3 class="text-lg font-bold text-bote-stone-900 mb-2">
                                                <?php echo htmlspecialchars($reward['name']); ?>
                                            </h3>
                                            
                                            <?php if (!empty($reward['description'])): ?>
                                                <p class="text-bote-stone-600 text-sm mb-4 line-clamp-2">
                                                    <?php echo htmlspecialchars($reward['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="flex items-center justify-between mb-4">
                                                <div class="flex items-center space-x-2">
                                                    <i data-lucide="coins" class="w-4 h-4 text-yellow-500"></i>
                                                    <span class="text-lg font-bold text-bote-stone-900"><?php echo number_format($reward['points_required']); ?></span>
                                                    <span class="text-sm text-bote-stone-500">points</span>
                                                </div>
                                                
                                                <?php 
                                                    $stock = (int)$reward['stock'];
                                                    $stockClass = $stock == 0 ? 'bg-red-100 text-red-800 border-red-200' : 
                                                                 ($stock <= 5 ? 'bg-orange-100 text-orange-800 border-orange-200' : 
                                                                 'bg-bote-green-100 text-bote-green-800 border-bote-green-200');
                                                ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border <?php echo e($stockClass); ?>">
                                                    <i data-lucide="package" class="w-3 h-3 mr-1"></i>
                                                    <?php echo e($stock); ?> in stock
                                                </span>
                                            </div>
                                            
                                            <div class="border-t border-bote-stone-200 pt-4 mt-4">
                                                <div class="flex items-center justify-between text-sm text-bote-stone-500">
                                                    <span class="flex items-center">
                                                        <i data-lucide="calendar" class="w-3 h-3 mr-1"></i>
                                                        Added <?php echo date('M j, Y', strtotime($reward['created_at'])); ?>
                                                    </span>
                                                    <?php if (!empty($reward['total_redemptions'])): ?>
                                                        <span class="flex items-center">
                                                            <i data-lucide="users" class="w-3 h-3 mr-1"></i>
                                                            <?php echo e($reward['total_redemptions']); ?> redeemed
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Recent Redemptions -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-bote-stone-100">
                        <div class="px-8 py-6 border-b border-bote-stone-200 bg-gradient-to-r from-bote-green-50 to-bote-green-50">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-lg flex items-center justify-center mr-3">
                                    <i data-lucide="clock" class="w-4 h-4 text-white"></i>
                                </div>
                                <h2 class="text-xl font-bold text-bote-stone-900">Recent Redemptions</h2>
                            </div>
                        </div>
                        
                        <div class="p-8">
                            <?php if (empty($recent_redemptions)): ?>
                                <div class="text-center py-12">
                                    <div class="w-20 h-20 bg-bote-stone-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i data-lucide="clock" class="w-10 h-10 text-bote-stone-400"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-bote-stone-900 mb-2">No Redemptions Yet</h3>
                                    <p class="text-bote-stone-600">Redemption history will appear here once residents start claiming rewards.</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($recent_redemptions as $redemption): ?>
                                        <div class="border-l-4 border-bote-green-500 bg-bote-green-50 pl-6 pr-4 py-4 rounded-r-xl hover:bg-bote-green-100 transition-colors">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <h4 class="font-semibold text-bote-stone-900 mb-1">
                                                        <?php echo htmlspecialchars($redemption['reward_name']); ?>
                                                    </h4>
                                                    <p class="text-sm text-bote-stone-600 mb-2">
                                                        <i data-lucide="user" class="w-3 h-3 inline mr-1"></i>
                                                        <?php echo htmlspecialchars($redemption['first_name'] . ' ' . $redemption['last_name']); ?>
                                                    </p>
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-xs text-bote-stone-500 flex items-center">
                                                            <i data-lucide="calendar" class="w-3 h-3 mr-1"></i>
                                                            <?php echo date('M j, g:i A', strtotime($redemption['created_at'])); ?>
                                                        </span>
                                                        <div class="flex items-center space-x-2">
                                                            <span class="text-xs font-semibold text-yellow-600 flex items-center">
                                                                <i data-lucide="coins" class="w-3 h-3 mr-1"></i>
                                                                <?php echo number_format($redemption['points_required']); ?>
                                                            </span>
                                                            <?php 
                                                                $statusClass = $redemption['status'] == 'Approved' ? 'bg-bote-green-100 text-bote-green-800' : 
                                                                              ($redemption['status'] == 'Pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                                            ?>
                                                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo e($statusClass); ?>">
                                                                <?php echo e($redemption['status']); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-6 text-center">
                                    <a href="admin_pickup_requests.php" class="text-bote-green-600 hover:text-bote-green-800 font-medium text-sm flex items-center justify-center">
                                        <span>View All Redemptions</span>
                                        <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Add Reward Modal -->
    <!-- Enhanced Add Reward Modal (Two-Column Layout) -->
<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full mx-4 transform transition-all duration-300 modal-enter">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 rounded-t-3xl px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                        <i data-lucide="plus-circle" class="w-5 h-5 text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Add New Reward</h3>
                </div>
                <button onclick="hideAddModal()" class="text-white hover:text-bote-stone-200 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        
        <!-- Modal Body -->
        <div class="p-8">
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <?php echo \App\Core\Csrf::field(); ?>
                <input type="hidden" name="action" value="add_reward">

                <!-- Two Column Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- LEFT SIDE -->
                    <div class="space-y-6">
                        <!-- Reward Name -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-bote-stone-700">
                                <i data-lucide="gift" class="w-4 h-4 mr-2 text-bote-green-500"></i>
                                Reward Name
                            </label>
                            <input type="text" name="name" required 
                                   class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all duration-200 hover:border-bote-stone-400"
                                   placeholder="e.g. Eco Shopping Bag">
                        </div>

                        <!-- Points Required -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-bote-stone-700">
                                <i data-lucide="coins" class="w-4 h-4 mr-2 text-bote-green-500"></i>
                                Points Required
                            </label>
                            <input type="number" name="points_required" required min="1" 
                                   class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all duration-200 hover:border-bote-stone-400"
                                   placeholder="e.g. 500">
                        </div>

                        <!-- Stock -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-bote-stone-700">
                                <i data-lucide="package" class="w-4 h-4 mr-2 text-bote-green-500"></i>
                                Initial Stock
                            </label>
                            <input type="number" name="stock" required min="0" 
                                   class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all duration-200 hover:border-bote-stone-400"
                                   placeholder="e.g. 100">
                        </div>
                    </div>

                    <!-- RIGHT SIDE -->
                    <div class="space-y-6">
                        <!-- Reward Image -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-bote-stone-700">
                                <i data-lucide="image" class="w-4 h-4 mr-2 text-bote-green-500"></i>
                                Reward Image (Optional)
                            </label>
                            <div class="relative">
                                <input type="file" name="reward_image" accept="image/*" id="reward_image"
                                       class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all duration-200 hover:border-bote-stone-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-bote-green-50 file:text-bote-green-700 hover:file:bg-bote-green-100">
                                <div class="mt-2 text-xs text-bote-stone-500 flex items-center">
                                    <i data-lucide="info" class="w-3 h-3 mr-1"></i>
                                    Supported formats: JPG, PNG, GIF (Max 5MB)
                                </div>
                            </div>
                            <!-- Image Preview -->
                            <div id="imagePreview" class="hidden mt-3">
                                <img id="previewImg" src="" alt="Preview" class="w-24 h-24 object-cover rounded-xl border-2 border-bote-stone-200">
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-bote-stone-700">
                                <i data-lucide="file-text" class="w-4 h-4 mr-2 text-bote-green-500"></i>
                                Description (Optional)
                            </label>
                            <textarea name="description" rows="3"
                                      class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all duration-200 hover:border-bote-stone-400 resize-none"
                                      placeholder="Brief description of the reward..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-4 pt-6">
                    <button type="button" onclick="hideAddModal()" 
                            class="flex-1 px-6 py-3 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-700 rounded-xl font-semibold transition-all duration-200 flex items-center justify-center">
                        <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white rounded-xl font-semibold transition-all duration-200 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                        Add Reward
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Enhanced Edit Reward Modal -->
 <!-- Enhanced Edit Reward Modal (Two-Column Layout) -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full mx-4 transform transition-all duration-300 modal-enter">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-bote-info-500 to-bote-info-600 rounded-t-3xl px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                        <i data-lucide="edit" class="w-5 h-5 text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Edit Reward</h3>
                </div>
                <button onclick="hideEditModal()" class="text-white hover:text-bote-stone-200 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        
        <!-- Modal Body -->
        <div class="p-8">
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <?php echo \App\Core\Csrf::field(); ?>
                <input type="hidden" name="action" value="update_reward">
                <input type="hidden" name="reward_id" id="edit_reward_id">

                <!-- Two Column Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- LEFT SIDE -->
                    <div class="space-y-6">
                        <!-- Reward Name -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-bote-stone-700">
                                <i data-lucide="gift" class="w-4 h-4 mr-2 text-bote-info-500"></i>
                                Reward Name
                            </label>
                            <input type="text" name="name" id="edit_name" required 
                                   class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all duration-200 hover:border-bote-stone-400">
                        </div>

                        <!-- Points Required -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-bote-stone-700">
                                <i data-lucide="coins" class="w-4 h-4 mr-2 text-bote-info-500"></i>
                                Points Required
                            </label>
                            <input type="number" name="points_required" id="edit_points" required min="1" 
                                   class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all duration-200 hover:border-bote-stone-400">
                        </div>

                        <!-- Stock -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-bote-stone-700">
                                <i data-lucide="package" class="w-4 h-4 mr-2 text-bote-info-500"></i>
                                Stock
                            </label>
                            <input type="number" name="stock" id="edit_stock" required min="0" 
                                   class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all duration-200 hover:border-bote-stone-400">
                        </div>
                    </div>

                    <!-- RIGHT SIDE -->
                    <div class="space-y-6">
                        <!-- Current Image Display -->
                        <div id="currentImageSection" class="space-y-2" style="display: none;">
                            <label class="flex items-center text-sm font-semibold text-bote-stone-700">
                                <i data-lucide="image" class="w-4 h-4 mr-2 text-bote-info-500"></i>
                                Current Image
                            </label>
                            <div class="flex items-center space-x-3">
                                <img id="currentImage" src="" alt="Current reward image" class="w-20 h-20 object-cover rounded-xl border-2 border-bote-stone-200">
                                <div class="text-sm text-bote-stone-600">
                                    <p>Current reward image</p>
                                    <p class="text-xs text-bote-stone-500">Upload a new image to replace this one</p>
                                </div>
                            </div>
                        </div>

                        <!-- Upload New Image -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-bote-stone-700">
                                <i data-lucide="image" class="w-4 h-4 mr-2 text-bote-info-500"></i>
                                <span id="imageLabel">Reward Image (Optional)</span>
                            </label>
                            <div class="relative">
                                <input type="file" name="reward_image" accept="image/*" id="edit_reward_image"
                                       class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all duration-200 hover:border-bote-stone-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-bote-info-50 file:text-bote-info-700 hover:file:bg-bote-info-100">
                                <div class="mt-2 text-xs text-bote-stone-500 flex items-center">
                                    <i data-lucide="info" class="w-3 h-3 mr-1"></i>
                                    Supported formats: JPG, PNG, GIF (Max 5MB)
                                </div>
                            </div>
                            <!-- Image Preview -->
                            <div id="editImagePreview" class="hidden mt-3">
                                <img id="editPreviewImg" src="" alt="Preview" class="w-24 h-24 object-cover rounded-xl border-2 border-bote-stone-200">
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-bote-stone-700">
                                <i data-lucide="file-text" class="w-4 h-4 mr-2 text-bote-info-500"></i>
                                Description (Optional)
                            </label>
                            <textarea name="description" id="edit_description" rows="3"
                                      class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all duration-200 hover:border-bote-stone-400 resize-none"
                                      placeholder="Brief description of the reward..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-4 pt-6">
                    <button type="button" onclick="hideEditModal()" 
                            class="flex-1 px-6 py-3 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-700 rounded-xl font-semibold transition-all duration-200 flex items-center justify-center">
                        <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-bote-info-500 to-bote-info-600 hover:from-bote-info-600 hover:to-bote-info-700 text-white rounded-xl font-semibold transition-all duration-200 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                        Update Reward
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="trash-2" class="w-8 h-8 text-red-600"></i>
                </div>
                <h3 class="text-xl font-bold text-bote-stone-900 mb-2">Delete Reward</h3>
                <p class="text-bote-stone-600 mb-6">Are you sure you want to delete this reward? This action cannot be undone.</p>
                
                <form method="POST">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <input type="hidden" name="action" value="delete_reward">
                    <input type="hidden" name="reward_id" id="delete_reward_id">
                    
                    <div class="flex space-x-4">
                        <button type="button" onclick="hideDeleteModal()" 
                                class="flex-1 px-4 py-2 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-800 rounded-lg font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                            Delete Reward
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

