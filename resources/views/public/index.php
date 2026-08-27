<?php
/**
 * Public landing page view.
 *
 * Expected variables: $system_name, $system_logo, $banner_image, $banner_title, $banner_subtitle, $banner_enabled, $carousel_slides
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($system_name); ?> - Tracking & Reward System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="assets/js/app/theme.js"></script>
    <script type="module" src="assets/js/pages/public/index.js"></script>
    <style>
        .hero-bg {
            background: linear-gradient(rgba(49, 101, 43, 0.8), rgba(49, 101, 43, 0.8)), 
                        <?php if($banner_enabled == '1' && $banner_image && file_exists("uploads/system/banners/" . $banner_image)): ?>
                        url('uploads/system/banners/<?php echo htmlspecialchars($banner_image); ?>');
                        <?php else: ?>
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%23f4fbf2" width="1200" height="600"/><circle fill="%23e3f5df" cx="200" cy="150" r="80"/><circle fill="%23c5e8bf" cx="800" cy="200" r="60"/><circle fill="%23e3f5df" cx="1000" cy="400" r="100"/><path fill="%23c5e8bf" d="M0,400 Q300,350 600,400 T1200,400 L1200,600 L0,600 Z"/></svg>');
                        <?php endif; ?>
            background-size: cover;
            background-position: center;
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .nav-sticky {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95);
        }
        
        .pulse-green {
            animation: pulse-green 2s infinite;
        }
        
        @keyframes pulse-green {
            0%, 100% { box-shadow: 0 0 0 0 rgba(49, 101, 43, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(49, 101, 43, 0); }
        }
        
        /* Carousel Styles */
        .carousel-container {
            position: relative;
            width: 100%;
            height: 100vh;
        }
        
        .carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .carousel-slide.active {
            opacity: 1;
            z-index: 1;
        }
        
        .carousel-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
            border: 2px solid white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .carousel-dot.active {
            background-color: white;
            width: 14px;
            height: 14px;
        }
        
        .carousel-dot:hover {
            background-color: rgba(255, 255, 255, 0.8);
            transform: scale(1.2);
        }
        
        .carousel-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .carousel-arrow:hover {
            background-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-50%) scale(1.1);
        }
        
        .carousel-arrow-left {
            left: 20px;
        }
        
        .carousel-arrow-right {
            right: 20px;
        }
        
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in {
            animation: fade-in 1s ease-out;
        }
        
        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }
        
        /* Add scroll margin for sections with anchor links */
        .scroll-mt-16 {
            scroll-margin-top: 4rem;
        }
    </style>
