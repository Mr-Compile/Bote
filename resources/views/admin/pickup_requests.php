    <style>
        .modal-hidden {
            display: none !important;
        }
        .modal-visible {
            display: flex !important;
        }
    </style>
<div class="p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-bote-stone-900 mb-2">Pickup Requests Management</h1>
                <p class="text-bote-stone-600">Manage and track all pickup requests</p>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="bg-bote-green-100 border border-bote-green-400 text-bote-green-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="clock" class="w-6 h-6 text-yellow-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-bote-stone-900"><?php echo (int) ($pickup_stats['Pending'] ?? 0); ?></p>
                            <p class="text-sm text-bote-stone-600">Pending</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-bote-info-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="calendar" class="w-6 h-6 text-bote-info-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-bote-stone-900"><?php echo (int) ($pickup_stats['Scheduled'] ?? 0); ?></p>
                            <p class="text-sm text-bote-stone-600">Scheduled</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="truck" class="w-6 h-6 text-orange-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-bote-stone-900"><?php echo (int) ($pickup_stats['In Progress'] ?? 0); ?></p>
                            <p class="text-sm text-bote-stone-600">In Progress</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-bote-green-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="check-circle" class="w-6 h-6 text-bote-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-bote-stone-900"><?php echo (int) ($pickup_stats['Completed'] ?? 0); ?></p>
                            <p class="text-sm text-bote-stone-600">Completed</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="x-circle" class="w-6 h-6 text-red-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-2xl font-bold text-bote-stone-900"><?php echo (int) ($pickup_stats['Cancelled'] ?? 0); ?></p>
                            <p class="text-sm text-bote-stone-600">Cancelled</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pickup Requests Table -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-bote-stone-200">
                    <h2 class="text-xl font-semibold text-bote-stone-900">All Pickup Requests</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-bote-stone-200">
                        <thead class="bg-bote-stone-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Request ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Resident</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Weight</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Worker</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-bote-stone-200">
                            <?php foreach ($pickup_requests as $request): ?>
                                <tr class="hover:bg-bote-stone-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-bote-stone-900">
                                        #<?php echo (int) $request['request_id']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-bote-stone-900">
                                            <?php echo htmlspecialchars($request['resident_name']); ?>
                                        </div>
                                        <div class="text-sm text-bote-stone-500"><?php echo htmlspecialchars($request['resident_phone']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-bote-stone-900"><?php echo htmlspecialchars($request['type_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-bote-stone-900">
                                            Est: <?php echo number_format((float) $request['weight_kg'], 2); ?>kg
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-bote-stone-900">
                                            <?php echo e($request['worker_name'] ? htmlspecialchars($request['worker_name']) : 'Not assigned'); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php 
                                            switch($request['status']) {
                                                case 'Pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'Scheduled': echo 'bg-bote-info-100 text-bote-info-800'; break;
                                                case 'In Progress': echo 'bg-orange-100 text-orange-800'; break;
                                                case 'Completed': echo 'bg-bote-green-100 text-bote-green-800'; break;
                                                case 'Cancelled': echo 'bg-red-100 text-red-800'; break;
                                                default: echo 'bg-bote-stone-100 text-bote-stone-800';
                                            }
                                            ?>">
                                            <?php echo htmlspecialchars($request['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-bote-stone-500">
                                        <?php echo date('M j, Y', strtotime($request['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($request['status'] === 'Pending'): ?>
                                            <button onclick="showAssignWorkerModal(<?php echo htmlspecialchars(json_encode($request, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS), ENT_QUOTES, 'UTF-8'); ?>)" 
                                                    class="text-bote-info-600 hover:text-bote-info-900 mr-2">
                                                <i data-lucide="user-plus" class="w-4 h-4"></i>
                                            </button>
                                        <?php elseif ($request['status'] === 'Scheduled'): ?>
                                            <button onclick="showCompleteModal(<?php echo htmlspecialchars(json_encode($request, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS), ENT_QUOTES, 'UTF-8'); ?>)" 
                                                    class="text-bote-green-600 hover:text-bote-green-900 mr-2">
                                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button onclick="showStatusModal(<?php echo htmlspecialchars(json_encode($request, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS), ENT_QUOTES, 'UTF-8'); ?>)" 
                                                class="text-bote-info-600 hover:text-bote-info-900">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Worker Modal -->
    <div id="assignWorkerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-bote-stone-900 mb-4">Assign Worker</h3>
            <form method="POST">
                <?php echo \App\Core\Csrf::field(); ?>
                <input type="hidden" name="action" value="assign_worker">
                <input type="hidden" name="request_id" id="assign_request_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-bote-stone-700 mb-2">Request</label>
                    <p id="assign_request_info" class="text-bote-stone-900"></p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-bote-stone-700 mb-2">Select Worker</label>
                    <select name="worker_id" required 
                            class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500">
                        <option value="">Choose a worker...</option>
                        <?php foreach ($workers as $worker): ?>
                            <option value="<?php echo e($worker['user_id']); ?>">
                                <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex space-x-4">
                    <button type="button" onclick="hideAssignWorkerModal()" 
                            class="flex-1 px-4 py-2 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-800 rounded-lg font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-bote-green-600 hover:bg-bote-green-700 text-white rounded-lg font-medium transition-colors">
                        Assign Worker
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Complete Request Modal -->
    <div id="completeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-bote-stone-900 mb-4">Complete Pickup Request</h3>
            <form method="POST">
                <?php echo \App\Core\Csrf::field(); ?>
                <input type="hidden" name="action" value="complete_request">
                <input type="hidden" name="request_id" id="complete_request_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-bote-stone-700 mb-2">Request Details</label>
                    <p id="complete_request_info" class="text-bote-stone-900"></p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-bote-stone-700 mb-2">Actual Weight (kg)</label>
                    <input type="number" name="actual_weight" step="0.1" min="0" required 
                           class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500">
                </div>
                
                <div class="flex space-x-4">
                    <button type="button" onclick="hideCompleteModal()" 
                            class="flex-1 px-4 py-2 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-800 rounded-lg font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-bote-green-600 hover:bg-bote-green-700 text-white rounded-lg font-medium transition-colors">
                        Complete Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-bote-stone-900 mb-4">Update Status</h3>
            <form method="POST">
                <?php echo \App\Core\Csrf::field(); ?>
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="request_id" id="status_request_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-bote-stone-700 mb-2">Request</label>
                    <p id="status_request_info" class="text-bote-stone-900"></p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-bote-stone-700 mb-2">New Status</label>
                    <select name="new_status" id="status_select" required 
                            class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bote-green-500">
                        <option value="Pending">Pending</option>
                        <option value="Scheduled">Scheduled</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="flex space-x-4">
                    <button type="button" onclick="hideStatusModal()" 
                            class="flex-1 px-4 py-2 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-800 rounded-lg font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-bote-green-600 hover:bg-bote-green-700 text-white rounded-lg font-medium transition-colors">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
