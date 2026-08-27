<?php
/**
 * Shared head partial.
 *
 * Expected variables: $title, $systemName, $basePath
 */
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo e(($title ?? 'Dashboard') . ' | ' . ($systemName ?? 'BoteBank')); ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/app.css">
