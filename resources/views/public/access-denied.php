<?php
/**
 * Access denied view for RBAC rejections.
 *
 * Expected variables: $message
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="assets/js/app/theme.js"></script>
</head>
<body class="bg-bote-stone-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-bote-stone-900 mb-2">Access Denied</h1>
        <p class="text-bote-stone-600 mb-6"><?php echo htmlspecialchars($message ?? 'You do not have permission to access this page.'); ?></p>
        <a href="index.php" class="inline-block bg-bote-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-bote-green-700 transition-colors">
            Back to Home
        </a>
    </div>
</body>
</html>
