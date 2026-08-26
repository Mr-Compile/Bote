<?php
require_once '../config/database.php';
require_once '../includes/system_functions.php';
require '../includes/auth_res.php'; // Ensure only resident can access
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Support - BoteBank</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'resident_sidebar.php'; ?>
?>

<!-- Main Content -->
<div class="ml-64 p-8 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                    <i data-lucide="help-circle" class="w-8 h-8 text-white"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Help & Support</h1>
                    <p class="text-gray-600">Get help with BoteBank and recycling</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <i data-lucide="phone" class="w-5 h-5 text-green-600"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900">Contact Support</h3>
                </div>
                <p class="text-gray-600 text-sm mb-3">Get direct help from our support team</p>
                <p class="text-green-600 font-medium">+63 123 456 7890</p>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i data-lucide="mail" class="w-5 h-5 text-blue-600"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900">Email Support</h3>
                </div>
                <p class="text-gray-600 text-sm mb-3">Send us your questions via email</p>
                <p class="text-blue-600 font-medium">support@botebank.com</p>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i data-lucide="clock" class="w-5 h-5 text-purple-600"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900">Support Hours</h3>
                </div>
                <p class="text-gray-600 text-sm mb-3">We're here to help</p>
                <p class="text-purple-600 font-medium">24/7 Available</p>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="bg-white rounded-2xl shadow-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-900">Frequently Asked Questions</h2>
            </div>

            <div class="p-6">
                <div class="space-y-6">
                    <div class="border-b border-gray-200 pb-6">
                        <button class="flex items-center justify-between w-full text-left" onclick="toggleFAQ('faq1')">
                            <h3 class="text-lg font-semibold text-gray-900">How do I schedule a pickup?</h3>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-gray-500 transform transition-transform" id="faq1-icon"></i>
                        </button>
                        <div class="hidden mt-3 text-gray-600" id="faq1-content">
                            <p>To schedule a pickup, go to "New Request" in the sidebar, select your recyclable type, enter the weight, and choose your preferred pickup date. Our workers will be assigned to collect your materials.</p>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-6">
                        <button class="flex items-center justify-between w-full text-left" onclick="toggleFAQ('faq2')">
                            <h3 class="text-lg font-semibold text-gray-900">How are points calculated?</h3>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-gray-500 transform transition-transform" id="faq2-icon"></i>
                        </button>
                        <div class="hidden mt-3 text-gray-600" id="faq2-content">
                            <p>Points are calculated based on the type and weight of recyclable materials. Each material type has a specific points-per-kilogram rate. You earn points when your pickup is completed and verified by our workers.</p>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-6">
                        <button class="flex items-center justify-between w-full text-left" onclick="toggleFAQ('faq3')">
                            <h3 class="text-lg font-semibold text-gray-900">What materials can I recycle?</h3>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-gray-500 transform transition-transform" id="faq3-icon"></i>
                        </button>
                        <div class="hidden mt-3 text-gray-600" id="faq3-content">
                            <p>We accept various recyclable materials including plastic bottles, paper, cardboard, aluminum cans, glass bottles, and electronic waste. Check the "New Request" page for the complete list with point values.</p>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-6">
                        <button class="flex items-center justify-between w-full text-left" onclick="toggleFAQ('faq4')">
                            <h3 class="text-lg font-semibold text-gray-900">How can I redeem my points?</h3>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-gray-500 transform transition-transform" id="faq4-icon"></i>
                        </button>
                        <div class="hidden mt-3 text-gray-600" id="faq4-content">
                            <p>Visit the "Redeem Rewards" page to see available rewards. You can exchange your points for various items like gift cards, eco-friendly products, or cash vouchers. Redemption requests are processed within 2-3 business days.</p>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-6">
                        <button class="flex items-center justify-between w-full text-left" onclick="toggleFAQ('faq5')">
                            <h3 class="text-lg font-semibold text-gray-900">Can I cancel a pickup request?</h3>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-gray-500 transform transition-transform" id="faq5-icon"></i>
                        </button>
                        <div class="hidden mt-3 text-gray-600" id="faq5-content">
                            <p>Yes, you can cancel a pickup request if it's still in "Pending" status. Go to "My Requests" and click the cancel button. Once a worker is assigned and the status changes to "Scheduled", cancellation requires contacting support.</p>
                        </div>
                    </div>

                    <div class="pb-6">
                        <button class="flex items-center justify-between w-full text-left" onclick="toggleFAQ('faq6')">
                            <h3 class="text-lg font-semibold text-gray-900">How do I track my request status?</h3>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-gray-500 transform transition-transform" id="faq6-icon"></i>
                        </button>
                        <div class="hidden mt-3 text-gray-600" id="faq6-content">
                            <p>You can track all your requests in the "My Requests" section. The status will show as Pending (waiting for assignment), Scheduled (worker assigned), or Completed (materials collected and points awarded).</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Getting Started Guide -->
        <div class="bg-white rounded-2xl shadow-lg">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-900">Getting Started Guide</h2>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <span class="text-green-600 font-bold text-sm">1</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Prepare Your Recyclables</h4>
                                <p class="text-gray-600 text-sm">Clean and sort your recyclable materials by type (plastic, paper, glass, etc.)</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <span class="text-green-600 font-bold text-sm">2</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Create a Request</h4>
                                <p class="text-gray-600 text-sm">Use the "New Request" page to schedule a pickup with material type and weight</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <span class="text-green-600 font-bold text-sm">3</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Wait for Pickup</h4>
                                <p class="text-gray-600 text-sm">Our worker will be assigned and will collect your materials on the scheduled date</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <span class="text-blue-600 font-bold text-sm">4</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Earn Points</h4>
                                <p class="text-gray-600 text-sm">Points are automatically added to your account after successful pickup</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <span class="text-blue-600 font-bold text-sm">5</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Redeem Rewards</h4>
                                <p class="text-gray-600 text-sm">Exchange your points for exciting rewards in the "Redeem Rewards" section</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <span class="text-blue-600 font-bold text-sm">6</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Track Progress</h4>
                                <p class="text-gray-600 text-sm">Monitor your environmental impact and earnings in your dashboard</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    function toggleFAQ(faqId) {
        const content = document.getElementById(faqId + '-content');
        const icon = document.getElementById(faqId + '-icon');
        
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
        } else {
            content.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
        }
    }
</script>
</body>
</html>
