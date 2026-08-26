<?php
require_once '../config/database.php';
require '../includes/auth_res.php'; // For now, using resident auth - you may want to create a separate staff auth

$user_id = $_SESSION['user_id'];

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

            $message = 'Drop-off verified and points credited successfully!';
        } elseif ($action === 'reject') {
            $rejection_reason = $_POST['rejection_reason'] ?? '';

            $container->dropoffService->reject(
                dropoffId: $dropoff_id,
                staffId: (int) $user_id,
                reason: $rejection_reason,
            );

            $message = 'Drop-off rejected successfully!';
        } else {
            $message = 'Unknown action.';
            header('Location: resident_staff_verification.php?error=' . urlencode($message));
            exit();
        }

        header('Location: resident_staff_verification.php?success=' . urlencode($message));
        exit();
    } catch (Exception $e) {
        error_log('Error in staff verification: ' . $e->getMessage());
        header('Location: resident_staff_verification.php?error=' . urlencode($e->getMessage()));
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
    <title>Staff Verification - BoteBank</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'resident_sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="shield-check" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Staff Verification</h1>
                        <p class="text-gray-600">Verify and process direct drop-offs</p>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6" role="alert">
                    <div class="flex items-center">
                        <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                        <span><?php echo htmlspecialchars($_GET['success']); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
                    <div class="flex items-center">
                        <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
                        <span><?php echo htmlspecialchars($_GET['error']); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Pending Verifications -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i data-lucide="clock" class="w-5 h-5 mr-2 text-orange-600"></i>
                    Pending Verifications (<?php echo count($pending_dropoffs); ?>)
                </h2>

                <?php if (empty($pending_dropoffs)): ?>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="check-circle" class="w-8 h-8 text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No Pending Verifications</h3>
                        <p class="text-gray-600">All drop-offs have been processed!</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($pending_dropoffs as $dropoff): ?>
                        <div class="border border-gray-200 rounded-xl p-6 hover:shadow-md transition-all duration-200">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                        <i data-lucide="package" class="w-6 h-6 text-blue-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($dropoff['resident_name']); ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($dropoff['collection_point']); ?></p>
                                        <p class="text-xs text-gray-500">Submitted: <?php echo date('M j, Y g:i A', strtotime($dropoff['created_at'])); ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-green-600"><?php echo $dropoff['points_earned']; ?> pts</div>
                                    <div class="text-sm text-gray-600">
                                        <?php if ($dropoff['unit'] === 'kg'): ?>
                                            <?php echo $dropoff['weight_kg']; ?> kg
                                        <?php else: ?>
                                            <?php echo $dropoff['quantity_pcs']; ?> pcs
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-900 mb-2">Material Details</h4>
                                    <p class="text-sm text-gray-600">
                                        <strong>Type:</strong> <?php echo htmlspecialchars($dropoff['recyclable_type']); ?><br>
                                        <strong>Unit:</strong> <?php echo $dropoff['unit']; ?><br>
                                        <?php if ($dropoff['unit'] === 'kg'): ?>
                                            <strong>Weight:</strong> <?php echo $dropoff['weight_kg']; ?> kg<br>
                                            <strong>Rate:</strong> ₱<?php echo $dropoff['points_per_kg']; ?>/kg
                                        <?php else: ?>
                                            <strong>Quantity:</strong> <?php echo $dropoff['quantity_pcs']; ?> pieces<br>
                                            <strong>Rate:</strong> ₱<?php echo $dropoff['points_per_unit']; ?>/piece
                                        <?php endif; ?>
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
                            <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-4">
                                <button onclick="openRejectModal(<?php echo $dropoff['dropoff_id']; ?>)" class="px-6 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg font-medium transition-all duration-200">
                                    <i data-lucide="x-circle" class="w-4 h-4 inline mr-2"></i>
                                    Reject
                                </button>
                                <button onclick="openVerifyModal(<?php echo $dropoff['dropoff_id']; ?>)" class="px-6 py-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg font-medium transition-all duration-200">
                                    <i data-lucide="check-circle" class="w-4 h-4 inline mr-2"></i>
                                    Verify & Credit Points
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Verifications -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i data-lucide="history" class="w-5 h-5 mr-2 text-green-600"></i>
                    Recent Verifications
                </h2>

                <?php if (empty($recent_verified)): ?>
                    <div class="text-center py-8">
                        <p class="text-gray-600">No recent verifications</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-900">Resident</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-900">Material</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-900">Collection Point</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-900">Points</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-900">Status</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-900">Verified By</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-900">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_verified as $verified): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($verified['resident_name']); ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="text-sm text-gray-600"><?php echo htmlspecialchars($verified['recyclable_type']); ?></div>
                                        <div class="text-xs text-gray-500">
                                            <?php if ($verified['unit'] === 'kg'): ?>
                                                <?php echo $verified['weight_kg']; ?> kg
                                            <?php else: ?>
                                                <?php echo $verified['quantity_pcs']; ?> pcs
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="text-sm text-gray-600"><?php echo htmlspecialchars($verified['collection_point']); ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-semibold text-green-600"><?php echo $verified['points_earned']; ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $verified['status'] === 'Completed' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                            <?php echo ucfirst($verified['status']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="text-sm text-gray-600"><?php echo htmlspecialchars($verified['verified_by_name']); ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="text-sm text-gray-600"><?php echo date('M j, g:i A', strtotime($verified['verified_at'])); ?></div>
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

    <!-- Verification Modal -->
    <div id="verifyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl p-8 max-w-md w-full">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Verify Drop-off</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to verify this drop-off and credit points to the resident?</p>
                
                <form method="POST" id="verifyForm">
                    <input type="hidden" name="action" value="verify">
                    <input type="hidden" name="dropoff_id" id="verifyDropoffId">
                    
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Verification Notes (Optional)</label>
                        <textarea name="verification_notes" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="Add any notes about the verification..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeVerifyModal()" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-all duration-200">
                            Cancel
                        </button>
                        <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-all duration-200">
                            <i data-lucide="check-circle" class="w-4 h-4 inline mr-2"></i>
                            Verify & Credit Points
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl p-8 max-w-md w-full">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Reject Drop-off</h3>
                <p class="text-gray-600 mb-6">Please provide a reason for rejecting this drop-off.</p>
                
                <form method="POST" id="rejectForm">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="dropoff_id" id="rejectDropoffId">
                    
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Rejection Reason *</label>
                        <textarea name="rejection_reason" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="Please explain why this drop-off is being rejected..." required></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeRejectModal()" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-all duration-200">
                            Cancel
                        </button>
                        <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-all duration-200">
                            <i data-lucide="x-circle" class="w-4 h-4 inline mr-2"></i>
                            Reject Drop-off
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openVerifyModal(dropoffId) {
            document.getElementById('verifyDropoffId').value = dropoffId;
            document.getElementById('verifyModal').classList.remove('hidden');
        }

        function closeVerifyModal() {
            document.getElementById('verifyModal').classList.add('hidden');
        }

        function openRejectModal(dropoffId) {
            document.getElementById('rejectDropoffId').value = dropoffId;
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        document.getElementById('verifyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVerifyModal();
            }
        });

        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });

        lucide.createIcons();
    </script>
</body>
</html>