</head>
<body class="bg-bote-stone-50">
    <!-- Logout Success Notification -->
    <?php if (isset($_GET['logged_out'])): ?>
        <div id="logout-notification" class="fixed top-4 right-4 bg-bote-green-500 text-white px-6 py-3 rounded-xl shadow-lg z-50 transform translate-x-full transition-transform duration-300">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span>Successfully logged out!</span>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Header / Navigation -->
    <nav class="fixed top-0 w-full z-50 nav-sticky border-b border-bote-stone-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <?php if ($system_logo && file_exists("uploads/system/logos/" . $system_logo)): ?>
                        <img src="uploads/system/logos/<?php echo htmlspecialchars($system_logo); ?>" 
                             alt="<?php echo htmlspecialchars($system_name); ?> Logo" 
                             class="w-10 h-10 rounded-full object-cover">
                    <?php else: ?>
                        <div class="w-10 h-10 bg-bote-green-700 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2Z"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    <span class="text-xl font-bold text-bote-green-700"><?php echo htmlspecialchars($system_name); ?></span>
                </div>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.php" class="text-bote-stone-700 hover:text-bote-green-700 font-medium transition-colors">Home</a>
                    <a href="#about" class="text-bote-stone-700 hover:text-bote-green-700 font-medium transition-colors">About Us</a>
                    <a href="login.php" class="bg-bote-green-700 text-white px-4 py-2 rounded-lg hover:bg-bote-green-700 transition-colors">Login</a>
                </div>
                
                <button class="md:hidden p-2">
                    <svg class="w-6 h-6 text-bote-stone-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Carousel -->
    <section class="relative min-h-screen pt-16 overflow-hidden">
        <!-- Carousel Background -->
        <div class="carousel-container absolute inset-0">
            <?php foreach ($carousel_slides as $index => $slide): ?>
            <!-- Slide <?php echo e($index + 1); ?> -->
            <div class="carousel-slide <?php echo e($index === 0 ? 'active' : ''); ?> flex items-center justify-center"
                 style="background: linear-gradient(rgba(49, 101, 43, 0.85), rgba(39, 81, 36, 0.85))<?php 
                 if ($slide['image']) {
                     echo ", url('uploads/carousel/" . htmlspecialchars($slide['image']) . "')";
                 } else {
                     echo ", url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 1200 600%22><rect fill=%22%23c5e8bf%22 width=%221200%22 height=%22600%22/><circle fill=%22%2331652b%22 cx=%22300%22 cy=%22200%22 r=%22100%22/><circle fill=%22%2372b968%22 cx=%22900%22 cy=%22350%22 r=%22120%22/></svg>')";
                 }
                 ?>; background-size: cover; background-position: center;">
                <div class="flex items-center justify-center h-full">
                    <div class="max-w-4xl mx-auto px-4 text-center text-white">
                        <h1 class="text-5xl md:text-6xl font-bold mb-6 leading-tight animate-fade-in">
                            <?php echo htmlspecialchars($slide['title']); ?>
                        </h1>
                        <?php if ($slide['subtitle']): ?>
                        <p class="text-xl md:text-2xl mb-8 text-bote-stone-100 max-w-3xl mx-auto">
                            <?php echo htmlspecialchars($slide['subtitle']); ?>
                        </p>
                        <?php endif; ?>
                        <?php if ($slide['button_text'] && $slide['button_link']): ?>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="<?php echo htmlspecialchars($slide['button_link']); ?>" 
                               class="bg-white text-bote-green-700 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-bote-stone-100 transition-colors pulse-green">
                                <?php echo htmlspecialchars($slide['button_text']); ?>
                            </a>
                            <?php if ($index === 0): ?>
                            <a href="login.php" class="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-white hover:text-bote-green-700 transition-colors">
                                Login
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Carousel Navigation Dots -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 flex space-x-3 z-10">
            <?php foreach ($carousel_slides as $index => $slide): ?>
            <button class="carousel-dot <?php echo e($index === 0 ? 'active' : ''); ?>" onclick="goToSlide(<?php echo htmlspecialchars(json_encode($index, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>)"></button>
            <?php endforeach; ?>
        </div>

        <!-- Carousel Navigation Arrows -->
        <button class="carousel-arrow carousel-arrow-left" onclick="prevSlide()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </button>
        <button class="carousel-arrow carousel-arrow-right" onclick="nextSlide()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </button>
    </section>

    <!-- System Overview Section -->
    <section class="py-20 bg-white">
        <div class="max-w-6xl mx-auto px-4 text-center">
            <div class="flex justify-center mb-8">
                <div class="w-16 h-16 bg-bote-green-200 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-bote-green-700" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1H5C3.9 1 3 1.9 3 3V21C3 22.1 3.9 23 5 23H19C20.1 23 21 22.1 21 21V9H21ZM19 21H5V3H13V9H19V21Z"/>
                    </svg>
                </div>
            </div>
            <h2 class="text-4xl font-bold text-bote-stone-800 mb-6">How BoteBank Works</h2>
            <p class="text-lg text-bote-stone-600 max-w-4xl mx-auto leading-relaxed">
                The BoteBank System helps residents schedule recyclable pickups, earn points, redeem rewards, and compete on the eco-leaderboard. It streamlines waste collection with smart routing and real-time SMS updates, making recycling rewarding and efficient for our entire community.
            </p>
        </div>
    </section>

    <!-- Core Features Section -->
    <section class="py-20 bg-bote-stone-50">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-4xl font-bold text-center text-bote-stone-800 mb-16">Core Features</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Eco-Leaderboard -->
                <div class="bg-white rounded-xl p-8 shadow-lg card-hover">
                    <div class="w-16 h-16 bg-bote-green-700 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16 4V2C16 1.45 15.55 1 15 1H9C8.45 1 8 1.45 8 2V4H16ZM15 6H9L8.5 18C8.5 19.1 9.4 20 10.5 20H13.5C14.6 20 15.5 19.1 15.5 18L15 6ZM6 6V4H4V6H1V8H2V19C2 20.1 2.9 21 4 21H6V8H7V6H6ZM18 6V8H19V19C19 20.1 19.9 21 21 21H23C24.1 21 25 20.1 25 19V8H26V6H23V4H21V6H18Z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-bote-stone-800 mb-4 text-center">Eco-Leaderboard</h3>
                    <p class="text-bote-stone-600 text-center">Track individual and purok performance with badges and rankings. Compete with neighbors and celebrate environmental achievements together.</p>
                </div>

                <!-- Pickup Scheduler -->
                <div class="bg-white rounded-xl p-8 shadow-lg card-hover">
                    <div class="w-16 h-16 bg-bote-green-700 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19 3H18V1H16V3H8V1H6V3H5C3.89 3 3.01 3.9 3.01 5L3 19C3 20.1 3.89 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM19 19H5V8H19V19ZM7 10H12V15H7V10Z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-bote-stone-800 mb-4 text-center">Pickup Scheduler</h3>
                    <p class="text-bote-stone-600 text-center">Easily request recyclable pickup at your convenience. Schedule collections and receive confirmations for hassle-free waste management.</p>
                </div>

                <!-- Smart Routing -->
                <div class="bg-white rounded-xl p-8 shadow-lg card-hover">
                    <div class="w-16 h-16 bg-bote-green-700 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2ZM12 4.5L11.5 7.5L8.5 8L11.5 8.5L12 11.5L12.5 8.5L15.5 8L12.5 7.5L12 4.5Z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-bote-stone-800 mb-4 text-center">Smart Routing</h3>
                    <p class="text-bote-stone-600 text-center">Optimized collection routes for waste workers ensure efficient pickups and reduced travel time throughout the barangay.</p>
                </div>

                <!-- Points & Rewards -->
                <div class="bg-white rounded-xl p-8 shadow-lg card-hover">
                    <div class="w-16 h-16 bg-bote-green-700 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM12 20C10.9 20 10 19.1 10 18C10 16.9 10.9 16 12 16C13.1 16 14 16.9 14 18C14 19.1 13.1 20 12 20ZM6 8C7.1 8 8 8.9 8 10C8 11.1 7.1 12 6 12C4.9 12 4 11.1 4 10C4 8.9 4.9 8 6 8ZM18 8C19.1 8 20 8.9 20 10C20 11.1 19.1 12 18 12C16.9 12 16 11.1 16 10C16 8.9 16.9 8 18 8Z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-bote-stone-800 mb-4 text-center">Points & Rewards</h3>
                    <p class="text-bote-stone-600 text-center">Earn points for every recyclable collected and redeem them for rice, mobile load, or school supplies for your family.</p>
                </div>

                <!-- SMS Notifications -->
                <div class="bg-white rounded-xl p-8 shadow-lg card-hover">
                    <div class="w-16 h-16 bg-bote-green-700 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20 4H4C2.9 4 2.01 4.9 2.01 6L2 18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V6C22 4.9 21.1 4 20 4ZM20 8L12 13L4 8V6L12 11L20 6V8Z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-bote-stone-800 mb-4 text-center">SMS Notifications</h3>
                    <p class="text-bote-stone-600 text-center">Receive timely reminders, pickup confirmations, and leaderboard rank updates directly to your mobile phone.</p>
                </div>

                <!-- Community Impact -->
                <div class="bg-white rounded-xl p-8 shadow-lg card-hover">
                    <div class="w-16 h-16 bg-bote-green-700 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16 4C18.2 4 20 5.8 20 8C20 10.2 18.2 12 16 12C13.8 12 12 10.2 12 8C12 5.8 13.8 4 16 4ZM16 14C18.7 14 22 15.3 22 18V20H10V18C10 15.3 13.3 14 16 14ZM8 12C10.2 12 12 10.2 12 8C12 5.8 10.2 4 8 4C5.8 4 4 5.8 4 8C4 10.2 5.8 12 8 12ZM8 14C5.3 14 2 15.3 2 18V20H10V18C10 16.4 9.2 15.1 8 14Z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-bote-stone-800 mb-4 text-center">Community Impact</h3>
                    <p class="text-bote-stone-600 text-center">Track collective environmental impact and see how our barangay is making a difference in plastic waste reduction.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about" class="py-20 bg-white scroll-mt-16">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-4xl font-bold text-center text-bote-stone-800 mb-16">About BoteBank</h2>
            <div class="max-w-4xl mx-auto text-center mb-16">
                <p class="text-lg text-bote-stone-600 mb-6 leading-relaxed">
                    BoteBank is a community-driven initiative designed to transform waste management in our barangay. 
                    We believe that recycling should be rewarding, efficient, and accessible to everyone.
                </p>
                <p class="text-lg text-bote-stone-600 leading-relaxed">
                    Through our innovative platform, residents can easily schedule pickups, track their environmental impact, 
                    and earn rewards while contributing to a cleaner, greener community. Together, we're building a sustainable future.
                </p>
            </div>
            <h3 class="text-3xl font-bold text-center text-bote-stone-800 mb-12">Who Uses BoteBank</h3>
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Residents -->
                <div class="text-center">
                    <div class="w-24 h-24 bg-bote-green-200 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <svg class="w-12 h-12 text-bote-green-700" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-bote-stone-800 mb-4">Residents</h3>
                    <p class="text-bote-stone-600">Schedule pickups, earn and redeem points, view leaderboard rankings, and contribute to a cleaner community.</p>
                </div>

                <!-- Workers -->
                <div class="text-center">
                    <div class="w-24 h-24 bg-bote-green-200 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <svg class="w-12 h-12 text-bote-green-700" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2ZM9 11H15L14 15H10L9 11ZM12.5 7.5L12 4.5L11.5 7.5L8.5 8L11.5 8.5L12 11.5L12.5 8.5L15.5 8L12.5 7.5Z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-bote-stone-800 mb-4">Workers</h3>
                    <p class="text-bote-stone-600">Follow optimized collection routes, record collected recyclables, and efficiently serve the community with smart logistics.</p>
                </div>

                <!-- Admin/Barangay -->
                <div class="text-center">
                    <div class="w-24 h-24 bg-bote-green-200 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <svg class="w-12 h-12 text-bote-green-700" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 1L3 5V11C3 16.55 6.84 21.74 12 23C17.16 21.74 21 16.55 21 11V5L12 1ZM12 7C13.1 7 14 7.9 14 9C14 10.1 13.1 11 12 11C10.9 11 10 10.1 10 9C10 7.9 10.9 7 12 7ZM18 15C18 16.1 17.1 17 16 17H8C6.9 17 6 16.1 6 15V14C6 13.4 6.4 13 7 13H17C17.6 13 18 13.4 18 14V15Z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-bote-stone-800 mb-4">Admin/Barangay</h3>
                    <p class="text-bote-stone-600">Monitor system performance, manage rewards inventory, configure SMS templates, and oversee community engagement.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Banner -->
    <section class="py-16 bg-bote-green-700">
        <div class="max-w-4xl mx-auto text-center px-4">
            <h2 class="text-4xl font-bold text-white mb-6">
                Start Recycling. Earn Rewards.<br>
                Build a Greener Barangay Today.
            </h2>
            <a href="register.php" class="inline-block bg-white text-bote-green-700 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-bote-stone-100 transition-colors pulse-green">
                Join Now
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-bote-stone-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <?php if ($system_logo && file_exists("uploads/system/logos/" . $system_logo)): ?>
                            <img src="uploads/system/logos/<?php echo htmlspecialchars($system_logo); ?>" 
                                 alt="<?php echo htmlspecialchars($system_name); ?> Logo" 
                                 class="w-10 h-10 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-10 h-10 bg-bote-green-700 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2Z"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                        <span class="text-xl font-bold"><?php echo htmlspecialchars($system_name); ?></span>
                    </div>
                    <p class="text-bote-stone-400">Building a sustainable future through community-driven recycling initiatives.</p>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-bote-stone-400">
                        <li><a href="index.php" class="hover:text-bote-green-200 transition-colors">Home</a></li>
                        <li><a href="#about" class="hover:text-bote-green-200 transition-colors">About Us</a></li>
                        <li><a href="login.php" class="hover:text-bote-green-200 transition-colors">Login</a></li>
                        <li><a href="register.php" class="hover:text-bote-green-200 transition-colors">Register</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Features</h4>
                    <ul class="space-y-2 text-bote-stone-400">
                        <li class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-bote-green-200" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Pickup Scheduler</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-bote-green-200" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Points & Rewards</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-bote-green-200" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>SMS Notifications</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-bote-green-200" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Smart Routing</span>
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contact Info</h4>
                    <div class="space-y-3 text-bote-stone-400">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-bote-green-200" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C8.13 2 5 5.13 5 9C5 14.25 12 22 12 22S19 14.25 19 9C19 5.13 15.87 2 12 2ZM12 11.5C10.62 11.5 9.5 10.38 9.5 9S10.62 6.5 12 6.5S14.5 7.62 14.5 9S13.38 11.5 12 11.5Z"/>
                            </svg>
                            <span>Barangay Cruz, City</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-bote-green-200" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M6.62 10.79C8.06 13.62 10.38 15.94 13.21 17.38L15.41 15.18C15.69 14.9 16.08 14.82 16.43 14.93C17.55 15.3 18.75 15.5 20 15.5C20.55 15.5 21 15.95 21 16.5V20C21 20.55 20.55 21 20 21C10.61 21 3 13.39 3 4C3 3.45 3.45 3 4 3H7.5C8.05 3 8.5 3.45 8.5 4C8.5 5.25 8.7 6.45 9.07 7.57C9.18 7.92 9.1 8.31 8.82 8.59L6.62 10.79Z"/>
                            </svg>
                            <span>(02) 123-4567</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-bote-green-200" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20 4H4C2.9 4 2.01 4.9 2.01 6L2 18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V6C22 4.9 21.1 4 20 4ZM20 8L12 13L4 8V6L12 11L20 6V8Z"/>
                            </svg>
                            <span>info@barangaycruz.gov.ph</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-bote-stone-700 mt-8 pt-8 text-center text-bote-stone-400">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($system_name); ?> System. All rights reserved. Building a greener tomorrow, today.</p>
            </div>
        </div>
    </footer>

</body>
</html>

