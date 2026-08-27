<?php
require_once '../config/database.php';
require '../includes/auth_worker.php'; // Ensure only worker can access

// Get user data for sidebar
$user_id = $_SESSION['user_id'];

// Handle form submission for new report
$errors = [];
$success_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_report'])) {
    $report_date = $_POST['report_date'] ?? null;
    $collections_count = (int) ($_POST['collections_count'] ?? 0);
    $total_weight = (float) ($_POST['total_weight'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $route_efficiency = $_POST['route_efficiency'] ?? 'good';

    if (empty($report_date)) {
        $errors[] = "Report date is required";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $report_date)) {
        $errors[] = "Report date is invalid";
    }
    if ($collections_count < 0) $errors[] = "Collections count cannot be negative";
    if ($total_weight < 0) $errors[] = "Total weight cannot be negative";
    if (!in_array($route_efficiency, ['excellent', 'good', 'average', 'needs_improvement'], true)) {
        $errors[] = "Invalid route efficiency";
    }

    if (empty($errors)) {
        $stmt = prepare_statement($conn, "
            INSERT INTO daily_reports (worker_id, report_date, collections_count, total_weight, route_efficiency, notes)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                collections_count = VALUES(collections_count),
                total_weight = VALUES(total_weight),
                route_efficiency = VALUES(route_efficiency),
                notes = VALUES(notes)
        ");
        if ($stmt) {
            $stmt->bind_param("isidss", $user_id, $report_date, $collections_count, $total_weight, $route_efficiency, $notes);
            if ($stmt->execute()) {
                $success_message = "Daily report saved successfully!";
            } else {
                $errors[] = "Failed to save report. Please try again.";
            }
        } else {
            $errors[] = "Database error. Please try again.";
        }
    }
}

// Get today's stats
$today = date('Y-m-d');
$stmt = prepare_statement($conn, "
    SELECT 
        COUNT(*) as today_collections,
        SUM(pr.weight_kg) as today_weight,
        SUM(pl.points) as today_points
    FROM pickuprequests pr
    LEFT JOIN pointsledger pl ON pr.request_id = pl.request_id
    WHERE pr.assigned_worker_id = ? 
    AND pr.status = 'Completed'
    AND DATE(pr.preferred_date) = ?
");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_stats = $stmt->get_result()->fetch_assoc();

// Get this week's stats
$week_start = date('Y-m-d', strtotime('monday this week'));
$stmt = prepare_statement($conn, "
    SELECT 
        COUNT(*) as week_collections,
        SUM(pr.weight_kg) as week_weight
    FROM pickuprequests pr
    WHERE pr.assigned_worker_id = ? 
    AND pr.status = 'Completed'
    AND DATE(pr.preferred_date) >= ?
");
$stmt->bind_param("is", $user_id, $week_start);
$stmt->execute();
$week_stats = $stmt->get_result()->fetch_assoc();

// Get saved reports for the last 7 days
$stmt = prepare_statement($conn, "
    SELECT report_id, report_date, collections_count, total_weight, route_efficiency, notes
    FROM daily_reports
    WHERE worker_id = ?
    AND report_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY report_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$saved_reports = $stmt->get_result();

// Get today's saved report for form defaults
$stmt = prepare_statement($conn, "
    SELECT collections_count, total_weight, route_efficiency, notes
    FROM daily_reports
    WHERE worker_id = ? AND report_date = ?
");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_report = $stmt->get_result()->fetch_assoc();

// Get daily breakdown for the last 7 days
$stmt = prepare_statement($conn, "
    SELECT 
        DATE(pr.preferred_date) as collection_date,
        COUNT(*) as daily_collections,
        SUM(pr.weight_kg) as daily_weight
    FROM pickuprequests pr
    WHERE pr.assigned_worker_id = ? 
    AND pr.status = 'Completed'
    AND DATE(pr.preferred_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(pr.preferred_date)
    ORDER BY collection_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$daily_breakdown = $stmt->get_result();

include 'worker_sidebar.php';
?>

<!-- Main Content -->
<div class="ml-64 p-8 bg-bote-stone-50 min-h-screen">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-bote-stone-900 mb-2">Daily Reports</h1>
                    <p class="text-bote-stone-600">Track your daily performance and create reports</p>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="showReportModal()" class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white px-6 py-3 rounded-xl font-medium transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center space-x-2">
                        <i data-lucide="file-plus" class="w-5 h-5"></i>
                        <span>Create Report</span>
                    </button>
                </div>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
        <div class="bg-bote-green-50 border border-bote-green-200 text-bote-green-700 px-4 py-3 rounded-xl mb-6">
            <div class="flex items-center">
                <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
            <div class="flex items-start">
                <i data-lucide="alert-circle" class="w-5 h-5 mr-2 mt-0.5"></i>
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Today's Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-bote-stone-600 mb-1">Today's Collections</p>
                        <p class="text-2xl font-bold text-bote-green-600"><?php echo number_format($today_stats['today_collections'] ?? 0); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-bote-green-100 rounded-full flex items-center justify-center">
                        <i data-lucide="package" class="w-6 h-6 text-bote-green-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-bote-stone-600 mb-1">Today's Weight</p>
                        <p class="text-2xl font-bold text-bote-info-600"><?php echo number_format($today_stats['today_weight'] ?? 0, 1); ?> kg</p>
                    </div>
                    <div class="w-12 h-12 bg-bote-info-100 rounded-full flex items-center justify-center">
                        <i data-lucide="weight" class="w-6 h-6 text-bote-info-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-bote-stone-600 mb-1">This Week</p>
                        <p class="text-2xl font-bold text-bote-green-600"><?php echo number_format($week_stats['week_collections'] ?? 0); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-bote-green-100 rounded-full flex items-center justify-center">
                        <i data-lucide="calendar-days" class="w-6 h-6 text-bote-green-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-bote-stone-600 mb-1">Week Weight</p>
                        <p class="text-2xl font-bold text-orange-600"><?php echo number_format($week_stats['week_weight'] ?? 0, 1); ?> kg</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-6 h-6 text-orange-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Chart -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-semibold text-bote-stone-900 mb-6">Weekly Performance</h2>
            <div class="grid grid-cols-7 gap-4">
                <?php
                $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                $daily_data = [];
                while ($row = $daily_breakdown->fetch_assoc()) {
                    $daily_data[$row['collection_date']] = $row;
                }
                
                for ($i = 6; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $day_name = date('D', strtotime($date));
                    $collections = isset($daily_data[$date]) ? $daily_data[$date]['daily_collections'] : 0;
                    $weight = isset($daily_data[$date]) ? $daily_data[$date]['daily_weight'] : 0;
                    $height = $collections > 0 ? min(100, ($collections / 10) * 100) : 0;
                ?>
                <div class="text-center">
                    <div class="bg-bote-stone-100 rounded-lg h-32 flex items-end justify-center mb-2 relative">
                        <div class="bg-bote-green-500 rounded-t w-8 transition-all duration-300" style="height: <?php echo e($height); ?>%"></div>
                        <div class="absolute top-1 text-xs text-bote-stone-600"><?php echo e($collections); ?></div>
                    </div>
                    <div class="text-sm font-medium text-bote-stone-700"><?php echo e($day_name); ?></div>
                    <div class="text-xs text-bote-stone-500"><?php echo number_format($weight, 1); ?>kg</div>
                </div>
                <?php } ?>
            </div>
        </div>

        <!-- Daily Breakdown -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-bote-stone-200 bg-bote-stone-50">
                <h2 class="text-xl font-semibold text-bote-stone-900">Saved Daily Reports (Last 7 Days)</h2>
            </div>

            <?php if ($saved_reports->num_rows > 0): ?>
                <div class="divide-y divide-bote-stone-200">
                    <?php while ($day = $saved_reports->fetch_assoc()): ?>
                        <div class="p-6 hover:bg-bote-stone-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-bote-stone-900 mb-2">
                                        <?php echo htmlspecialchars(date('l, F j, Y', strtotime($day['report_date']))); ?>
                                    </h3>

                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-sm mb-3">
                                        <div>
                                            <span class="text-bote-stone-500">Collections:</span>
                                            <span class="font-medium text-bote-green-600 ml-1"><?php echo (int) $day['collections_count']; ?></span>
                                        </div>
                                        <div>
                                            <span class="text-bote-stone-500">Total Weight:</span>
                                            <span class="font-medium text-bote-info-600 ml-1"><?php echo number_format((float) $day['total_weight'], 1); ?> kg</span>
                                        </div>
                                        <div>
                                            <span class="text-bote-stone-500">Avg per Collection:</span>
                                            <span class="font-medium text-bote-green-600 ml-1">
                                                <?php echo e($day['collections_count'] > 0 ? number_format((float) $day['total_weight'] / (int) $day['collections_count'], 1) : 0); ?> kg
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-bote-stone-500">Efficiency:</span>
                                            <span class="font-medium text-orange-600 ml-1"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $day['route_efficiency']))); ?></span>
                                        </div>
                                    </div>

                                    <?php if (!empty($day['notes'])): ?>
                                        <p class="text-sm text-bote-stone-600 bg-bote-stone-50 p-3 rounded-lg mt-2">
                                            <?php echo htmlspecialchars($day['notes']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-bote-stone-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="calendar-x" class="w-12 h-12 text-bote-stone-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-bote-stone-900 mb-2">No saved reports yet</h3>
                    <p class="text-bote-stone-500">Create your first daily report with the button above</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Report Modal -->
<div id="reportModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-bote-stone-900">Create Daily Report</h3>
            <button onclick="hideReportModal()" class="text-bote-stone-400 hover:text-bote-stone-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <?php echo \App\Core\Csrf::field(); ?>
            <input type="hidden" name="create_report" value="1">
            
            <div>
                <label for="report_date" class="block text-sm font-medium text-bote-stone-700 mb-2">Report Date</label>
                <input type="date" id="report_date" name="report_date" value="<?php echo date('Y-m-d'); ?>"
                       class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500"
                       required>
            </div>
            
            <div>
                <label for="collections_count" class="block text-sm font-medium text-bote-stone-700 mb-2">Collections Completed</label>
                <input type="number" id="collections_count" name="collections_count" min="0" 
                       value="<?php echo (int) ($today_report['collections_count'] ?? $today_stats['today_collections'] ?? 0); ?>"
                       class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500"
                       required>
            </div>
            
            <div>
                <label for="total_weight" class="block text-sm font-medium text-bote-stone-700 mb-2">Total Weight (kg)</label>
                <input type="number" id="total_weight" name="total_weight" min="0" step="0.1"
                       value="<?php echo number_format((float) ($today_report['total_weight'] ?? $today_stats['today_weight'] ?? 0), 1, '.', ''); ?>"
                       class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500"
                       required>
            </div>
            
            <div>
                <label for="route_efficiency" class="block text-sm font-medium text-bote-stone-700 mb-2">Route Efficiency</label>
                <?php $selectedEfficiency = $today_report['route_efficiency'] ?? 'good'; ?>
                <select id="route_efficiency" name="route_efficiency"
                        class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500">
                    <option value="excellent" <?php echo e($selectedEfficiency === 'excellent' ? 'selected' : ''); ?>>Excellent</option>
                    <option value="good" <?php echo e($selectedEfficiency === 'good' ? 'selected' : ''); ?>>Good</option>
                    <option value="average" <?php echo e($selectedEfficiency === 'average' ? 'selected' : ''); ?>>Average</option>
                    <option value="needs_improvement" <?php echo e($selectedEfficiency === 'needs_improvement' ? 'selected' : ''); ?>>Needs Improvement</option>
                </select>
            </div>
            
            <div>
                <label for="notes" class="block text-sm font-medium text-bote-stone-700 mb-2">Notes</label>
                <textarea id="notes" name="notes" rows="3"
                          class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500"
                          placeholder="Any additional notes or observations..."><?php echo htmlspecialchars($today_report['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            
            <div class="flex space-x-4 pt-4">
                <button type="button" onclick="hideReportModal()" 
                        class="flex-1 px-4 py-2 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-800 rounded-xl font-medium transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-bote-green-600 hover:bg-bote-green-700 text-white rounded-xl font-medium transition-colors">
                    Create Report
                </button>
            </div>
        </form>
    </div>
</div>

<script type="module" src="assets/js/worker_daily_reports.js"></script>
