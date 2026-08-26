<?php
require_once '../config/database.php';
require '../includes/auth_worker.php'; // Ensure only worker can access

// Get user data for sidebar
$user_id = $_SESSION['user_id'];

// Fetch completed collections for the worker
$stmt = prepare_statement($conn, "
    SELECT pr.request_id, pr.weight_kg, pr.preferred_date, pr.created_at,
           rt.name as recyclable_type, rt.points_per_kg,
           CONCAT(u.first_name, ' ', u.last_name) as resident_name,
           u.address as pickup_address,
           pl.points as points_awarded
    FROM pickuprequests pr
    JOIN recyclabletypes rt ON pr.type_id = rt.type_id
    JOIN users u ON pr.resident_id = u.user_id
    LEFT JOIN pointsledger pl ON pr.request_id = pl.request_id
    WHERE pr.assigned_worker_id = ? AND pr.status = 'Completed'
    ORDER BY pr.preferred_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_collections = $stmt->get_result();

// Get summary statistics
$stmt = prepare_statement($conn, "
    SELECT 
        COUNT(*) as total_collections,
        SUM(pr.weight_kg) as total_weight,
        SUM(pl.points) as total_points_awarded
    FROM pickuprequests pr
    LEFT JOIN pointsledger pl ON pr.request_id = pl.request_id
    WHERE pr.assigned_worker_id = ? AND pr.status = 'Completed'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get this month's stats
$stmt = prepare_statement($conn, "
    SELECT 
        COUNT(*) as month_collections,
        SUM(pr.weight_kg) as month_weight
    FROM pickuprequests pr
    WHERE pr.assigned_worker_id = ? AND pr.status = 'Completed'
    AND MONTH(pr.preferred_date) = MONTH(CURDATE()) 
    AND YEAR(pr.preferred_date) = YEAR(CURDATE())
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$month_stats = $stmt->get_result()->fetch_assoc();

include 'worker_sidebar.php';
?>

<!-- Main Content -->
<div class="ml-64 p-8 bg-gray-50 min-h-screen">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Completed Collections</h1>
                    <p class="text-gray-600">Review your successful pickup history and performance</p>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-green-600"><?php echo number_format($stats['total_collections'] ?? 0); ?></div>
                    <div class="text-sm text-gray-500">Total Collections</div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Collections</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo number_format($stats['total_collections'] ?? 0); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Weight</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo number_format($stats['total_weight'] ?? 0, 1); ?> kg</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i data-lucide="weight" class="w-6 h-6 text-blue-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">This Month</p>
                        <p class="text-2xl font-bold text-purple-600"><?php echo number_format($month_stats['month_collections'] ?? 0); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <i data-lucide="calendar" class="w-6 h-6 text-purple-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Month Weight</p>
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
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-900">Collection History</h2>
            </div>
            
            <?php if ($completed_collections->num_rows > 0): ?>
                <div class="divide-y divide-gray-200">
                    <?php while ($collection = $completed_collections->fetch_assoc()): ?>
                        <div class="p-6 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-4 mb-3">
                                        <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                                        <span class="text-sm font-medium text-green-700">Completed</span>
                                        <span class="text-sm text-gray-500">Request #<?php echo $collection['request_id']; ?></span>
                                    </div>
                                    
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                        <?php echo htmlspecialchars($collection['recyclable_type']); ?>
                                    </h3>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-3">
                                        <div>
                                            <span class="text-gray-500">Resident:</span>
                                            <span class="font-medium ml-1"><?php echo htmlspecialchars($collection['resident_name']); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Weight:</span>
                                            <span class="font-medium ml-1"><?php echo number_format($collection['weight_kg'], 1); ?> kg</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Collection Date:</span>
                                            <span class="font-medium ml-1"><?php echo date('M j, Y', strtotime($collection['preferred_date'])); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Points Awarded:</span>
                                            <span class="font-medium text-green-600 ml-1">
                                                <?php echo number_format($collection['points_awarded'] ?? ($collection['weight_kg'] * $collection['points_per_kg'])); ?> pts
                                            </span>
                                        </div>
                                    </div>

                                    <div class="text-sm text-gray-600">
                                        <i data-lucide="map-pin" class="w-4 h-4 inline mr-1"></i>
                                        <?php echo htmlspecialchars($collection['pickup_address']); ?>
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    <div class="text-sm text-gray-500 mb-1">
                                        Collected: <?php echo date('M j, Y g:i A', strtotime($collection['preferred_date'])); ?>
                                    </div>
                                    <button onclick="viewCollectionDetails(<?php echo $collection['request_id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                        View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="package" class="w-12 h-12 text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No completed collections</h3>
                    <p class="text-gray-500 mb-6">Your completed collections will appear here</p>
                    <a href="worker_assigned_pickups.php" class="inline-flex items-center space-x-2 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-medium transition-colors">
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
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="check-circle" class="w-8 h-8 text-green-600"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Collection Details</h3>
            <div id="collectionDetails" class="text-gray-600 mb-6">
                <!-- Collection details will be loaded here -->
            </div>
            <button onclick="hideCollectionModal()" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-xl font-medium transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    function viewCollectionDetails(requestId) {
        // This would typically fetch more detailed information about the collection
        document.getElementById('collectionDetails').innerHTML = `
            <p>Loading collection details for request #${requestId}...</p>
        `;
        
        showCollectionModal();
        
        // Simulate API call
        setTimeout(() => {
            document.getElementById('collectionDetails').innerHTML = `
                <p>Detailed information about collection request #${requestId} would be displayed here, including photos, notes, and verification data.</p>
            `;
        }, 1000);
    }

    function showCollectionModal() {
        const modal = document.getElementById('collectionModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            modal.querySelector('div > div').classList.remove('scale-95');
            modal.querySelector('div > div').classList.add('scale-100');
        }, 10);
    }

    function hideCollectionModal() {
        const modal = document.getElementById('collectionModal');
        modal.querySelector('div > div').classList.remove('scale-100');
        modal.querySelector('div > div').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }

    // Close modal when clicking outside
    document.getElementById('collectionModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideCollectionModal();
        }
    });
</script>
