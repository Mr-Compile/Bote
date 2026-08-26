<?php
require_once '../config/database.php';
require '../includes/auth_res.php'; // Ensure only resident can access

$user_id = $_SESSION['user_id'];

// Handle direct drop-off submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        \App\Core\Csrf::validate($_POST['csrf_token'] ?? null);

        if (!isset($_POST['type_id'], $_POST['point_id'])) {
            throw new Exception('Missing required fields');
        }

        $dropoff = $container->dropoffRequestService->create(
            residentId: (int) $user_id,
            pointId: (int) $_POST['point_id'],
            typeId: (int) $_POST['type_id'],
            weightKg: isset($_POST['weight_kg']) && $_POST['weight_kg'] !== '' ? (float) $_POST['weight_kg'] : 0.0,
            quantityPcs: isset($_POST['quantity_pcs']) && $_POST['quantity_pcs'] !== '' ? (int) $_POST['quantity_pcs'] : null,
        );

        // Send SMS notification for drop-off submission
        require_once '../includes/sms_notifications.php';
        sendDropoffSubmissionSMS($dropoff->dropoffId);

        header('Location: resident_dropoff_success.php?dropoff_id=' . $dropoff->dropoffId);
        exit();
    } catch (Exception $e) {
        error_log('Error in resident_direct_dropoff.php: ' . $e->getMessage());
        header('Location: resident_direct_dropoff.php?error=' . urlencode($e->getMessage()));
        exit();
    }
}

