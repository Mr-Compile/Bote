<?php
require_once '../config/database.php';
require '../includes/auth_worker.php'; // Ensure only worker can access

// Get user data for sidebar
$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = prepare_statement($conn, "SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validate required fields
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($phone)) $errors[] = "Phone is required";
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if email is already taken by another user
    $stmt = prepare_statement($conn, "SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email is already taken";
    }
    
    // Password validation if changing password
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }
    
    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = prepare_statement($conn, "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, password = ? WHERE user_id = ?");
                $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $hashed_password, $user_id);
            } else {
                // Update without changing password
                $stmt = prepare_statement($conn, "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?");
                $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $user_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $success_message = "Profile updated successfully!";
                // Refresh user data
                $stmt = prepare_statement($conn, "SELECT * FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                $errors[] = "Failed to update profile";
            }
        } catch (Exception $e) {
            $errors[] = "An error occurred: " . $e->getMessage();
        }
    }
}

// Get worker performance stats
$stmt = prepare_statement($conn, "
    SELECT 
        COUNT(*) as total_collections,
        SUM(pr.weight_kg) as total_weight
    FROM pickuprequests pr
    WHERE pr.assigned_worker_id = ? AND pr.status = 'Completed'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$worker_stats = $stmt->get_result()->fetch_assoc();

include 'worker_sidebar.php';
?>

<!-- Main Content -->
<div class="ml-64 p-8 bg-bote-stone-50 min-h-screen">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <?php if ($user['profile_photo']): ?>
                        <img src="../uploads/profiles/thumbs/<?php echo htmlspecialchars($user['profile_photo']); ?>" 
                             alt="Profile Photo" 
                             class="w-16 h-16 rounded-full object-cover ring-2 ring-bote-info-200">
                    <?php else: ?>
                        <div class="w-16 h-16 bg-gradient-to-br from-bote-info-500 to-bote-green-600 rounded-full flex items-center justify-center">
                            <span class="text-white font-bold text-xl">
                                <?php echo e(strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1))); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <button onclick="showPhotoModal()" 
                            class="absolute -bottom-1 -right-1 w-6 h-6 bg-bote-green-600 hover:bg-bote-green-700 rounded-full flex items-center justify-center text-white transition-colors">
                        <i data-lucide="camera" class="w-3 h-3"></i>
                    </button>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-bote-stone-900">Worker Profile</h1>
                    <p class="text-bote-stone-600">Manage your account and view performance</p>
                </div>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
        <div class="bg-bote-green-50 border border-bote-green-200 text-bote-green-700 px-4 py-3 rounded-xl mb-6">
            <div class="flex items-center">
                <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Performance Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-bote-stone-600 mb-1">Total Collections</p>
                        <p class="text-2xl font-bold text-bote-green-600"><?php echo number_format($worker_stats['total_collections'] ?? 0); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-bote-green-100 rounded-full flex items-center justify-center">
                        <i data-lucide="truck" class="w-6 h-6 text-bote-green-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-bote-stone-600 mb-1">Total Weight</p>
                        <p class="text-2xl font-bold text-bote-info-600"><?php echo number_format($worker_stats['total_weight'] ?? 0, 1); ?> kg</p>
                    </div>
                    <div class="w-12 h-12 bg-bote-info-100 rounded-full flex items-center justify-center">
                        <i data-lucide="weight" class="w-6 h-6 text-bote-info-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-bote-stone-600 mb-1">Performance</p>
                        <p class="text-2xl font-bold text-bote-green-600">Excellent</p>
                    </div>
                    <div class="w-12 h-12 bg-bote-green-100 rounded-full flex items-center justify-center">
                        <i data-lucide="star" class="w-6 h-6 text-bote-green-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-bote-stone-200 bg-bote-stone-50">
                <h2 class="text-xl font-semibold text-bote-stone-900">Account Information</h2>
            </div>

            <form method="POST" class="p-6 space-y-6">
                <?php echo \App\Core\Csrf::field(); ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-bote-stone-700 mb-2">First Name</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($user['first_name']); ?>"
                               class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500"
                               required>
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-medium text-bote-stone-700 mb-2">Last Name</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($user['last_name']); ?>"
                               class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500"
                               required>
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-bote-stone-700 mb-2">Email Address</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>"
                           class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500"
                           required>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-bote-stone-700 mb-2">Phone Number</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($user['phone']); ?>"
                           class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500"
                           required>
                </div>

                <div class="border-t border-bote-stone-200 pt-6">
                    <h3 class="text-lg font-medium text-bote-stone-900 mb-4">Change Password</h3>
                    <p class="text-sm text-bote-stone-600 mb-4">Leave blank to keep current password</p>

                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-bote-stone-700 mb-2">Current Password</label>
                            <input type="password" id="current_password" name="current_password"
                                   class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-bote-stone-700 mb-2">New Password</label>
                                <input type="password" id="new_password" name="new_password"
                                       class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500">
                            </div>

                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-bote-stone-700 mb-2">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password"
                                       class="w-full px-3 py-2 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 pt-6">
                    <button type="button" onclick="goToDashboard()" 
                            class="px-6 py-2 border border-bote-stone-300 text-bote-stone-700 rounded-lg hover:bg-bote-stone-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-6 py-2 bg-bote-green-600 hover:bg-bote-green-700 text-white rounded-lg transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Account Information -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mt-8">
            <h3 class="text-lg font-semibold text-bote-stone-900 mb-4">Account Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-bote-info-50 rounded-lg">
                    <div class="text-2xl font-bold text-bote-info-600"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                    <div class="text-sm text-bote-stone-600">Joined Date</div>
                </div>
                <div class="text-center p-4 bg-bote-green-50 rounded-lg">
                    <div class="text-2xl font-bold text-bote-green-600"><?php echo e(ucfirst($user['role'])); ?></div>
                    <div class="text-sm text-bote-stone-600">Account Type</div>
                </div>
                <div class="text-center p-4 bg-bote-green-50 rounded-lg">
                    <div class="text-2xl font-bold text-bote-green-600">Active</div>
                    <div class="text-sm text-bote-stone-600">Status</div>
                </div>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mt-8">
            <h3 class="text-lg font-semibold text-bote-stone-900 mb-4">Emergency Information</h3>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-yellow-600 mt-0.5 mr-3"></i>
                    <div>
                        <h4 class="font-medium text-yellow-800">Emergency Contact</h4>
                        <p class="text-yellow-700 text-sm mt-1">
                            In case of emergency during collection, contact: <br>
                            <strong>BoteBank Emergency Line: +63 123 456 7890</strong><br>
                            Available 24/7 for worker assistance
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Photo Upload Modal -->
<div id="photoModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95">
        <div class="text-center">
            <div class="w-16 h-16 bg-bote-info-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="camera" class="w-8 h-8 text-bote-info-600"></i>
            </div>
            <h3 class="text-xl font-bold text-bote-stone-900 mb-2">Update Profile Photo</h3>
            <p class="text-bote-stone-600 mb-6">Choose a new profile photo. Maximum file size is 5MB.</p>
            
            <div class="mb-6">
                <input type="file" id="photoInput" accept="image/*" class="hidden">
                <button onclick="triggerPhotoInput()" 
                        class="w-full p-4 border-2 border-dashed border-bote-stone-300 rounded-lg hover:border-bote-info-500 transition-colors">
                    <i data-lucide="upload" class="w-8 h-8 text-bote-stone-400 mx-auto mb-2"></i>
                    <p class="text-bote-stone-600">Click to select photo</p>
                </button>
            </div>
            
            <div id="photoPreview" class="hidden mb-6">
                <img id="previewImage" class="w-24 h-24 rounded-full object-cover mx-auto">
            </div>
            
            <div class="flex space-x-4">
                <button onclick="hidePhotoModal()" class="flex-1 px-4 py-2 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-800 rounded-xl font-medium transition-colors">
                    Cancel
                </button>
                <button onclick="uploadPhoto()" id="uploadBtn" disabled 
                        class="flex-1 px-4 py-2 bg-bote-green-600 hover:bg-bote-green-700 disabled:bg-bote-stone-300 disabled:cursor-not-allowed text-white rounded-xl font-medium transition-colors">
                    Upload
                </button>
            </div>
        </div>
    </div>
</div>

<script type="module" src="assets/js/worker_profile.js"></script>
