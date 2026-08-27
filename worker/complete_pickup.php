<?php
require_once '../config/database.php';
require '../includes/auth_worker.php';

$worker_id = (int) $_SESSION['user_id'];
$error = '';
$success = '';

function redirect_with(string $type, string $message): void
{
    $encoded = urlencode($message);
    header("Location: worker_dashboard.php?{$type}={$encoded}");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
    $actual_weight = isset($_POST['actual_weight']) && $_POST['actual_weight'] !== '' ? (float) $_POST['actual_weight'] : null;
    $actual_quantity = isset($_POST['actual_quantity']) && $_POST['actual_quantity'] !== '' ? (int) $_POST['actual_quantity'] : null;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

    try {
        $pickup = $container->pickupRepository->findById($request_id);

        if ($pickup === null) {
            throw new \DomainException('Pickup request not found');
        }

        if ($pickup->assignedWorkerId !== $worker_id) {
            throw new \DomainException('Pickup is not assigned to you');
        }

        $effective_weight = $actual_weight ?? $pickup->weightKg;
        $effective_quantity = $actual_quantity ?? $pickup->quantityPcs;

        $completed = $container->pickupService->complete(
            requestId: $request_id,
            workerId: $worker_id,
            actualWeight: $effective_weight,
            actualQuantity: $effective_quantity,
            description: $notes,
        );

        $actual_amount = $completed->recyclableType->unit === \App\Enums\Unit::Pcs
            ? $completed->quantityPcs
            : $completed->actualWeight;
        $unit_text = $completed->recyclableType->unit === \App\Enums\Unit::Pcs ? 'pieces' : 'kg';

        $success = "Pickup completed! Collected {$actual_amount} {$unit_text} of {$completed->recyclableType->name}. Resident earned {$completed->pointsEarned} points.";
        redirect_with('success', $success);
    } catch (\Throwable $e) {
        error_log("complete_pickup error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

$request_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$pickup = null;
$resident = null;

if ($request_id > 0) {
    $pickup = $container->pickupRepository->findById($request_id);

    if ($pickup !== null) {
        if ($pickup->assignedWorkerId !== $worker_id || $pickup->status !== \App\Enums\PickupStatus::Scheduled) {
            $pickup = null;
        } else {
            $stmt = prepare_statement($conn, "SELECT first_name, last_name, address, phone FROM users WHERE user_id = ?");
            $resident_id = $pickup->residentId;
            $stmt->bind_param("i", $resident_id);
            $stmt->execute();
            $resident = $stmt->get_result()->fetch_assoc();
        }
    }
}

if ($pickup === null) {
    redirect_with('error', 'Pickup request not found or cannot be completed');
}

$is_piece_based = $pickup->recyclableType->unit === \App\Enums\Unit::Pcs;

include 'worker_sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Pickup - <?= htmlspecialchars($system_name ?? 'BoteBank') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="../assets/js/theme.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-bote-stone-50 min-h-screen">
    <main class="ml-64 p-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl shadow-lg border border-bote-stone-100 overflow-hidden">
                <div class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 px-8 py-6">
                    <h1 class="text-2xl font-bold text-white">Complete Pickup</h1>
                    <p class="text-white text-opacity-90 mt-1">Record the actual collected materials for this pickup.</p>
                </div>

                <div class="p-8 space-y-6">
                    <?php if ($error): ?>
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                            <p class="text-red-700 font-medium"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-bote-stone-500 uppercase tracking-wide">Resident</p>
                            <p class="text-lg font-semibold text-bote-stone-900">
                                <?php echo htmlspecialchars(($resident['first_name'] ?? '') . ' ' . ($resident['last_name'] ?? '')); ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-bote-stone-500 uppercase tracking-wide">Material</p>
                            <p class="text-lg font-semibold text-bote-stone-900"><?php echo htmlspecialchars($pickup->recyclableType->name); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-bote-stone-500 uppercase tracking-wide">Unit</p>
                            <p class="text-lg font-semibold text-bote-stone-900"><?php echo e($is_piece_based ? 'Pieces' : 'Kilograms'); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-bote-stone-500 uppercase tracking-wide">Estimated</p>
                            <p class="text-lg font-semibold text-bote-stone-900">
                                <?php echo e($is_piece_based
                                    ? ((int) $pickup->quantityPcs . ' pieces')
                                    : (number_format($pickup->weightKg, 2) . ' kg')); ?>
                            </p>
                        </div>
                    </div>

                    <form method="POST" class="space-y-6">
                        <?php echo \App\Core\Csrf::field(); ?>
                        <input type="hidden" name="request_id" value="<?php echo (int) $pickup->requestId; ?>">

                        <?php if ($is_piece_based): ?>
                            <input type="hidden" name="actual_weight" value="<?php echo number_format($pickup->weightKg, 2); ?>">
                            <div>
                                <label for="actual_quantity" class="block text-sm font-medium text-bote-stone-700 mb-2">
                                    Actual Quantity (pieces) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="actual_quantity" name="actual_quantity" min="1" required
                                       value="<?php echo (int) $pickup->quantityPcs; ?>"
                                       class="w-full px-4 py-3 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition">
                            </div>
                        <?php else: ?>
                            <div>
                                <label for="actual_weight" class="block text-sm font-medium text-bote-stone-700 mb-2">
                                    Actual Weight (kg) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="actual_weight" name="actual_weight" step="0.01" min="0.01" required
                                       value="<?php echo number_format($pickup->weightKg, 2); ?>"
                                       class="w-full px-4 py-3 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition">
                            </div>
                        <?php endif; ?>

                        <div>
                            <label for="notes" class="block text-sm font-medium text-bote-stone-700 mb-2">Notes (optional)</label>
                            <textarea id="notes" name="notes" rows="3"
                                      class="w-full px-4 py-3 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition"
                                      placeholder="Any special observations..."></textarea>
                        </div>

                        <div class="flex items-center space-x-4 pt-4">
                            <a href="worker_dashboard.php" class="px-6 py-3 border border-bote-stone-300 rounded-lg text-bote-stone-700 hover:bg-bote-stone-50 font-medium transition">
                                Cancel
                            </a>
                            <button type="submit" class="flex-1 bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white px-6 py-3 rounded-lg font-medium transition shadow-lg hover:shadow-xl">
                                <i data-lucide="check-circle" class="w-5 h-5 inline mr-2"></i>
                                Complete Pickup
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script type="module" src="assets/js/complete_pickup.js"></script>
</body>
</html>