// Get recyclable types with images
$stmt = $conn->prepare("SELECT * FROM recyclabletypes ORDER BY name ASC");
$stmt->execute();
$recyclable_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get collection points with coordinates
$stmt = $conn->prepare("SELECT point_id, name, description, latitude, longitude FROM collection_points ORDER BY name ASC");
$stmt->execute();
$collection_points = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total_dropoffs FROM direct_dropoffs WHERE resident_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_dropoffs = $stmt->get_result()->fetch_assoc()['total_dropoffs'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE -points END), 0) as total_points FROM pointsledger WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_points = $stmt->get_result()->fetch_assoc()['total_points'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct Drop-off - BoteBank</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="assets/css/new_request.css">
    <link rel="stylesheet" href="assets/css/my_request.css">
    <style>
        .dropoff-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        .dropoff-card:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .selected-dropoff {
            border: 3px solid #10b981 !important;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.3);
            transform: scale(1.02);
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        
        .shake-animation {
            animation: shake 0.4s ease-in-out;
        }
        
        .border-red-500 {
            border-color: #ef4444 !important;
        }
        
        .fade-in {
            animation: fade-in 0.3s ease-in-out;
        }
        
        @keyframes fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'resident_sidebar.php'; ?>
    
    <div class="ml-64 p-8 bg-gradient-to-br from-gray-50 via-white to-gray-50 min-h-screen">
        <div class="max-w-6xl mx-auto">
            <!-- Enhanced Header -->
            <div class="bg-gradient-to-r from-white to-gray-50 rounded-3xl shadow-xl p-8 mb-10 relative overflow-hidden">
                <div class="shimmer absolute inset-0"></div>
                <div class="relative z-10 flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="floating-animation">
                            <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mr-6 shadow-lg">
                                <i data-lucide="map-pin" class="w-8 h-8 text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent mb-2">
                                Direct Drop-off
                            </h1>
                            <p class="text-xl text-gray-600">Log your recyclables directly at collection points</p>
                            <div class="mt-3 flex items-center space-x-6 text-sm text-gray-500">
                                <div class="flex items-center">
                                    <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i>
                                    <span>Multiple locations</span>
                                </div>
                                <div class="flex items-center">
                                    <i data-lucide="zap" class="w-4 h-4 mr-2"></i>
                                    <span>Instant verification</span>
                                </div>
                                <div class="flex items-center">
                                    <i data-lucide="coins" class="w-4 h-4 mr-2"></i>
                                    <span>Earn points</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Enhanced Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-3xl font-bold"><?php echo $total_dropoffs; ?></p>
                            <p class="text-blue-100 text-lg">Total Drop-offs</p>
                        </div>
                        <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                            <i data-lucide="package" class="w-8 h-8"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-green-500 via-green-600 to-emerald-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-3xl font-bold"><?php echo $total_points; ?></p>
                            <p class="text-green-100 text-lg">Total Points</p>
                        </div>
                        <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                            <i data-lucide="star" class="w-8 h-8"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-purple-500 via-purple-600 to-pink-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-3xl font-bold"><?php echo $collection_points ? count($collection_points) : 0; ?></p>
                            <p class="text-purple-100 text-lg">Collection Points</p>
                        </div>
                        <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                            <i data-lucide="map-pin" class="w-8 h-8"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-xl mb-8 shadow-lg card-enter" role="alert">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-4">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                        </div>
                        <div>
                            <p class="font-semibold">Error</p>
                            <span><?php echo htmlspecialchars($_GET['error']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Direct Drop-off Form -->
            <form method="POST" class="space-y-8">
                <?php echo \App\Core\Csrf::field(); ?>
                <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 p-8">
                    <div class="flex items-center mb-8">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mr-4">
                            <i data-lucide="package" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Drop-off Information</h2>
                            <p class="text-gray-600 mt-1">Fill in the details of your recyclables</p>
                        </div>
                    </div>

                    <!-- Collection Point Selection -->
                    <div class="mb-8">
                        <label class="block text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <i data-lucide="map-pin" class="w-5 h-5 mr-2 text-green-600"></i>
                            Select Collection Point
                        </label>
                        <p class="text-gray-600 mb-6">Choose where you'll drop off your recyclables</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach ($collection_points as $point): ?>
                            <div class="dropoff-card rounded-2xl p-8 cursor-pointer transition-all duration-300 shadow-lg" onclick="selectCollectionPoint(<?php echo $point['point_id']; ?>)">
                                <input type="radio" name="point_id" value="<?php echo $point['point_id']; ?>" id="point_<?php echo $point['point_id']; ?>" class="hidden" required>
                                <div class="text-center">
                                    <div class="w-20 h-20 bg-white bg-opacity-25 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                                        <i data-lucide="building" class="w-10 h-10 text-white"></i>
                                    </div>
                                    <h3 class="font-bold text-white mb-2 text-xl"><?php echo htmlspecialchars($point['name']); ?></h3>
                                    <p class="text-green-100 text-sm mb-4"><?php echo htmlspecialchars($point['description']); ?></p>
                                    <div class="bg-white bg-opacity-25 rounded-xl px-4 py-2">
                                        <p class="text-white text-sm font-semibold flex items-center justify-center">
                                            <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i>
                                            Drop-off Location
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Material Type Selection -->
                    <div class="mb-8">
                        <label class="block text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <i data-lucide="recycle" class="w-5 h-5 mr-2 text-green-600"></i>
                            Select Recyclable Material
                        </label>
                        <p class="text-gray-600 mb-6">Choose the type of recyclable you're dropping off</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($recyclable_types as $type): ?>
                            <div class="bg-gradient-to-br from-white to-gray-50 border-2 border-gray-200 rounded-2xl p-6 cursor-pointer transition-all duration-300 hover:border-green-500 hover:shadow-xl hover:-translate-y-1" onclick="selectMaterial(<?php echo $type['type_id']; ?>, '<?php echo $type['unit']; ?>', '<?php echo htmlspecialchars($type['name']); ?>')">
                                <input type="radio" name="type_id" value="<?php echo $type['type_id']; ?>" id="type_<?php echo $type['type_id']; ?>" class="hidden" required>
                                <div class="text-center">
                                    <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 overflow-hidden shadow-lg">
                                        <?php if (!empty($type['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($type['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($type['name']); ?>"
                                                 class="w-full h-full object-cover rounded-2xl"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <i data-lucide="package" class="w-10 h-10 text-white" style="display:none;"></i>
                                        <?php else: ?>
                                            <i data-lucide="package" class="w-10 h-10 text-white"></i>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="font-bold text-gray-900 mb-2 text-lg"><?php echo htmlspecialchars($type['name']); ?></h3>
                                    <p class="text-gray-600 text-sm mb-3"><?php echo $type['unit'] === 'kg' ? 'Per kilogram' : 'Per piece'; ?></p>
                                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl px-4 py-2 border border-green-100">
                                        <p class="text-green-700 text-sm font-bold flex items-center justify-center">
                                            <i data-lucide="coins" class="w-4 h-4 mr-2"></i>
                                            <?php echo $type['unit'] === 'kg' ? $type['points_per_kg'] . ' pts/kg' : $type['points_per_unit'] . ' pts/pc'; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Weight/Quantity Input -->
                    <div class="mb-8">
                        <div id="weightInput" class="hidden fade-in">
                            <label class="block text-lg font-bold text-gray-900 mb-4 flex items-center">
                                <i data-lucide="scale" class="w-5 h-5 mr-2 text-green-600"></i>
                                <span id="weightLabel">Weight (kg)</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="weight_kg" id="weight_kg" step="0.1" min="10" class="w-full px-6 py-4 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-lg font-semibold" placeholder="Enter weight in kilograms (min 10kg)">
                                <div class="absolute right-4 top-4 text-gray-400">
                                    <i data-lucide="weight" class="w-6 h-6"></i>
                                </div>
                            </div>
                            <div id="weightHint" class="flex items-center text-sm text-gray-600 mt-3 bg-blue-50 px-4 py-3 rounded-lg">
                                <i data-lucide="info" class="w-4 h-4 mr-2 text-blue-600"></i>
                                <span>Minimum requirement: 10 kilograms</span>
                            </div>
                        </div>

                        <div id="quantityInput" class="hidden fade-in">
                            <label class="block text-lg font-bold text-gray-900 mb-4 flex items-center">
                                <i data-lucide="hash" class="w-5 h-5 mr-2 text-green-600"></i>
                                <span id="quantityLabel">Quantity (pieces)</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="quantity_pcs" id="quantity_pcs" min="30" class="w-full px-6 py-4 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-lg font-semibold" placeholder="Enter quantity (min 30pcs)">
                                <div class="absolute right-4 top-4 text-gray-400">
                                    <i data-lucide="package" class="w-6 h-6"></i>
                                </div>
                            </div>
                            <div id="quantityHint" class="flex items-center text-sm text-gray-600 mt-3 bg-blue-50 px-4 py-3 rounded-lg">
                                <i data-lucide="info" class="w-4 h-4 mr-2 text-blue-600"></i>
                                <span>Minimum requirement: 30 pieces</span>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 sm:space-x-6 pt-6 border-t border-gray-200">
                        <div class="text-gray-600 text-sm">
                            <i data-lucide="info" class="w-4 h-4 inline mr-2"></i>
                            Your drop-off will be verified by staff before points are awarded
                        </div>
                        <div class="flex space-x-4">
                            <a href="resident_dashboard.php" class="px-8 py-4 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-2xl font-semibold transition-all duration-300 text-center hover:shadow-lg hover:scale-105 flex items-center">
                                <i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>
                                Cancel
                            </a>
                            <button type="submit" class="px-10 py-4 bg-gradient-to-r from-green-500 via-green-600 to-emerald-600 hover:from-green-600 hover:via-green-700 hover:to-emerald-700 text-white rounded-2xl font-semibold transition-all duration-300 flex items-center justify-center space-x-3 shadow-xl hover:shadow-2xl hover:scale-105" id="submitBtn">
                                <i data-lucide="check-circle" class="w-5 h-5"></i>
                                <span>Submit Drop-off</span>
                                <div class="hidden" id="submitLoader">
                                    <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedMaterial = null;
        let selectedPoint = null;

        function selectCollectionPoint(pointId) {
            selectedPoint = pointId;
            
            // Remove selection from all cards
            document.querySelectorAll('.dropoff-card').forEach(card => {
                card.classList.remove('selected-dropoff');
            });
            
            // Add selection to clicked card
            event.currentTarget.classList.add('selected-dropoff');
            
            // Check the radio button
            document.getElementById('point_' + pointId).checked = true;
            
            updateSubmitButton();
        }

        function selectMaterial(typeId, unit, materialName) {
            selectedMaterial = { typeId, unit, materialName };
            
            // Remove selection from all cards
            document.querySelectorAll('.bg-white.border-2').forEach(card => {
                card.classList.remove('selected-dropoff');
            });
            
            // Add selection to clicked card
            event.currentTarget.classList.add('selected-dropoff');
            
            // Check the radio button
            document.getElementById('type_' + typeId).checked = true;
            
            // Show/hide appropriate input fields
            const weightDiv = document.getElementById('weightInput');
            const quantityDiv = document.getElementById('quantityInput');
            const weightInput = document.getElementById('weight_kg');
            const quantityInput = document.getElementById('quantity_pcs');
            const weightLabel = document.getElementById('weightLabel');
            const quantityLabel = document.getElementById('quantityLabel');
            const weightHint = document.getElementById('weightHint');
            const quantityHint = document.getElementById('quantityHint');
            
            if (unit === 'kg') {
                // Show weight input, hide quantity
                weightDiv.classList.remove('hidden');
                quantityDiv.classList.add('hidden');
                weightInput.setAttribute('required', 'required');
                quantityInput.removeAttribute('required');
                
                weightLabel.textContent = `Weight for ${materialName} (kg)`;
                weightHint.innerHTML = '<i data-lucide="info" class="w-4 h-4 mr-2"></i>Minimum requirement: 10 kilograms';
                
                // Update icon
                weightLabel.previousElementSibling.setAttribute('data-lucide', 'scale');
            } else if (unit === 'pcs') {
                // Show quantity input, hide weight
                weightDiv.classList.add('hidden');
                quantityDiv.classList.remove('hidden');
                quantityInput.setAttribute('required', 'required');
                weightInput.removeAttribute('required');
                
                quantityLabel.textContent = `Quantity for ${materialName} (pieces)`;
                quantityHint.innerHTML = '<i data-lucide="info" class="w-4 h-4 mr-2"></i>Minimum requirement: 30 pieces';
                
                // Update icon
                quantityLabel.previousElementSibling.setAttribute('data-lucide', 'hash');
            }
            
            // Recreate lucide icons
            lucide.createIcons();
            updateSubmitButton();
        }

        function updateSubmitButton() {
            const submitBtn = document.getElementById('submitBtn');
            let isValid = true;
            
            if (!selectedMaterial || !selectedPoint) {
                isValid = false;
            } else {
                // Check material-specific requirements
                if (selectedMaterial.unit === 'kg') {
                    const weightInput = document.getElementById('weight_kg');
                    const weight = parseFloat(weightInput.value);
                    if (!weight || weight < 10) {
                        isValid = false;
                    }
                } else if (selectedMaterial.unit === 'pcs') {
                    const quantityInput = document.getElementById('quantity_pcs');
                    const quantity = parseInt(quantityInput.value);
                    if (!quantity || quantity < 30) {
                        isValid = false;
                    }
                }
            }
            
            if (isValid) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        // Form validation function
        function validateForm() {
            if (!selectedMaterial || !selectedPoint) {
                return false;
            }
            
            // Check material-specific requirements
            if (selectedMaterial.unit === 'kg') {
                const weightInput = document.getElementById('weight_kg');
                const weight = parseFloat(weightInput.value);
                if (!weight || weight < 10) {
                    // Add visual indication
                    weightInput.classList.add('border-red-500', 'shake-animation');
                    weightInput.focus();
                    
                    // Show error message more visibly
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-4 mt-2 fade-in';
                    errorMsg.innerHTML = '<div class="flex items-center"><i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i><p>Minimum weight requirement is 10 kilograms</p></div>';
                    
                    const parent = weightInput.closest('div');
                    const existingError = parent.querySelector('.bg-red-100');
                    if (existingError) parent.removeChild(existingError);
                    
                    parent.appendChild(errorMsg);
                    lucide.createIcons();
                    
                    setTimeout(() => {
                        weightInput.classList.remove('shake-animation');
                    }, 500);
                    
                    return false;
                } else {
                    const parent = weightInput.closest('div');
                    const existingError = parent.querySelector('.bg-red-100');
                    if (existingError) parent.removeChild(existingError);
                    weightInput.classList.remove('border-red-500');
                }
            } else if (selectedMaterial.unit === 'pcs') {
                const quantityInput = document.getElementById('quantity_pcs');
                const quantity = parseInt(quantityInput.value);
                if (!quantity || quantity < 30) {
                    // Add visual indication
                    quantityInput.classList.add('border-red-500', 'shake-animation');
                    quantityInput.focus();
                    
                    // Show error message more visibly
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-4 mt-2 fade-in';
                    errorMsg.innerHTML = '<div class="flex items-center"><i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i><p>Minimum quantity requirement is 30 pieces</p></div>';
                    
                    const parent = quantityInput.closest('div');
                    const existingError = parent.querySelector('.bg-red-100');
                    if (existingError) parent.removeChild(existingError);
                    
                    parent.appendChild(errorMsg);
                    lucide.createIcons();
                    
                    setTimeout(() => {
                        quantityInput.classList.remove('shake-animation');
                    }, 500);
                    
                    return false;
                } else {
                    const parent = quantityInput.closest('div');
                    const existingError = parent.querySelector('.bg-red-100');
                    if (existingError) parent.removeChild(existingError);
                    quantityInput.classList.remove('border-red-500');
                }
            }
            
            return true;
        }

        // Form submission handling
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            const submitLoader = document.getElementById('submitLoader');
            
            submitBtn.disabled = true;
            submitBtn.querySelector('span').classList.add('hidden');
            submitLoader.classList.remove('hidden');
        });

        // Add input validation listeners
        const weightInput = document.getElementById('weight_kg');
        if (weightInput) {
            weightInput.addEventListener('input', function() {
                // Real-time validation
                const value = parseFloat(this.value);
                if (value < 10) {
                    this.classList.add('border-red-500');
                    const parent = this.closest('div');
                    const hint = parent.querySelector('#weightHint');
                    if (hint) {
                        hint.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 mr-2 text-red-500"></i>Minimum requirement: 10 kilograms';
                        lucide.createIcons();
                    }
                } else {
                    this.classList.remove('border-red-500');
                    const parent = this.closest('div');
                    const hint = parent.querySelector('#weightHint');
                    if (hint) {
                        hint.innerHTML = '<i data-lucide="info" class="w-4 h-4 mr-2"></i>Minimum requirement: 10 kilograms';
                        lucide.createIcons();
                    }
                }
                
                updateSubmitButton();
            });
        }
        
        const quantityInput = document.getElementById('quantity_pcs');
        if (quantityInput) {
            quantityInput.addEventListener('input', function() {
                // Real-time validation
                const value = parseInt(this.value);
                if (value < 30) {
                    this.classList.add('border-red-500');
                    const parent = this.closest('div');
                    const hint = parent.querySelector('#quantityHint');
                    if (hint) {
                        hint.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 mr-2 text-red-500"></i>Minimum requirement: 30 pieces';
                        lucide.createIcons();
                    }
                } else {
                    this.classList.remove('border-red-500');
                    const parent = this.closest('div');
                    const hint = parent.querySelector('#quantityHint');
                    if (hint) {
                        hint.innerHTML = '<i data-lucide="info" class="w-4 h-4 mr-2"></i>Minimum requirement: 30 pieces';
                        lucide.createIcons();
                    }
                }
                
                updateSubmitButton();
            });
        }

        // Initialize
        lucide.createIcons();
        updateSubmitButton();
    </script>
</body>
</html>
