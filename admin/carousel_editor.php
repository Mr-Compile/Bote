<?php
require_once '../config/database.php';
require_once '../includes/auth_admin.php';
require_once '../includes/system_functions.php';

$slide_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slide = null;

if ($slide_id > 0) {
    $stmt = prepare_statement($conn, "SELECT * FROM carousel_slides WHERE slide_id = ?");
    $stmt->bind_param("i", $slide_id);
    $stmt->execute();
    $slide = $stmt->get_result()->fetch_assoc();
    
    if (!$slide) {
        header("Location: admin_settings.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $slide ? 'Edit' : 'Add'; ?> Carousel Slide - BoteBank Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="flex">
        <main class="flex-1 ml-64 p-8">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-700 shadow-xl rounded-3xl mb-8">
                <div class="max-w-4xl mx-auto px-8 py-8">
                    <div class="flex items-center space-x-6">
                        <a href="admin_settings.php" class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm hover:bg-opacity-30 transition-all">
                            <i data-lucide="arrow-left" class="w-6 h-6 text-white"></i>
                        </a>
                        <div>
                            <h1 class="text-4xl font-bold text-white tracking-tight">
                                <?php echo $slide ? 'Edit' : 'Add New'; ?> Carousel Slide
                            </h1>
                            <p class="text-white text-opacity-80 text-lg">Configure the slide details and image</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                    <form action="carousel_handler.php" method="POST" enctype="multipart/form-data" id="carouselForm">
                        <input type="hidden" name="action" value="<?php echo $slide ? 'update' : 'add'; ?>">
                        <?php if ($slide): ?>
                            <input type="hidden" name="slide_id" value="<?php echo $slide['slide_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="p-8 space-y-6">
                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Slide Title *</label>
                                <input type="text" name="title" required
                                       value="<?php echo htmlspecialchars($slide['title'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
                                       placeholder="Enter slide title">
                            </div>

                            <!-- Subtitle -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Slide Subtitle</label>
                                <textarea name="subtitle" rows="3"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
                                          placeholder="Enter slide subtitle or description"><?php echo htmlspecialchars($slide['subtitle'] ?? ''); ?></textarea>
                            </div>

                            <!-- Image Upload -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Slide Image</label>
                                <?php if ($slide && $slide['image']): ?>
                                    <div class="mb-4">
                                        <img src="../uploads/carousel/<?php echo htmlspecialchars($slide['image']); ?>" 
                                             alt="Current Image" 
                                             class="w-full max-w-md h-48 object-cover rounded-xl border-2 border-gray-200">
                                        <p class="text-sm text-gray-500 mt-2">Current image (upload a new one to replace)</p>
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="image" accept="image/*"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                                <p class="text-xs text-gray-500 mt-2">Recommended size: 1920x1080px. Max 5MB. Supported formats: JPG, PNG, GIF</p>
                            </div>

                            <!-- Button Text & Link -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Button Text</label>
                                    <input type="text" name="button_text"
                                           value="<?php echo htmlspecialchars($slide['button_text'] ?? ''); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
                                           placeholder="e.g., Get Started">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Button Link</label>
                                    <input type="text" name="button_link"
                                           value="<?php echo htmlspecialchars($slide['button_link'] ?? ''); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
                                           placeholder="e.g., register.php or #about">
                                </div>
                            </div>

                            <!-- Display Order & Active Status -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Display Order</label>
                                    <input type="number" name="display_order" min="0"
                                           value="<?php echo $slide['display_order'] ?? 0; ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
                                           placeholder="0">
                                    <p class="text-xs text-gray-500 mt-2">Lower numbers appear first</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Status</label>
                                    <div class="flex items-center p-4 bg-gray-50 rounded-xl border border-gray-200">
                                        <input type="checkbox" name="is_active" value="1" 
                                               <?php echo (!$slide || $slide['is_active']) ? 'checked' : ''; ?>
                                               class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                        <label class="ml-3 text-sm font-medium text-gray-700">
                                            Active (Show this slide on homepage)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="bg-gray-50 px-8 py-6 border-t border-gray-200 flex justify-between items-center">
                            <a href="admin_settings.php" class="px-6 py-3 text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-all font-medium">
                                Cancel
                            </a>
                            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-700 text-white rounded-xl hover:from-purple-700 hover:to-indigo-800 transition-all font-semibold flex items-center shadow-lg">
                                <i data-lucide="save" class="w-5 h-5 mr-2"></i>
                                <?php echo $slide ? 'Update' : 'Create'; ?> Slide
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
        document.getElementById('carouselForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('carousel_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Get text first to check for problems
                const responseText = await response.text();
                
                // Try to parse as JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (jsonError) {
                    console.error("JSON parse error:", jsonError);
                    console.error("Response text:", responseText);
                    alert('Error parsing server response. Check browser console for details.');
                    return;
                }
                
                if (result.success) {
                    alert(result.message);
                    window.location.href = 'admin_settings.php';
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error("Fetch error:", error);
                alert('An error occurred: ' + error.message);
            }
        });
    </script>
</body>
</html>
