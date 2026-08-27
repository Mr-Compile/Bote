    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/pages/admin-puroks.css" >
<div class="p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Enhanced Header -->
            <div class="mb-8">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center shadow-lg">
                        <i data-lucide="map-pin" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-bote-stone-900">Purok Management</h1>
                        <p class="text-bote-stone-600 flex items-center">
                            <i data-lucide="home" class="w-4 h-4 mr-2"></i>
                            Manage purok areas and their assignments
                        </p>
                    </div>
                </div>
                
                <!-- Breadcrumb -->
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="admin_dashboard.php" class="inline-flex items-center text-sm font-medium text-bote-stone-700 hover:text-bote-info-600">
                                <i data-lucide="home" class="w-4 h-4 mr-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i data-lucide="chevron-right" class="w-4 h-4 text-bote-stone-400"></i>
                                <span class="ml-1 text-sm font-medium text-bote-stone-500 md:ml-2">Purok Management</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>

            <!-- Enhanced Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="bg-gradient-to-r from-bote-green-400 to-bote-green-500 border-0 text-white px-6 py-4 rounded-xl mb-6 shadow-lg notification flex items-center">
                    <i data-lucide="check-circle" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium"><?php echo htmlspecialchars($success_message); ?></span>
                    <button onclick="hideParent(this, 1)" class="ml-auto">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-gradient-to-r from-red-400 to-red-500 border-0 text-white px-6 py-4 rounded-xl mb-6 shadow-lg notification flex items-center">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-3"></i>
                    <span class="font-medium"><?php echo htmlspecialchars($error_message); ?></span>
                    <button onclick="hideParent(this, 1)" class="ml-auto">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Enhanced Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Total Puroks -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-bote-info-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-info-400 to-bote-info-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="map" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($total_puroks); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="layers" class="w-3 h-3 mr-1"></i>
                                    Total Puroks
                                </p>
                            </div>
                        </div>
                        <div class="text-bote-info-500">
                            <i data-lucide="trending-up" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Active Puroks -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-bote-green-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-green-400 to-bote-green-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($active_puroks); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="activity" class="w-3 h-3 mr-1"></i>
                                    Active Puroks
                                </p>
                            </div>
                        </div>
                        <div class="text-bote-green-500">
                            <i data-lucide="zap" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Total Residents -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-bote-green-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-bote-green-400 to-bote-green-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="users" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($total_residents); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="user-check" class="w-3 h-3 mr-1"></i>
                                    Total Residents
                                </p>
                            </div>
                        </div>
                        <div class="text-bote-green-500">
                            <i data-lucide="user-plus" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Newest Purok -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="plus-circle" class="w-6 h-6 text-white"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-2xl font-bold text-bote-stone-900"><?php echo e($new_puroks_month); ?></p>
                                <p class="text-sm text-bote-stone-600 flex items-center">
                                    <i data-lucide="calendar" class="w-3 h-3 mr-1"></i>
                                    New (30 days)
                                    <?php if ($new_puroks_month > 0): ?>
                                        <span class="ml-2 px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full badge-pulse">New</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="text-orange-500">
                            <i data-lucide="calendar-plus" class="w-5 h-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Puroks Table -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-bote-stone-100">
                <div class="px-6 py-5 border-b border-bote-stone-200 bg-gradient-to-r from-bote-info-50 to-bote-info-50">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-br from-bote-info-500 to-bote-info-600 rounded-lg flex items-center justify-center mr-3">
                                <i data-lucide="map" class="w-4 h-4 text-white"></i>
                            </div>
                            <h2 class="text-xl font-bold text-bote-stone-900">Purok Management</h2>
                        </div>
                        <button onclick="showAddPurokModal()" 
                                class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white px-6 py-2.5 rounded-xl font-medium transition-all duration-300 flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                            Add New Purok
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-bote-stone-200">
                        <thead class="bg-gradient-to-r from-bote-stone-50 to-bote-stone-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-bote-stone-600 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <i data-lucide="map-pin" class="w-3 h-3 mr-2"></i>
                                        Purok Info
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-bote-stone-600 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <i data-lucide="file-text" class="w-3 h-3 mr-2"></i>
                                        Description
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-bote-stone-600 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <i data-lucide="users" class="w-3 h-3 mr-2"></i>
                                        Residents
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-bote-stone-600 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <i data-lucide="calendar" class="w-3 h-3 mr-2"></i>
                                        Created
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-bote-stone-600 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <i data-lucide="settings" class="w-3 h-3 mr-2"></i>
                                        Actions
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-bote-stone-100">
                            <?php foreach ($puroks as $purok): ?>
                                <tr class="hover:bg-gradient-to-r hover:from-bote-info-50 hover:to-bote-info-50 transition-all duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-12 h-12 bg-gradient-to-br from-bote-info-500 to-bote-info-600 rounded-xl flex items-center justify-center shadow-lg">
                                                <span class="text-white font-bold text-sm">
                                                    <?php echo htmlspecialchars(strtoupper(substr($purok['name'], 0, 2))); ?>
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-semibold text-bote-stone-900 flex items-center">
                                                    <i data-lucide="map-pin" class="w-3 h-3 mr-1 text-bote-info-500"></i>
                                                    <?php echo htmlspecialchars($purok['name']); ?>
                                                </div>
                                                <div class="text-xs text-bote-stone-500 flex items-center mt-1">
                                                    <i data-lucide="hash" class="w-2 h-2 mr-1"></i>
                                                    ID: <?php echo e($purok['purok_id']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-bote-stone-900 max-w-xs">
                                            <?php echo htmlspecialchars($purok['description'] ?: 'No description available'); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full shadow-sm
                                                <?php echo e($purok['user_count'] > 0 ? 'bg-gradient-to-r from-bote-green-100 to-bote-green-200 text-bote-green-800 border border-bote-green-300' : 'bg-gradient-to-r from-bote-stone-100 to-bote-stone-200 text-bote-stone-600 border border-bote-stone-300'); ?>">
                                                <i data-lucide="users" class="w-3 h-3 mr-1"></i>
                                                <?php echo e($purok['user_count']); ?> resident<?php echo e($purok['user_count'] != 1 ? 's' : ''); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center text-sm text-bote-stone-500">
                                            <i data-lucide="calendar" class="w-3 h-3 mr-1"></i>
                                            <?php echo date('M j, Y', strtotime($purok['created_at'])); ?>
                                        </div>
                                        <div class="text-xs text-bote-stone-400 mt-1">
                                            <?php echo date('g:i A', strtotime($purok['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-2">
                                            <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($purok)); ?>)" 
                                                    class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-bote-info-500 to-bote-info-600 hover:from-bote-info-600 hover:to-bote-info-700 text-white text-xs font-medium rounded-lg transition-all duration-200 transform hover:scale-105 shadow-sm hover:shadow-md">
                                                <i data-lucide="edit" class="w-3 h-3 mr-1"></i>
                                                Edit
                                            </button>
                                            <?php if ($purok['user_count'] == 0): ?>
                                                <button onclick="confirmDelete(<?php echo e($purok['purok_id']); ?>)" 
                                                        class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white text-xs font-medium rounded-lg transition-all duration-200 transform hover:scale-105 shadow-sm hover:shadow-md">
                                                    <i data-lucide="trash-2" class="w-3 h-3 mr-1"></i>
                                                    Delete
                                                </button>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-3 py-1.5 bg-bote-stone-100 text-bote-stone-500 text-xs font-medium rounded-lg cursor-not-allowed" 
                                                      title="Cannot delete purok with residents">
                                                    <i data-lucide="lock" class="w-3 h-3 mr-1"></i>
                                                    Protected
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Add Purok Modal -->
    <div id="addPurokModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300 modal-enter">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 rounded-t-3xl px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                            <i data-lucide="plus-circle" class="w-5 h-5 text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Add New Purok</h3>
                    </div>
                    <button onclick="hideAddPurokModal()" class="text-white hover:text-bote-stone-200 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-8">
                <form method="POST" class="space-y-6">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <input type="hidden" name="action" value="add_purok">
                    
                    <div class="space-y-2">
                        <label class="flex items-center text-sm font-semibold text-bote-stone-700">
                            <i data-lucide="map-pin" class="w-4 h-4 mr-2 text-bote-green-500"></i>
                            Purok Name
                        </label>
                        <input type="text" name="name" required 
                               class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all duration-200 hover:border-bote-stone-400"
                               placeholder="e.g. Purok 1 - Mabini">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="flex items-center text-sm font-semibold text-bote-stone-700">
                            <i data-lucide="file-text" class="w-4 h-4 mr-2 text-bote-green-500"></i>
                            Description
                        </label>
                        <textarea name="description" rows="3"
                                  class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all duration-200 hover:border-bote-stone-400 resize-none"
                                  placeholder="Optional description of the purok area"></textarea>
                    </div>
                    
                    <div class="flex space-x-4 pt-4">
                        <button type="button" onclick="hideAddPurokModal()" 
                                class="flex-1 px-6 py-3 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-700 rounded-xl font-semibold transition-all duration-200 flex items-center justify-center">
                            <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 px-6 py-3 bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white rounded-xl font-semibold transition-all duration-200 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                            Add Purok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Enhanced Edit Purok Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300 modal-enter">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-bote-info-500 to-bote-info-600 rounded-t-3xl px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                            <i data-lucide="edit" class="w-5 h-5 text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Edit Purok</h3>
                    </div>
                    <button onclick="hideEditModal()" class="text-white hover:text-bote-stone-200 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-8">
                <form method="POST" class="space-y-6">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <input type="hidden" name="action" value="edit_purok">
                    <input type="hidden" name="purok_id" id="edit_purok_id">
                    
                    <div class="space-y-2">
                        <label class="flex items-center text-sm font-semibold text-bote-stone-700">
                            <i data-lucide="map-pin" class="w-4 h-4 mr-2 text-bote-info-500"></i>
                            Purok Name
                        </label>
                        <input type="text" name="name" id="edit_name" required 
                               class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all duration-200 hover:border-bote-stone-400">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="flex items-center text-sm font-semibold text-bote-stone-700">
                            <i data-lucide="file-text" class="w-4 h-4 mr-2 text-bote-info-500"></i>
                            Description
                        </label>
                        <textarea name="description" id="edit_description" rows="3"
                                  class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all duration-200 hover:border-bote-stone-400 resize-none"></textarea>
                    </div>
                    
                    <div class="flex space-x-4 pt-4">
                        <button type="button" onclick="hideEditModal()" 
                                class="flex-1 px-6 py-3 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-700 rounded-xl font-semibold transition-all duration-200 flex items-center justify-center">
                            <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 px-6 py-3 bg-gradient-to-r from-bote-info-500 to-bote-info-600 hover:from-bote-info-600 hover:to-bote-info-700 text-white rounded-xl font-semibold transition-all duration-200 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                            Update Purok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Enhanced Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full mx-4 transform transition-all duration-300 modal-enter">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-t-3xl px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Delete Confirmation</h3>
                    </div>
                    <button onclick="hideDeleteModal()" class="text-white hover:text-bote-stone-200 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="trash-2" class="w-10 h-10 text-red-600"></i>
                </div>
                
                <h4 class="text-xl font-bold text-bote-stone-900 mb-3">Delete this Purok?</h4>
                <p class="text-bote-stone-600 mb-8 leading-relaxed">
                    This action cannot be undone. The purok will be permanently removed from the system.
                    <br><br>
                    <span class="font-semibold text-red-600">⚠ Warning:</span> Make sure no residents are assigned to this purok before deletion.
                </p>
                
                <form method="POST" class="space-y-6">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <input type="hidden" name="action" value="delete_purok">
                    <input type="hidden" name="purok_id" id="delete_purok_id">
                    
                    <div class="flex space-x-4">
                        <button type="button" onclick="hideDeleteModal()" 
                                class="flex-1 px-6 py-3 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-700 rounded-xl font-semibold transition-all duration-200 flex items-center justify-center">
                            <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-xl font-semibold transition-all duration-200 flex items-center justify-center shadow-lg hover:shadow-xl">
                            <i data-lucide="trash-2" class="w-4 h-4 mr-2"></i>
                            Delete Purok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
