<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-bote-stone-50 to-bote-stone-100 px-4">
    <div class="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i data-lucide="shield-alert" class="w-10 h-10 text-red-600"></i>
        </div>
        <h1 class="text-2xl font-bold text-bote-stone-900 mb-2">Access Denied</h1>
        <p class="text-bote-stone-600 mb-6">You do not have permission to view this page. Please sign in with an authorized account.</p>
        <a href="<?php echo $basePath; ?>login.php" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-bote-green-500 to-bote-green-600 text-white rounded-xl font-semibold hover:from-bote-green-600 hover:to-bote-green-700 transition-all">
            <i data-lucide="log-in" class="w-5 h-5 mr-2"></i>
            Go to Login
        </a>
    </div>
</div>
