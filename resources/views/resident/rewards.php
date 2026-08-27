    <link rel="stylesheet" href="assets/css/rewards.css">
    <script type="application/json" id="rewards-data">
        <?php echo json_encode([
            'userPoints' => (int) $user_points,
            'redemptionsCount' => (int) $redemptions->num_rows,
            'csrfToken' => \App\Core\Csrf::token(),
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
    </script>
?>

<!-- Main Content -->
<div class="p-8 bg-gradient-to-br from-bote-stone-50 via-white to-bote-green-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Enhanced Header -->
        <div class="bg-gradient-to-r from-white to-bote-green-50 rounded-3xl shadow-2xl p-10 mb-12 relative overflow-hidden">
            <div class="shimmer absolute inset-0"></div>
            <div class="relative z-10 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="floating-animation">
                        <div class="w-20 h-20 bg-gradient-to-r from-bote-green-500 via-bote-green-600 to-bote-green-600 rounded-2xl flex items-center justify-center mr-8 shadow-xl pulse-glow">
                            <i data-lucide="gift" class="w-10 h-10 text-white"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-5xl font-bold bg-gradient-to-r from-bote-green-600 via-bote-green-600 to-bote-green-700 bg-clip-text text-transparent mb-3">
                            Reward Store
                        </h1>
                        <p class="text-xl text-bote-stone-600 mb-4">Transform your eco-efforts into amazing rewards</p>
                        <div class="flex items-center space-x-8 text-sm text-bote-stone-500">
                            <div class="flex items-center">
                                <i data-lucide="sparkles" class="w-4 h-4 mr-2"></i>
                                <span>Exclusive rewards</span>
                            </div>
                            <div class="flex items-center">
                                <i data-lucide="zap" class="w-4 h-4 mr-2"></i>
                                <span>Instant redemption</span>
                            </div>
                            <div class="flex items-center">
                                <i data-lucide="shield-check" class="w-4 h-4 mr-2"></i>
                                <span>Guaranteed delivery</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center">
                    <div class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 rounded-3xl p-8 text-white shadow-2xl transform hover:scale-105 transition-all duration-300">
                        <div class="flex items-center justify-center mb-3">
                            <i data-lucide="coins" class="w-8 h-8 mr-3"></i>
                            <div class="text-4xl font-bold points-counter"><?php echo number_format($user_points); ?></div>
                        </div>
                        <div class="text-bote-green-100 font-medium">Available Points</div>
                        <div class="mt-3 text-xs text-bote-green-200">Ready to spend</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Points Progress Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <div class="bg-gradient-to-br from-bote-info-500 to-bote-info-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold"><?php echo e($user_points >= 100 ? '100+' : $user_points); ?></p>
                        <p class="text-bote-info-100">Points Earned</p>
                    </div>
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold"><?php echo e($redemptions->num_rows); ?></p>
                        <p class="text-bote-green-100">Items Redeemed</p>
                    </div>
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i data-lucide="shopping-bag" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-yellow-500 to-orange-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold"><?php echo e($rewards->num_rows); ?></p>
                        <p class="text-yellow-100">Available Rewards</p>
                    </div>
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i data-lucide="gift-box" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Available Rewards -->
        <div class="bg-white rounded-3xl shadow-2xl mb-12 overflow-hidden">
            <div class="bg-gradient-to-r from-bote-green-50 to-bote-green-50 px-8 py-6 border-b border-bote-green-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-bote-green-500 to-bote-green-600 rounded-xl flex items-center justify-center mr-4">
                            <i data-lucide="star" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-bote-stone-900">Premium Rewards Collection</h2>
                            <p class="text-bote-stone-600 mt-1">Discover amazing rewards waiting for you</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <select class="px-4 py-2 border border-bote-stone-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500" id="sortRewards">
                            <option value="points_asc">Points: Low to High</option>
                            <option value="points_desc">Points: High to Low</option>
                            <option value="stock">Most Available</option>
                        </select>
                        <button class="p-2 bg-bote-green-100 hover:bg-bote-green-200 rounded-xl transition-colors" onclick="refreshRewards()">
                            <i data-lucide="refresh-cw" class="w-5 h-5 text-bote-green-600"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if ($rewards->num_rows > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8 p-8" id="rewardsGrid">
                    <?php $card_index = 0; ?>
                    <?php while ($reward = $rewards->fetch_assoc()): ?>
                        <div class="reward-card gradient-border rounded-2xl p-6 bg-white shadow-lg hover:shadow-2xl transition-all duration-300 card-enter" 
                             style="animation-delay: <?php echo e($card_index * 0.1); ?>s"
                             data-points="<?php echo e($reward['points_required']); ?>"
                             data-stock="<?php echo e($reward['stock']); ?>">
                            
                            <!-- Reward Image -->
                            <div class="relative mb-6">
                                <?php if (!empty($reward['image'])): ?>
                                    <div class="w-full h-52 rounded-xl overflow-hidden bg-gradient-to-br from-bote-green-100 to-bote-green-100 shadow-lg">
                                        <img src="../uploads/<?php echo htmlspecialchars($reward['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($reward['name']); ?>" 
                                             class="w-full h-full object-cover hover:scale-110 transition-transform duration-500"
                                             onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center bg-gradient-to-br from-bote-green-200 to-bote-green-200\'><i data-lucide=\'gift\' class=\'w-16 h-16 text-bote-green-600\'></i></div>';">
                                    </div>
                                <?php else: ?>
                                    <div class="w-full h-52 bg-gradient-to-br from-bote-green-200 via-bote-green-200 to-bote-green-300 rounded-xl flex items-center justify-center shadow-lg">
                                        <i data-lucide="gift" class="w-20 h-20 text-bote-green-700"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Stock Badge -->
                                <div class="absolute top-3 right-3">
                                    <div class="bg-white bg-opacity-90 backdrop-blur-sm px-3 py-1 rounded-full shadow-lg">
                                        <span class="text-sm font-semibold text-bote-green-600 flex items-center">
                                            <i data-lucide="package" class="w-4 h-4 mr-1"></i>
                                            <?php echo e($reward['stock']); ?> left
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Reward Content -->
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-xl font-bold text-bote-stone-900 mb-2 line-clamp-1">
                                        <?php echo htmlspecialchars($reward['name']); ?>
                                    </h3>
                                    
                                    <?php if (!empty($reward['description'])): ?>
                                        <p class="text-bote-stone-600 text-sm leading-relaxed line-clamp-2">
                                            <?php echo htmlspecialchars($reward['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Points Display -->
                                <div class="bg-gradient-to-r from-bote-green-50 to-bote-green-50 rounded-xl p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm text-bote-stone-600 mb-1">Required Points</p>
                                            <p class="text-2xl font-bold bg-gradient-to-r from-bote-green-600 to-bote-green-600 bg-clip-text text-transparent">
                                                <?php echo number_format($reward['points_required']); ?>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <?php if ($user_points >= $reward['points_required']): ?>
                                                <div class="w-12 h-12 bg-bote-green-500 rounded-full flex items-center justify-center">
                                                    <i data-lucide="check" class="w-6 h-6 text-white"></i>
                                                </div>
                                            <?php else: ?>
                                                <div class="w-12 h-12 bg-bote-stone-300 rounded-full flex items-center justify-center">
                                                    <i data-lucide="lock" class="w-6 h-6 text-bote-stone-500"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Action Button -->
                                <div class="pt-2">
                                    <?php if ($user_points >= $reward['points_required']): ?>
                                        <button onclick="redeemReward(<?php echo (int) $reward['reward_id']; ?>, <?php echo json_encode($reward['name'], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS); ?>, <?php echo (int) $reward['points_required']; ?>)" 
                                                class="w-full bg-gradient-to-r from-bote-green-500 via-bote-green-600 to-bote-green-600 hover:from-bote-green-600 hover:via-bote-green-700 hover:to-bote-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center justify-center space-x-2">
                                            <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                                            <span>Redeem Now</span>
                                        </button>
                                    <?php else: ?>
                                        <button class="w-full bg-bote-stone-200 text-bote-stone-500 px-6 py-3 rounded-xl font-semibold cursor-not-allowed flex items-center justify-center space-x-2" disabled>
                                            <i data-lucide="coins" class="w-5 h-5"></i>
                                            <span>Need <?php echo number_format($reward['points_required'] - $user_points); ?> More Points</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php $card_index++; ?>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-16">
                    <div class="floating-animation">
                        <div class="w-32 h-32 bg-gradient-to-br from-bote-green-100 to-bote-green-200 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="gift-off" class="w-16 h-16 text-bote-green-500"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-bote-stone-900 mb-3">No rewards available</h3>
                    <p class="text-bote-stone-600 mb-8 max-w-md mx-auto">
                        We're working hard to bring you amazing rewards. Check back soon for exciting new additions!
                    </p>
                    <button onclick="refreshRewards()" class="inline-flex items-center space-x-2 bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white px-6 py-3 rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl">
                        <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                        <span>Check Again</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
            
            <?php if ($rewards->num_rows > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                    <?php while ($reward = $rewards->fetch_assoc()): ?>
                        <div class="border border-bote-stone-200 rounded-xl p-6 hover:shadow-lg transition-shadow">
                            <!-- Reward Image -->
                            <?php if (!empty($reward['image'])): ?>
                                <div class="w-full h-48 rounded-xl overflow-hidden mb-4 bg-bote-stone-100">
                                    <img src="../uploads/<?php echo htmlspecialchars($reward['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($reward['name']); ?>" 
                                         class="w-full h-full object-cover"
                                         onerror="console.log('Image failed to load: ../uploads/<?php echo htmlspecialchars($reward['image']); ?>'); this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-red-500\'><i data-lucide=\'image-off\' class=\'w-8 h-8\'></i></div>';">
                                </div>

                            <?php else: ?>
                                <div class="w-full h-48 bg-gradient-to-br from-bote-green-100 to-bote-green-100 rounded-xl flex items-center justify-center mb-4">
                                    <i data-lucide="gift" class="w-16 h-16 text-bote-green-600"></i>
                                </div>
                                <!-- Debug info (remove in production) -->
                                <div class="text-xs text-bote-stone-500 mb-2">No image available</div>
                            <?php endif; ?>
                            
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-sm text-bote-green-600 font-medium bg-bote-green-50 px-3 py-1 rounded-full">
                                    <?php echo e($reward['stock']); ?> left
                                </span>
                            </div>
                            
                            <h3 class="text-lg font-semibold text-bote-stone-900 mb-2">
                                <?php echo htmlspecialchars($reward['name']); ?>
                            </h3>
                            
                            <?php if (!empty($reward['description'])): ?>
                                <p class="text-sm text-bote-stone-600 mb-4 overflow-hidden" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                    <?php echo htmlspecialchars($reward['description']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-bote-green-600">
                                    <?php echo number_format($reward['points_required']); ?> pts
                                </span>
                                
                                <?php if ($user_points >= $reward['points_required']): ?>
                                    <button onclick="redeemReward(<?php echo (int) $reward['reward_id']; ?>, <?php echo json_encode($reward['name'], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS); ?>, <?php echo (int) $reward['points_required']; ?>)" 
                                            class="bg-bote-green-600 hover:bg-bote-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                        Redeem
                                    </button>
                                <?php else: ?>
                                    <button class="bg-bote-stone-300 text-bote-stone-500 px-4 py-2 rounded-lg font-medium cursor-not-allowed" disabled>
                                        Not Enough Points
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-bote-stone-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="gift-off" class="w-12 h-12 text-bote-stone-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-bote-stone-900 mb-2">No rewards available</h3>
                    <p class="text-bote-stone-500">Check back later for new rewards</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Enhanced Redemption History -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-bote-info-50 to-bote-info-50 px-8 py-6 border-b border-bote-info-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-bote-info-500 to-bote-info-600 rounded-xl flex items-center justify-center mr-4">
                            <i data-lucide="history" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-bote-stone-900">My Redemption History</h2>
                            <p class="text-bote-stone-600 mt-1">Track your reward purchases and deliveries</p>
                        </div>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-bote-info-600"><?php echo e($redemptions->num_rows); ?></p>
                        <p class="text-sm text-bote-stone-500">Total Redemptions</p>
                    </div>
                </div>
            </div>
            
            <?php if ($redemptions->num_rows > 0): ?>
                <div class="divide-y divide-bote-stone-100">
                    <?php $redemption_index = 0; ?>
                    <?php while ($redemption = $redemptions->fetch_assoc()): ?>
                        <div class="p-8 hover:bg-gradient-to-r hover:from-bote-stone-50 hover:to-bote-info-50 transition-all duration-300 redemption-card"
                             style="animation-delay: <?php echo e($redemption_index * 0.1); ?>s">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-6">
                                    <!-- Status Icon -->
                                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg
                                        <?php echo e($redemption['status'] == 'Pending' ? 'bg-gradient-to-br from-yellow-400 to-orange-500' : 
                                            ($redemption['status'] == 'Approved' ? 'bg-gradient-to-br from-bote-green-400 to-bote-green-500' : 
                                            'bg-gradient-to-br from-red-400 to-red-500')); ?>">
                                        <i data-lucide="<?php echo e($redemption['status'] == 'Pending' ? 'clock' : 
                                                ($redemption['status'] == 'Approved' ? 'check-circle' : 'x-circle')); ?>" class="w-8 h-8 text-white"></i>
                                    </div>
                                    
                                    <!-- Redemption Details -->
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold text-bote-stone-900 mb-2">
                                            <?php echo htmlspecialchars($redemption['reward_name']); ?>
                                        </h3>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                            <div class="bg-bote-stone-50 rounded-xl p-3">
                                                <p class="text-bote-stone-600 mb-1">Quantity</p>
                                                <p class="font-semibold text-bote-stone-900 text-lg"><?php echo e($redemption['quantity']); ?></p>
                                            </div>
                                            <div class="bg-bote-green-50 rounded-xl p-3">
                                                <p class="text-bote-green-600 mb-1">Points Used</p>
                                                <p class="font-semibold text-bote-green-700 text-lg">
                                                    <?php echo number_format($redemption['points_required'] * $redemption['quantity']); ?>
                                                </p>
                                            </div>
                                            <div class="bg-bote-info-50 rounded-xl p-3">
                                                <p class="text-bote-info-600 mb-1">Date</p>
                                                <p class="font-semibold text-bote-info-700">
                                                    <?php echo date('M j, Y', strtotime($redemption['created_at'])); ?>
                                                </p>
                                                <p class="text-xs text-bote-info-500">
                                                    <?php echo date('g:i A', strtotime($redemption['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Status Badge -->
                                <div class="text-right">
                                    <div class="inline-flex items-center px-4 py-2 rounded-2xl font-semibold text-sm mb-3
                                        <?php echo e($redemption['status'] == 'Pending' ? 'bg-yellow-100 text-yellow-800 border-2 border-yellow-200' : 
                                            ($redemption['status'] == 'Approved' ? 'bg-bote-green-100 text-bote-green-800 border-2 border-bote-green-200' : 
                                            'bg-red-100 text-red-800 border-2 border-red-200')); ?>">
                                        <i data-lucide="<?php echo e($redemption['status'] == 'Pending' ? 'clock' : 
                                                ($redemption['status'] == 'Approved' ? 'check-circle' : 'x-circle')); ?>" class="w-4 h-4 mr-2"></i>
                                        <?php echo e($redemption['status']); ?>
                                    </div>
                                    <?php if ($redemption['status'] == 'Pending'): ?>
                                        <p class="text-xs text-bote-stone-500">Processing your request</p>
                                    <?php elseif ($redemption['status'] == 'Approved'): ?>
                                        <p class="text-xs text-bote-green-600">Ready for pickup/delivery</p>
                                    <?php else: ?>
                                        <p class="text-xs text-red-600">Contact support for details</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Progress Bar for Pending Items -->
                            <?php if ($redemption['status'] == 'Pending'): ?>
                            <div class="mt-6">
                                <div class="flex justify-between text-sm text-bote-stone-600 mb-2">
                                    <span>Processing Progress</span>
                                    <span>65%</span>
                                </div>
                                <div class="w-full bg-bote-stone-200 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-yellow-400 to-orange-500 h-2 rounded-full progress-bar" style="width: 65%"></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php $redemption_index++; ?>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-16">
                    <div class="floating-animation">
                        <div class="w-32 h-32 bg-gradient-to-br from-bote-info-100 to-bote-info-200 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="inbox" class="w-16 h-16 text-bote-info-500"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-bote-stone-900 mb-3">No redemptions yet</h3>
                    <p class="text-bote-stone-600 mb-8 max-w-md mx-auto">
                        Start redeeming your hard-earned points for amazing rewards and build your collection!
                    </p>
                    <a href="#rewardsGrid" class="inline-flex items-center space-x-2 bg-gradient-to-r from-bote-info-500 to-bote-info-600 hover:from-bote-info-600 hover:to-bote-info-700 text-white px-6 py-3 rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl">
                        <i data-lucide="arrow-up" class="w-5 h-5"></i>
                        <span>Browse Rewards</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Enhanced Redemption Modal -->
<div id="redemptionModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl max-w-md w-full mx-4 transform transition-all duration-300 scale-95 shadow-2xl">
        <div class="bg-gradient-to-r from-bote-green-500 via-bote-green-600 to-bote-green-600 text-white px-8 py-6 rounded-t-3xl">
            <div class="text-center">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="gift" class="w-8 h-8"></i>
                </div>
                <h3 class="text-2xl font-bold mb-2">Confirm Redemption</h3>
                <p class="text-bote-green-100">You're about to redeem your points</p>
            </div>
        </div>
        
        <div class="p-8">
            <div class="text-center mb-6">
                <p class="text-bote-stone-700 text-lg" id="redemptionText"></p>
                <div class="mt-4 bg-gradient-to-r from-bote-green-50 to-bote-green-50 rounded-2xl p-4">
                    <p class="text-sm text-bote-stone-600 mb-2">Points After Redemption</p>
                    <p class="text-2xl font-bold text-bote-green-600" id="remainingPoints"></p>
                </div>
            </div>
            
            <div class="flex space-x-4">
                <button onclick="hideRedemptionModal()" class="flex-1 px-6 py-3 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-800 rounded-2xl font-semibold transition-all duration-300 hover:scale-105">
                    Cancel
                </button>
                <button onclick="confirmRedemption()" class="flex-1 px-6 py-3 bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white rounded-2xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105" id="confirmBtn">
                    <span class="confirm-text">Confirm Redemption</span>
                    <div class="hidden confirm-loader">
                        <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mx-auto"></div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>
