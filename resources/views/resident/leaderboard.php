<!-- Main Content -->
<div class="p-8 bg-bote-stone-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-full flex items-center justify-center">
                    <i data-lucide="trophy" class="w-6 h-6 text-white"></i>
                </div>
                <h1 class="text-3xl font-bold text-bote-stone-800"><?php echo htmlspecialchars($system_name); ?> Leaderboard</h1>
            </div>
            <p class="text-bote-stone-600 text-lg">Track recycling performance of individuals and puroks (<?php echo htmlspecialchars($period_label); ?> Rankings)</p>
        </div>

        <!-- Summary Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-bote-stone-800 mb-2">Community Impact (<?php echo htmlspecialchars($period_label); ?>)</h3>
                    <div class="flex items-center gap-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-bote-green-600"><?php echo number_format($community_stats['total_points']); ?></div>
                            <div class="text-sm text-bote-stone-600">Total Points</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-bote-info-600"><?php echo number_format($community_stats['active_recyclers']); ?></div>
                            <div class="text-sm text-bote-stone-600">Active Recyclers</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-bote-green-600"><?php echo number_format($community_stats['total_weight'], 1); ?>kg</div>
                            <div class="text-sm text-bote-stone-600">Waste Recycled</div>
                        </div>
                    </div>
                </div>
                <div class="text-6xl">?</div>
            </div>
        </div>

        <!-- Time Period Toggle -->
        <div class="flex justify-center mb-8">
            <div class="bg-white rounded-lg p-1 shadow-md">
                <a href="?period=<?php echo htmlspecialchars('weekly'); ?>" 
                   class="px-6 py-2 rounded-md font-medium transition-all duration-300 <?php echo e($period === 'weekly' ? 'bg-gradient-to-r from-bote-green-500 to-bote-green-600 text-white' : 'text-bote-stone-600 hover:text-bote-stone-800'); ?>">
                    Weekly Rankings
                </a>
                <a href="?period=monthly" 
                   class="px-6 py-2 rounded-md font-medium transition-all duration-300 <?php echo e($period === 'monthly' ? 'bg-gradient-to-r from-bote-green-500 to-bote-green-600 text-white' : 'text-bote-stone-600 hover:text-bote-stone-800'); ?>">
                    Monthly Rankings
                </a>
            </div>
        </div>

        <!-- Leaderboard Tabs -->
        <div class="flex justify-center mb-8">
            <div class="bg-white rounded-lg p-1 shadow-md">
                <button onclick="showTab('individual')" id="individualTab" class="px-8 py-3 rounded-md font-medium transition-all duration-300 bg-gradient-to-r from-bote-green-500 to-bote-green-600 text-white">
                    Individual Leaderboard
                </button>
                <button onclick="showTab('purok')" id="purokTab" class="px-8 py-3 rounded-md font-medium transition-all duration-300 text-bote-stone-600 hover:text-bote-stone-800">
                    Purok Leaderboard
                </button>
            </div>
        </div>

        <!-- Individual Leaderboard -->
        <div id="individualLeaderboard" class="space-y-4">
            <?php if (!empty($individual_leaderboard)): ?>
                <!-- Individual Rankings Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <i data-lucide="users" class="w-6 h-6"></i>
                            Individual Leaderboard (<?php echo htmlspecialchars($period_label); ?>)
                        </h3>
                        <p class="text-bote-green-100 mt-1">Rankings based on total points earned from recycling activities</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-bote-stone-50 border-b">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold text-bote-stone-900">Rank</th>
                                    <th class="px-6 py-4 text-left font-semibold text-bote-stone-900">Resident</th>
                                    <th class="px-6 py-4 text-center font-semibold text-bote-stone-900">Points Balance</th>
                                    <th class="px-6 py-4 text-center font-semibold text-bote-stone-900">Requests</th>
                                    <th class="px-6 py-4 text-center font-semibold text-bote-stone-900">Achievement</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-bote-stone-200">
                                <?php foreach ($individual_leaderboard as $index => $user): 
                                    $profile = getProfileDisplay($user);
                                    $badge = getBadge($user['total_points']);
                                    $rank = $index + 1;
                                    
                                    // Special styling for top 3
                                    $row_class = "";
                                    $rank_badge = "";
                                    if ($rank === 1) {
                                        $row_class = "bg-gradient-to-r from-yellow-50 to-orange-50";
                                        $rank_badge = "?";
                                    } elseif ($rank === 2) {
                                        $row_class = "bg-bote-stone-50";
                                        $rank_badge = "?";
                                    } elseif ($rank === 3) {
                                        $row_class = "bg-orange-50";
                                        $rank_badge = "?";
                                    }
                                ?>
                                <tr class="hover:bg-bote-stone-50 transition-colors duration-200 <?php echo e($row_class); ?>">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-2xl"><?php echo e($rank_badge); ?></span>
                                            <span class="font-bold text-lg text-bote-stone-800"><?php echo e($rank); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <?php if ($profile['type'] === 'photo'): ?>
                                                <img src="<?php echo htmlspecialchars($profile['src']); ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover ring-2 ring-bote-green-200">
                                            <?php else: ?>
                                                <div class="w-12 h-12 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-full flex items-center justify-center text-white font-bold ring-2 ring-bote-green-200">
                                                    <?php echo htmlspecialchars($profile['initials']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="font-medium text-bote-stone-900"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                                <div class="text-sm text-bote-stone-500">Resident</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-2xl font-bold <?php echo e($user['total_points'] > 0 ? 'text-bote-green-600' : ($user['total_points'] < 0 ? 'text-red-500' : 'text-bote-stone-400')); ?>">
                                            <?php echo number_format($user['total_points']); ?>
                                        </div>
                                        <div class="text-sm text-bote-stone-500">points</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-lg font-semibold text-bote-stone-700"><?php echo e($user['total_requests']); ?></div>
                                        <div class="text-sm text-bote-stone-500">completed</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-block <?php echo e($badge['class']); ?> px-3 py-2 rounded-full text-sm font-medium">
                                            <?php echo htmlspecialchars($badge['name']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                    <div class="text-6xl mb-4">?</div>
                    <h3 class="text-xl font-bold text-bote-stone-800 mb-2">Welcome to the Leaderboard!</h3>
                    <p class="text-bote-stone-600">No residents have been registered yet. Be the first to join and start earning points for recycling!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Purok Leaderboard -->
        <div id="purokLeaderboard" class="space-y-4 hidden">
            <?php if (!empty($purok_leaderboard)): ?>
                <!-- Purok Rankings Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-bote-info-500 to-bote-info-600 px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <i data-lucide="map" class="w-6 h-6"></i>
                            Purok Leaderboard (<?php echo htmlspecialchars($period_label); ?>)
                        </h3>
                        <p class="text-bote-info-100 mt-1">Community rankings based on collective recycling efforts</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-bote-stone-50 border-b">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold text-bote-stone-900">Rank</th>
                                    <th class="px-6 py-4 text-left font-semibold text-bote-stone-900">Purok</th>
                                    <th class="px-6 py-4 text-center font-semibold text-bote-stone-900">Total Points</th>
                                    <th class="px-6 py-4 text-center font-semibold text-bote-stone-900">Members</th>
                                    <th class="px-6 py-4 text-center font-semibold text-bote-stone-900">Requests</th>
                                    <th class="px-6 py-4 text-center font-semibold text-bote-stone-900">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-bote-stone-200">
                                <?php foreach ($purok_leaderboard as $index => $purok): 
                                    $badge = getPurokBadge($purok['total_points']);
                                    $rank = $index + 1;
                                    
                                    // Special styling for top 3
                                    $row_class = "";
                                    $rank_badge = "";
                                    if ($rank === 1) {
                                        $row_class = "bg-gradient-to-r from-yellow-50 to-orange-50";
                                        $rank_badge = "?";
                                    } elseif ($rank === 2) {
                                        $row_class = "bg-bote-stone-50";
                                        $rank_badge = "?";
                                    } elseif ($rank === 3) {
                                        $row_class = "bg-orange-50";
                                        $rank_badge = "?";
                                    }
                                ?>
                                <tr class="hover:bg-bote-stone-50 transition-colors duration-200 <?php echo e($row_class); ?>">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-2xl"><?php echo e($rank_badge); ?></span>
                                            <span class="font-bold text-lg text-bote-stone-800"><?php echo e($rank); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 bg-gradient-to-br from-bote-info-500 to-bote-info-600 rounded-full flex items-center justify-center text-white font-bold ring-2 ring-bote-info-200">
                                                P<?php echo e($purok['purok_id']); ?>
                                            </div>
                                            <div>
                                                <div class="font-medium text-bote-stone-900"><?php echo htmlspecialchars($purok['purok_name']); ?></div>
                                                <div class="text-sm text-bote-stone-500">Community Area</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-2xl font-bold <?php echo e($purok['total_points'] > 0 ? 'text-bote-green-600' : ($purok['total_points'] < 0 ? 'text-red-500' : 'text-bote-stone-400')); ?>">
                                            <?php echo number_format($purok['total_points']); ?>
                                        </div>
                                        <div class="text-sm text-bote-stone-500">total points</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-lg font-semibold text-bote-stone-700"><?php echo e($purok['active_members']); ?></div>
                                        <div class="text-sm text-bote-stone-500">residents</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-lg font-semibold text-bote-stone-700"><?php echo e($purok['total_requests']); ?></div>
                                        <div class="text-sm text-bote-stone-500">completed</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-block <?php echo e($badge['class']); ?> px-3 py-2 rounded-full text-sm font-medium">
                                            <?php echo htmlspecialchars($badge['name']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                    <div class="text-6xl mb-4">??</div>
                    <h3 class="text-xl font-bold text-bote-stone-800 mb-2">Welcome Puroks!</h3>
                    <p class="text-bote-stone-600">No puroks have been set up yet. Contact your administrator to add community areas.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
