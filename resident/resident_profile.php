<?php
require_once '../config/database.php';
require_once '../includes/system_functions.php';
require '../includes/auth_res.php'; // Ensure only resident can access

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
    $address = trim($_POST['address']);
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validate required fields
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($phone)) $errors[] = "Phone is required";
    if (empty($address)) $errors[] = "Address is required";
    
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
                // Update with new password and coordinates
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = prepare_statement($conn, "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, latitude = ?, longitude = ?, password = ? WHERE user_id = ?");
                $stmt->bind_param("ssssssdsi", $first_name, $last_name, $email, $phone, $address, $latitude, $longitude, $hashed_password, $user_id);
            } else {
                // Update without changing password but include coordinates
                $stmt = prepare_statement($conn, "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, latitude = ?, longitude = ? WHERE user_id = ?");
                $stmt->bind_param("sssssddi", $first_name, $last_name, $email, $phone, $address, $latitude, $longitude, $user_id);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - BoteBank</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <!-- Leaflet CSS and JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <link rel="stylesheet" href="assets/css/profile.css">
    <!-- Pass PHP data to JavaScript -->
    <script>
        // Create user data object that will be accessible in profile.js
        const userData = {
            latitude: <?php echo $user['latitude'] ?: '6.4217'; ?>,
            longitude: <?php echo $user['longitude'] ?: '124.7801'; ?>,
            hasCoordinates: <?php echo $user['latitude'] ? 'true' : 'false'; ?>
        };
    </script>
</head>
<body class="bg-gradient-to-br from-emerald-50 via-green-50 to-teal-50 min-h-screen relative overflow-x-hidden">`
    <!-- Animated Background Elements -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-green-400/20 to-emerald-400/20 rounded-full blur-3xl float-animation"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-br from-teal-400/20 to-green-400/20 rounded-full blur-3xl float-animation" style="animation-delay: 3s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-60 h-60 bg-gradient-to-br from-emerald-400/10 to-green-400/10 rounded-full blur-3xl float-animation" style="animation-delay: 1.5s;"></div>
    </div>

    <?php include 'resident_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 p-8 relative z-10">
        <div class="max-w-5xl mx-auto">
            <!-- Enhanced Header with Profile Overview -->
            <div class="gradient-border mb-8 slide-up">
                <div class="bg-white/95 backdrop-blur-lg rounded-2xl p-8 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-green-500/5 to-emerald-500/5"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-6">
                                <div class="relative group">
                                    <?php if ($user['profile_photo']): ?>
                                        <img src="../uploads/profiles/thumbs/<?php echo htmlspecialchars($user['profile_photo']); ?>" 
                                             alt="Profile Photo" 
                                             class="w-24 h-24 rounded-2xl object-cover ring-4 ring-green-200/50 group-hover:ring-green-300/70 transition-all duration-300 pulse-glow">
                                    <?php else: ?>
                                        <div class="w-24 h-24 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center pulse-glow group-hover:scale-105 transition-all duration-300">
                                            <span class="text-white font-bold text-2xl">
                                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <button onclick="showPhotoModal()" 
                                            class="absolute -bottom-2 -right-2 w-8 h-8 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 rounded-xl flex items-center justify-center text-white transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-110">
                                        <i data-lucide="camera" class="w-4 h-4"></i>
                                    </button>
                                </div>
                                <div>
                                    <h1 class="text-4xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">
                                        My Profile
                                    </h1>
                                    <p class="text-gray-600 text-lg mt-1">Manage your account and personal information</p>
                                    <div class="flex items-center space-x-4 mt-3">
                                        <div class="flex items-center space-x-2 text-sm text-gray-500">
                                            <i data-lucide="calendar" class="w-4 h-4"></i>
                                            <span>Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                                        </div>
                                        <div class="flex items-center space-x-2 text-sm text-emerald-600">
                                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                                            <span class="font-medium"><?php echo ucfirst($user['role']); ?> Account</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="bg-gradient-to-r from-green-500 to-emerald-500 text-white px-6 py-3 rounded-xl shadow-lg">
                                    <div class="text-sm opacity-90">Account Status</div>
                                    <div class="text-xl font-bold">Active</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Message with Enhanced Design -->
            <?php if (isset($success_message)): ?>
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 mb-6 slide-up">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <i data-lucide="check" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-green-800 font-medium"><?php echo $success_message; ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Error Messages with Enhanced Design -->
            <?php if (!empty($errors)): ?>
            <div class="bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 rounded-xl p-4 mb-6 slide-up">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-red-800 font-medium mb-2">Please fix the following errors:</h3>
                        <ul class="list-disc list-inside text-red-700 space-y-1">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Enhanced Profile Form -->
            <div class="bg-white/80 backdrop-blur-lg rounded-2xl shadow-xl overflow-hidden card-hover slide-up">
                <div class="bg-gradient-to-r from-green-500 to-emerald-500 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-white">Account Information</h2>
                            <p class="text-green-100 mt-1">Update your personal details and security settings</p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <i data-lucide="user-cog" class="w-6 h-6 text-white"></i>
                        </div>
                    </div>
                </div>

                <form method="POST" class="p-8 space-y-8">
                    <!-- Personal Information Section -->
                    <div class="space-y-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                                <i data-lucide="user" class="w-4 h-4 text-white"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900">Personal Information</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="group">
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-3 group-hover:text-green-600 transition-colors">
                                    <i data-lucide="user" class="w-4 h-4 inline mr-2"></i>First Name
                                </label>
                                <input type="text" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 input-focus transition-all duration-300"
                                       required>
                            </div>

                            <div class="group">
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-3 group-hover:text-green-600 transition-colors">
                                    <i data-lucide="user" class="w-4 h-4 inline mr-2"></i>Last Name
                                </label>
                                <input type="text" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 input-focus transition-all duration-300"
                                       required>
                            </div>
                        </div>

                        <div class="group">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-3 group-hover:text-green-600 transition-colors">
                                <i data-lucide="mail" class="w-4 h-4 inline mr-2"></i>Email Address
                            </label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 input-focus transition-all duration-300"
                                   required>
                        </div>

                        <div class="group">
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-3 group-hover:text-green-600 transition-colors">
                                <i data-lucide="phone" class="w-4 h-4 inline mr-2"></i>Phone Number
                            </label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 input-focus transition-all duration-300"
                                   required>
                        </div>

                        <div class="group">
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-3 group-hover:text-green-600 transition-colors">
                                <i data-lucide="map-pin" class="w-4 h-4 inline mr-2"></i>Address
                            </label>
                            <textarea id="address" name="address" rows="4"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 input-focus transition-all duration-300 resize-none"
                                      required><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>

                        <!-- Map Section -->
                        <div class="group">
                            <label class="block text-sm font-medium text-gray-700 mb-3 group-hover:text-green-600 transition-colors">
                                <i data-lucide="navigation" class="w-4 h-4 inline mr-2"></i>Set Home Location on Map
                            </label>
                            <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-xl p-6 space-y-4">
                                <!-- Address Search -->
                                <div class="address-search">
                                    <div class="flex gap-3">
                                        <input type="text" id="address-search" placeholder="Search for your address (e.g., Mabini Street, Yangco)..."
                                               class="flex-1 px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-300">
                                        <button type="button" onclick="searchAddress()" 
                                                class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl transition-all duration-300 font-medium flex items-center gap-2">
                                            <i data-lucide="search" class="w-4 h-4"></i>
                                            <span>Search</span>
                                        </button>
                                    </div>
                                    <div id="search-results" class="search-results hidden"></div>
                                    
                                    <!-- Test Button for debugging -->
                                    <div class="mt-2 flex gap-2">
                                        <button type="button" onclick="testSearch()" 
                                                class="text-xs px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-600 rounded-lg transition-colors">
                                            <i data-lucide="test-tube" class="w-3 h-3 inline mr-1"></i>
                                            Test Search
                                        </button>
                                        <button type="button" onclick="getCurrentLocation()" 
                                                class="text-xs px-3 py-1 bg-purple-100 hover:bg-purple-200 text-purple-600 rounded-lg transition-colors">
                                            <i data-lucide="crosshair" class="w-3 h-3 inline mr-1"></i>
                                            Use My Location
                                        </button>
                                    </div>
                                </div>

                                <!-- Map Container -->
                                <div id="map" class="map-container"></div>

                                <!-- Coordinates Display -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-white rounded-lg p-3 border">
                                        <label class="text-xs text-gray-500 font-medium">Latitude</label>
                                        <div id="latitude-display" class="text-sm font-mono text-gray-800">
                                            <?php echo $user['latitude'] ? number_format($user['latitude'], 6) : 'Not set'; ?>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-lg p-3 border">
                                        <label class="text-xs text-gray-500 font-medium">Longitude</label>
                                        <div id="longitude-display" class="text-sm font-mono text-gray-800">
                                            <?php echo $user['longitude'] ? number_format($user['longitude'], 6) : 'Not set'; ?>
                                        </div>
                                    </div>
                                </div>

                                                                        <div class="flex gap-2">
                                          
                                            <button type="button" onclick="centerMap()" 
                                                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2">
                                                <i data-lucide="focus" class="w-4 h-4"></i>
                                                <span>Center Map</span>
                                            </button>
                                        </div>

                                <div class="text-xs text-gray-600 bg-blue-50 border border-blue-200 rounded-lg p-3">
                                    <i data-lucide="info" class="w-4 h-4 inline mr-1"></i>
                                    <strong>Instructions:</strong> 
                                    <ul class="mt-1 ml-4 list-disc space-y-1">
                                        <li>Search for your address using the search box above</li>
                                        <li>Click "Use My Location" to automatically detect your location</li>
                                        <li>Or click directly on the map to set your exact home location</li>
                                        <li>Use the test button if search is not working</li>
                                    </ul>
                                    <div class="mt-2 text-amber-600">
                                        <strong>Note:</strong> Setting your location helps our collection team find you more easily.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hidden coordinate inputs -->
                            <input type="hidden" id="latitude" name="latitude" value="<?php echo $user['latitude']; ?>">
                            <input type="hidden" id="longitude" name="longitude" value="<?php echo $user['longitude']; ?>">
                        </div>
                    </div>

                    <!-- Security Section -->
                    <div class="border-t border-gray-200 pt-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-8 h-8 bg-gradient-to-r from-red-500 to-pink-500 rounded-lg flex items-center justify-center">
                                <i data-lucide="shield" class="w-4 h-4 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900">Security Settings</h3>
                                <p class="text-sm text-gray-600">Update your password to keep your account secure</p>
                            </div>
                        </div>

                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                            <div class="flex">
                                <i data-lucide="info" class="w-5 h-5 text-amber-600 mt-0.5 mr-3"></i>
                                <div>
                                    <p class="text-amber-800 font-medium">Password Change Notice</p>
                                    <p class="text-amber-700 text-sm mt-1">Leave password fields blank to keep your current password unchanged</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="group">
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-3 group-hover:text-red-600 transition-colors">
                                    <i data-lucide="key" class="w-4 h-4 inline mr-2"></i>Current Password
                                </label>
                                <input type="password" id="current_password" name="current_password"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 input-focus transition-all duration-300">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="group">
                                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-3 group-hover:text-red-600 transition-colors">
                                        <i data-lucide="lock" class="w-4 h-4 inline mr-2"></i>New Password
                                    </label>
                                    <input type="password" id="new_password" name="new_password"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 input-focus transition-all duration-300">
                                </div>

                                <div class="group">
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-3 group-hover:text-red-600 transition-colors">
                                        <i data-lucide="lock" class="w-4 h-4 inline mr-2"></i>Confirm Password
                                    </label>
                                    <input type="password" id="confirm_password" name="confirm_password"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 input-focus transition-all duration-300">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 sm:space-x-4 pt-8 border-t border-gray-200">
                        <div class="flex items-center space-x-2 text-sm text-gray-500">
                            <i data-lucide="info" class="w-4 h-4"></i>
                            <span>All changes will be saved immediately</span>
                        </div>
                        <div class="flex space-x-4">
                            <button type="button" onclick="window.location.href='resident_dashboard.php'" 
                                    class="px-8 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-gray-400 transition-all duration-300 font-medium">
                                <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>Cancel
                            </button>
                            <button type="submit" 
                                    class="px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:scale-105">
                                <i data-lucide="save" class="w-4 h-4 inline mr-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Enhanced Account Statistics -->
            <div class="bg-white/80 backdrop-blur-lg rounded-2xl shadow-xl overflow-hidden card-hover slide-up mt-8">
                <div class="bg-gradient-to-r from-purple-500 to-indigo-500 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-white">Account Overview</h3>
                            <p class="text-purple-100 mt-1">Your account details and membership information</p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <i data-lucide="bar-chart-3" class="w-6 h-6 text-white"></i>
                        </div>
                    </div>
                </div>

                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="group relative overflow-hidden bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-6 border border-green-100 hover:border-green-200 transition-all duration-300 card-hover">
                            <div class="absolute inset-0 bg-gradient-to-br from-green-400/5 to-emerald-400/5"></div>
                            <div class="relative z-10">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
                                        <i data-lucide="calendar-check" class="w-6 h-6 text-white"></i>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-green-600"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                                        <div class="text-sm text-gray-600">Member Since</div>
                                    </div>
                                </div>
                                <div class="text-center pt-4 border-t border-green-100">
                                    <div class="text-sm text-gray-500">
                                        <?php 
                                        $memberSince = new DateTime($user['created_at']);
                                        $now = new DateTime();
                                        $diff = $now->diff($memberSince);
                                        echo $diff->days . ' days active';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="group relative overflow-hidden bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-100 hover:border-blue-200 transition-all duration-300 card-hover">
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-400/5 to-indigo-400/5"></div>
                            <div class="relative z-10">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center">
                                        <i data-lucide="shield-check" class="w-6 h-6 text-white"></i>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-blue-600"><?php echo ucfirst($user['role']); ?></div>
                                        <div class="text-sm text-gray-600">Account Type</div>
                                    </div>
                                </div>
                                <div class="text-center pt-4 border-t border-blue-100">
                                    <div class="inline-flex items-center space-x-1 text-sm text-blue-600 bg-blue-100 px-3 py-1 rounded-full">
                                        <i data-lucide="verified" class="w-3 h-3"></i>
                                        <span>Verified</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="group relative overflow-hidden bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl p-6 border border-purple-100 hover:border-purple-200 transition-all duration-300 card-hover">
                            <div class="absolute inset-0 bg-gradient-to-br from-purple-400/5 to-pink-400/5"></div>
                            <div class="relative z-10">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                                        <i data-lucide="activity" class="w-6 h-6 text-white"></i>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-purple-600">Active</div>
                                        <div class="text-sm text-gray-600">Status</div>
                                    </div>
                                </div>
                                <div class="text-center pt-4 border-t border-purple-100">
                                    <div class="inline-flex items-center space-x-1 text-sm text-green-600 bg-green-100 px-3 py-1 rounded-full">
                                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                        <span>Online</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Account Information -->
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-gray-50 to-slate-50 rounded-xl p-6 border border-gray-100">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <i data-lucide="info" class="w-5 h-5 mr-2 text-gray-600"></i>
                                Account Details
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">User ID</span>
                                    <span class="font-medium text-gray-900">#<?php echo str_pad($user['user_id'], 6, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Email Status</span>
                                    <span class="inline-flex items-center space-x-1 text-green-600">
                                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                                        <span class="font-medium">Verified</span>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Last Updated</span>
                                    <span class="font-medium text-gray-900"><?php echo date('M j, Y', strtotime($user['updated_at'] ?? $user['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-6 border border-amber-100">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <i data-lucide="settings" class="w-5 h-5 mr-2 text-amber-600"></i>
                                Quick Actions
                            </h4>
                            <div class="space-y-3">
                                <button onclick="window.location.href='resident_new_request.php'" 
                                        class="w-full text-left p-3 bg-white rounded-lg hover:bg-gray-50 transition-colors flex items-center space-x-3 border border-gray-200 hover:border-green-300">
                                    <i data-lucide="plus-circle" class="w-5 h-5 text-green-600"></i>
                                    <span class="font-medium text-gray-900">New Request</span>
                                </button>
                                <button onclick="window.location.href='resident_rewards.php'" 
                                        class="w-full text-left p-3 bg-white rounded-lg hover:bg-gray-50 transition-colors flex items-center space-x-3 border border-gray-200 hover:border-purple-300">
                                    <i data-lucide="gift" class="w-5 h-5 text-purple-600"></i>
                                    <span class="font-medium text-gray-900">View Rewards</span>
                                </button>
                                <button onclick="showPhotoModal()" 
                                        class="w-full text-left p-3 bg-white rounded-lg hover:bg-gray-50 transition-colors flex items-center space-x-3 border border-gray-200 hover:border-blue-300">
                                    <i data-lucide="camera" class="w-5 h-5 text-blue-600"></i>
                                    <span class="font-medium text-gray-900">Update Photo</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Photo Upload Modal -->
    <div id="photoModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-3xl p-8 max-w-lg w-full mx-4 transform transition-all duration-500 scale-95 opacity-0">
            <div class="text-center">
                <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-xl">
                    <i data-lucide="camera" class="w-10 h-10 text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Update Profile Photo</h3>
                <p class="text-gray-600 mb-8 leading-relaxed">
                    Choose a new profile photo to personalize your account.<br>
                    <span class="text-sm text-gray-500">Maximum file size: 5MB • Formats: JPG, PNG, GIF</span>
                </p>
                
                <div class="mb-8">
                    <input type="file" id="photoInput" accept="image/*" class="hidden">
                    <button onclick="document.getElementById('photoInput').click()" 
                            class="w-full group relative overflow-hidden bg-gradient-to-r from-green-50 to-emerald-50 hover:from-green-100 hover:to-emerald-100 border-2 border-dashed border-green-300 hover:border-green-400 rounded-2xl p-8 transition-all duration-300 transform hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-r from-green-400/0 to-emerald-400/0 group-hover:from-green-400/5 group-hover:to-emerald-400/5 transition-all duration-300"></div>
                        <div class="relative z-10">
                            <div class="w-16 h-16 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:bg-green-600 transition-colors">
                                <i data-lucide="upload" class="w-8 h-8 text-white"></i>
                            </div>
                            <p class="text-gray-700 font-medium mb-2">Click to select photo</p>
                            <p class="text-sm text-gray-500">or drag and drop your image here</p>
                        </div>
                    </button>
                </div>
                
                <div id="photoPreview" class="hidden mb-8">
                    <div class="relative inline-block">
                        <img id="previewImage" class="w-32 h-32 rounded-2xl object-cover mx-auto shadow-lg border-4 border-white">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent rounded-2xl"></div>
                        <button onclick="clearPreview()" class="absolute -top-2 -right-2 w-8 h-8 bg-red-500 hover:bg-red-600 rounded-full flex items-center justify-center text-white shadow-lg transition-colors">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <p class="text-sm text-gray-600 mt-3">Preview of your new profile photo</p>
                </div>
                
                <div class="flex space-x-4">
                    <button onclick="hidePhotoModal()" 
                            class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl font-medium transition-all duration-300 transform hover:scale-105">
                        <i data-lucide="x" class="w-4 h-4 inline mr-2"></i>Cancel
                    </button>
                    <button onclick="uploadPhoto()" id="uploadBtn" disabled 
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 disabled:from-gray-300 disabled:to-gray-300 disabled:cursor-not-allowed text-white rounded-xl font-medium transition-all duration-300 transform hover:scale-105 disabled:transform-none shadow-lg disabled:shadow-none">
                        <i data-lucide="upload" class="w-4 h-4 inline mr-2"></i>
                        <span id="uploadBtnText">Upload Photo</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Location Confirmation Modal -->
    <div id="locationConfirmModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-3xl p-8 max-w-lg w-full mx-4 transform transition-all duration-500 scale-95 opacity-0">
            <div class="text-center">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-xl">
                    <i data-lucide="map-pin" class="w-10 h-10 text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Confirm Location</h3>
                <p class="text-gray-600 mb-6 leading-relaxed">
                    Do you want to update your home location to:
                </p>
                <div class="bg-blue-50 rounded-xl p-4 mb-6">
                    <p class="text-sm font-medium text-blue-900 mb-2" id="confirm-address">Loading address...</p>
                    <div class="flex justify-center space-x-4 text-xs text-blue-700">
                        <span>Lat: <span id="confirm-lat" class="font-mono">0</span></span>
                        <span>Lng: <span id="confirm-lng" class="font-mono">0</span></span>
                    </div>
                </div>
                
                <div class="flex space-x-4">
                    <button onclick="cancelLocationUpdate()" 
                            class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl font-medium transition-all duration-300 transform hover:scale-105">
                        <i data-lucide="x" class="w-4 h-4 inline mr-2"></i>Cancel
                    </button>
                    <button onclick="confirmLocationUpdate()" 
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <i data-lucide="check" class="w-4 h-4 inline mr-2"></i>
                        <span>Confirm</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

<script src="assets/js/profile.js"></script>
</body>
</html>
