<?php

declare(strict_types=1);

$pages = [
    'admin/admin_users.php',
    'admin/admin_pickup_requests.php',
    'admin/admin_rewards.php',
    'admin/admin_reports.php',
    'admin/admin_puroks.php',
    'admin/admin_recyclable_types.php',
    'admin/admin_collection_points.php',
    'admin/admin_profile.php',
    'admin/admin_settings.php',
    'admin/admin_sms_settings.php',
    'admin/carousel_editor.php',
    'resident/resident_profile.php',
    'resident/resident_new_request.php',
    'resident/resident_save_request.php',
    'resident/resident_my_requests.php',
    'resident/resident_history.php',
    'resident/resident_rewards.php',
    'resident/resident_redeem_reward.php',
    'resident/resident_leaderboard.php',
    'resident/resident_direct_dropoff.php',
    'resident/resident_dropoff_success.php',
    'worker/worker_profile.php',
    'worker/worker_assigned_pickups.php',
    'worker/worker_collection_routes.php',
    'worker/worker_completed_collections.php',
    'worker/worker_daily_reports.php',
    'worker/worker_verification.php',
    'admin/carousel_handler.php',
    'admin/collection_points_handler.php',
    'admin/save_settings.php',
    'resident/cancel_request.php',
    'worker/complete_pickup.php',
    'worker/generate_route.php',
    'worker/get_map_data.php',
    'worker/get_route_coordinates.php',
    'worker/get_route_details.php',
];

$base = realpath(__DIR__ . '/..');

foreach ($pages as $page) {
    $cmd = 'git -C ' . escapeshellarg($base) . ' checkout -- ' . escapeshellarg($page);
    passthru($cmd, $code);
    if ($code === 0) {
        echo "Restored $page\n";
    } else {
        echo "Failed to restore $page\n";
    }
}
