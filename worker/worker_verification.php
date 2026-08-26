<?php
require_once '../config/database.php';
require '../includes/auth_staff.php'; // Only workers and admins can access

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Handle verification action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $dropoff_id = (int) $_POST['dropoff_id'];
    $action = $_POST['action'];

    try {
        if ($action === 'verify') {
            $verification_notes = $_POST['verification_notes'] ?? '';

            $container->dropoffService->verify(
                dropoffId: $dropoff_id,
                staffId: (int) $user_id,
                notes: $verification_notes,
            );

            // Send SMS notification for drop-off verification
            require_once '../includes/sms_notifications.php';
            sendDropoffVerificationSMS($dropoff_id);

            $message = 'Drop-off verified and points credited successfully!';
        } elseif ($action === 'reject') {
            $rejection_reason = $_POST['rejection_reason'] ?? '';

            $container->dropoffService->reject(
                dropoffId: $dropoff_id,
                staffId: (int) $user_id,
                reason: $rejection_reason,
            );

            // Send SMS notification for drop-off rejection
            require_once '../includes/sms_notifications.php';
            sendDropoffRejectionSMS($dropoff_id);

            $message = 'Drop-off rejected successfully!';
        } else {
            $message = 'Unknown action.';
            header('Location: worker_verification.php?error=' . urlencode($message));
            exit();
        }

        header('Location: worker_verification.php?success=' . urlencode($message));
        exit();
    } catch (Exception $e) {
        error_log('Error in worker verification: ' . $e->getMessage());
        header('Location: worker_verification.php?error=' . urlencode($e->getMessage()));
        exit();
    }
}

