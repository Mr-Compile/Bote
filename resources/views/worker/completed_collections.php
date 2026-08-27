<!-- Main Content -->
<div class="p-8 bg-bote-stone-50 min-h-screen">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-bote-stone-900 mb-2">Completed Collections</h1>
                    <p class="text-bote-stone-600">Review your successful pickup history and performance</p>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-bote-green-600"><?php echo number_format($stats['total_collections'] ?? 0); ?></div>
                    <div class="text-sm text-bote-stone-500">Total Collections</div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-bote-stone-600 mb-1">Total Collections</p>
                        <p class="text-2xl font-bold text-bote-green-600"><?php echo number_format($stats['total_collections'] ?? 0); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-bote-green-100 rounded-full flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-6 h-6 text-bote-green-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-bote-stone-600 mb-1">Total Weight</p>
                        <p class="text-2xl font-bold text-bote-info-600"><?php echo number_format($stats['total_weight'] ?? 0, 1); ?> kg</p>
                    </div>
                    <div class="w-12 h-12 bg-bote-info-100 rounded-full flex items-center justify-center">
                        <i data-lucide="weight" class="w-6 h-6 text-bote-info-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-bote-stone-600 mb-1">This Month</p>
                        <p class="text-2xl font-bold text-bote-green-600"><?php echo number_format($month_stats['month_collections'] ?? 0); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-bote-green-100 rounded-full flex items-center justify-center">
                        <i data-lucide="calendar" class="w-6 h-6 text-bote-green-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-bote-stone-600 mb-1">Month Weight</p>
                        <p class="text-2xl font-bold text-orange-600"><?php echo number_format($month_stats['month_weight'] ?? 0, 1); ?> kg</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-6 h-6 text-orange-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collections List -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-bote-stone-200 bg-bote-stone-50">
                <h2 class="text-xl font-semibold text-bote-stone-900">Collection History</h2>
            </div>
            
            <?php if ($completed_collections->num_rows > 0): ?>
                <div class="divide-y divide-bote-stone-200">
                    <?php while ($collection = $completed_collections->fetch_assoc()): ?>
                        <div class="p-6 hover:bg-bote-stone-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-4 mb-3">
                                        <div class="w-3 h-3 bg-bote-green-400 rounded-full"></div>
                                        <span class="text-sm font-medium text-bote-green-700">Completed</span>
                                        <span class="text-sm text-bote-stone-500">Request #<?php echo e($collection['request_id']); ?></span>
                                    </div>
                                    
                                    <h3 class="text-lg font-semibold text-bote-stone-900 mb-2">
                                        <?php echo htmlspecialchars($collection['recyclable_type']); ?>
                                    </h3>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-3">
                                        <div>
                                            <span class="text-bote-stone-500">Resident:</span>
                                            <span class="font-medium ml-1"><?php echo htmlspecialchars($collection['resident_name']); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-bote-stone-500">Weight:</span>
                                            <span class="font-medium ml-1"><?php echo number_format($collection['weight_kg'], 1); ?> kg</span>
                                        </div>
                                        <div>
                                            <span class="text-bote-stone-500">Collection Date:</span>
                                            <span class="font-medium ml-1"><?php echo date('M j, Y', strtotime($collection['preferred_date'])); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-bote-stone-500">Points Awarded:</span>
                                            <span class="font-medium text-bote-green-600 ml-1">
                                                <?php echo number_format($collection['points_awarded'] ?? ($collection['weight_kg'] * $collection['points_per_kg'])); ?> pts
                                            </span>
                                        </div>
                                    </div>

                                    <div class="text-sm text-bote-stone-600">
                                        <i data-lucide="map-pin" class="w-4 h-4 inline mr-1"></i>
                                        <?php echo htmlspecialchars($collection['pickup_address']); ?>
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    <div class="text-sm text-bote-stone-500 mb-1">
                                        Collected: <?php echo date('M j, Y g:i A', strtotime($collection['preferred_date'])); ?>
                                    </div>
                                    <button onclick="viewCollectionDetails(<?php echo e($collection['request_id']); ?>)" 
                                            class="text-bote-info-600 hover:text-bote-info-700 text-sm font-medium">
                                        View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-bote-stone-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="package" class="w-12 h-12 text-bote-stone-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-bote-stone-900 mb-2">No completed collections</h3>
                    <p class="text-bote-stone-500 mb-6">Your completed collections will appear here</p>
                    <a href="worker_assigned_pickups.php" class="inline-flex items-center space-x-2 bg-bote-green-600 hover:bg-bote-green-700 text-white px-6 py-3 rounded-xl font-medium transition-colors">
                        <i data-lucide="truck" class="w-5 h-5"></i>
                        <span>View Assigned Pickups</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Collection Details Modal -->
<div id="collectionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95">
        <div class="text-center">
            <div class="w-16 h-16 bg-bote-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="check-circle" class="w-8 h-8 text-bote-green-600"></i>
            </div>
            <h3 class="text-xl font-bold text-bote-stone-900 mb-2">Collection Details</h3>
            <div id="collectionDetails" class="text-bote-stone-600 mb-6">
                <!-- Collection details will be loaded here -->
            </div>
            <button onclick="hideCollectionModal()" class="px-6 py-2 bg-bote-stone-600 hover:bg-bote-stone-700 text-white rounded-xl font-medium transition-colors">
                Close
            </button>
        </div>
    </div>
</div>