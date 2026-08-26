<?php
require_once '../config/database.php';
require_once '../includes/system_functions.php';
require '../includes/auth_res.php'; // Ensure only resident can access

$user_id = $_SESSION['user_id'];

// Get recyclable types with images
$stmt = prepare_statement($conn, "SELECT * FROM recyclabletypes ORDER BY name ASC");
$stmt->execute();
$recyclable_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get collection points with coordinates
$stmt = prepare_statement($conn, "SELECT point_id, name, description, latitude, longitude FROM collection_points ORDER BY name ASC");
$stmt->execute();
$collection_points = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user coordinates for fallback
$stmt = prepare_statement($conn, "SELECT latitude, longitude FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_coords = $stmt->get_result()->fetch_assoc();

// Get user statistics
$stmt = prepare_statement($conn, "SELECT COUNT(*) as total_requests FROM pickuprequests WHERE resident_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_requests = $stmt->get_result()->fetch_assoc()['total_requests'];

$stmt = prepare_statement($conn, "SELECT COALESCE(SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE -points END), 0) as total_points FROM pointsledger WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_points = $stmt->get_result()->fetch_assoc()['total_points'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Pickup Request - BoteBank</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="assets/css/new_request.css">
    
    <!-- JavaScript is handled internally -->
</head>
<body class="bg-gray-50">
    <?php include 'resident_sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="max-w-5xl mx-auto">
            <!-- Animated Header -->
            <div class="mb-10 text-center">
                <div class="floating-animation inline-block mb-4">
                    <div class="w-20 h-20 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full flex items-center justify-center pulse-ring">
                        <i data-lucide="recycle" class="w-10 h-10 text-white"></i>
                    </div>
                </div>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent mb-3">
                    Schedule New Pickup
                </h1>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Turn your recyclables into rewards! Request a pickup and contribute to a greener future while earning valuable points.
                </p>
                <div class="mt-6 flex justify-center space-x-8 text-sm text-gray-500">
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
                <div class="error-shake bg-gradient-to-r from-red-50 to-pink-50 border-2 border-red-200 text-red-800 px-6 py-4 rounded-2xl mb-8 shadow-lg">
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
                <div class="success-enter bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 text-green-800 px-6 py-4 rounded-2xl mb-8 shadow-lg">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mr-4">
                            <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg">Request Submitted Successfully!</h3>
                            <p class="text-green-700"><?php echo $success_message; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Enhanced Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                <div class="bg-gradient-to-br from-green-500 via-green-600 to-emerald-700 rounded-2xl p-8 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                                    <i data-lucide="coins" class="w-8 h-8"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-3xl font-bold"><?php echo number_format($total_points); ?></p>
                                    <p class="text-green-100 text-lg">Points Balance</p>
                                </div>
                            </div>
                        </div>
                        <div class="text-right opacity-20">
                            <i data-lucide="trending-up" class="w-16 h-16"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-green-100">
                        <i data-lucide="arrow-up" class="w-4 h-4 mr-2"></i>
                        <span class="text-sm">Ready to earn more rewards</span>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-700 rounded-2xl p-8 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                                    <i data-lucide="truck" class="w-8 h-8"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-3xl font-bold"><?php echo $total_requests; ?></p>
                                    <p class="text-blue-100 text-lg">Total Requests</p>
                                </div>
                            </div>
                        </div>
                        <div class="text-right opacity-20">
                            <i data-lucide="calendar" class="w-16 h-16"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-blue-100">
                        <i data-lucide="clock" class="w-4 h-4 mr-2"></i>
                        <span class="text-sm">Reliable pickup service</span>
                    </div>
                </div>
            </div>

            <!-- Enhanced Pickup Request Form -->
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-green-500 via-green-600 to-emerald-600 px-8 py-6 relative overflow-hidden">
                    <div class="shimmer absolute inset-0"></div>
                    <div class="relative z-10">
                        <h2 class="text-2xl font-bold text-white flex items-center">
                            <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                                <i data-lucide="plus-circle" class="w-6 h-6"></i>
                            </div>
                            Create Pickup Request
                        </h2>
                        <p class="text-green-100 mt-2">Fill out the details below to schedule your material pickup</p>
                    </div>
                </div>
                
                <form method="POST" action="resident_save_request.php" class="p-8" id="pickupForm">
                    <!-- Enhanced Material Type Selection -->
                    <div class="mb-10">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mr-4">
                                <i data-lucide="package" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <label class="block text-xl font-semibold text-gray-900">Select Material Type</label>
                                <p class="text-gray-600 mt-1">Choose the type of recyclable material you want to submit</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($recyclable_types as $type): ?>
                                <div class="material-card gradient-border rounded-2xl p-6 transition-all duration-300" onclick="selectMaterial(<?php echo $type['type_id']; ?>, '<?php echo htmlspecialchars($type['name']); ?>', '<?php echo $type['unit']; ?>', <?php echo $type['points_per_kg'] ?? 0; ?>, <?php echo $type['points_per_unit'] ?? 0; ?>)">
                                    <input type="radio" name="type_id" value="<?php echo $type['type_id']; ?>" class="hidden" required>
                                    <div class="selection-check">
                                        <i data-lucide="check" class="w-4 h-4 text-white"></i>
                                    </div>
                                    <div class="text-center">
                                        <!-- Material Image -->
                                        <div class="w-20 h-20 bg-gradient-to-br from-green-400 via-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg overflow-hidden">
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
                                        <h3 class="font-bold text-gray-900 mb-2 text-lg"><?php echo htmlspecialchars($type['name']); ?></h3>
                                        <div class="bg-gradient-to-r from-green-100 to-emerald-100 rounded-xl px-4 py-2 mb-3">
                                            <p class="text-sm font-semibold text-green-700">
                                                <?php if ($type['unit'] === 'kg' && $type['points_per_kg'] > 0): ?>
                                                    <i data-lucide="coins" class="w-4 h-4 inline mr-1"></i>
                                                    <?php echo $type['points_per_kg']; ?> points/kg
                                                <?php elseif ($type['unit'] === 'pcs' && $type['points_per_unit'] > 0): ?>
                                                    <i data-lucide="coins" class="w-4 h-4 inline mr-1"></i>
                                                    <?php echo $type['points_per_unit']; ?> points/piece
                                                <?php else: ?>
                                                    <i data-lucide="phone" class="w-4 h-4 inline mr-1"></i>
                                                    Contact for pricing
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider">
                                            Unit: <?php echo strtoupper($type['unit']); ?>
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
                                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mr-3">
                                    <i data-lucide="weight" class="w-4 h-4 text-white"></i>
                                </div>
                                <label class="block text-lg font-semibold text-gray-900" id="materialInputLabel">Select Material First</label>
                            </div>
                            
                            <!-- Weight Input (for kg materials) -->
                            <div class="relative hidden" id="weightInputDiv">
                                <input type="number" name="weight_kg" step="0.1" min="10"
                                       id="weight_input"
                                       class="form-input w-full px-6 py-4 border-2 border-gray-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-green-500 focus:ring-opacity-30 focus:border-green-500 text-lg bg-gray-50 hover:bg-white transition-all duration-300"
                                       placeholder="Enter weight in kilograms (min 10kg)"
                                       oninput="calculatePoints()">
                                <div class="absolute inset-y-0 right-0 pr-6 flex items-center pointer-events-none">
                                    <span class="text-gray-500 font-medium">kg</span>
                                </div>
                            </div>
                            
                            <!-- Quantity Input (for pcs materials) -->
                            <div class="relative hidden" id="quantityInputDiv">
                                <input type="number" name="quantity_pcs" step="1" min="30"
                                       id="quantity_input"
                                       class="form-input w-full px-6 py-4 border-2 border-gray-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-blue-500 focus:ring-opacity-30 focus:border-blue-500 text-lg bg-gray-50 hover:bg-white transition-all duration-300"
                                       placeholder="Enter number of pieces (min 30pcs)"
                                       oninput="calculatePoints()">
                                <div class="absolute inset-y-0 right-0 pr-6 flex items-center pointer-events-none">
                                    <span class="text-gray-500 font-medium">pcs</span>
                                </div>
                            </div>
                            
                            <p class="text-sm text-gray-500 mt-2 flex items-center" id="materialInputHint">
                                <i data-lucide="info" class="w-4 h-4 mr-2"></i>
                                Please select a material type first to see the appropriate input field
                            </p>
                        </div>
                        
                        <div class="space-y-2">
                            <div class="flex items-center mb-3">
                                <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                                    <i data-lucide="calendar" class="w-4 h-4 text-white"></i>
                                </div>
                                <label class="block text-lg font-semibold text-gray-900">Preferred Pickup Date & Time</label>
                            </div>
                            <input type="datetime-local" name="preferred_date" required 
                                   min="<?php echo date('Y-m-d\TH:i', strtotime('+1 day')); ?>"
                                   class="form-input w-full px-6 py-4 border-2 border-gray-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-purple-500 focus:ring-opacity-30 focus:border-purple-500 text-lg bg-gray-50 hover:bg-white transition-all duration-300">
                            <p class="text-sm text-gray-500 mt-2 flex items-center">
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
                                        <p class="text-xl font-bold text-gray-900">Estimated Points Reward</p>
                                        <p class="text-gray-600">Based on current weight and material type</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-4xl font-bold text-orange-600" id="estimated_points">0</p>
                                    <p class="text-sm text-orange-500 font-medium">Points</p>
                                </div>
                            </div>
                            <div class="mt-4 bg-white bg-opacity-60 rounded-xl p-3">
                                <p class="text-sm text-gray-700 flex items-center">
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
                                <label class="block text-xl font-semibold text-gray-900">Pickup Method</label>
                                <p class="text-gray-600 mt-1">Choose how you want your recyclables collected</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="pickup-type-card border-2 border-gray-200 rounded-2xl p-6 cursor-pointer transition-all duration-300 hover:border-green-500 hover:shadow-lg" onclick="selectPickupType('Home')">
                                <input type="radio" name="pickup_type" value="Home" id="pickup_home" class="hidden">
                                <div class="text-center">
                                    <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <i data-lucide="home" class="w-8 h-8 text-white"></i>
                                    </div>
                                    <h3 class="font-bold text-gray-900 mb-2 text-lg">Home Pickup</h3>
                                    <p class="text-gray-600 text-sm">We'll collect from your home address</p>
                                    <div class="mt-3 bg-green-50 rounded-lg px-3 py-2">
                                        <p class="text-green-700 text-xs font-medium">📍 GPS location required</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="pickup-type-card border-2 border-gray-200 rounded-2xl p-6 cursor-pointer transition-all duration-300 hover:border-blue-500 hover:shadow-lg" onclick="selectPickupType('CollectionPoint')">
                                <input type="radio" name="pickup_type" value="CollectionPoint" id="pickup_collection" class="hidden">
                                <div class="text-center">
                                    <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <i data-lucide="map-pin" class="w-8 h-8 text-white"></i>
                                    </div>
                                    <h3 class="font-bold text-gray-900 mb-2 text-lg">Collection Point</h3>
                                    <p class="text-gray-600 text-sm">Drop off at designated locations</p>
                                    <div class="mt-3 bg-blue-50 rounded-lg px-3 py-2">
                                        <p class="text-blue-700 text-xs font-medium">🏢 Choose from available points</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Collection Point Selection (Hidden by default) -->
                        <div id="collectionPointSection" class="hidden bg-gradient-to-br from-blue-50 via-indigo-50 to-blue-50 border-2 border-blue-200 rounded-2xl p-6">
                            <!-- Header with Enhanced Design -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                                        <i data-lucide="map-pin" class="w-6 h-6 text-white"></i>
                                    </div>
                                    <div>
                                        <label class="block text-xl font-bold text-blue-900">Select Collection Point</label>
                                        <p class="text-blue-700 text-sm mt-1">Choose the most convenient drop-off location</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button type="button" onclick="refreshCollectionPoints()" class="px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-300 flex items-center space-x-2">
                                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                        <span class="text-sm">Refresh</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Search and Filter Bar -->
                            <div class="mb-6 bg-white rounded-xl p-4 shadow-sm border border-blue-100">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-1 relative">
                                        <i data-lucide="search" class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-blue-500"></i>
                                        <input type="text" id="collectionPointSearch" placeholder="Search collection points..." 
                                               class="w-full pl-10 pr-4 py-3 border border-blue-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               onkeyup="filterCollectionPoints()">
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <label class="text-sm font-medium text-blue-800">Sort by:</label>
                                        <select id="collectionPointSort" onchange="sortCollectionPoints()" class="px-3 py-2 border border-blue-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                    <div class="collection-point-card bg-white rounded-xl p-4 border-2 border-blue-100 hover:border-blue-300 cursor-pointer transition-all duration-300 hover:shadow-lg" 
                                         onclick="selectCollectionPointCard(<?php echo $point['point_id']; ?>, '<?php echo htmlspecialchars($point['name']); ?>', <?php echo $point['latitude']; ?>, <?php echo $point['longitude']; ?>, '<?php echo htmlspecialchars($point['description']); ?>')"
                                         data-name="<?php echo strtolower($point['name']); ?>"
                                         data-description="<?php echo strtolower($point['description'] ?? ''); ?>"
                                         data-lat="<?php echo $point['latitude']; ?>"
                                         data-lng="<?php echo $point['longitude']; ?>"
                                    >
                                        
                                        <!-- Card Header -->
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center mr-3">
                                                    <i data-lucide="map-pin" class="w-5 h-5 text-white"></i>
                                                </div>
                                                <div>
                                                    <h3 class="font-bold text-gray-900 text-lg"><?php echo htmlspecialchars($point['name']); ?></h3>
                                                    <p class="text-sm text-gray-600">Collection Point</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                                                    <i data-lucide="check-circle" class="w-3 h-3 inline mr-1"></i>
                                                    Available
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Card Content -->
                                        <div class="space-y-2 mb-4">
                                            <?php if ($point['description']): ?>
                                                <p class="text-sm text-gray-700 flex items-start">
                                                    <i data-lucide="info" class="w-4 h-4 mr-2 mt-0.5 text-blue-500"></i>
                                                    <?php echo htmlspecialchars($point['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i data-lucide="clock" class="w-4 h-4 mr-2 text-blue-500"></i>
                                                <span>Operating Hours: 6:00 AM - 8:00 PM</span>
                                            </div>
                                            
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i data-lucide="users" class="w-4 h-4 mr-2 text-blue-500"></i>
                                                <span>Capacity: 50 residents/day</span>
                                            </div>
                                            
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i data-lucide="navigation" class="w-4 h-4 mr-2 text-blue-500"></i>
                                                <span>Distance: <span class="distance-indicator">Calculating...</span></span>
                                            </div>
                                        </div>

                                        <!-- Card Footer -->
                                        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                            <div class="flex items-center text-xs text-gray-500">
                                                <i data-lucide="calendar" class="w-3 h-3 mr-1"></i>
                                                <span>Next available: Today</span>
                                            </div>
                                            <div class="flex items-center text-xs text-gray-500">
                                                <i data-lucide="star" class="w-3 h-3 mr-1 text-yellow-500"></i>
                                                <span>4.8/5 rating</span>
                                            </div>
                                        </div>

                                        <!-- Hidden Radio Input -->
                                        <input type="radio" name="point_id" value="<?php echo $point['point_id']; ?>" class="hidden">
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Selected Collection Point Details -->
                            <div id="selectedPointDetails" class="hidden bg-gradient-to-r from-blue-100 to-indigo-100 border border-blue-300 rounded-xl p-4 mb-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                                            <i data-lucide="check-circle" class="w-4 h-4 text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-blue-900" id="selectedPointName">Selected Collection Point</h4>
                                            <p class="text-blue-700 text-sm" id="selectedPointDescription">Ready for scheduling</p>
                                        </div>
                                    </div>
                                    <button type="button" onclick="clearCollectionPointSelection()" class="text-blue-600 hover:text-blue-800">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Enhanced Collection Point Info -->
                            <div class="bg-gradient-to-r from-blue-100 to-indigo-100 border border-blue-300 rounded-xl p-4 mb-4">
                                <div class="flex items-start">
                                    <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center mr-3 mt-0.5">
                                        <i data-lucide="info" class="w-4 h-4 text-white"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-blue-900 mb-2">How Collection Point Pickup Works</h4>
                                        <div class="space-y-2 text-sm text-blue-700">
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
                            <div class="bg-white rounded-xl p-4 shadow-sm border border-blue-100">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-semibold text-gray-900 flex items-center">
                                        <i data-lucide="map" class="w-4 h-4 mr-2 text-blue-500"></i>
                                        Collection Point Location
                                    </h4>
                                    <div class="flex items-center space-x-2">
                                        <button type="button" onclick="centerMapOnSelectedPoint()" class="px-3 py-1 bg-blue-500 text-white text-xs rounded-lg hover:bg-blue-600">
                                            <i data-lucide="crosshair" class="w-3 h-3 inline mr-1"></i>
                                            Center
                                        </button>
                                        <button type="button" onclick="getDirectionsToPoint()" class="px-3 py-1 bg-green-500 text-white text-xs rounded-lg hover:bg-green-600">
                                            <i data-lucide="navigation" class="w-3 h-3 inline mr-1"></i>
                                            Directions
                                        </button>
                                    </div>
                                </div>
                                <div id="collectionPointMap" style="height: 300px; border-radius: 12px; overflow: hidden;" class="border-2 border-blue-200"></div>
                            </div>
                        </div>

                        <!-- GPS Location Section (Hidden by default) -->
                        <div id="gpsSection" class="hidden bg-green-50 border-2 border-green-200 rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                        <i data-lucide="map-pin" class="w-4 h-4 text-white"></i>
                                    </div>
                                    <div>
                                        <label class="block text-lg font-semibold text-green-900">Your Location</label>
                                        <p class="text-green-700 text-sm">We'll use this for pickup navigation</p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button type="button" onclick="getCurrentLocation()" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors duration-300 flex items-center space-x-2">
                                        <i data-lucide="crosshair" class="w-4 h-4"></i>
                                        <span>Auto-Detect</span>
                                    </button>
                                    <?php if ($user_coords && $user_coords['latitude'] && $user_coords['longitude']): ?>
                                    <button type="button" onclick="useUserLocation()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-300 flex items-center space-x-2">
                                        <i data-lucide="user" class="w-4 h-4"></i>
                                        <span>Use Profile</span>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-green-800 mb-2">Latitude</label>
                                    <input type="number" step="any" name="latitude" id="latitude" readonly 
                                           class="w-full px-4 py-3 border border-green-200 rounded-lg bg-white text-gray-700">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-800 mb-2">Longitude</label>
                                    <input type="number" step="any" name="longitude" id="longitude" readonly 
                                           class="w-full px-4 py-3 border border-green-200 rounded-lg bg-white text-gray-700">
                                </div>
                            </div>
                            
                            <div id="locationStatus" class="text-sm text-green-600 flex items-center mb-4">
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
                                <div id="map" style="height: 300px; border-radius: 12px; overflow: hidden;" class="border-2 border-green-200"></div>
                            </div>
                        </div>

                        <!-- Hidden input for user coordinates -->
                        <input type="hidden" id="userLat" value="<?php echo $user_coords['latitude'] ?? ''; ?>">
                        <input type="hidden" id="userLng" value="<?php echo $user_coords['longitude'] ?? ''; ?>">
                    </div>



                    <!-- Enhanced Tips Section -->
                    <div class="mb-10">
                        <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-blue-50 border-2 border-blue-200 rounded-2xl p-8 shadow-lg">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                                    <i data-lucide="lightbulb" class="w-6 h-6 text-white"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-blue-900">Preparation Guidelines</h3>
                                    <p class="text-blue-700">Follow these tips for a successful pickup</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div class="flex items-start bg-white bg-opacity-60 rounded-xl p-4">
                                        <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center mr-3 mt-0.5">
                                            <i data-lucide="check" class="w-4 h-4 text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-blue-900">Clean Materials</h4>
                                            <p class="text-sm text-blue-700">Rinse all containers and remove labels when possible</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start bg-white bg-opacity-60 rounded-xl p-4">
                                        <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center mr-3 mt-0.5">
                                            <i data-lucide="check" class="w-4 h-4 text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-blue-900">Separate by Type</h4>
                                            <p class="text-sm text-blue-700">Keep different materials in separate bags or containers</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="flex items-start bg-white bg-opacity-60 rounded-xl p-4">
                                        <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center mr-3 mt-0.5">
                                            <i data-lucide="check" class="w-4 h-4 text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-blue-900">Be Available</h4>
                                            <p class="text-sm text-blue-700">Ensure someone is present during scheduled pickup time</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start bg-white bg-opacity-60 rounded-xl p-4">
                                        <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center mr-3 mt-0.5">
                                            <i data-lucide="check" class="w-4 h-4 text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-blue-900">Accurate Weight</h4>
                                            <p class="text-sm text-blue-700">Provide realistic weight estimates for efficient service</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-4 text-white">
                                <p class="text-center font-medium flex items-center justify-center">
                                    <i data-lucide="phone" class="w-5 h-5 mr-2"></i>
                                    Need help? Contact us at (123) 456-7890 or email support@botebank.com
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Submit Section -->
                    <div class="flex flex-col sm:flex-row justify-end space-y-4 sm:space-y-0 sm:space-x-6">
                        <a href="resident_dashboard.php" class="px-8 py-4 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-2xl font-semibold transition-all duration-300 text-center hover:shadow-lg hover:scale-105">
                            <i data-lucide="arrow-left" class="w-5 h-5 inline mr-2"></i>
                            Cancel
                        </a>
                        <button type="submit" class="px-10 py-4 bg-gradient-to-r from-green-500 via-green-600 to-emerald-600 hover:from-green-600 hover:via-green-700 hover:to-emerald-700 text-white rounded-2xl font-semibold transition-all duration-300 flex items-center justify-center space-x-3 shadow-xl hover:shadow-2xl hover:scale-105 pulse-ring" id="submitBtn">
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
    <script>

        lucide.createIcons();

        let selectedMaterial = null;
        let map = null;
        let userMarker = null;
        let collectionPointMap = null;
        let collectionPointMarker = null;
        let selectedPickupType = null;
        let collectionPointCoords = [];

        function selectMaterial(typeId, name, unit, pointsPerKg, pointsPerUnit) {
            // Remove selection from all cards with animation
            document.querySelectorAll('.material-card').forEach(card => {
                card.classList.remove('selected');
                card.style.transform = 'scale(1)';
            });
            
            // Add selection to clicked card with animation
            const selectedCard = event.currentTarget;
            selectedCard.classList.add('selected');
            selectedCard.style.transform = 'scale(1.05)';
            
            // Animate the selection
            setTimeout(() => {
                selectedCard.style.transform = 'scale(1)';
            }, 200);
            
            // Check the radio button
            selectedCard.querySelector('input[type="radio"]').checked = true;
            
            // Store selected material info
            selectedMaterial = {
                id: typeId,
                name: name,
                unit: unit,
                pointsPerKg: parseFloat(pointsPerKg) || 0,
                pointsPerUnit: parseFloat(pointsPerUnit) || 0
            };
            
            // Update input section based on material unit
            updateMaterialInputs(unit, name);
            
            // Show success feedback
            showMaterialSelectionFeedback(name);
            
            // Recalculate points
            calculatePoints();
            validateForm();
        }

        function updateMaterialInputs(unit, materialName) {
            const weightDiv = document.getElementById('weightInputDiv');
            const quantityDiv = document.getElementById('quantityInputDiv');
            const label = document.getElementById('materialInputLabel');
            const hint = document.getElementById('materialInputHint');
            const weightInput = document.getElementById('weight_input');
            const quantityInput = document.getElementById('quantity_input');
            
            // Clear previous values
            weightInput.value = '';
            quantityInput.value = '';
            
            // Remove required attribute from both
            weightInput.removeAttribute('required');
            quantityInput.removeAttribute('required');
            
            if (unit === 'kg') {
                // Show weight input, hide quantity
                weightDiv.classList.remove('hidden');
                quantityDiv.classList.add('hidden');
                weightInput.setAttribute('required', 'required');
                
                label.textContent = `Weight for ${materialName} (kg)`;
                hint.innerHTML = '<i data-lucide="info" class="w-4 h-4 mr-2"></i>Minimum requirement: 10 kilograms';
                
                // Update icon
                label.previousElementSibling.querySelector('i').setAttribute('data-lucide', 'weight');
                
            } else if (unit === 'pcs') {
                // Show quantity input, hide weight
                weightDiv.classList.add('hidden');
                quantityDiv.classList.remove('hidden');
                quantityInput.setAttribute('required', 'required');
                
                label.textContent = `Quantity for ${materialName} (pieces)`;
                hint.innerHTML = '<i data-lucide="info" class="w-4 h-4 mr-2"></i>Minimum requirement: 30 pieces';
                
                // Update icon
                label.previousElementSibling.querySelector('i').setAttribute('data-lucide', 'hash');
            }
            
            // Recreate lucide icons
            lucide.createIcons();
        }

        function selectPickupType(type) {
            selectedPickupType = type;
            console.log('Selected pickup type:', type);
            
            // Remove selection from all cards
            document.querySelectorAll('.pickup-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection to clicked card
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            let radioId;
            if (type === 'Home') {
                radioId = 'pickup_home';
            } else if (type === 'CollectionPoint') {
                radioId = 'pickup_collection';
            }
            console.log('Checking radio button:', radioId);
            const radioElement = document.getElementById(radioId);
            if (radioElement) {
                radioElement.checked = true;
                console.log('Radio button checked successfully');
            } else {
                console.error('Radio button not found:', radioId);
            }
            
            // Show/hide relevant sections
            const gpsSection = document.getElementById('gpsSection');
            const collectionPointSection = document.getElementById('collectionPointSection');
            
            console.log('GPS Section element:', gpsSection);
            console.log('Collection Point Section element:', collectionPointSection);
            
            if (type === 'Home') {
                console.log('Showing GPS section, hiding collection point section');
                if (gpsSection) {
                    gpsSection.classList.remove('hidden');
                    // Set GPS inputs as required for home pickup
                    const latInput = document.getElementById('latitude');
                    const lngInput = document.getElementById('longitude');
                    if (latInput) latInput.setAttribute('required', 'required');
                    if (lngInput) lngInput.setAttribute('required', 'required');
                }
                if (collectionPointSection) collectionPointSection.classList.add('hidden');
                
                // Clear any selected collection point and remove requirements
                document.querySelectorAll('.collection-point-card').forEach(card => {
                    card.classList.remove('selected');
                    const radio = card.querySelector('input[name="point_id"]');
                    if (radio) {
                        radio.checked = false;
                        radio.removeAttribute('required');
                    }
                });
                
                // Initialize map if not already done
                if (!map) {
                    setTimeout(initializeMap, 100);
                }
                
                // Try to get current location
                setTimeout(getCurrentLocation, 500);
            } else {
                console.log('Hiding GPS section, showing collection point section');
                if (gpsSection) gpsSection.classList.add('hidden');
                if (collectionPointSection) {
                    collectionPointSection.classList.remove('hidden');
                    console.log('Collection point section should now be visible');
                } else {
                    console.error('Collection point section element not found!');
                }
                
                // Clear GPS coordinates and remove requirements for collection point pickup
                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');
                if (latInput) {
                    latInput.value = '';
                    latInput.removeAttribute('required');
                }
                if (lngInput) {
                    lngInput.value = '';
                    lngInput.removeAttribute('required');
                }
                
                // Set collection point as required
                document.querySelectorAll('input[name="point_id"]').forEach(radio => {
                    radio.setAttribute('required', 'required');
                });
                
                // Hide GPS warning
                const gpsWarning = document.getElementById('gpsWarning');
                if (gpsWarning) gpsWarning.classList.add('hidden');
            }
            
            validateForm();
        }

        function initializeMap() {
            if (map) return;
            
            // Default center (you can change this to your city/area)
            const defaultLat = 6.4214;
            const defaultLng = 124.7811;
            
            map = L.map('map').setView([defaultLat, defaultLng], 14);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            // Add click event to map
            map.on('click', function(e) {
                updateLocation(e.latlng.lat, e.latlng.lng);
            });
        }

        function getCurrentLocation() {
            const locationStatus = document.getElementById('locationStatus');
            const gpsWarning = document.getElementById('gpsWarning');
            
            if (!navigator.geolocation) {
                showLocationError('Geolocation is not supported by this browser');
                return;
            }
            
            locationStatus.innerHTML = '<i data-lucide="loader" class="w-4 h-4 mr-2 animate-spin"></i><span>Getting your location...</span>';
            gpsWarning.classList.add('hidden');
            lucide.createIcons();
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    updateLocation(lat, lng);
                    
                    locationStatus.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4 mr-2"></i><span class="text-green-600">Location detected successfully</span>';
                    lucide.createIcons();
                },
                function(error) {
                    let errorMessage = 'Unable to get location';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Location access denied by user';
                            showGPSWarning();
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information unavailable';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'Location request timed out';
                            break;
                    }
                    
                    showLocationError(errorMessage);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        function useUserLocation() {
            const userLat = document.getElementById('userLat').value;
            const userLng = document.getElementById('userLng').value;
            
            if (userLat && userLng) {
                updateLocation(parseFloat(userLat), parseFloat(userLng));
                
                const locationStatus = document.getElementById('locationStatus');
                locationStatus.innerHTML = '<i data-lucide="user-check" class="w-4 h-4 mr-2"></i><span class="text-blue-600">Using your profile location</span>';
                lucide.createIcons();
            } else {
                showLocationError('No saved location in your profile');
            }
        }

        function showGPSWarning() {
            document.getElementById('gpsWarning').classList.remove('hidden');
            document.getElementById('locationStatus').innerHTML = '<i data-lucide="x-circle" class="w-4 h-4 mr-2"></i><span class="text-red-600">GPS access denied</span>';
            lucide.createIcons();
        }

        function showLocationError(message) {
            const locationStatus = document.getElementById('locationStatus');
            locationStatus.innerHTML = `<i data-lucide="x-circle" class="w-4 h-4 mr-2"></i><span class="text-red-600">${message}</span>`;
            lucide.createIcons();
        }

        function switchToCollectionPoint() {
            // Switch to collection point pickup type
            document.querySelectorAll('.pickup-type-card')[1].click();
        }

        // Enhanced Collection Point Functions
        let selectedCollectionPoint = null;
        let collectionPointCards = [];

        function selectCollectionPointCard(pointId, name, lat, lng, description) {
            // Remove selection from all cards
            document.querySelectorAll('.collection-point-card').forEach(card => {
                card.classList.remove('selected');
                card.style.transform = 'scale(1)';
                // Uncheck all radio buttons
                const radio = card.querySelector('input[name="point_id"]');
                if (radio) radio.checked = false;
            });
            
            // Add selection to clicked card
            const selectedCard = event.currentTarget;
            selectedCard.classList.add('selected');
            selectedCard.style.transform = 'scale(1.02)';
            
            // Check the radio button for this collection point
            const radioButton = selectedCard.querySelector('input[name="point_id"]');
            if (radioButton) {
                radioButton.checked = true;
                console.log('Collection point radio checked:', pointId);
            }
            
            // Store selected point info
            selectedCollectionPoint = {
                id: pointId,
                name: name,
                lat: lat,
                lng: lng,
                description: description
            };
            
            // Update selected point details
            document.getElementById('selectedPointName').textContent = name;
            document.getElementById('selectedPointDescription').textContent = description || 'Ready for scheduling';
            document.getElementById('selectedPointDetails').classList.remove('hidden');
            
            // Show on map
            showCollectionPointOnMap(lat, lng, name);
            
            // Calculate distance if user location is available
            calculateDistanceToPoint(lat, lng);
            
            validateForm();
        }

        function clearCollectionPointSelection() {
            selectedCollectionPoint = null;
            document.getElementById('selectedPointDetails').classList.add('hidden');
            document.querySelectorAll('.collection-point-card').forEach(card => {
                card.classList.remove('selected');
                card.style.transform = 'scale(1)';
                // Uncheck all radio buttons
                const radio = card.querySelector('input[name="point_id"]');
                if (radio) radio.checked = false;
            });
            hideCollectionPointMap();
            validateForm();
        }

        function refreshCollectionPoints() {
            // Simulate refresh animation
            const refreshBtn = event.target.closest('button');
            const icon = refreshBtn.querySelector('i');
            icon.style.animation = 'spin 1s linear infinite';
            
            setTimeout(() => {
                icon.style.animation = '';
                // In a real implementation, this would fetch updated data
                console.log('Collection points refreshed');
            }, 1000);
        }

        function filterCollectionPoints() {
            const searchTerm = document.getElementById('collectionPointSearch').value.toLowerCase();
            const cards = document.querySelectorAll('.collection-point-card');
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                const description = card.getAttribute('data-description');
                
                if (name.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function sortCollectionPoints() {
            const sortBy = document.getElementById('collectionPointSort').value;
            const grid = document.getElementById('collectionPointsGrid');
            const cards = Array.from(grid.querySelectorAll('.collection-point-card'));
            
            cards.sort((a, b) => {
                switch(sortBy) {
                    case 'name':
                        return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
                    case 'distance':
                        // Sort by distance (would need actual distance calculation)
                        return 0;
                    case 'availability':
                        // Sort by availability status
                        return 0;
                    default:
                        return 0;
                }
            });
            
            // Re-append sorted cards
            cards.forEach(card => grid.appendChild(card));
        }

        function calculateDistanceToPoint(pointLat, pointLng) {
            // Try to get user's profile coordinates
            const userLat = parseFloat(document.getElementById('userLat').value);
            const userLng = parseFloat(document.getElementById('userLng').value);
            
            if (userLat && userLng) {
                const distance = calculateDistance(userLat, userLng, pointLat, pointLng);
                
                // Update distance indicators
                document.querySelectorAll('.distance-indicator').forEach(indicator => {
                    indicator.textContent = `${distance.toFixed(1)} km`;
                });
            } else {
                document.querySelectorAll('.distance-indicator').forEach(indicator => {
                    indicator.textContent = 'Location unavailable';
                });
            }
        }

        function calculateDistance(lat1, lng1, lat2, lng2) {
            const R = 6371; // Earth's radius in kilometers
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLng/2) * Math.sin(dLng/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        function centerMapOnSelectedPoint() {
            if (selectedCollectionPoint && collectionPointMap) {
                collectionPointMap.setView([selectedCollectionPoint.lat, selectedCollectionPoint.lng], 16);
            }
        }

        function getDirectionsToPoint() {
            if (selectedCollectionPoint) {
                const url = `https://www.google.com/maps/dir/?api=1&destination=${selectedCollectionPoint.lat},${selectedCollectionPoint.lng}`;
                window.open(url, '_blank');
            }
        }

        function showCollectionPointOnMap(lat, lng, name) {
            const mapContainer = document.getElementById('collectionPointMap');
            mapContainer.classList.remove('hidden');
            
            if (!collectionPointMap) {
                collectionPointMap = L.map('collectionPointMap').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(collectionPointMap);
            } else {
                collectionPointMap.setView([lat, lng], 16);
            }
            
            // Remove existing marker
            if (collectionPointMarker) {
                collectionPointMap.removeLayer(collectionPointMarker);
            }
            
            // Add new marker
            collectionPointMarker = L.marker([lat, lng])
                .addTo(collectionPointMap)
                .bindPopup(`📍 ${name}`)
                .openPopup();
            
            // Refresh map size
            setTimeout(() => {
                collectionPointMap.invalidateSize();
            }, 100);
        }

        function hideCollectionPointMap() {
            const mapContainer = document.getElementById('collectionPointMap');
            mapContainer.classList.add('hidden');
        }

        function updateLocation(lat, lng) {
            // Update input fields
            document.getElementById('latitude').value = lat.toFixed(7);
            document.getElementById('longitude').value = lng.toFixed(7);
            
            // Update map if it exists
            if (map) {
                map.setView([lat, lng], 16);
                
                // Remove existing marker
                if (userMarker) {
                    map.removeLayer(userMarker);
                }
                
                // Add new marker
                userMarker = L.marker([lat, lng])
                    .addTo(map)
                    .bindPopup('📍 Pickup Location')
                    .openPopup();
            }
            
            validateForm();
        }

        function showMaterialSelectionFeedback(materialName) {
            const feedback = document.createElement('div');
            feedback.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-xl shadow-lg z-50 transform translate-x-full transition-transform duration-500';
            feedback.innerHTML = `
                <div class="flex items-center">
                    <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                    <span>Selected: ${materialName}</span>
                </div>
            `;
            document.body.appendChild(feedback);
            lucide.createIcons();
            
            setTimeout(() => feedback.style.transform = 'translateX(0)', 100);
            setTimeout(() => {
                feedback.style.transform = 'translateX(100%)';
                setTimeout(() => feedback.remove(), 500);
            }, 2000);
        }

        function calculatePoints() {
    const pointsPreview = document.getElementById('points_preview');
    const estimatedPoints = document.getElementById('estimated_points');
    
    if (!selectedMaterial) {
        pointsPreview.classList.add('hidden');
        return;
    }
    
    let inputValue = 0;
    let points = 0;
    
    if (selectedMaterial.unit === 'kg') {
        const weightInput = document.getElementById('weight_input');
        if (weightInput.value) {
            inputValue = parseFloat(weightInput.value);
            points = inputValue * selectedMaterial.pointsPerKg; // removed Math.floor
        }
    } else if (selectedMaterial.unit === 'pcs') {
        const quantityInput = document.getElementById('quantity_input');
        if (quantityInput.value) {
            inputValue = parseInt(quantityInput.value);
            points = inputValue * selectedMaterial.pointsPerUnit; // removed Math.floor
        }
    }
    
    if (inputValue > 0 && points > 0) {
        // Format with 2 decimals
        let formattedPoints = points.toFixed(2);

        // Animate number if you want decimals (update animateNumber to handle floats)
        animateNumber(estimatedPoints, parseFloat(estimatedPoints.textContent) || 0, points);

        // Or simply set the text without animation:
        // estimatedPoints.textContent = formattedPoints;

        pointsPreview.classList.remove('hidden');
        pointsPreview.style.animation = 'slideInRight 0.5s ease-out';
    } else {
        pointsPreview.classList.add('hidden');
    }
}


        function animateNumber(element, from, to) {
            const duration = 1000;
            const steps = 30;
            const stepValue = (to - from) / steps;
            let current = from;
            let step = 0;
            
            const timer = setInterval(() => {
                current += stepValue;
                element.textContent = Math.round(current).toLocaleString();
                step++;
                
                if (step >= steps) {
                    clearInterval(timer);
                    element.textContent = to.toLocaleString();
                }
            }, duration / steps);
        }

        // Enhanced form submission
        document.getElementById('pickupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Debug logging
            console.log('Pickup Type:', document.querySelector('input[name="pickup_type"]:checked')?.value);
            console.log('Collection Point:', document.querySelector('input[name="point_id"]:checked')?.value);
            console.log('Material Type:', selectedMaterial);
            console.log('Weight/Quantity:', 
                selectedMaterial?.unit === 'kg' ? document.getElementById('weight_input').value : 
                document.getElementById('quantity_input').value
            );
            
            // Validate form before submission
            if (!validateFormComplete()) {
                console.error('Form validation failed');
                return;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            const submitLoader = document.getElementById('submitLoader');
            const submitText = submitBtn.querySelector('span');
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            submitText.classList.add('hidden');
            submitLoader.classList.remove('hidden');
            
            // Add pulse animation
            submitBtn.classList.add('animate-pulse');
            
            // Submit the form
            this.submit();
        });

        // Real-time form validation
        function validateForm() {
            const submitBtn = document.getElementById('submitBtn');
            let isValid = true;
            
            // Check material selection
            if (!selectedMaterial) {
                isValid = false;
            } else {
                // Check material-specific input
                if (selectedMaterial.unit === 'kg') {
                    const weightInput = document.getElementById('weight_input');
                    if (!weightInput.value || parseFloat(weightInput.value) <= 0) {
                        isValid = false;
                    } else if (parseFloat(weightInput.value) < 10) {
                        isValid = false;
                        // Highlight the field to indicate error
                        weightInput.classList.add('border-red-500');
                        weightInput.focus();
                    } else {
                        weightInput.classList.remove('border-red-500');
                    }
                } else if (selectedMaterial.unit === 'pcs') {
                    const quantityInput = document.getElementById('quantity_input');
                    if (!quantityInput.value || parseInt(quantityInput.value) <= 0) {
                        isValid = false;
                    } else if (parseInt(quantityInput.value) < 30) {
                        isValid = false;
                        // Highlight the field to indicate error
                        quantityInput.classList.add('border-red-500');
                        quantityInput.focus();
                    } else {
                        quantityInput.classList.remove('border-red-500');
                    }
                }
            }
            
            // Check pickup type selection (must have one pickup type selected)
            const pickupTypeRadios = document.querySelectorAll('input[name="pickup_type"]:checked');
            if (pickupTypeRadios.length === 0) {
                isValid = false;
            }
            
            // Check date
            const dateInput = document.querySelector('[name="preferred_date"]');
            if (!dateInput.value) {
                isValid = false;
            }
            
            // Check pickup type specific requirements
            const selectedPickupTypeValue = pickupTypeRadios.length > 0 ? pickupTypeRadios[0].value : null;
            
            if (selectedPickupTypeValue === 'Home') {
                // For home pickup, GPS is required
                const lat = document.getElementById('latitude').value;
                const lng = document.getElementById('longitude').value;
                if (!lat || !lng) {
                    isValid = false;
                }
                
                // Ensure no collection point is selected
                document.querySelectorAll('input[name="point_id"]').forEach(radio => {
                    radio.checked = false;
                });
            } else if (selectedPickupTypeValue === 'CollectionPoint') {
                // For collection point, point selection is required
                const pointRadios = document.querySelectorAll('input[name="point_id"]:checked');
                if (pointRadios.length === 0) {
                    isValid = false;
                } else {
                    // If a collection point is selected, calculate and show distance
                    const selectedPointCard = document.querySelector('.collection-point-card.selected');
                    if (selectedPointCard) {
                        const lat = parseFloat(selectedPointCard.getAttribute('data-lat'));
                        const lng = parseFloat(selectedPointCard.getAttribute('data-lng'));
                        
                        if (lat && lng) {
                            calculateDistanceToPoint(lat, lng);
                        }
                    }
                }
                
                // Clear GPS coordinates for collection point (backend will use collection point coordinates)
                document.getElementById('latitude').value = '';
                document.getElementById('longitude').value = '';
            }
            
            submitBtn.classList.toggle('opacity-50', !isValid);
            submitBtn.classList.toggle('cursor-not-allowed', !isValid);
            
            return isValid;
        }

        function validateFormComplete() {
            const isValid = validateForm();
            
            if (!isValid) {
                let errorMessage = 'Please complete all required fields:';
                const errors = [];
                
                if (!selectedMaterial) {
                    errors.push('Select a material type');
                } else {
                    // Check material-specific input
                    if (selectedMaterial.unit === 'kg') {
                        const weightInput = document.getElementById('weight_input');
                        if (!weightInput.value) {
                            errors.push('Enter weight in kilograms');
                        } else if (parseFloat(weightInput.value) < 10) {
                            errors.push('Minimum weight requirement is 10 kilograms');
                        }
                    } else if (selectedMaterial.unit === 'pcs') {
                        const quantityInput = document.getElementById('quantity_input');
                        if (!quantityInput.value) {
                            errors.push('Enter quantity in pieces');
                        } else if (parseInt(quantityInput.value) < 30) {
                            errors.push('Minimum quantity requirement is 30 pieces');
                        }
                    }
                }
                
                // Check if pickup type is selected
                const pickupTypeRadios = document.querySelectorAll('input[name="pickup_type"]:checked');
                if (pickupTypeRadios.length === 0) {
                    errors.push('Choose pickup method (Home or Collection Point)');
                } else {
                    const selectedPickupTypeValue = pickupTypeRadios[0].value;
                    
                    if (selectedPickupTypeValue === 'Home') {
                        if (!document.getElementById('latitude').value || !document.getElementById('longitude').value) {
                            errors.push('Get your GPS location for home pickup');
                        }
                    } else if (selectedPickupTypeValue === 'CollectionPoint') {
                        const pointRadios = document.querySelectorAll('input[name="point_id"]:checked');
                        if (pointRadios.length === 0) {
                            errors.push('Select a collection point for pickup');
                        }
                        // Clear GPS inputs
                        document.getElementById('latitude').value = '';
                        document.getElementById('longitude').value = '';
                    }
                }
                
                if (!document.querySelector('[name="preferred_date"]').value) {
                    errors.push('Select pickup date');
                }
                
                if (errors.length > 0) {
                    alert(errorMessage + '\n• ' + errors.join('\n• '));
                }
            }
            
            return isValid;
        }

        // Enhanced input interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Set minimum date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const minDateTime = tomorrow.toISOString().slice(0, 16);
            document.querySelector('input[name="preferred_date"]').min = minDateTime;
            
            // Add real-time validation for both inputs
            const weightInput = document.getElementById('weight_input');
            const quantityInput = document.getElementById('quantity_input');
            
            [weightInput, quantityInput].forEach(input => {
                input.addEventListener('input', function() {
                    calculatePoints();
                    validateForm();
                });
                input.addEventListener('blur', validateForm);
            });
            
            // Date input validation
            document.querySelector('[name="preferred_date"]').addEventListener('change', validateForm);
            
            // Weight input enhancements
            weightInput.addEventListener('input', function() {
                // Real-time formatting and validation
                if (this.value > 1000) {
                    this.value = 1000; // Reasonable limit
                }
                
                // Add error indicator if below minimum
                if (parseFloat(this.value) < 10) {
                    this.classList.add('border-red-500');
                    showInputError(this, 'Minimum weight requirement is 10 kilograms');
                } else {
                    this.classList.remove('border-red-500');
                    hideInputError(this);
                }
            });
            
            // Quantity input enhancements
            quantityInput.addEventListener('input', function() {
                // Real-time formatting
                if (this.value > 10000) {
                    this.value = 10000; // Reasonable limit
                }
                
                // Add error indicator if below minimum
                if (parseInt(this.value) < 30) {
                    this.classList.add('border-red-500');
                    showInputError(this, 'Minimum quantity requirement is 30 pieces');
                } else {
                    this.classList.remove('border-red-500');
                    hideInputError(this);
                }
            });
            
            // Add hover effects to material cards
            document.querySelectorAll('.material-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('selected')) {
                        this.style.transform = 'translateY(-4px) scale(1.02)';
                    }
                });
                
                card.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('selected')) {
                        this.style.transform = 'translateY(0) scale(1)';
                    }
                });
            });
            
            // Auto-select home pickup by default
            setTimeout(() => {
                if (!selectedPickupType) {
                    document.querySelector('.pickup-type-card').click();
                }
            }, 1000);
            
            // Show welcome message
            setTimeout(() => {
                showWelcomeMessage();
            }, 1500);
        });
        
        // Error handling utilities for input validation
        function showInputError(inputElement, message) {
            // Remove any existing error tooltip
            hideInputError(inputElement);
            
            // Create error tooltip
            const errorTooltip = document.createElement('div');
            errorTooltip.className = 'error-tooltip absolute mt-2 bg-red-50 border border-red-200 text-red-600 px-3 py-2 rounded-lg text-sm z-10 fade-in';
            errorTooltip.innerHTML = `
                <div class="flex items-center">
                    <i data-lucide="alert-circle" class="w-4 h-4 mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            // Position the tooltip
            const parent = inputElement.closest('div');
            parent.style.position = 'relative';
            parent.appendChild(errorTooltip);
            
            // Initialize lucide icons in the tooltip
            lucide.createIcons();
            
            // Add to input for reference
            inputElement.dataset.hasError = 'true';
        }
        
        function hideInputError(inputElement) {
            const parent = inputElement.closest('div');
            const existingTooltip = parent.querySelector('.error-tooltip');
            if (existingTooltip) {
                existingTooltip.remove();
            }
            inputElement.dataset.hasError = 'false';
        }
        
        function showWelcomeMessage() {
            const welcome = document.createElement('div');
            welcome.className = 'fixed bottom-4 left-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-2xl shadow-xl z-50 transform -translate-x-full transition-transform duration-500';
            welcome.innerHTML = `
                <div class="flex items-center">
                    <i data-lucide="recycle" class="w-6 h-6 mr-3"></i>
                    <div>
                        <div class="font-semibold">Let's make a difference!</div>
                        <div class="text-sm opacity-90">Schedule your pickup and earn rewards</div>
                    </div>
                </div>
            `;
            document.body.appendChild(welcome);
            lucide.createIcons();
            
            setTimeout(() => welcome.style.transform = 'translateX(0)', 100);
            setTimeout(() => {
                welcome.style.transform = 'translateX(-100%)';
                setTimeout(() => welcome.remove(), 500);
            }, 4000);
        }
        
        // Add custom CSS animations and enhanced collection point styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInUp {
                from { opacity: 0; transform: translateY(30px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            @keyframes slideInRight {
                from { opacity: 0; transform: translateX(100px); }
                to { opacity: 1; transform: translateX(0); }
            }
            
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            @keyframes fade-in {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            .fade-in {
                animation: fade-in 0.3s ease-in-out;
            }
            
            .error-tooltip {
                box-shadow: 0 4px 10px rgba(229, 62, 62, 0.1);
                max-width: 90%;
            }
            
            input.border-red-500 {
                animation: shake 0.4s ease-in-out;
            }
            
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                20%, 60% { transform: translateX(-5px); }
                40%, 80% { transform: translateX(5px); }
            }
            
            .form-input:focus {
                transform: translateY(-2px);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            /* Enhanced Collection Point Card Styles */
            .collection-point-card {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                overflow: hidden;
            }
            
            .collection-point-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                transition: left 0.5s;
            }
            
            .collection-point-card:hover::before {
                left: 100%;
            }
            
            .collection-point-card.selected {
                border-color: #3b82f6;
                box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15);
                background: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 100%);
            }
            
            .collection-point-card.selected::after {
                content: '✓';
                position: absolute;
                top: 8px;
                right: 8px;
                width: 24px;
                height: 24px;
                background: #3b82f6;
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: bold;
                animation: checkmark 0.3s ease-in-out;
            }
            
            @keyframes checkmark {
                0% { transform: scale(0); }
                50% { transform: scale(1.2); }
                100% { transform: scale(1); }
            }
            
            .collection-point-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            }
            
            .distance-indicator {
                font-weight: 600;
                color: #059669;
            }
            
            /* Enhanced Map Container */
            #collectionPointMap {
                transition: all 0.3s ease;
            }
            
            #collectionPointMap:hover {
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
            
            /* Search Input Enhancement */
            #collectionPointSearch:focus {
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
                border-color: #3b82f6;
            }
            
            /* Status Badge Animations */
            .collection-point-card .bg-green-100 {
                animation: pulse-green 2s infinite;
            }
            
            @keyframes pulse-green {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.8; }
            }
            
            /* Loading States */
            .loading-shimmer {
                background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                background-size: 200% 100%;
                animation: shimmer 1.5s infinite;
            }
            
            @keyframes shimmer {
                0% { background-position: -200% 0; }
                100% { background-position: 200% 0; }
            }
        `;
        document.head.appendChild(style);

    </script>
    
</body>
</html>
