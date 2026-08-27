<?php

declare(strict_types=1);

/** @var string $content */
/** @var string $basePath */
/** @var string|null $pageScript */
/** @var string|null $title */
/** @var string|null $bodyClass */
/** @var array<string, mixed> $sidebar */
/** @var string|null $csrfField */
/** @var array<string, mixed> $currentUser */
/** @var string $role */
/** @var string $systemName */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
</head>
<body class="<?php echo e($bodyClass ?? 'bg-gradient-to-br from-bote-stone-50 to-bote-stone-100 min-h-screen font-sans'); ?>">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="ml-64 min-h-screen">
        <?php echo $content; ?>
    </main>

    <?php include __DIR__ . '/logout-modal.php'; ?>

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
    <script type="module" src="<?php echo $basePath; ?>assets/js/app/theme.js"></script>
    <script type="module" src="<?php echo $basePath; ?>assets/js/app/sidebar.js"></script>
    <?php if (!empty($pageScript)): ?>
        <script type="module" src="<?php echo $basePath . $pageScript; ?>"></script>
    <?php endif; ?>
</body>
</html>
