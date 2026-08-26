<?php
require_once '../config/database.php';
require '../includes/auth_worker.php'; // Ensure only worker can access

include 'worker_sidebar.php';
?>

<!-- Main Content -->
<div class="ml-64 p-8 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                    <i data-lucide="headphones" class="w-8 h-8 text-white"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Worker Support</h1>
                    <p class="text-gray-600">Get help with collections, routes, and system guidance</p>
                </div>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="bg-red-50 border border-red-200 rounded-2xl p-6 mb-8">
            <div class="flex items-start space-x-4">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i data-lucide="phone" class="w-6 h-6 text-red-600"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-red-900 mb-2">Emergency Contact</h2>
                    <p class="text-red-700 mb-3">For immediate assistance during collections or emergencies:</p>
                    <div class="text-red-800 font-bold text-lg">+63 123 456 7890</div>
                    <p class="text-red-600 text-sm mt-1">Available 24/7 for worker assistance and emergency support</p>
                </div>
            </div>
        </div>

        <!-- Quick Support Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <i data-lucide="message-circle" class="w-5 h-5 text-green-600"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900">Live Chat</h3>
                </div>
                <p class="text-gray-600 text-sm mb-3">Chat with our support team in real-time</p>
                <button class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg font-medium transition-colors">
                    Start Chat
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i data-lucide="mail" class="w-5 h-5 text-blue-600"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900">Email Support</h3>
                </div>
                <p class="text-gray-600 text-sm mb-3">Send detailed questions via email</p>
                <p class="text-blue-600 font-medium">workers@botebank.com</p>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i data-lucide="clock" class="w-5 h-5 text-purple-600"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900">Support Hours</h3>
                </div>
                <p class="text-gray-600 text-sm mb-3">Regular support availability</p>
                <p class="text-purple-600 font-medium">6:00 AM - 10:00 PM</p>
            </div>
        </div>

        <!-- Worker Guidelines -->
        <div class="bg-white rounded-2xl shadow-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-900">Worker Guidelines & Safety</h2>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Safety Protocols</h3>
                        
                        <div class="flex items-start space-x-3">
                            <div class="w-6 h-6 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <i data-lucide="hard-hat" class="w-4 h-4 text-yellow-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Protective Equipment</h4>
                                <p class="text-gray-600 text-sm">Always wear provided safety gear including gloves, reflective vest, and safety shoes</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-red-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Hazardous Materials</h4>
                                <p class="text-gray-600 text-sm">Do not collect unknown chemicals, batteries, or medical waste without proper authorization</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <i data-lucide="truck" class="w-4 h-4 text-blue-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Vehicle Safety</h4>
                                <p class="text-gray-600 text-sm">Conduct pre-trip inspections and follow all traffic regulations during collections</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Collection Best Practices</h3>
                        
                        <div class="flex items-start space-x-3">
                            <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <i data-lucide="check" class="w-4 h-4 text-green-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Quality Control</h4>
                                <p class="text-gray-600 text-sm">Verify materials match the request before collection and weigh accurately</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <i data-lucide="users" class="w-4 h-4 text-purple-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Customer Service</h4>
                                <p class="text-gray-600 text-sm">Be courteous, punctual, and professional when interacting with residents</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="w-6 h-6 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <i data-lucide="smartphone" class="w-4 h-4 text-orange-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">System Updates</h4>
                                <p class="text-gray-600 text-sm">Update pickup status immediately after collection and upload any required photos</p>
                            </div>
                        </div>
                    </div>
                </div>
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
                            <h3 class="text-lg font-semibold text-gray-900">How do I update pickup status?</h3>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-gray-500 transform transition-transform" id="faq1-icon"></i>
                        </button>
                        <div class="hidden mt-3 text-gray-600" id="faq1-content">
                            <p>Go to "Assigned Pickups" and click on the specific request. Use the status update buttons to mark as "In Progress" when you start, and "Completed" when finished. Always verify the weight and take photos if required.</p>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-6">
                        <button class="flex items-center justify-between w-full text-left" onclick="toggleFAQ('faq2')">
                            <h3 class="text-lg font-semibold text-gray-900">What if residents have more materials than requested?</h3>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-gray-500 transform transition-transform" id="faq2-icon"></i>
                        </button>
                        <div class="hidden mt-3 text-gray-600" id="faq2-content">
                            <p>You can collect additional materials if they are of the same type and quality. Update the weight in the system to reflect the actual amount collected. For significantly different materials, create a note and contact support.</p>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-6">
                        <button class="flex items-center justify-between w-full text-left" onclick="toggleFAQ('faq3')">
                            <h3 class="text-lg font-semibold text-gray-900">How are collection routes optimized?</h3>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-gray-500 transform transition-transform" id="faq3-icon"></i>
                        </button>
                        <div class="hidden mt-3 text-gray-600" id="faq3-content">
                            <p>Routes are automatically generated based on pickup locations, traffic patterns, and vehicle capacity. You can view and follow the suggested route order, but may adjust based on real-time conditions.</p>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-6">
                        <button class="flex items-center justify-between w-full text-left" onclick="toggleFAQ('faq4')">
                            <h3 class="text-lg font-semibold text-gray-900">What to do if no one is home?</h3>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-gray-500 transform transition-transform" id="faq4-icon"></i>
                        </button>
                        <div class="hidden mt-3 text-gray-600" id="faq4-content">
                            <p>Try calling the resident first. If they don't answer, wait 10 minutes and try again. If still no response, mark the pickup as "Resident Not Available" and proceed to the next stop. Contact the resident via SMS if possible.</p>
                        </div>
                    </div>

                    <div class="pb-6">
                        <button class="flex items-center justify-between w-full text-left" onclick="toggleFAQ('faq5')">
                            <h3 class="text-lg font-semibold text-gray-900">How do I report vehicle or equipment issues?</h3>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-gray-500 transform transition-transform" id="faq5-icon"></i>
                        </button>
                        <div class="hidden mt-3 text-gray-600" id="faq5-content">
                            <p>For immediate safety issues, stop work and call the emergency line. For non-urgent problems, use the "Report Issue" feature in your dashboard or contact support. Include photos and detailed descriptions when reporting equipment problems.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Training Resources -->
        <div class="bg-white rounded-2xl shadow-lg">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-900">Training & Resources</h2>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900">Video Tutorials</h3>
                        
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                    <i data-lucide="play" class="w-5 h-5 text-red-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">Safe Collection Procedures</h4>
                                    <p class="text-gray-600 text-sm">15 min video on proper collection techniques</p>
                                </div>
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i data-lucide="play" class="w-5 h-5 text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">Using the Worker App</h4>
                                    <p class="text-gray-600 text-sm">10 min guide to the mobile application</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900">Documentation</h3>
                        
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i data-lucide="file-text" class="w-5 h-5 text-green-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">Worker Handbook</h4>
                                    <p class="text-gray-600 text-sm">Complete guide to policies and procedures</p>
                                </div>
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <i data-lucide="download" class="w-5 h-5 text-purple-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">Safety Guidelines PDF</h4>
                                    <p class="text-gray-600 text-sm">Downloadable safety reference guide</p>
                                </div>
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
