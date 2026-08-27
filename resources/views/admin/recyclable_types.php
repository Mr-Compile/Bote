    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/pages/admin-recyclable-types.css">
<div class="p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Enhanced Header -->
            <div class="bg-white rounded-3xl shadow-lg p-8 mb-8 border border-bote-stone-100">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i data-lucide="recycle" class="w-8 h-8 text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-bote-stone-900">Recyclable Types</h1>
                            <p class="text-bote-stone-600 mt-1">Manage recyclable materials and their point values</p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Enhanced Success/Error Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="bg-gradient-to-r from-bote-green-50 to-bote-green-100 border-l-4 border-bote-green-500 p-6 rounded-2xl mb-8 shadow-lg">
                    <div class="flex items-center">
                        <i data-lucide="check-circle" class="w-6 h-6 text-bote-green-600 mr-3"></i>
                        <p class="text-bote-green-800 font-medium"><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-500 p-6 rounded-2xl mb-8 shadow-lg">
                    <div class="flex items-center">
                        <i data-lucide="x-circle" class="w-6 h-6 text-red-600 mr-3"></i>
                        <p class="text-red-800 font-medium"><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

         <!-- Enhanced Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Types -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-bote-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-bote-stone-900"><?php echo e($total_types); ?></p>
                        <p class="text-sm text-bote-stone-600 mt-1">Total Types</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-bote-green-100 to-bote-green-200 rounded-2xl flex items-center justify-center">
                        <i data-lucide="layers" class="w-8 h-8 text-bote-green-600"></i>
                    </div>
                </div>
            </div>
            
            <!-- Average Points -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-bote-stone-900"><?php echo e($avg_points); ?></p>
                        <p class="text-sm text-bote-stone-600 mt-1">Avg Points</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-100 to-yellow-200 rounded-2xl flex items-center justify-center">
                        <i data-lucide="star" class="w-8 h-8 text-yellow-600"></i>
                    </div>
                </div>
            </div>
            
            <!-- Types with Images -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-bote-info-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-bote-stone-900"><?php echo e($active_types); ?></p>
                        <p class="text-sm text-bote-stone-600 mt-1">With Images</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-bote-info-100 to-bote-info-200 rounded-2xl flex items-center justify-center">
                        <i data-lucide="image" class="w-8 h-8 text-bote-info-600"></i>
                    </div>
                </div>
            </div>
            
            <!-- Most Used -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-bote-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-lg font-bold text-bote-stone-900"><?php echo e($most_used ? htmlspecialchars($most_used['name']) : 'N/A'); ?></p>
                        <p class="text-sm text-bote-stone-600 mt-1">Most Used</p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-br from-bote-green-100 to-bote-green-200 rounded-2xl flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-8 h-8 text-bote-green-600"></i>
                    </div>
                </div>
            </div>
        </div>

            <!-- Enhanced Recyclable Types Table -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-bote-stone-100">
                <div class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 px-8 py-6">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <i data-lucide="recycle" class="w-6 h-6 text-white mr-3"></i>
                            <h2 class="text-xl font-bold text-white">Recyclable Types</h2>
                        </div>
                        <button onclick="showAddTypeModal()" 
                                class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-xl font-medium transition-all transform hover:scale-105 flex items-center backdrop-blur-sm border border-white/30">
                            <i data-lucide="plus-circle" class="w-4 h-4 mr-2"></i>
                            Add Type
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-bote-stone-200">
                        <thead class="bg-bote-stone-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Image</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Type Name</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Points</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Unit Type</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-bote-stone-200">
                            <?php if (empty($recyclable_types)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-20 h-20 bg-gradient-to-br from-bote-stone-100 to-bote-stone-200 rounded-full flex items-center justify-center mb-6">
                                                <i data-lucide="package-x" class="w-10 h-10 text-bote-stone-400"></i>
                                            </div>
                                            <h3 class="text-xl font-semibold text-bote-stone-900 mb-3">No recyclable types found</h3>
                                            <p class="text-bote-stone-500 mb-6 max-w-md mx-auto">
                                                Get started by adding your first recyclable type with point values and images.
                                            </p>
                                            <button onclick="showAddTypeModal()" 
                                                    class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white px-6 py-3 rounded-xl font-medium transition-all transform hover:scale-105 flex items-center shadow-lg">
                                                <i data-lucide="plus-circle" class="w-5 h-5 mr-2"></i>
                                                Add Your First Type
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                            <?php foreach ($recyclable_types as $type): ?>
                                <tr class="hover:bg-bote-stone-50 transition-colors">
                                    <!-- Image Column -->
                                    <td class="px-6 py-4">
                                        <div class="w-16 h-16 rounded-xl overflow-hidden shadow-md">
                                            <?php if (!empty($type['image'])): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($type['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($type['name']); ?>" 
                                                     class="w-full h-full object-cover hover:scale-110 transition-transform duration-300">
                                            <?php else: ?>
                                                <div class="w-full h-full bg-gradient-to-br from-bote-stone-100 to-bote-stone-200 flex items-center justify-center">
                                                    <i data-lucide="image-off" class="w-6 h-6 text-bote-stone-400"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <!-- Type Name Column -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 bg-bote-green-500 rounded-full mr-3"></div>
                                            <div>
                                                <span class="text-lg font-semibold text-bote-stone-900"><?php echo htmlspecialchars($type['name']); ?></span>
                                                <div class="text-sm text-bote-stone-500 flex items-center mt-1">
                                                    <i data-lucide="hash" class="w-3 h-3 mr-1"></i>
                                                    ID: <?php echo e($type['type_id']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Points Column -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <i data-lucide="coins" class="w-4 h-4 text-yellow-500 mr-2"></i>
                                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-gradient-to-r from-bote-green-100 to-bote-green-200 text-bote-green-800">
                                                <?php
                                                    if ($type['unit'] === 'kg') {
                                                        echo number_format((float)$type['points_per_kg'], 2) . " pts/kg";
                                                    } else {
                                                        echo number_format((float)$type['points_per_unit'], 2) . " pts/pc";
                                                    }
                                                ?>
                                            </span>
                                        </div>
                                    </td>
                                    <!-- Unit Type Column -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center text-sm text-bote-stone-900 max-w-xs">
                                            <?php if ($type['unit'] === 'kg'): ?>
                                                <i data-lucide="weight" class="w-4 h-4 text-bote-info-500 mr-2"></i>
                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-bote-info-100 text-bote-info-800">Weight-based</span>
                                            <?php else: ?>
                                                <i data-lucide="package" class="w-4 h-4 text-bote-green-500 mr-2"></i>
                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-bote-green-100 text-bote-green-800">Count-based</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <!-- Actions Column -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <button onclick="showEditTypeModal(<?php echo htmlspecialchars(json_encode($type)); ?>)" 
                                                    class="inline-flex items-center px-3 py-1 bg-gradient-to-r from-bote-info-100 to-bote-info-200 hover:from-bote-info-200 hover:to-bote-info-300 text-bote-info-700 rounded-lg text-sm font-medium transition-all transform hover:scale-105">
                                                <i data-lucide="edit-3" class="w-4 h-4 mr-1"></i>
                                                Edit
                                            </button>
                                            <button onclick="confirmDeleteType(<?php echo e($type['type_id']); ?>)" 
                                                    class="inline-flex items-center px-3 py-1 bg-gradient-to-r from-red-100 to-red-200 hover:from-red-200 hover:to-red-300 text-red-700 rounded-lg text-sm font-medium transition-all transform hover:scale-105">
                                                <i data-lucide="trash-2" class="w-4 h-4 mr-1"></i>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

                                    <td class="px-6 py-4">
                                        <div class="flex items-center text-sm text-bote-stone-900 max-w-xs">
                                            <?php if ($type['unit'] === 'kg'): ?>
                                                <i data-lucide="weight" class="w-4 h-4 text-bote-info-500 mr-2"></i>
                                                <span>Weight-based material</span>
                                            <?php else: ?>
                                                <i data-lucide="package" class="w-4 h-4 text-bote-green-500 mr-2"></i>
                                                <span>Count-based material</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <button onclick="showEditTypeModal(<?php echo htmlspecialchars(json_encode($type)); ?>)" 
                                                    class="inline-flex items-center px-3 py-1 bg-gradient-to-r from-bote-info-100 to-bote-info-200 hover:from-bote-info-200 hover:to-bote-info-300 text-bote-info-700 rounded-lg text-sm font-medium transition-all transform hover:scale-105">
                                                <i data-lucide="edit-3" class="w-4 h-4 mr-1"></i>
                                                Edit
                                            </button>
                                            <button onclick="confirmDeleteType(<?php echo e($type['type_id']); ?>)" 
                                                    class="inline-flex items-center px-3 py-1 bg-gradient-to-r from-red-100 to-red-200 hover:from-red-200 hover:to-red-300 text-red-700 rounded-lg text-sm font-medium transition-all transform hover:scale-105">
                                                <i data-lucide="trash-2" class="w-4 h-4 mr-1"></i>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Add Type Modal -->
    <div id="addTypeModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300 modal-enter">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-bote-green-500 to-bote-green-600 rounded-t-3xl px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i data-lucide="plus-circle" class="w-6 h-6 text-white mr-3"></i>
                        <h3 class="text-xl font-bold text-white">Add New Recyclable Type</h3>
                    </div>
                    <button onclick="hideAddTypeModal()" class="text-white hover:text-bote-stone-200 transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-8">
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <input type="hidden" name="action" value="add_type">
                    
                    <!-- Image Upload Section -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-bote-stone-700 mb-3 flex items-center">
                            <i data-lucide="image" class="w-4 h-4 mr-2 text-bote-stone-500"></i>
                            Recyclable Type Image
                        </label>
                        <div class="file-drop-area p-6 border-2 border-dashed border-bote-stone-300 rounded-xl text-center hover:border-bote-green-500 transition-colors cursor-pointer" 
                             onclick="triggerFileInput('add_image')">
                            <div id="add_image_preview" class="hidden">
                                <img id="add_preview_img" src="" alt="Preview" class="w-24 h-24 object-cover rounded-lg mx-auto mb-3">
                                <p class="text-sm text-bote-stone-600">Click to change image</p>
                            </div>
                            <div id="add_image_placeholder">
                                <i data-lucide="upload" class="w-8 h-8 text-bote-stone-400 mx-auto mb-3"></i>
                                <p class="text-sm text-bote-stone-600 mb-1">Click to upload an image</p>
                                <p class="text-xs text-bote-stone-500">PNG, JPG, GIF up to 5MB</p>
                            </div>
                        </div>
                        <input type="file" id="add_image" name="image" accept="image/*" class="hidden" onchange="previewAddImage(this)">
                    </div>
                
                    <!-- Type Name -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-bote-stone-700 flex items-center">
                            <i data-lucide="tag" class="w-4 h-4 mr-2 text-bote-stone-500"></i>
                            Type Name
                        </label>
                        <input type="text" name="name" required placeholder="e.g., Plastic Bottles, Paper, Glass"
                               class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all">
                    </div>
                    
                    <!-- Unit Selection -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-bote-stone-700 flex items-center">
                            <i data-lucide="ruler" class="w-4 h-4 mr-2 text-bote-stone-500"></i>
                            Unit Type
                        </label>
                        <select name="unit" id="unit" required 
                                class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all">
                            <option value="kg">Weight-based (kg)</option>
                            <option value="pcs">Count-based (pieces)</option>
                        </select>
                    </div>

                    <!-- Points per KG -->
                    <div class="space-y-2" id="points_kg_field">
                        <label class="block text-sm font-medium text-bote-stone-700 flex items-center">
                            <i data-lucide="coins" class="w-4 h-4 mr-2 text-bote-stone-500"></i>
                            Points per Kilogram
                        </label>
                        <input type="number" name="points_per_kg" step="0.1" min="0" placeholder="10.0"
                               class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all">
                    </div>

                    <!-- Points per Unit -->
                    <div class="space-y-2 hidden" id="points_unit_field">
                        <label class="block text-sm font-medium text-bote-stone-700 flex items-center">
                            <i data-lucide="coins" class="w-4 h-4 mr-2 text-bote-stone-500"></i>
                            Points per Piece
                        </label>
                        <input type="number" name="points_per_unit" step="0.01" min="0" placeholder="1.50"
                               class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all">
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex space-x-4 pt-4">
                        <button type="button" onclick="hideAddTypeModal()" 
                                class="flex-1 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-700 py-3 rounded-xl font-medium transition-all">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 bg-gradient-to-r from-bote-green-500 to-bote-green-600 hover:from-bote-green-600 hover:to-bote-green-700 text-white py-3 rounded-xl font-medium transition-all transform hover:scale-105 shadow-lg">
                            Add Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Enhanced Edit Type Modal -->
    <div id="editTypeModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300 modal-enter">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-bote-info-500 to-bote-info-600 rounded-t-3xl px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i data-lucide="edit-3" class="w-6 h-6 text-white mr-3"></i>
                        <h3 class="text-xl font-bold text-white">Edit Recyclable Type</h3>
                    </div>
                    <button onclick="hideEditTypeModal()" class="text-white hover:text-bote-stone-200 transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-8">
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <input type="hidden" name="action" value="update_type">
                    <input type="hidden" name="type_id" id="edit_type_id">
                    <input type="hidden" name="current_image" id="edit_current_image">
                    
                    <!-- Image Upload Section -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-bote-stone-700 mb-3 flex items-center">
                            <i data-lucide="image" class="w-4 h-4 mr-2 text-bote-stone-500"></i>
                            Update Image
                        </label>
                        <div class="file-drop-area p-6 border-2 border-dashed border-bote-stone-300 rounded-xl text-center hover:border-bote-info-500 transition-colors cursor-pointer" 
                             onclick="triggerFileInput('edit_image')">
                            <div id="edit_image_preview" class="hidden">
                                <img id="edit_preview_img" src="" alt="Preview" class="w-24 h-24 object-cover rounded-lg mx-auto mb-3">
                                <p class="text-sm text-bote-stone-600">Click to change image</p>
                            </div>
                            <div id="edit_image_placeholder">
                                <i data-lucide="upload" class="w-8 h-8 text-bote-stone-400 mx-auto mb-3"></i>
                                <p class="text-sm text-bote-stone-600 mb-1">Click to upload new image</p>
                                <p class="text-xs text-bote-stone-500">PNG, JPG, GIF up to 5MB</p>
                            </div>
                        </div>
                        <input type="file" id="edit_image" name="image" accept="image/*" class="hidden" onchange="previewEditImage(this)">
                    </div>
                    
                    <!-- Type Name -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-bote-stone-700 flex items-center">
                            <i data-lucide="tag" class="w-4 h-4 mr-2 text-bote-stone-500"></i>
                            Type Name
                        </label>
                        <input type="text" name="name" id="edit_name" required placeholder="e.g., Plastic Bottles, Paper, Glass"
                               class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all">
                    </div>
                    
                    <!-- Unit Selection -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-bote-stone-700 flex items-center">
                            <i data-lucide="ruler" class="w-4 h-4 mr-2 text-bote-stone-500"></i>
                            Unit Type
                        </label>
                        <select name="unit" id="edit_unit" required 
                                class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all">
                            <option value="kg">Weight-based (kg)</option>
                            <option value="pcs">Count-based (pieces)</option>
                        </select>
                    </div>

                    <!-- Points per KG -->
                    <div class="space-y-2" id="edit_points_kg_field">
                        <label class="block text-sm font-medium text-bote-stone-700 flex items-center">
                            <i data-lucide="coins" class="w-4 h-4 mr-2 text-bote-stone-500"></i>
                            Points per Kilogram
                        </label>
                        <input type="number" name="points_per_kg" id="edit_points_per_kg" step="0.1" min="0" placeholder="10.0"
                               class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all">
                    </div>

                    <!-- Points per Unit -->
                    <div class="space-y-2 hidden" id="edit_points_unit_field">
                        <label class="block text-sm font-medium text-bote-stone-700 flex items-center">
                            <i data-lucide="coins" class="w-4 h-4 mr-2 text-bote-stone-500"></i>
                            Points per Piece
                        </label>
                        <input type="number" name="points_per_unit" id="edit_points_per_unit" step="0.01" min="0" placeholder="1.50"
                               class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-bote-green-500 focus:border-transparent transition-all">
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex space-x-4 pt-4">
                        <button type="button" onclick="hideEditTypeModal()" 
                                class="flex-1 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-700 py-3 rounded-xl font-medium transition-all">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 bg-gradient-to-r from-bote-info-500 to-bote-info-600 hover:from-bote-info-600 hover:to-bote-info-700 text-white py-3 rounded-xl font-medium transition-all transform hover:scale-105 shadow-lg">
                            Update Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Enhanced Delete Confirmation Modal -->
    <div id="deleteTypeModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full mx-4 transform transition-all duration-300 modal-enter">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-t-3xl px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i data-lucide="trash-2" class="w-6 h-6 text-white mr-3"></i>
                        <h3 class="text-xl font-bold text-white">Delete Recyclable Type</h3>
                    </div>
                    <button onclick="hideDeleteTypeModal()" class="text-white hover:text-bote-stone-200 transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="trash-2" class="w-10 h-10 text-red-600"></i>
                </div>
                
                <h4 class="text-xl font-bold text-bote-stone-900 mb-3">Delete this Recyclable Type?</h4>
                <p class="text-bote-stone-600 mb-8 leading-relaxed">
                    This action cannot be undone. The recyclable type and its associated image will be permanently removed.
                </p>
                
                <form method="POST" class="space-y-6">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <input type="hidden" name="action" value="delete_type">
                    <input type="hidden" name="type_id" id="delete_type_id">
                    
                    <div class="flex space-x-4">
                        <button type="button" onclick="hideDeleteTypeModal()" 
                                class="flex-1 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-700 py-3 rounded-xl font-medium transition-all">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white py-3 rounded-xl font-medium transition-all transform hover:scale-105 shadow-lg">
                            Delete Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