// Get pending drop-offs for verification
$stmt = $conn->prepare("
    SELECT dd.*, rt.name as recyclable_type, rt.unit, rt.points_per_kg, rt.points_per_unit,
           cp.name as collection_point, cp.description as point_description,
           CONCAT(u.first_name, ' ', u.last_name) as resident_name, u.phone
    FROM direct_dropoffs dd
    JOIN recyclabletypes rt ON dd.type_id = rt.type_id
    JOIN collection_points cp ON dd.point_id = cp.point_id
    JOIN users u ON dd.resident_id = u.user_id
    WHERE dd.status = 'Pending'
    ORDER BY dd.created_at ASC
");
$stmt->execute();
$pending_dropoffs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent verified drop-offs
$stmt = $conn->prepare("
    SELECT dd.*, rt.name as recyclable_type, rt.unit,
           cp.name as collection_point,
           CONCAT(u.first_name, ' ', u.last_name) as resident_name,
           CONCAT(verifier.first_name, ' ', verifier.last_name) as verified_by_name
    FROM direct_dropoffs dd
    JOIN recyclabletypes rt ON dd.type_id = rt.type_id
    JOIN collection_points cp ON dd.point_id = cp.point_id
    JOIN users u ON dd.resident_id = u.user_id
    LEFT JOIN users verifier ON dd.verified_by = verifier.user_id
    WHERE dd.status IN ('Verified', 'Completed')
    ORDER BY dd.verified_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_verified = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Verification - BoteBank</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'worker_sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="shield-check" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Worker Verification</h1>
                        <p class="text-gray-600">Verify and process direct drop-offs</p>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <i data-lucide="check-circle" class="w-4 h-4 inline mr-2"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <i data-lucide="alert-circle" class="w-4 h-4 inline mr-2"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Pending Drop-offs -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <i data-lucide="clock" class="w-6 h-6 mr-2 text-orange-500"></i>
                    Pending Verifications
                    <span class="ml-2 bg-orange-100 text-orange-800 text-sm px-2 py-1 rounded-full">
                        <?php echo count($pending_dropoffs); ?>
                    </span>
                </h2>
                
                <?php if (empty($pending_dropoffs)): ?>
                    <div class="bg-white rounded-lg shadow p-8 text-center">
                        <i data-lucide="check-circle" class="w-16 h-16 text-green-500 mx-auto mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No Pending Verifications</h3>
                        <p class="text-gray-600">All drop-offs have been processed!</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($pending_dropoffs as $dropoff): ?>
                        <div class="bg-white rounded-lg shadow-lg border-l-4 border-orange-500 p-6">
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <!-- Drop-off Details -->
                                <div class="space-y-4">
                                    <div class="bg-blue-50 rounded-lg p-4">
                                        <h4 class="font-semibold text-gray-900 mb-2">Drop-off Information</h4>
                                        <p class="text-sm text-gray-600">
                                            <strong>Resident:</strong> <?php echo htmlspecialchars($dropoff['resident_name']); ?><br>
                                            <strong>Material:</strong> <?php echo htmlspecialchars($dropoff['recyclable_type']); ?><br>
                                            <?php if (!empty($dropoff['weight_kg']) && $dropoff['weight_kg'] > 0): ?>
                                                <strong>Weight:</strong> <?php echo $dropoff['weight_kg']; ?> kg<br>
                                            <?php endif; ?>
                                            <?php if (!empty($dropoff['quantity_pcs']) && $dropoff['quantity_pcs'] > 0): ?>
                                                <strong>Quantity:</strong> <?php echo $dropoff['quantity_pcs']; ?> pieces<br>
                                            <?php endif; ?>
                                            <strong>Points:</strong> <?php echo $dropoff['points_earned']; ?>
                                        </p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="font-semibold text-gray-900 mb-2">Contact Info</h4>
                                        <p class="text-sm text-gray-600">
                                            <strong>Phone:</strong> <?php echo htmlspecialchars($dropoff['phone']); ?><br>
                                            <strong>Collection Point:</strong> <?php echo htmlspecialchars($dropoff['collection_point']); ?><br>
                                            <strong>Description:</strong> <?php echo htmlspecialchars($dropoff['point_description']); ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Verification Actions -->
                                <div class="flex flex-col justify-center space-y-4">
                                    <button onclick="openRejectModal(<?php echo $dropoff['dropoff_id']; ?>)" 
                                            class="px-6 py-3 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg font-medium transition-all duration-200 flex items-center justify-center">
                                        <i data-lucide="x-circle" class="w-4 h-4 mr-2"></i>
                                        Reject Drop-off
                                    </button>
                                    <button onclick="openVerifyModal(<?php echo $dropoff['dropoff_id']; ?>)" 
                                            class="px-6 py-3 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg font-medium transition-all duration-200 flex items-center justify-center">
                                        <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i>
                                        Verify & Credit Points
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Verifications -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <i data-lucide="history" class="w-6 h-6 mr-2 text-green-500"></i>
                    Recent Verifications
                </h2>
                
                <?php if (empty($recent_verified)): ?>
                    <div class="bg-white rounded-lg shadow p-8 text-center">
                        <i data-lucide="inbox" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No Recent Verifications</h3>
                        <p class="text-gray-600">Verified drop-offs will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resident</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Material</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verified By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recent_verified as $verified): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($verified['resident_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($verified['recyclable_type']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $verified['points_earned']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($verified['verified_by_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y g:i A', strtotime($verified['verified_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Verify Modal -->
    <div id="verifyModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Verify Drop-off</h3>
            <form method="POST">
                <input type="hidden" name="action" value="verify">
                <input type="hidden" name="dropoff_id" id="verify_dropoff_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Verification Notes (Optional)</label>
                    <textarea name="verification_notes" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                              placeholder="Add any notes about the verification..."></textarea>
                </div>
                
                <div class="flex space-x-4">
                    <button type="button" onclick="closeVerifyModal()" 
                            class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-md font-medium">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md font-medium">
                        Verify & Credit Points
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Reject Drop-off</h3>
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="dropoff_id" id="reject_dropoff_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
                    <textarea name="rejection_reason" rows="3" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                              placeholder="Please provide a reason for rejection..."></textarea>
                </div>
                
                <div class="flex space-x-4">
                    <button type="button" onclick="closeRejectModal()" 
                            class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-md font-medium">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md font-medium">
                        Reject Drop-off
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openVerifyModal(dropoffId) {
            document.getElementById('verify_dropoff_id').value = dropoffId;
            document.getElementById('verifyModal').classList.remove('hidden');
        }

        function closeVerifyModal() {
            document.getElementById('verifyModal').classList.add('hidden');
        }

        function openRejectModal(dropoffId) {
            document.getElementById('reject_dropoff_id').value = dropoffId;
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        document.getElementById('verifyModal').addEventListener('click', function(e) {
            if (e.target === this) closeVerifyModal();
        });

        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) closeRejectModal();
        });
    </script>
</body>
</html>
