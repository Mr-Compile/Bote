<div class="p-8">
            <!-- Header -->
            <div class="bg-gradient-to-r from-bote-green-600 to-bote-info-700 shadow-xl rounded-3xl mb-8">
                <div class="max-w-4xl mx-auto px-8 py-8">
                    <div class="flex items-center space-x-6">
                        <a href="admin_settings.php" class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm hover:bg-opacity-30 transition-all">
                            <i data-lucide="arrow-left" class="w-6 h-6 text-white"></i>
                        </a>
                        <div>
                            <h1 class="text-4xl font-bold text-white tracking-tight">
                                <?php echo e($slide ? 'Edit' : 'Add New'); ?> Carousel Slide
                            </h1>
                            <p class="text-white text-opacity-80 text-lg">Configure the slide details and image</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-3xl shadow-lg border border-bote-stone-100 overflow-hidden">
                    <form action="carousel_handler.php" method="POST" enctype="multipart/form-data" id="carouselForm">
                        <?php echo \App\Core\Csrf::field(); ?>
                        <input type="hidden" name="action" value="<?php echo e($slide ? 'update' : 'add'); ?>">
                        <?php if ($slide): ?>
                            <input type="hidden" name="slide_id" value="<?php echo e($slide['slide_id']); ?>">
                        <?php endif; ?>
                        
                        <div class="p-8 space-y-6">
                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-semibold text-bote-stone-700 mb-3">Slide Title *</label>
                                <input type="text" name="title" required
                                       value="<?php echo htmlspecialchars($slide['title'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition-all"
                                       placeholder="Enter slide title">
                            </div>

                            <!-- Subtitle -->
                            <div>
                                <label class="block text-sm font-semibold text-bote-stone-700 mb-3">Slide Subtitle</label>
                                <textarea name="subtitle" rows="3"
                                          class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition-all"
                                          placeholder="Enter slide subtitle or description"><?php echo htmlspecialchars($slide['subtitle'] ?? ''); ?></textarea>
                            </div>

                            <!-- Image Upload -->
                            <div>
                                <label class="block text-sm font-semibold text-bote-stone-700 mb-3">Slide Image</label>
                                <?php if ($slide && $slide['image']): ?>
                                    <div class="mb-4">
                                        <img src="../uploads/carousel/<?php echo htmlspecialchars($slide['image']); ?>" 
                                             alt="Current Image" 
                                             class="w-full max-w-md h-48 object-cover rounded-xl border-2 border-bote-stone-200">
                                        <p class="text-sm text-bote-stone-500 mt-2">Current image (upload a new one to replace)</p>
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="image" accept="image/*"
                                       class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition-all">
                                <p class="text-xs text-bote-stone-500 mt-2">Recommended size: 1920x1080px. Max 5MB. Supported formats: JPG, PNG, GIF</p>
                            </div>

                            <!-- Button Text & Link -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-bote-stone-700 mb-3">Button Text</label>
                                    <input type="text" name="button_text"
                                           value="<?php echo htmlspecialchars($slide['button_text'] ?? ''); ?>"
                                           class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition-all"
                                           placeholder="e.g., Get Started">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-bote-stone-700 mb-3">Button Link</label>
                                    <input type="text" name="button_link"
                                           value="<?php echo htmlspecialchars($slide['button_link'] ?? ''); ?>"
                                           class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition-all"
                                           placeholder="e.g., register.php or #about">
                                </div>
                            </div>

                            <!-- Display Order & Active Status -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-bote-stone-700 mb-3">Display Order</label>
                                    <input type="number" name="display_order" min="0"
                                           value="<?php echo e($slide['display_order'] ?? 0); ?>"
                                           class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition-all"
                                           placeholder="0">
                                    <p class="text-xs text-bote-stone-500 mt-2">Lower numbers appear first</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-bote-stone-700 mb-3">Status</label>
                                    <div class="flex items-center p-4 bg-bote-stone-50 rounded-xl border border-bote-stone-200">
                                        <input type="checkbox" name="is_active" value="1" 
                                               <?php echo e((!$slide || $slide['is_active']) ? 'checked' : ''); ?>
                                               class="w-5 h-5 text-bote-green-600 border-bote-stone-300 rounded focus:ring-bote-green-500">
                                        <label class="ml-3 text-sm font-medium text-bote-stone-700">
                                            Active (Show this slide on homepage)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="bg-bote-stone-50 px-8 py-6 border-t border-bote-stone-200 flex justify-between items-center">
                            <a href="admin_settings.php" class="px-6 py-3 text-bote-stone-700 bg-white border border-bote-stone-300 rounded-xl hover:bg-bote-stone-50 transition-all font-medium">
                                Cancel
                            </a>
                            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-bote-green-600 to-bote-info-700 text-white rounded-xl hover:from-bote-green-700 hover:to-bote-info-800 transition-all font-semibold flex items-center shadow-lg">
                                <i data-lucide="save" class="w-5 h-5 mr-2"></i>
                                <?php echo e($slide ? 'Update' : 'Create'); ?> Slide
                            </button>
                        </div>
                    </form>
                </div>
            </div>
</div>
