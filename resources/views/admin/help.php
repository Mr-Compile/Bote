<?php

declare(strict_types=1);

/** @var string $basePath */
?>
<div class="flex-1 p-8">
        <div class="bg-gradient-to-r from-bote-info-600 to-bote-green-700 shadow-xl rounded-3xl">
            <div class="max-w-7xl mx-auto px-8 py-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-6">
                        <div class="w-20 h-20 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                            <i data-lucide="help-circle" class="w-10 h-10 text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold text-white tracking-tight">Help & Support</h1>
                            <p class="text-white text-opacity-80 text-lg">Get assistance with system administration</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto mt-8 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-bote-stone-100 hover:shadow-xl transition-all duration-200">
                    <div class="flex items-center space-x-4 mb-4">
                        <div class="w-12 h-12 bg-bote-info-100 rounded-xl flex items-center justify-center">
                            <i data-lucide="users" class="w-6 h-6 text-bote-info-600"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-bote-stone-900">User Management</h3>
                    </div>
                    <p class="text-bote-stone-600 mb-4">Learn how to manage residents, workers, and admin accounts.</p>
                    <ul class="space-y-2 text-sm text-bote-stone-600">
                        <li> Add new users</li>
                        <li> Edit user profiles</li>
                        <li> Manage user roles</li>
                        <li> Reset passwords</li>
                    </ul>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6 border border-bote-stone-100 hover:shadow-xl transition-all duration-200">
                    <div class="flex items-center space-x-4 mb-4">
                        <div class="w-12 h-12 bg-bote-green-100 rounded-xl flex items-center justify-center">
                            <i data-lucide="truck" class="w-6 h-6 text-bote-green-600"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-bote-stone-900">Pickup Requests</h3>
                    </div>
                    <p class="text-bote-stone-600 mb-4">Manage waste collection and pickup scheduling.</p>
                    <ul class="space-y-2 text-sm text-bote-stone-600">
                        <li> View pending requests</li>
                        <li> Assign workers</li>
                        <li> Track collections</li>
                        <li> Generate reports</li>
                    </ul>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6 border border-bote-stone-100 hover:shadow-xl transition-all duration-200">
                    <div class="flex items-center space-x-4 mb-4">
                        <div class="w-12 h-12 bg-bote-green-100 rounded-xl flex items-center justify-center">
                            <i data-lucide="gift" class="w-6 h-6 text-bote-green-600"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-bote-stone-900">Rewards System</h3>
                    </div>
                    <p class="text-bote-stone-600 mb-4">Configure points and manage reward offerings.</p>
                    <ul class="space-y-2 text-sm text-bote-stone-600">
                        <li> Set point values</li>
                        <li> Add new rewards</li>
                        <li> Manage inventory</li>
                        <li> Process redemptions</li>
                    </ul>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6 border border-bote-stone-100 hover:shadow-xl transition-all duration-200">
                    <div class="flex items-center space-x-4 mb-4">
                        <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                            <i data-lucide="settings" class="w-6 h-6 text-orange-600"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-bote-stone-900">System Settings</h3>
                    </div>
                    <p class="text-bote-stone-600 mb-4">Configure system preferences and security.</p>
                    <ul class="space-y-2 text-sm text-bote-stone-600">
                        <li> Barangay information</li>
                        <li> Notification settings</li>
                        <li> Security policies</li>
                        <li> Admin profile</li>
                    </ul>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6 border border-bote-stone-100 hover:shadow-xl transition-all duration-200">
                    <div class="flex items-center space-x-4 mb-4">
                        <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                            <i data-lucide="map-pin" class="w-6 h-6 text-red-600"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-bote-stone-900">Location Management</h3>
                    </div>
                    <p class="text-bote-stone-600 mb-4">Manage puroks and collection points.</p>
                    <ul class="space-y-2 text-sm text-bote-stone-600">
                        <li> Add new puroks</li>
                        <li> Set coordinates</li>
                        <li> Map locations</li>
                        <li> Collection routes</li>
                    </ul>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6 border border-bote-stone-100 hover:shadow-xl transition-all duration-200">
                    <div class="flex items-center space-x-4 mb-4">
                        <div class="w-12 h-12 bg-bote-info-100 rounded-xl flex items-center justify-center">
                            <i data-lucide="file-text" class="w-6 h-6 text-bote-info-600"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-bote-stone-900">Reports & Analytics</h3>
                    </div>
                    <p class="text-bote-stone-600 mb-4">Generate reports and view system analytics.</p>
                    <ul class="space-y-2 text-sm text-bote-stone-600">
                        <li> Collection reports</li>
                        <li> User activity</li>
                        <li> Points summary</li>
                        <li> Performance metrics</li>
                    </ul>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-lg border border-bote-stone-100 overflow-hidden">
                <div class="bg-gradient-to-r from-bote-info-600 to-bote-info-700 px-8 py-6">
                    <h2 class="text-2xl font-bold text-white flex items-center">
                        <i data-lucide="help-circle" class="w-6 h-6 mr-3"></i>
                        Frequently Asked Questions
                    </h2>
                </div>

                <div class="p-8">
                    <div class="space-y-6">
                        <div class="border-b border-bote-stone-200 pb-6">
                            <h3 class="text-lg font-semibold text-bote-stone-900 mb-2">How do I reset a user's password?</h3>
                            <p class="text-bote-stone-600">Go to Users &gt; Select the user &gt; Click "Reset Password" button. The user will receive an email with instructions to set a new password.</p>
                        </div>

                        <div class="border-b border-bote-stone-200 pb-6">
                            <h3 class="text-lg font-semibold text-bote-stone-900 mb-2">How do I add a new recyclable type?</h3>
                            <p class="text-bote-stone-600">Navigate to Recyclable Types &gt; Click "Add New Type" &gt; Fill in the details including name, unit (kg/pcs), and point values &gt; Save.</p>
                        </div>

                        <div class="border-b border-bote-stone-200 pb-6">
                            <h3 class="text-lg font-semibold text-bote-stone-900 mb-2">How do I assign pickup requests to workers?</h3>
                            <p class="text-bote-stone-600">Go to Pickup Requests &gt; Select pending requests &gt; Choose "Assign Worker" &gt; Select available worker &gt; Confirm assignment.</p>
                        </div>

                        <div class="border-b border-bote-stone-200 pb-6">
                            <h3 class="text-lg font-semibold text-bote-stone-900 mb-2">How do I configure point values?</h3>
                            <p class="text-bote-stone-600">Visit Settings &gt; Points &amp; Rewards &gt; Adjust point values for each recyclable type &gt; Set daily limits and minimum redemption points &gt; Save changes.</p>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-bote-stone-900 mb-2">How do I backup system data?</h3>
                            <p class="text-bote-stone-600">Go to Settings &gt; Security &gt; Click "Download Backup" to export system data. Regular backups are recommended for data protection.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-bote-green-50 to-bote-green-50 rounded-3xl border border-bote-green-200 p-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-bote-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="message-circle" class="w-8 h-8 text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-bote-stone-900 mb-2">Need More Help?</h3>
                    <p class="text-bote-stone-600 mb-6">If you can't find the answer you're looking for, don't hesitate to contact our support team.</p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button class="bg-bote-green-600 hover:bg-bote-green-700 text-white px-6 py-3 rounded-xl font-medium transition-colors flex items-center justify-center">
                            <i data-lucide="mail" class="w-4 h-4 mr-2"></i>
                            Email Support
                        </button>
                        <button class="bg-white hover:bg-bote-stone-50 text-bote-green-600 border border-bote-green-600 px-6 py-3 rounded-xl font-medium transition-colors flex items-center justify-center">
                            <i data-lucide="phone" class="w-4 h-4 mr-2"></i>
                            Call Support
                        </button>
                    </div>
                </div>
            </div>
        </div>
</div>
