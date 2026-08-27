    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/pages/admin-settings.css">
<div class="p-8">
            <!-- Enhanced Header -->
            <div class="gradient-bg shadow-xl rounded-3xl">
                <div class="max-w-7xl mx-auto px-8 py-8">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-6">
                            <div class="w-20 h-20 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                                <i data-lucide="settings" class="w-10 h-10 text-white"></i>
                            </div>
                            <div>
                                <h1 class="text-4xl font-bold text-white tracking-tight">System Settings</h1>
                                <p class="text-white text-opacity-80 text-lg">Configure system preferences and administrative settings</p>
                                <div class="flex items-center mt-3 text-sm text-white text-opacity-70">
                                    <i data-lucide="user" class="w-4 h-4 mr-2"></i>
                                    Welcome back, <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto mt-8">
                <!-- Enhanced Alert Messages -->
                <?php if ($success_message): ?>
                    <div class="bg-gradient-to-r from-bote-green-50 to-bote-green-100 border border-bote-green-200 text-bote-green-800 px-6 py-4 rounded-2xl mb-8 flex items-center shadow-lg alert-auto-hide">
                        <i data-lucide="check-circle" class="w-6 h-6 mr-3 text-bote-green-600"></i>
                        <div>
                            <h4 class="font-semibold">Success!</h4>
                            <p><?php echo htmlspecialchars($success_message); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="bg-gradient-to-r from-red-50 to-red-100 border border-red-200 text-red-800 px-6 py-4 rounded-2xl mb-8 flex items-center shadow-lg alert-auto-hide">
                        <i data-lucide="alert-circle" class="w-6 h-6 mr-3 text-red-600"></i>
                        <div>
                            <h4 class="font-semibold">Error!</h4>
                            <p><?php echo htmlspecialchars($error_message); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Enhanced Tab Navigation -->
                <div class="bg-white rounded-3xl shadow-lg p-8 mb-8 border border-bote-stone-100">
                    <div class="flex space-x-3 bg-bote-stone-100 p-3 rounded-2xl">
                        <button onclick="showTab('barangay')" id="tab-barangay" class="flex-1 flex items-center justify-center space-x-3 py-4 px-6 rounded-xl font-semibold transition-all duration-300 tab-active">
                            <i data-lucide="map-pin" class="w-5 h-5"></i>
                            <span>Barangay Info</span>
                        </button>
                        <button onclick="showTab('carousel')" id="tab-carousel" class="flex-1 flex items-center justify-center space-x-3 py-4 px-6 rounded-xl font-semibold transition-all duration-300 text-bote-stone-600 hover:text-bote-stone-800 hover:bg-bote-stone-200">
                            <i data-lucide="images" class="w-5 h-5"></i>
                            <span>Carousel</span>
                        </button>
                        <button onclick="showTab('security')" id="tab-security" class="flex-1 flex items-center justify-center space-x-3 py-4 px-6 rounded-xl font-semibold transition-all duration-300 text-bote-stone-600 hover:text-bote-stone-800 hover:bg-bote-stone-200">
                            <i data-lucide="shield" class="w-5 h-5"></i>
                            <span>Security</span>
                        </button>
                    </div>
                </div>

            <!-- Tab Contents -->
            <div class="space-y-8">
                <!-- Barangay Info Tab -->
                <div id="content-barangay" class="tab-content active">
                    <div class="bg-white rounded-3xl shadow-lg border border-bote-stone-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-bote-info-600 to-bote-info-700 px-8 py-6">
                            <h2 class="text-2xl font-bold text-white flex items-center">
                                <i data-lucide="map-pin" class="w-6 h-6 mr-3"></i>
                                Barangay Information
                            </h2>
                            <p class="text-bote-info-100 mt-2">Configure your barangay's basic information and branding</p>
                        </div>
                        
                        <div class="p-8">
                            <form action="save_settings.php" method="POST" enctype="multipart/form-data" class="space-y-8">
                                <?php echo \App\Core\Csrf::field(); ?>
                                <input type="hidden" name="tab" value="barangay">
                                
                                <!-- Main Settings Card -->
                                <div class="bg-bote-info-50 rounded-2xl p-6 border border-bote-info-100 transition-all duration-300 hover:shadow-md">
                                    <div class="flex items-center space-x-3 mb-6">
                                        <div class="w-12 h-12 bg-gradient-to-br from-bote-info-500 to-bote-info-600 rounded-xl flex items-center justify-center shadow-lg">
                                            <i data-lucide="info" class="w-6 h-6 text-white"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-bold text-bote-stone-900">Basic Information</h3>
                                            <p class="text-bote-stone-600">Enter your barangay's official details</p>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <!-- Basic Information - Left Column -->
                                        <div class="space-y-5">
                                            <div class="transform transition-all duration-300 hover:scale-[1.02]">
                                                <label class="block text-sm font-semibold text-bote-stone-700 mb-3">Barangay Name</label>
                                                <div class="relative">
                                                    <input type="text" name="barangay_name" 
                                                           value="<?php echo htmlspecialchars($settings['barangay_name']); ?>"
                                                           class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition-all duration-200 pl-12"
                                                           placeholder="Enter barangay name">
                                                    <i data-lucide="map-pin" class="w-5 h-5 text-bote-stone-400 absolute left-4 top-1/2 transform -translate-y-1/2"></i>
                                                </div>
                                            </div>
    
                                            <div class="transform transition-all duration-300 hover:scale-[1.02]">
                                                <label class="block text-sm font-semibold text-bote-stone-700 mb-3">Contact Number</label>
                                                <div class="relative">
                                                    <input type="text" name="barangay_contact" 
                                                           value="<?php echo htmlspecialchars($settings['barangay_contact']); ?>"
                                                           class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition-all duration-200 pl-12"
                                                           placeholder="e.g., +63 912 345 6789">
                                                    <i data-lucide="phone" class="w-5 h-5 text-bote-stone-400 absolute left-4 top-1/2 transform -translate-y-1/2"></i>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Basic Information - Right Column -->
                                        <div class="space-y-5">
                                            <div class="transform transition-all duration-300 hover:scale-[1.02]">
                                                <label class="block text-sm font-semibold text-bote-stone-700 mb-3">Email Address</label>
                                                <div class="relative">
                                                    <input type="email" name="barangay_email" 
                                                           value="<?php echo htmlspecialchars($settings['barangay_email']); ?>"
                                                           class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition-all duration-200 pl-12"
                                                           placeholder="barangay@example.com">
                                                    <i data-lucide="mail" class="w-5 h-5 text-bote-stone-400 absolute left-4 top-1/2 transform -translate-y-1/2"></i>
                                                </div>
                                            </div>
    
                                            <div class="transform transition-all duration-300 hover:scale-[1.02]">
                                                <label class="block text-sm font-semibold text-bote-stone-700 mb-3">Address</label>
                                                <div class="relative">
                                                    <textarea name="barangay_address" rows="3" 
                                                              class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition-all duration-200 pl-12"
                                                              placeholder="Enter complete address"><?php echo htmlspecialchars($settings['barangay_address']); ?></textarea>
                                                    <i data-lucide="map" class="w-5 h-5 text-bote-stone-400 absolute left-4 top-4"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Branding Section -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <!-- Logo Upload Card -->
                                    <div class="bg-gradient-to-br from-bote-info-50 to-bote-info-50 rounded-2xl p-6 border border-bote-info-100 shadow-sm hover:shadow-md transition-all duration-300">
                                        <div class="flex items-center space-x-3 mb-4">
                                            <div class="w-10 h-10 bg-bote-info-100 rounded-lg flex items-center justify-center">
                                                <i data-lucide="image" class="w-5 h-5 text-bote-info-600"></i>
                                            </div>
                                            <h4 class="text-lg font-semibold text-bote-stone-800">Barangay Logo</h4>
                                        </div>
                                        
                                        <div class="border-2 border-dashed border-bote-info-200 rounded-xl p-6 text-center file-drop-area hover:border-bote-info-500 hover:bg-bote-info-50 transition-all duration-200">
                                            <div id="logo-preview" class="mb-4">
                                                <?php if ($settings['barangay_logo']): ?>
                                                    <img src="../uploads/system/logos/<?php echo htmlspecialchars($settings['barangay_logo']); ?>" 
                                                         alt="Current Logo" class="mx-auto h-24 w-24 object-cover rounded-lg shadow-md">
                                                    <p class="text-sm text-bote-stone-600 mt-2">Current Logo</p>
                                                <?php else: ?>
                                                    <i data-lucide="image" class="w-16 h-16 text-bote-stone-400 mx-auto mb-3"></i>
                                                    <p class="text-bote-stone-600">No logo uploaded</p>
                                                <?php endif; ?>
                                            </div>
                                            <input type="file" name="barangay_logo" id="logoInput" 
                                                   class="hidden" accept="image/*" onchange="previewImage(this, 'logo-preview')">
                                            <button type="button" onclick="triggerFileInput('logoInput')" 
                                                    class="bg-bote-green-600 text-white px-6 py-3 rounded-xl hover:bg-bote-green-700 transition-all duration-200 flex items-center mx-auto transform hover:scale-105">
                                                <i data-lucide="upload" class="w-5 h-5 mr-2"></i>
                                                Upload Logo
                                            </button>
                                            <p class="text-xs text-bote-stone-500 mt-3">PNG, JPG up to 5MB</p>
                                        </div>
                                    </div>

                                    <!-- Banner Settings Card -->
                                    <div class="bg-gradient-to-br from-bote-green-50 to-bote-green-50 rounded-2xl p-6 border border-bote-green-100 shadow-sm hover:shadow-md transition-all duration-300">
                                        <div class="flex items-center space-x-3 mb-4">
                                            <div class="w-10 h-10 bg-bote-green-100 rounded-lg flex items-center justify-center">
                                                <i data-lucide="layout" class="w-5 h-5 text-bote-green-600"></i>
                                            </div>
                                            <h4 class="text-lg font-semibold text-bote-stone-800">Banner Settings</h4>
                                        </div>
                                        
                                        <div class="space-y-4">
                                            <div class="transform transition-all duration-300 hover:scale-[1.02]">
                                                <label class="block text-sm font-medium text-bote-stone-700 mb-2">Banner Title</label>
                                                <input type="text" name="banner_title" value="<?php echo htmlspecialchars($settings['banner_title']); ?>" 
                                                       class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition-all">
                                            </div>
                                            
                                            <div class="transform transition-all duration-300 hover:scale-[1.02]">
                                                <label class="block text-sm font-medium text-bote-stone-700 mb-2">Banner Subtitle</label>
                                                <textarea name="banner_subtitle" rows="2" 
                                                          class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition-all"><?php echo htmlspecialchars($settings['banner_subtitle']); ?></textarea>
                                            </div>
                                            
                                            <div class="flex items-center justify-between p-4 bg-bote-green-50 rounded-xl border border-bote-green-100">
                                                <div>
                                                    <label class="font-medium text-bote-stone-700">Enable Banner</label>
                                                    <p class="text-sm text-bote-stone-500">Show banner on homepage</p>
                                                </div>
                                                <label class="toggle-switch">
                                                    <input type="checkbox" name="banner_enabled" value="1" <?php echo e($settings['banner_enabled'] == '1' ? 'checked' : ''); ?>>
                                                    <span class="slider"></span>
                                                </label>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-bote-stone-700 mb-2">Banner Image</label>
                                                <div class="border-2 border-dashed border-bote-green-200 rounded-xl p-4 text-center file-drop-area hover:border-bote-green-500 hover:bg-bote-green-50 transition-all duration-200">
                                                    <?php if ($settings['system_banner']): ?>
                                                        <img src="../uploads/system/banners/<?php echo htmlspecialchars($settings['system_banner']); ?>" 
                                                             alt="Current Banner" class="w-full max-w-sm h-20 object-cover rounded-lg border mx-auto mb-2">
                                                        <p class="text-sm text-bote-stone-600">Current banner</p>
                                                    <?php else: ?>
                                                        <i data-lucide="image" class="w-12 h-12 text-bote-stone-400 mx-auto mb-2"></i>
                                                        <p class="text-bote-stone-600">No banner uploaded</p>
                                                    <?php endif; ?>
                                                    <input type="file" name="system_banner" accept="image/*" 
                                                           class="mt-2 w-full px-3 py-2 border border-bote-stone-300 rounded-lg text-sm">
                                                    <p class="text-xs text-bote-stone-500 mt-1">Recommended: 1920x1080px</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Login Image Card -->
                                <div class="bg-gradient-to-br from-bote-green-50 to-bote-green-50 rounded-2xl p-6 border border-bote-green-100 shadow-sm hover:shadow-md transition-all duration-300">
                                    <div class="flex items-center space-x-3 mb-4">
                                        <div class="w-10 h-10 bg-bote-green-100 rounded-lg flex items-center justify-center">
                                            <i data-lucide="log-in" class="w-5 h-5 text-bote-green-600"></i>
                                        </div>
                                        <h4 class="text-lg font-semibold text-bote-stone-800">Login Page Image</h4>
                                    </div>
                                    
                                    <p class="text-bote-stone-600 mb-4">This image will be displayed on the left side of the login and registration pages.</p>
                                    
                                    <div class="border-2 border-dashed border-bote-green-200 rounded-xl p-6 text-center file-drop-area hover:border-bote-green-500 hover:bg-bote-green-50 transition-all duration-200">
                                        <div id="login-image-preview" class="mb-4">
                                            <?php if (isset($settings['login_image']) && $settings['login_image']): ?>
                                                <img src="../uploads/system/auth/<?php echo htmlspecialchars($settings['login_image']); ?>" 
                                                     alt="Current Login Image" class="mx-auto max-h-36 w-auto object-cover rounded-lg shadow-md">
                                                <p class="text-sm text-bote-stone-600 mt-2">Current Login Image</p>
                                            <?php else: ?>
                                                <i data-lucide="image" class="w-16 h-16 text-bote-stone-400 mx-auto mb-3"></i>
                                                <p class="text-bote-stone-600">No login image uploaded</p>
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" name="login_image" id="loginImageInput" 
                                               class="hidden" accept="image/*" onchange="previewImage(this, 'login-image-preview')">
                                        <button type="button" onclick="triggerFileInput('loginImageInput')" 
                                                class="bg-bote-green-600 text-white px-6 py-3 rounded-xl hover:bg-bote-green-700 transition-all duration-200 flex items-center mx-auto transform hover:scale-105">
                                            <i data-lucide="upload" class="w-5 h-5 mr-2"></i>
                                            Upload Login Image
                                        </button>
                                        <p class="text-xs text-bote-stone-500 mt-3">Recommended: 1200x1600px. PNG, JPG up to 5MB</p>
                                    </div>
                                </div>

                                <div class="border-t border-bote-stone-200 pt-6 text-center">
                                    <button type="submit" class="bg-gradient-to-r from-bote-info-600 to-bote-info-700 text-white px-8 py-4 rounded-xl hover:from-bote-info-700 hover:to-bote-info-800 transition-all duration-200 font-semibold shadow-lg flex items-center mx-auto transform hover:scale-105">
                                        <i data-lucide="save" class="w-5 h-5 mr-2"></i>
                                        Save Barangay Information
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Carousel Management Tab -->
                <div id="content-carousel" class="tab-content">
                    <div class="bg-white rounded-3xl shadow-lg border border-bote-stone-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-bote-green-600 to-bote-info-700 px-8 py-6">
                            <h2 class="text-2xl font-bold text-white flex items-center">
                                <i data-lucide="images" class="w-6 h-6 mr-3"></i>
                                Homepage Carousel Management
                            </h2>
                            <p class="text-bote-green-100 mt-2">Manage slides displayed on the homepage carousel</p>
                        </div>
                        
                        <div class="p-8">
                            <!-- Carousel Slides -->
                            <div class="mb-8">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-xl font-bold text-bote-stone-900">Current Slides</h3>
                                    <button type="button" onclick="openAddSlideModal()" class="bg-gradient-to-r from-bote-green-600 to-bote-info-700 text-white px-6 py-3 rounded-xl hover:from-bote-green-700 hover:to-bote-info-800 transition-all duration-200 font-semibold flex items-center">
                                        <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
                                        Add New Slide
                                    </button>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="carouselSlidesList">
                                    <?php if (empty($carousel_slides)): ?>
                                        <div class="col-span-full text-center py-12 bg-bote-stone-50 rounded-xl">
                                            <i data-lucide="image" class="w-16 h-16 text-bote-stone-400 mx-auto mb-4"></i>
                                            <p class="text-bote-stone-600">No carousel slides yet. Add your first slide!</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($carousel_slides as $slide): ?>
                                        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-bote-stone-200 hover:shadow-xl transition-all duration-200" data-slide-id="<?php echo e($slide['slide_id']); ?>">
                                            <div class="relative h-48 bg-gradient-to-br from-bote-green-500 to-bote-info-600">
                                                <?php if ($slide['image']): ?>
                                                    <img src="../uploads/carousel/<?php echo htmlspecialchars($slide['image']); ?>" alt="Slide" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <div class="flex items-center justify-center h-full">
                                                        <i data-lucide="image" class="w-16 h-16 text-white opacity-50"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="absolute top-2 right-2 flex space-x-2">
                                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo e($slide['is_active'] ? 'bg-bote-green-500 text-white' : 'bg-bote-stone-400 text-white'); ?>">
                                                        <?php echo e($slide['is_active'] ? 'Active' : 'Inactive'); ?>
                                                    </span>
                                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white text-bote-stone-700">
                                                        Order: <?php echo e($slide['display_order']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="p-4">
                                                <h4 class="font-bold text-bote-stone-900 mb-2 truncate"><?php echo htmlspecialchars($slide['title']); ?></h4>
                                                <p class="text-sm text-bote-stone-600 mb-4 line-clamp-2"><?php echo htmlspecialchars($slide['subtitle'] ?: 'No subtitle'); ?></p>
                                                <div class="flex space-x-2">
                                                    <button type="button" onclick="editSlide(<?php echo e($slide['slide_id']); ?>)" class="flex-1 bg-bote-green-600 text-white px-3 py-2 rounded-lg hover:bg-bote-green-700 transition-all text-sm">
                                                        <i data-lucide="edit" class="w-4 h-4 inline mr-1"></i> Edit
                                                    </button>
                                                    <button type="button" onclick="toggleSlide(<?php echo e($slide['slide_id']); ?>)" class="flex-1 bg-bote-stone-600 text-white px-3 py-2 rounded-lg hover:bg-bote-stone-700 transition-all text-sm">
                                                        <i data-lucide="eye" class="w-4 h-4 inline mr-1"></i> Toggle
                                                    </button>
                                                    <button type="button" onclick="deleteSlide(<?php echo e($slide['slide_id']); ?>)" class="bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 transition-all text-sm">
                                                        <i data-lucide="trash-2" class="w-4 h-4 inline"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Tab -->
                <div id="content-security" class="tab-content">
                    <div class="bg-white rounded-3xl shadow-lg border border-bote-stone-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-red-600 to-red-700 px-8 py-6">
                            <h2 class="text-2xl font-bold text-white flex items-center">
                                <i data-lucide="shield" class="w-6 h-6 mr-3"></i>
                                Security Settings
                            </h2>
                            <p class="text-red-100 mt-2">Update your password and security preferences</p>
                        </div>
                        
                        <div class="p-8">
                            <!-- Change Password -->
                            <form action="save_settings.php" method="POST" class="space-y-6">
                                <?php echo \App\Core\Csrf::field(); ?>
                                <input type="hidden" name="tab" value="password">
                                
                                <div class="bg-gradient-to-br from-red-50 to-red-50 rounded-2xl p-6 border border-red-200">
                                    <div class="flex items-center space-x-3 mb-6">
                                        <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                                            <i data-lucide="key" class="w-6 h-6 text-white"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-bold text-bote-stone-900">Change Password</h3>
                                            <p class="text-bote-stone-600">Update your account password</p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-bote-stone-700 mb-3">Current Password</label>
                                            <div class="relative">
                                                <input type="password" name="current_password" required
                                                       class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all pl-12"
                                                       placeholder="Enter current password">
                                                <i data-lucide="lock" class="w-5 h-5 text-bote-stone-400 absolute left-4 top-1/2 transform -translate-y-1/2"></i>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-semibold text-bote-stone-700 mb-3">New Password</label>
                                            <div class="relative">
                                                <input type="password" name="new_password" required
                                                       class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all pl-12"
                                                       placeholder="Enter new password">
                                                <i data-lucide="key" class="w-5 h-5 text-bote-stone-400 absolute left-4 top-1/2 transform -translate-y-1/2"></i>
                                            </div>
                                            <p class="text-xs text-bote-stone-500 mt-2">Password must be at least 8 characters long</p>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-semibold text-bote-stone-700 mb-3">Confirm New Password</label>
                                            <div class="relative">
                                                <input type="password" name="confirm_password" required
                                                       class="w-full px-4 py-3 border border-bote-stone-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all pl-12"
                                                       placeholder="Confirm new password">
                                                <i data-lucide="check-circle" class="w-5 h-5 text-bote-stone-400 absolute left-4 top-1/2 transform -translate-y-1/2"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="border-t border-bote-stone-200 pt-6 text-center">
                                    <button type="submit" class="bg-gradient-to-r from-red-600 to-red-700 text-white px-8 py-4 rounded-xl hover:from-red-700 hover:to-red-800 transition-all duration-200 font-semibold shadow-lg flex items-center mx-auto transform hover:scale-105">
                                        <i data-lucide="save" class="w-5 h-5 mr-2"></i>
                                        Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
</div>
