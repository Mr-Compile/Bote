    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/pages/resident-new-request.css">
    
    <!-- JavaScript is handled internally -->
<div class="p-8">
        <div class="max-w-5xl mx-auto">
            <!-- Animated Header -->
            <div class="mb-10 text-center">
                <div class="floating-animation inline-block mb-4">
                    <div class="w-20 h-20 bg-gradient-to-r from-bote-green-500 to-bote-green-600 rounded-full flex items-center justify-center pulse-ring">
                        <i data-lucide="recycle" class="w-10 h-10 text-white"></i>
                    </div>
                </div>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-bote-green-600 to-bote-green-600 bg-clip-text text-transparent mb-3">
                    Schedule New Pickup
                </h1>
                <p class="text-xl text-bote-stone-600 max-w-2xl mx-auto">
                    Turn your recyclables into rewards! Request a pickup and contribute to a greener future while earning valuable points.
                </p>
                <div class="mt-6 flex justify-center space-x-8 text-sm text-bote-stone-500">
                    <div class="flex items-center">
                        <i data-lucide="clock" class="w-4 h-4 mr-2"></i>
                        <span>24-48 hours response</span>
                    </div>
                    <div class="flex items-center">
                        <i data-lucide="shield-check" class="w-4 h-4 mr-2"></i>
                        <span>Secure collection</span>
                    </div>
                    <div class="flex items-center">
                        <i data-lucide="coins" class="w-4 h-4 mr-2"></i>
                        <span>Instant points</span>
                    </div>
                </div>
            </div>

            <!-- Enhanced Success/Error Messages -->
            <?php if (isset($_GET['error'])): ?>
                <div class="error-shake bg-gradient-to-r from-red-50 to-red-50 border-2 border-red-200 text-red-800 px-6 py-4 rounded-2xl mb-8 shadow-lg">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center mr-4">
                            <i data-lucide="alert-circle" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg">Submission Failed</h3>
                            <p class="text-red-700"><?php echo htmlspecialchars($_GET['error']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success_message)): ?>
                <div class="success-enter bg-gradient-to-r from-bote-green-50 to-bote-green-50 border-2 border-bote-green-200 text-bote-green-800 px-6 py-4 rounded-2xl mb-8 shadow-lg">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-bote-green-500 rounded-full flex items-center justify-center mr-4">
                            <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg">Request Submitted Successfully!</h3>
                            <p class="text-bote-green-700"><?php echo htmlspecialchars($success_message); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Enhanced Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                <div class="bg-gradient-to-br from-bote-green-500 via-bote-green-600 to-bote-green-700 rounded-2xl p-8 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                                    <i data-lucide="coins" class="w-8 h-8"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-3xl font-bold"><?php echo number_format($total_points); ?></p>
                                    <p class="text-bote-green-100 text-lg">Points Balance</p>
                                </div>
                            </div>
                        </div>
                        <div class="text-right opacity-20">
                            <i data-lucide="trending-up" class="w-16 h-16"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-bote-green-100">
                        <i data-lucide="arrow-up" class="w-4 h-4 mr-2"></i>
                        <span class="text-sm">Ready to earn more rewards</span>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-bote-info-500 via-bote-info-600 to-bote-info-700 rounded-2xl p-8 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                                    <i data-lucide="truck" class="w-8 h-8"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-3xl font-bold"><?php echo e($total_requests); ?></p>
                                    <p class="text-bote-info-100 text-lg">Total Requests</p>
                                </div>
                            </div>
                        </div>
                        <div class="text-right opacity-20">
                            <i data-lucide="calendar" class="w-16 h-16"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-bote-info-100">
                        <i data-lucide="clock" class="w-4 h-4 mr-2"></i>
                        <span class="text-sm">Reliable pickup service</span>
                    </div>
                </div>
            </div>

            <!-- Enhanced Pickup Request Form -->
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-bote-green-500 via-bote-green-600 to-bote-green-600 px-8 py-6 relative overflow-hidden">
                    <div class="shimmer absolute inset-0"></div>
                    <div class="relative z-10">
                        <h2 class="text-2xl font-bold text-white flex items-center">
                            <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                                <i data-lucide="plus-circle" class="w-6 h-6"></i>
                            </div>
                            Create Pickup Request
                        </h2>
                        <p class="text-bote-green-100 mt-2">Fill out the details below to schedule your material pickup</p>
                    </div>
                </div>
                
                <form method="POST" action="resident_save_request.php" class="p-8" id="pickupForm">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <!-- Enhanced Material Type Selection -->
                    <div class="mb-10">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-bote-green-500 to-bote-green-600 rounded-xl flex items-center justify-center mr-4">
                                <i data-lucide="package" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <label class="block text-xl font-semibold text-bote-stone-900">Select Material Type</label>
                                <p class="text-bote-stone-600 mt-1">Choose the type of recyclable material you want to submit</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($recyclable_types as $type): ?>
                                <div class="material-card gradient-border rounded-2xl p-6 transition-all duration-300" onclick="selectMaterial(<?php echo (int) $type['type_id']; ?>, <?php echo json_encode($type['name'], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS); ?>, <?php echo json_encode($type['unit'], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS); ?>, <?php echo (float) ($type['points_per_kg'] ?? 0); ?>, <?php echo (float) ($type['points_per_unit'] ?? 0); ?>, this)">
                                    <input type="radio" name="type_id" value="<?php echo e($type['type_id']); ?>" class="hidden" required>
                                    <div class="selection-check">
                                        <i data-lucide="check" class="w-4 h-4 text-white"></i>
                                    </div>
                                    <div class="text-center">
                                        <!-- Material Image -->
                                        <div class="w-20 h-20 bg-gradient-to-br from-bote-green-400 via-bote-green-500 to-bote-green-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg overflow-hidden">
                                            <?php if (!empty($type['image']) && file_exists($type['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($type['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($type['name']); ?>"
                                                     class="w-full h-full object-cover rounded-2xl"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <i data-lucide="recycle" class="w-10 h-10 text-white" style="display:none;"></i>
                                            <?php else: ?>
                                                <i data-lucide="recycle" class="w-10 h-10 text-white"></i>
                                            <?php endif; ?>
                                        </div>
                                        <h3 class="font-bold text-bote-stone-900 mb-2 text-lg"><?php echo htmlspecialchars($type['name']); ?></h3>
                                        <div class="bg-gradient-to-r from-bote-green-100 to-bote-green-100 rounded-xl px-4 py-2 mb-3">
                                            <p class="text-sm font-semibold text-bote-green-700">
                                                <?php if ($type['unit'] === 'kg' && $type['points_per_kg'] > 0): ?>
                                                    <i data-lucide="coins" class="w-4 h-4 inline mr-1"></i>
                                                    <?php echo e($type['points_per_kg']); ?> points/kg
                                                <?php elseif ($type['unit'] === 'pcs' && $type['points_per_unit'] > 0): ?>
                                                    <i data-lucide="coins" class="w-4 h-4 inline mr-1"></i>
                                                    <?php echo e($type['points_per_unit']); ?> points/piece
                                                <?php else: ?>
                                                    <i data-lucide="phone" class="w-4 h-4 inline mr-1"></i>
                                                    Contact for pricing
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <p class="text-xs text-bote-stone-500 uppercase tracking-wider">
                                            Unit: <?php echo e(strtoupper($type['unit'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Enhanced Weight and Date Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                        <!-- Dynamic Weight/Quantity Input -->
                        <div class="space-y-2" id="materialInputSection">
                            <div class="flex items-center mb-3">
                                <div class="w-8 h-8 bg-gradient-to-r from-bote-info-500 to-bote-info-600 rounded-lg flex items-center justify-center mr-3">
                                    <i data-lucide="weight" class="w-4 h-4 text-white"></i>
                                </div>
                                <label class="block text-lg font-semibold text-bote-stone-900" id="materialInputLabel">Select Material First</label>
                            </div>
                            
                            <!-- Weight Input (for kg materials) -->
                            <div class="relative hidden" id="weightInputDiv">
                                <input type="number" name="weight_kg" step="0.1" min="10"
                                       id="weight_input"
                                       class="form-input w-full px-6 py-4 border-2 border-bote-stone-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-bote-green-500 focus:ring-opacity-30 focus:border-bote-green-500 text-lg bg-bote-stone-50 hover:bg-white transition-all duration-300"
                                       placeholder="Enter weight in kilograms (min 10kg)"
                                       oninput="calculatePoints()">
                                <div class="absolute inset-y-0 right-0 pr-6 flex items-center pointer-events-none">
                                    <span class="text-bote-stone-500 font-medium">kg</span>
                                </div>
                            </div>
                            
                            <!-- Quantity Input (for pcs materials) -->
                            <div class="relative hidden" id="quantityInputDiv">
                                <input type="number" name="quantity_pcs" step="1" min="30"
                                       id="quantity_input"
                                       class="form-input w-full px-6 py-4 border-2 border-bote-stone-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-bote-green-500 focus:ring-opacity-30 focus:border-bote-green-500 text-lg bg-bote-stone-50 hover:bg-white transition-all duration-300"
                                       placeholder="Enter number of pieces (min 30pcs)"
                                       oninput="calculatePoints()">
                                <div class="absolute inset-y-0 right-0 pr-6 flex items-center pointer-events-none">
                                    <span class="text-bote-stone-500 font-medium">pcs</span>
                                </div>
                            </div>
                            
                            <p class="text-sm text-bote-stone-500 mt-2 flex items-center" id="materialInputHint">
                                <i data-lucide="info" class="w-4 h-4 mr-2"></i>
                                Please select a material type first to see the appropriate input field
                            </p>
                        </div>
                        
                        <div class="space-y-2">
                            <div class="flex items-center mb-3">
                                <div class="w-8 h-8 bg-gradient-to-r from-bote-green-500 to-bote-green-600 rounded-lg flex items-center justify-center mr-3">
                                    <i data-lucide="calendar" class="w-4 h-4 text-white"></i>
                                </div>
                                <label class="block text-lg font-semibold text-bote-stone-900">Preferred Pickup Date & Time</label>
                            </div>
                            <input type="datetime-local" name="preferred_date" required 
                                   min="<?php echo date('Y-m-d\TH:i', strtotime('+1 day')); ?>"
                                   class="form-input w-full px-6 py-4 border-2 border-bote-stone-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-bote-green-500 focus:ring-opacity-30 focus:border-bote-green-500 text-lg bg-bote-stone-50 hover:bg-white transition-all duration-300">
                            <p class="text-sm text-bote-stone-500 mt-2 flex items-center">
                                <i data-lucide="clock" class="w-4 h-4 mr-2"></i>
                                We'll contact you within 24 hours to confirm
                            </p>
                        </div>
                    </div>
                                        <!-- Enhanced Points Calculation -->
                    <div id="points_preview" class="mb-10 hidden">
                        <div class="bg-gradient-to-r from-yellow-50 via-orange-50 to-yellow-50 border-2 border-orange-200 rounded-2xl p-6 shadow-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center mr-4">
                                        <i data-lucide="coins" class="w-6 h-6 text-white"></i>
                                    </div>
                                    <div>
                                        <p class="text-xl font-bold text-bote-stone-900">Estimated Points Reward</p>
                                        <p class="text-bote-stone-600">Based on current weight and material type</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-4xl font-bold text-orange-600" id="estimated_points">0</p>
                                    <p class="text-sm text-orange-500 font-medium">Points</p>
                                </div>
                            </div>
                            <div class="mt-4 bg-white bg-opacity-60 rounded-xl p-3">
                                <p class="text-sm text-bote-stone-700 flex items-center">
                                    <i data-lucide="info" class="w-4 h-4 mr-2 text-orange-500"></i>
                                    Final points may vary based on actual weight verification during pickup
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Pickup Type Selection -->
                    <div class="mb-10">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-600 rounded-xl flex items-center justify-center mr-4">
                                <i data-lucide="truck" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <label class="block text-xl font-semibold text-bote-stone-900">Pickup Method</label>
                                <p class="text-bote-stone-600 mt-1">Choose how you want your recyclables collected</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="pickup-type-card border-2 border-bote-stone-200 rounded-2xl p-6 cursor-pointer transition-all duration-300 hover:border-bote-green-500 hover:shadow-lg" onclick="selectPickupType('Home', this)">
                                <input type="radio" name="pickup_type" value="Home" id="pickup_home" class="hidden">
                                <div class="text-center">
                                    <div class="w-16 h-16 bg-gradient-to-br from-bote-green-400 to-bote-green-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <i data-lucide="home" class="w-8 h-8 text-white"></i>
                                    </div>
                                    <h3 class="font-bold text-bote-stone-900 mb-2 text-lg">Home Pickup</h3>
                                    <p class="text-bote-stone-600 text-sm">We'll collect from your home address</p>
                                    <div class="mt-3 bg-bote-green-50 rounded-lg px-3 py-2">
                                        <p class="text-bote-green-700 text-xs font-medium">ðŸ“ GPS location required</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="pickup-type-card border-2 border-bote-stone-200 rounded-2xl p-6 cursor-pointer transition-all duration-300 hover:border-bote-info-500 hover:shadow-lg" onclick="selectPickupType('CollectionPoint', this)">
                                <input type="radio" name="pickup_type" value="CollectionPoint" id="pickup_collection" class="hidden">
                                <div class="text-center">
                                    <div class="w-16 h-16 bg-gradient-to-br from-bote-info-400 to-bote-info-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <i data-lucide="map-pin" class="w-8 h-8 text-white"></i>
                                    </div>
                                    <h3 class="font-bold text-bote-stone-900 mb-2 text-lg">Collection Point</h3>
                                    <p class="text-bote-stone-600 text-sm">Drop off at designated locations</p>
                                    <div class="mt-3 bg-bote-info-50 rounded-lg px-3 py-2">
                                        <p class="text-bote-info-700 text-xs font-medium">ðŸ¢ Choose from available points</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Collection Point Selection (Hidden by default) -->
                        <div id="collectionPointSection" class="hidden bg-gradient-to-br from-bote-info-50 via-bote-info-50 to-bote-info-50 border-2 border-bote-info-200 rounded-2xl p-6">
                            <!-- Header with Enhanced Design -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-bote-info-500 to-bote-info-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                                        <i data-lucide="map-pin" class="w-6 h-6 text-white"></i>
                                    </div>
                                    <div>
                                        <label class="block text-xl font-bold text-bote-info-900">Select Collection Point</label>
                                        <p class="text-bote-info-700 text-sm mt-1">Choose the most convenient drop-off location</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button type="button" onclick="refreshCollectionPoints()" class="px-3 py-2 bg-bote-green-500 text-white rounded-lg hover:bg-bote-green-600 transition-colors duration-300 flex items-center space-x-2">
                                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                        <span class="text-sm">Refresh</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Search and Filter Bar -->
                            <div class="mb-6 bg-white rounded-xl p-4 shadow-sm border border-bote-info-100">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-1 relative">
                                        <i data-lucide="search" class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-bote-info-500"></i>
                                        <input type="text" id="collectionPointSearch" placeholder="Search collection points..." 
                                               class="w-full pl-10 pr-4 py-3 border border-bote-info-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent"
                                               onkeyup="filterCollectionPoints()">
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <label class="text-sm font-medium text-bote-info-800">Sort by:</label>
                                        <select id="collectionPointSort" onchange="sortCollectionPoints()" class="px-3 py-2 border border-bote-info-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500">
                                            <option value="name">Name</option>
                                            <option value="distance">Distance</option>
                                            <option value="availability">Availability</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Collection Points Grid -->
                            <div id="collectionPointsGrid" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <?php foreach ($collection_points as $point): ?>
                                    <div class="collection-point-card bg-white rounded-xl p-4 border-2 border-bote-info-100 hover:border-bote-info-300 cursor-pointer transition-all duration-300 hover:shadow-lg"
                                         onclick="selectCollectionPointCard(<?php echo (int) $point['point_id']; ?>, <?php echo json_encode($point['name'], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS); ?>, <?php echo (float) $point['latitude']; ?>, <?php echo (float) $point['longitude']; ?>, <?php echo json_encode($point['description'] ?? '', JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS); ?>, this)"
                                         data-name="<?php echo htmlspecialchars(strtolower($point['name']), ENT_QUOTES, 'UTF-8'); ?>"
                                         data-description="<?php echo htmlspecialchars(strtolower($point['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                         data-lat="<?php echo (float) $point['latitude']; ?>"
                                         data-lng="<?php echo (float) $point['longitude']; ?>"
                                    >
                                        
                                        <!-- Card Header -->
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-gradient-to-br from-bote-info-400 to-bote-info-600 rounded-lg flex items-center justify-center mr-3">
                                                    <i data-lucide="map-pin" class="w-5 h-5 text-white"></i>
                                                </div>
                                                <div>
                                                    <h3 class="font-bold text-bote-stone-900 text-lg"><?php echo htmlspecialchars($point['name']); ?></h3>
                                                    <p class="text-sm text-bote-stone-600">Collection Point</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 py-1 bg-bote-green-100 text-bote-green-800 text-xs font-medium rounded-full">
                                                    <i data-lucide="check-circle" class="w-3 h-3 inline mr-1"></i>
                                                    Available
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Card Content -->
                                        <div class="space-y-2 mb-4">
                                            <?php if ($point['description']): ?>
                                                <p class="text-sm text-bote-stone-700 flex items-start">
                                                    <i data-lucide="info" class="w-4 h-4 mr-2 mt-0.5 text-bote-info-500"></i>
                                                    <?php echo htmlspecialchars($point['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="flex items-center text-sm text-bote-stone-600">
                                                <i data-lucide="clock" class="w-4 h-4 mr-2 text-bote-info-500"></i>
                                                <span>Operating Hours: 6:00 AM - 8:00 PM</span>
                                            </div>
                                            
                                            <div class="flex items-center text-sm text-bote-stone-600">
                                                <i data-lucide="users" class="w-4 h-4 mr-2 text-bote-info-500"></i>
                                                <span>Capacity: 50 residents/day</span>
                                            </div>
                                            
                                            <div class="flex items-center text-sm text-bote-stone-600">
                                                <i data-lucide="navigation" class="w-4 h-4 mr-2 text-bote-info-500"></i>
                                                <span>Distance: <span class="distance-indicator">Calculating...</span></span>
                                            </div>
                                        </div>

                                        <!-- Card Footer -->
                                        <div class="flex items-center justify-between pt-3 border-t border-bote-stone-100">
                                            <div class="flex items-center text-xs text-bote-stone-500">
                                                <i data-lucide="calendar" class="w-3 h-3 mr-1"></i>
                                                <span>Next available: Today</span>
                                            </div>
                                            <div class="flex items-center text-xs text-bote-stone-500">
                                                <i data-lucide="star" class="w-3 h-3 mr-1 text-yellow-500"></i>
                                                <span>4.8/5 rating</span>
                                            </div>
                                        </div>

                                        <!-- Hidden Radio Input -->
                                        <input type="radio" name="point_id" value="<?php echo e($point['point_id']); ?>" class="hidden">
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Selected Collection Point Details -->
                            <div id="selectedPointDetails" class="hidden bg-gradient-to-r from-bote-info-100 to-bote-info-100 border border-bote-info-300 rounded-xl p-4 mb-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-bote-green-500 rounded-lg flex items-center justify-center mr-3">
                                            <i data-lucide="check-circle" class="w-4 h-4 text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-bote-info-900" id="selectedPointName">Selected Collection Point</h4>
                                            <p class="text-bote-info-700 text-sm" id="selectedPointDescription">Ready for scheduling</p>
                                        </div>
                                    </div>
                                    <button type="button" onclick="clearCollectionPointSelection()" class="text-bote-info-600 hover:text-bote-info-800">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Enhanced Collection Point Info -->
                            <div class="bg-gradient-to-r from-bote-info-100 to-bote-info-100 border border-bote-info-300 rounded-xl p-4 mb-4">
                                <div class="flex items-start">
                                    <div class="w-8 h-8 bg-bote-info-500 rounded-lg flex items-center justify-center mr-3 mt-0.5">
                                        <i data-lucide="info" class="w-4 h-4 text-white"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-bote-info-900 mb-2">How Collection Point Pickup Works</h4>
                                        <div class="space-y-2 text-sm text-bote-info-700">
                                            <div class="flex items-start">
                                                <i data-lucide="calendar" class="w-4 h-4 mr-2 mt-0.5"></i>
                                                <span>Select your preferred date and time above</span>
                                            </div>
                                            <div class="flex items-start">
                                                <i data-lucide="map-pin" class="w-4 h-4 mr-2 mt-0.5"></i>
                                                <span>Meet the worker at the selected collection point</span>
                                            </div>
                                            <div class="flex items-start">
                                                <i data-lucide="handshake" class="w-4 h-4 mr-2 mt-0.5"></i>
                                                <span>Hand over your recyclables to the worker</span>
                                            </div>
                                            <div class="flex items-start">
                                                <i data-lucide="coins" class="w-4 h-4 mr-2 mt-0.5"></i>
                                                <span>Receive points immediately after verification</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Enhanced Collection Point Map -->
                            <div class="bg-white rounded-xl p-4 shadow-sm border border-bote-info-100">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-semibold text-bote-stone-900 flex items-center">
                                        <i data-lucide="map" class="w-4 h-4 mr-2 text-bote-info-500"></i>
                                        Collection Point Location
                                    </h4>
                                    <div class="flex items-center space-x-2">
                                        <button type="button" onclick="centerMapOnSelectedPoint()" class="px-3 py-1 bg-bote-green-500 text-white text-xs rounded-lg hover:bg-bote-green-600">
                                            <i data-lucide="crosshair" class="w-3 h-3 inline mr-1"></i>
                                            Center
                                        </button>
                                        <button type="button" onclick="getDirectionsToPoint()" class="px-3 py-1 bg-bote-green-500 text-white text-xs rounded-lg hover:bg-bote-green-600">
                                            <i data-lucide="navigation" class="w-3 h-3 inline mr-1"></i>
                                            Directions
                                        </button>
                                    </div>
                                </div>
                                <div id="collectionPointMap" style="height: 300px; border-radius: 12px; overflow: hidden;" class="border-2 border-bote-info-200"></div>
                            </div>
                        </div>

                        <!-- GPS Location Section (Hidden by default) -->
                        <div id="gpsSection" class="hidden bg-bote-green-50 border-2 border-bote-green-200 rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-bote-green-500 rounded-lg flex items-center justify-center mr-3">
                                        <i data-lucide="map-pin" class="w-4 h-4 text-white"></i>
                                    </div>
                                    <div>
                                        <label class="block text-lg font-semibold text-bote-green-900">Your Location</label>
                                        <p class="text-bote-green-700 text-sm">We'll use this for pickup navigation</p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button type="button" onclick="getCurrentLocation()" class="px-4 py-2 bg-bote-green-500 text-white rounded-lg hover:bg-bote-green-600 transition-colors duration-300 flex items-center space-x-2">
                                        <i data-lucide="crosshair" class="w-4 h-4"></i>
                                        <span>Auto-Detect</span>
                                    </button>
                                    <?php if ($user_coords && $user_coords['latitude'] && $user_coords['longitude']): ?>
                                    <button type="button" onclick="useUserLocation()" class="px-4 py-2 bg-bote-green-500 text-white rounded-lg hover:bg-bote-green-600 transition-colors duration-300 flex items-center space-x-2">
                                        <i data-lucide="user" class="w-4 h-4"></i>
                                        <span>Use Profile</span>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-bote-green-800 mb-2">Latitude</label>
                                    <input type="number" step="any" name="latitude" id="latitude" readonly 
                                           class="w-full px-4 py-3 border border-bote-green-200 rounded-lg bg-white text-bote-stone-700">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-bote-green-800 mb-2">Longitude</label>
                                    <input type="number" step="any" name="longitude" id="longitude" readonly 
                                           class="w-full px-4 py-3 border border-bote-green-200 rounded-lg bg-white text-bote-stone-700">
                                </div>
                            </div>
                            
                            <div id="locationStatus" class="text-sm text-bote-green-600 flex items-center mb-4">
                                <i data-lucide="info" class="w-4 h-4 mr-2"></i>
                                <span>Choose location detection method above</span>
                            </div>
                            
                            <!-- GPS Denied Warning -->
                            <div id="gpsWarning" class="hidden bg-yellow-50 border-2 border-yellow-200 rounded-xl p-4 mb-4">
                                <div class="flex items-center text-yellow-800">
                                    <i data-lucide="alert-triangle" class="w-5 h-5 mr-2"></i>
                                    <div>
                                        <p class="font-semibold">GPS Location Required</p>
                                        <p class="text-sm">Please enable location services or use collection point pickup instead.</p>
                                        <button type="button" onclick="switchToCollectionPoint()" class="mt-2 px-3 py-1 bg-yellow-500 text-white rounded text-sm hover:bg-yellow-600">
                                            Switch to Collection Point
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Map Container -->
                            <div class="mt-4">
                                <div id="map" style="height: 300px; border-radius: 12px; overflow: hidden;" class="border-2 border-bote-green-200"></div>
                            </div>
                        </div>

                        <!-- Hidden input for user coordinates -->
                        <input type="hidden" id="userLat" value="<?php echo e($user_coords['latitude'] ?? ''); ?>">
                        <input type="hidden" id="userLng" value="<?php echo e($user_coords['longitude'] ?? ''); ?>">
                    </div>

                    <!-- Enhanced Tips Section -->
                    <div class="mb-10">
                        <div class="bg-gradient-to-br from-bote-info-50 via-bote-info-50 to-bote-info-50 border-2 border-bote-info-200 rounded-2xl p-8 shadow-lg">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-gradient-to-r from-bote-info-500 to-bote-info-600 rounded-xl flex items-center justify-center mr-4">
                                    <i data-lucide="lightbulb" class="w-6 h-6 text-white"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-bote-info-900">Preparation Guidelines</h3>
                                    <p class="text-bote-info-700">Follow these tips for a successful pickup</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div class="flex items-start bg-white bg-opacity-60 rounded-xl p-4">
                                        <div class="w-8 h-8 bg-bote-green-500 rounded-lg flex items-center justify-center mr-3 mt-0.5">
                                            <i data-lucide="check" class="w-4 h-4 text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-bote-info-900">Clean Materials</h4>
                                            <p class="text-sm text-bote-info-700">Rinse all containers and remove labels when possible</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start bg-white bg-opacity-60 rounded-xl p-4">
                                        <div class="w-8 h-8 bg-bote-green-500 rounded-lg flex items-center justify-center mr-3 mt-0.5">
                                            <i data-lucide="check" class="w-4 h-4 text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-bote-info-900">Separate by Type</h4>
                                            <p class="text-sm text-bote-info-700">Keep different materials in separate bags or containers</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="flex items-start bg-white bg-opacity-60 rounded-xl p-4">
                                        <div class="w-8 h-8 bg-bote-green-500 rounded-lg flex items-center justify-center mr-3 mt-0.5">
                                            <i data-lucide="check" class="w-4 h-4 text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-bote-info-900">Be Available</h4>
                                            <p class="text-sm text-bote-info-700">Ensure someone is present during scheduled pickup time</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start bg-white bg-opacity-60 rounded-xl p-4">
                                        <div class="w-8 h-8 bg-bote-green-500 rounded-lg flex items-center justify-center mr-3 mt-0.5">
                                            <i data-lucide="check" class="w-4 h-4 text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-bote-info-900">Accurate Weight</h4>
                                            <p class="text-sm text-bote-info-700">Provide realistic weight estimates for efficient service</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 bg-gradient-to-r from-bote-info-500 to-bote-info-600 rounded-xl p-4 text-white">
                                <p class="text-center font-medium flex items-center justify-center">
                                    <i data-lucide="phone" class="w-5 h-5 mr-2"></i>
                                    Need help? Contact us at (123) 456-7890 or email support@botebank.com
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Submit Section -->
                    <div class="flex flex-col sm:flex-row justify-end space-y-4 sm:space-y-0 sm:space-x-6">
                        <a href="resident_dashboard.php" class="px-8 py-4 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-800 rounded-2xl font-semibold transition-all duration-300 text-center hover:shadow-lg hover:scale-105">
                            <i data-lucide="arrow-left" class="w-5 h-5 inline mr-2"></i>
                            Cancel
                        </a>
                        <button type="submit" class="px-10 py-4 bg-gradient-to-r from-bote-green-500 via-bote-green-600 to-bote-green-600 hover:from-bote-green-600 hover:via-bote-green-700 hover:to-bote-green-700 text-white rounded-2xl font-semibold transition-all duration-300 flex items-center justify-center space-x-3 shadow-xl hover:shadow-2xl hover:scale-105 pulse-ring" id="submitBtn">
                            <i data-lucide="calendar-check" class="w-5 h-5"></i>
                            <span>Schedule Pickup Request</span>
                            <div class="hidden" id="submitLoader">
                                <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script type="application/json" id="new-request-data">
        <?php echo $json_new_request_data; ?>
    </script>
