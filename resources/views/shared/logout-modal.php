<?php

declare(strict_types=1);

/** @var string $basePath */
/** @var string|null $systemName */
?>
<div id="logoutModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" aria-modal="true" role="dialog">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95">
        <div class="text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="log-out" class="w-8 h-8 text-red-600"></i>
            </div>
            <h3 class="text-xl font-bold text-bote-stone-900 mb-2">Confirm Logout</h3>
            <p class="text-bote-stone-600 mb-6">Are you sure you want to log out of <?php echo e($systemName ?? 'BoteBank'); ?>?</p>
            <div class="flex space-x-4">
                <button type="button" onclick="BoteApp.hideLogoutModal()" class="flex-1 px-4 py-2 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-800 rounded-xl font-medium transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="BoteApp.confirmLogout()" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium transition-colors">
                    Logout
                </button>
            </div>
        </div>
    </div>
</div>
