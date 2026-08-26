<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/system_functions.php';

// Ensure system settings table exists
ensureSystemSettingsTable($conn);

// Get user data from database
$user_id = $_SESSION['user_id'];
$stmt = prepare_statement($conn, "SELECT first_name, last_name, email, phone, address, profile_photo FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Get system settings
$system_name = getSystemSetting($conn, 'barangay_name', 'BoteBank');
$system_logo = getSystemSetting($conn, 'barangay_logo', '');

// Get user profile display info
$profile_display = getUserProfileDisplay($user_data);

// Get user statistics - separate queries for better accuracy
// Get points from pointsledger
$stmt = prepare_statement($conn, "
    SELECT COALESCE(SUM(CASE WHEN transaction_type = 'Earned' THEN points ELSE -points END), 0) as total_points
    FROM pointsledger WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$points_result = $stmt->get_result()->fetch_assoc();
$total_points = $points_result['total_points'] ?? 0;

// Get request statistics
$stmt = prepare_statement($conn, "
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_requests,
        SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled_requests,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_requests,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_requests,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_requests
    FROM pickuprequests WHERE resident_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Ensure we have valid data
if (!$stats) {
    $stats = [
        'total_requests' => 0,
        'pending_requests' => 0,
        'scheduled_requests' => 0,
        'in_progress_requests' => 0,
        'completed_requests' => 0,
        'cancelled_requests' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resident Dashboard - <?php echo htmlspecialchars($system_name); ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script>
    // Fallback for CDN failures
    setTimeout(() => {
        if (typeof lucide === 'undefined') console.warn('Lucide icons failed to load from CDN');
        if (!window.tailwind) console.warn('Tailwind CSS failed to load from CDN');
    }, 1000);
</script>
<style>
    .sidebar-active { background: linear-gradient(135deg, #10b981, #059669); color: white; }
    .sidebar-active i { color: white; }
    .notification-badge { animation: pulse 2s infinite; }
    .points-glow { box-shadow: 0 0 20px rgba(16, 185, 129, 0.3); }
</style>
</head>
<body class="bg-gray-50 font-sans">

<!-- Sidebar -->
<div class="fixed left-0 top-0 h-full w-64 bg-white shadow-xl flex flex-col z-50">
    <!-- Header -->
    <div class="bg-gradient-to-br from-green-500 via-green-600 to-emerald-700 p-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-black bg-opacity-10"></div>
        <div class="relative z-10 flex items-center space-x-3 mb-2">
            <?php if (file_exists("../uploads/$system_logo")): ?>
                <img src="../uploads/<?php echo htmlspecialchars($system_logo); ?>" class="w-10 h-10 rounded-xl object-cover bg-white bg-opacity-20 p-1" alt="System Logo">
            <?php else: ?>
                <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                    <i data-lucide="recycle" class="w-6 h-6 text-white"></i>
                </div>
            <?php endif; ?>
            <h1 class="text-xl font-bold text-white"><?php echo htmlspecialchars($system_name); ?></h1>
        </div>
        <p class="text-green-100 text-sm font-medium">Resident Portal</p>
    </div>

    <!-- User Info -->
    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-green-50 to-emerald-50 flex items-center space-x-3">
        <?php if ($profile_display['type'] === 'photo'): ?>
            <img src="../uploads/profiles/thumbs/<?php echo htmlspecialchars($profile_display['value']); ?>" class="w-12 h-12 rounded-full object-cover ring-2 ring-green-200">
        <?php else: ?>
            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center ring-2 ring-green-200">
                <span class="text-white font-bold text-lg"><?php echo htmlspecialchars($profile_display['value']); ?></span>
            </div>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($user_data['first_name'].' '.$user_data['last_name']); ?></p>
            <p class="text-sm text-green-600 font-medium">Eco Resident</p>
            <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($user_data['email']); ?></p>
        </div>
    </div>

    

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
        <?php
        $links = [
            ['href'=>'resident_dashboard.php','icon'=>'layout-dashboard','label'=>'Dashboard','color'=>'green','badge'=>0],
            ['href'=>'resident_new_request.php','icon'=>'plus-circle','label'=>'Schedule Pickup','color'=>'blue','badge'=>0],
            ['href'=>'resident_direct_dropoff.php','icon'=>'map-pin','label'=>'Direct Drop-off','color'=>'indigo','badge'=>0],
            ['href'=>'resident_my_requests.php','icon'=>'list','label'=>'My Requests','color'=>'orange','badge'=>$stats['pending_requests']],
            ['href'=>'resident_history.php','icon'=>'coins','label'=>'History','color'=>'yellow','badge'=>0],
            ['href'=>'resident_rewards.php','icon'=>'gift','label'=>'Redeem Rewards','color'=>'purple','badge'=>0],
            ['href'=>'resident_leaderboard.php','icon'=>'trophy','label'=>'Leaderboard','color'=>'yellow','badge'=>0],
            ['divider'=>true],
            ['href'=>'resident_profile.php','icon'=>'user','label'=>'My Profile','color'=>'gray','badge'=>0],
            ['href'=>'resident_help.php','icon'=>'help-circle','label'=>'Help & Support','color'=>'indigo','badge'=>0]
        ];

        $currentPage = basename($_SERVER['PHP_SELF']);
        foreach($links as $link):
            if(isset($link['divider'])): ?>
                <div class="border-t border-gray-200 my-4"></div>
            <?php else: 
                $active = $link['href'] === $currentPage ? 'sidebar-active' : '';
        ?>
        <a href="<?php echo $link['href']; ?>" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-green-50 transition-all duration-200 text-gray-700 hover:text-green-700 <?php echo $active; ?>">
            <div class="w-8 h-8 rounded-lg bg-<?php echo $link['color']; ?>-100 group-hover:bg-<?php echo $link['color']; ?>-200 flex items-center justify-center transition-colors">
                <i data-lucide="<?php echo $link['icon']; ?>" class="w-4 h-4 text-<?php echo $link['color']; ?>-600"></i>
            </div>
            <span class="font-medium"><?php echo $link['label']; ?></span>
            <?php if(!empty($link['badge'])): ?>
                <span class="ml-auto bg-<?php echo $link['color']; ?>-100 text-<?php echo $link['color']; ?>-700 text-xs px-2 py-1 rounded-full notification-badge">
                    <?php echo $link['badge']; ?>
                </span>
            <?php endif; ?>
        </a>
        <?php endif; endforeach; ?>
    </nav>

    <!-- Logout Button -->
    <div class="p-4 border-t border-gray-100">
        <button onclick="ResidentSidebar.showLogoutModal()" class="w-full flex items-center justify-center space-x-2 p-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 rounded-xl text-white transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
            <i data-lucide="log-out" class="w-4 h-4"></i>
            <span class="font-medium">Logout</span>
        </button>
    </div>
</div>

<!-- Logout Modal -->
<div id="residentLogoutModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95">
        <div class="text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="log-out" class="w-8 h-8 text-red-600"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Confirm Logout</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to log out? Your recycling progress will be saved.</p>
            <div class="flex space-x-4">
                <button onclick="ResidentSidebar.hideLogoutModal()" class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl font-medium transition-colors">Cancel</button>
                <button onclick="ResidentSidebar.confirmLogout()" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium transition-colors">Logout</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if(typeof lucide !== 'undefined') lucide.createIcons();

    window.ResidentSidebar = {
        modal: document.getElementById('residentLogoutModal'),

        showLogoutModal() { this.modal?.classList.replace('hidden','flex'); setTimeout(()=>{ this.modal.querySelector('div > div')?.classList.replace('scale-95','scale-100'); },10); },
        hideLogoutModal() { 
            const inner = this.modal.querySelector('div > div'); 
            inner?.classList.replace('scale-100','scale-95'); 
            setTimeout(()=>{ this.modal?.classList.replace('flex','hidden'); },300);
        },
        confirmLogout() { window.location.href='../logout.php'; },

        init() {
            this.modal.addEventListener('click', e => { if(e.target === this.modal) this.hideLogoutModal(); });
            document.addEventListener('keydown', e => { if(e.key==='Escape' && !this.modal.classList.contains('hidden')) this.hideLogoutModal(); });
        }
    };

    ResidentSidebar.init();

    // Animate points
    const points = document.querySelector('.points-glow');
    if(points){ points.style.transform='scale(1.05)'; setTimeout(()=>{ points.style.transform='scale(1)'; },300); }

    // Highlight active page
    document.querySelectorAll('nav a').forEach(link => { if(link.href.endsWith(window.location.pathname.split('/').pop())) link.classList.add('sidebar-active'); });
});
</script>
</body>
</html>
