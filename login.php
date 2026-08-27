<?php
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/system_functions.php';

// Get login image if available
$login_image = getSystemSetting($conn, 'login_image', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="assets/js/app/theme.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-bote-stone-50">
    <div class="flex min-h-screen">
        <!-- Left Side - System Image -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
            <?php if ($login_image): ?>
                <!-- Custom login image -->
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('uploads/system/auth/<?php echo htmlspecialchars($login_image); ?>');">
                    <div class="absolute inset-0 bg-black bg-opacity-30"></div>
                </div>
            <?php else: ?>
                <!-- Default gradient background with elements -->
                <div class="absolute inset-0 bg-gradient-to-br from-bote-info-600 to-bote-green-700"></div>
            <?php endif; ?>
            
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            <div class="relative z-10 flex flex-col justify-center items-center text-white p-12">
                <!-- System Logo/Icon -->
                <div class="mb-8">
                    <svg class="w-24 h-24 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </div>
                
                <!-- System Info -->
                <h1 class="text-4xl font-bold mb-4 text-center">BoteBank System</h1>
                <p class="text-xl text-center text-bote-info-100 max-w-md">
                    Barangay Cruz Recycling & Reward Management Platform - Secure login for residents, workers, and administrators.
                </p>
                
                <!-- Decorative Elements -->
                <div class="absolute top-20 left-20 w-32 h-32 bg-white bg-opacity-10 rounded-full"></div>
                <div class="absolute bottom-20 right-20 w-24 h-24 bg-white bg-opacity-10 rounded-full"></div>
                <div class="absolute top-1/2 left-10 w-16 h-16 bg-white bg-opacity-10 rounded-full"></div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
            <div class="max-w-md w-full space-y-8">
                <!-- Header -->
                <div class="text-center">
                    <h2 class="text-3xl font-bold text-bote-stone-900 mb-2">Sign In to BoteBank</h2>
                    <p class="text-bote-stone-600">Welcome back! Please sign in to your account.</p>
                </div>

                <!-- Login Form -->
                <form class="mt-8 space-y-6" action="login_handler.php" method="POST">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo htmlspecialchars($_GET['error']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['registration']) && $_GET['registration'] == 'success'): ?>
                        <div class="bg-bote-green-100 border border-bote-green-400 text-bote-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">Registration successful! Please login.</span>
                        </div>
                    <?php endif; ?>
                    <div class="space-y-4">
                        <!-- Email Field -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-bote-stone-700 mb-2">
                                Email Address
                            </label>
                            <input 
                                id="email" 
                                name="email" 
                                type="email" 
                                required 
                                class="w-full px-4 py-3 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition duration-200 text-bote-stone-900 placeholder-bote-stone-500"
                                placeholder="Enter your email"
                            >
                        </div>

                        <!-- Password Field -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-bote-stone-700 mb-2">
                                Password
                            </label>
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                required 
                                class="w-full px-4 py-3 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 transition duration-200 text-bote-stone-900 placeholder-bote-stone-500"
                                placeholder="Enter your password"
                            >
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input 
                                id="remember-me" 
                                name="remember-me" 
                                type="checkbox" 
                                class="h-4 w-4 text-bote-info-600 focus:ring-bote-green-500 border-bote-stone-300 rounded"
                            >
                            <label for="remember-me" class="ml-2 block text-sm text-bote-stone-700">
                                Remember me
                            </label>
                        </div>
                        <div class="text-sm">
                            <a href="#" class="font-medium text-bote-info-600 hover:text-bote-info-500 transition duration-200">
                                Forgot your password?
                            </a>
                        </div>
                    </div>

                    <!-- Sign In Button -->
                    <div>
                        <button 
                            type="submit" 
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-bote-green-600 hover:bg-bote-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-bote-green-500 transition duration-200"
                        >
                            Login
                        </button>
                    </div>


                    <div class="text-center">
                        <p class="text-sm text-bote-stone-600">
                            Don't have an account? 
                            <a href="register.php" class="font-medium text-bote-info-600 hover:text-bote-info-500 transition duration-200">
                                Sign up here
                            </a>
                        </p>
                        <p class="text-sm text-bote-stone-600 mt-2">
                            <a href="index.php" class="font-medium text-bote-info-600 hover:text-bote-info-500 transition duration-200">
                                ← Back to Home
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
