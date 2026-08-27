<?php
require_once '../config/database.php';
require '../includes/auth_worker.php';
header('Content-Type: application/json');

if (!isset($_GET['route_id'])) {
	echo json_encode(['success' => false, 'message' => 'Missing route_id']);
	exit;
}

$route_id = (int) $_GET['route_id'];

// Fetch route meta
$stmt = prepare_statement($conn, "SELECT route_id, worker_id, generated_on FROM collectionroutes WHERE route_id = ?");
$stmt->bind_param('i', $route_id);
$stmt->execute();
$route = $stmt->get_result()->fetch_assoc();

if (!$route) {
	echo json_encode(['success' => false, 'message' => 'Route not found']);
	exit;
}

// Fetch ordered stops with optional pickup details and collection points
$stmt = prepare_statement($conn, "
	SELECT rs.stop_id, rs.stop_order, rs.latitude, rs.longitude,
	       pr.request_id, pr.status,
	       u.first_name, u.last_name, u.address, u.phone,
	       rt.name AS material_type,
	       cp.name AS collection_point_name, cp.description AS collection_description,
	       rs.point_id
	FROM routestops rs
	LEFT JOIN pickuprequests pr ON rs.request_id = pr.request_id
	LEFT JOIN users u ON pr.resident_id = u.user_id
	LEFT JOIN recyclabletypes rt ON pr.type_id = rt.type_id
	LEFT JOIN collection_points cp ON rs.point_id = cp.point_id
	WHERE rs.route_id = ?
	ORDER BY rs.stop_order ASC
");
$stmt->bind_param('i', $route_id);
$stmt->execute();
$stops = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

ob_start();
?>
<div class="space-y-4">
	<div class="text-sm text-bote-stone-600">Generated: <?php echo date('M j, Y g:i A', strtotime($route['generated_on'])); ?></div>
	<ol class="space-y-3 list-decimal list-inside">
		<?php foreach ($stops as $stop): ?>
			<li class="p-3 rounded-lg border border-bote-stone-200">
				<div class="flex items-start justify-between">
					<div class="w-full">
						<?php if ($stop['request_id']): ?>
							<!-- Home Pickup -->
							<div class="flex items-center space-x-2 mb-2">
								<i class="fas fa-home text-bote-info-600"></i>
								<span class="font-semibold text-bote-stone-900">Home Pickup</span>
							</div>
							<div class="font-medium text-bote-stone-900">
								<?php echo htmlspecialchars(($stop['first_name'] ?? '') . ' ' . ($stop['last_name'] ?? '')); ?>
							</div>
							<div class="text-sm text-bote-stone-600">
								<?php echo htmlspecialchars($stop['material_type'] ?? ''); ?> � <?php echo htmlspecialchars($stop['address'] ?? ''); ?>
							</div>
							<div class="text-xs text-bote-stone-500">Status: <?php echo htmlspecialchars($stop['status'] ?? 'Scheduled'); ?></div>
						<?php elseif ($stop['point_id']): ?>
							<!-- Collection Point -->
							<div class="flex items-center space-x-2 mb-2">
								<i class="fas fa-recycle text-bote-green-600"></i>
								<span class="font-semibold text-bote-stone-900">Collection Point</span>
							</div>
							<div class="font-medium text-bote-stone-900">
								<?php echo htmlspecialchars($stop['collection_point_name'] ?? 'Collection Point'); ?>
							</div>
							<div class="text-sm text-bote-stone-600">
								<?php echo htmlspecialchars($stop['collection_description'] ?? 'Collection Point for recyclables'); ?>
							</div>
							<div class="text-xs text-bote-stone-500">Type: Collection Point</div>
						<?php else: ?>
							<!-- Starting Point -->
							<div class="flex items-center space-x-2 mb-2">
								<i class="fas fa-flag-checkered text-red-600"></i>
								<span class="font-semibold text-bote-stone-900">Starting Point</span>
							</div>
							<div class="text-sm text-bote-stone-600">Lat: <?php echo e($stop['latitude']); ?>, Lng: <?php echo e($stop['longitude']); ?></div>
						<?php endif; ?>
						
						<div class="mt-2 text-xs text-bote-stone-400">
							Coordinates: <?php echo e($stop['latitude']); ?>, <?php echo e($stop['longitude']); ?>
						</div>
					</div>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
</div>
<?php
$html = ob_get_clean();

echo json_encode(['success' => true, 'html' => $html]);
?>


