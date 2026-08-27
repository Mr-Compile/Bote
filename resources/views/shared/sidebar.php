<?php

declare(strict_types=1);

/** @var string $basePath */
/** @var array<string, mixed> $sidebar */
/** @var array<string, mixed> $currentUser */

$navItems = $sidebar['navItems'] ?? [];
$currentPage = $sidebar['currentPage'] ?? basename($_SERVER['PHP_SELF'] ?? '');
$profile = $sidebar['profile'] ?? ['type' => 'initials', 'value' => 'U'];
$user = $sidebar['user'] ?? $currentUser;
$roleLabel = $sidebar['roleLabel'] ?? 'User';
$systemName = $sidebar['systemName'] ?? 'BoteBank';
$systemLogo = $sidebar['systemLogo'] ?? '';

$colorMap = [
    'bote-green' => ['bg' => 'bg-bote-green-100', 'text' => 'text-bote-green-600'],
    'bote-info' => ['bg' => 'bg-bote-info-100', 'text' => 'text-bote-info-600'],
    'orange' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600'],
    'yellow' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600'],
    'bote-stone' => ['bg' => 'bg-bote-stone-100', 'text' => 'text-bote-stone-600'],
    'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
    'red' => ['bg' => 'bg-red-100', 'text' => 'text-red-600'],
];

function sidebarColorClasses(string $color, array $map): array
{
    return $map[$color] ?? $map['bote-green'];
}

$logoPath = '';
if ($systemLogo !== '' && file_exists("{$basePath}uploads/{$systemLogo}")) {
    $logoPath = $basePath . 'uploads/' . $systemLogo;
}

$profilePhotoPath = '';
if ($profile['type'] === 'photo' && file_exists("{$basePath}uploads/profiles/thumbs/" . ($profile['value'] ?? ''))) {
    $profilePhotoPath = $basePath . 'uploads/profiles/thumbs/' . $profile['value'];
}
?>
<!-- Sidebar -->
<div class="fixed left-0 top-0 h-full w-64 bg-white shadow-xl flex flex-col z-50">
    <div class="bg-gradient-to-br from-bote-green-600 via-bote-green-700 to-bote-green-800 p-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-black bg-opacity-10"></div>
        <div class="relative z-10">
            <div class="flex items-center space-x-3 mb-2">
                <?php if ($logoPath !== ''): ?>
                    <img src="<?php echo e($logoPath); ?>" alt="System Logo" class="w-10 h-10 rounded-lg object-cover bg-white bg-opacity-20 p-1">
                <?php else: ?>
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <i data-lucide="recycle" class="w-6 h-6 text-white"></i>
                    </div>
                <?php endif; ?>
                <h1 class="text-xl font-bold text-white truncate"><?php echo e($systemName); ?></h1>
            </div>
            <p class="text-bote-green-100 text-sm font-medium"><?php echo e($roleLabel); ?> Panel</p>
        </div>
    </div>

    <div class="px-6 py-4 border-b border-bote-stone-100 bg-gradient-to-r from-bote-green-50 to-bote-green-50">
        <div class="flex items-center space-x-3">
            <?php if ($profilePhotoPath !== ''): ?>
                <img src="<?php echo e($profilePhotoPath); ?>" alt="Profile Photo" class="w-12 h-12 rounded-full object-cover ring-2 ring-bote-green-200">
            <?php else: ?>
                <div class="w-12 h-12 bg-gradient-to-br from-bote-green-500 to-bote-green-600 rounded-full flex items-center justify-center ring-2 ring-bote-green-200">
                    <span class="text-white font-bold text-lg"><?php echo e($profile['value'] ?? 'U'); ?></span>
                </div>
            <?php endif; ?>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-bote-stone-800 truncate">
                    <?php echo e(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>
                </p>
                <p class="text-sm text-bote-green-600 font-medium"><?php echo e($roleLabel); ?></p>
                <p class="text-xs text-bote-stone-500 truncate"><?php echo e((string) ($user['email'] ?? '')); ?></p>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
        <?php foreach ($navItems as $item): ?>
            <?php if (!empty($item['divider'])): ?>
                <hr class="my-4 border-bote-stone-200">
                <?php continue; ?>
            <?php endif; ?>

            <?php
            $href = (string) ($item['href'] ?? '#');
            $isActive = $href === $currentPage;
            $classes = $isActive
                ? 'sidebar-active bg-gradient-to-r from-bote-green-500 to-bote-green-600 text-white'
                : 'text-bote-stone-700 hover:bg-bote-green-50 hover:text-bote-green-700';
            $color = sidebarColorClasses((string) ($item['color'] ?? 'bote-green'), $colorMap);
            $iconBg = $isActive ? 'bg-white bg-opacity-20' : $color['bg'];
            $iconColor = $isActive ? 'text-white' : $color['text'];
            $badge = isset($item['badge']) ? (int) $item['badge'] : 0;
            ?>

            <a href="<?php echo e($href); ?>"
               class="group flex items-center space-x-3 p-3 rounded-xl transition-all duration-200 <?php echo e($classes); ?>">
                <div class="w-8 h-8 rounded-lg <?php echo e($iconBg); ?> flex items-center justify-center transition-colors">
                    <i data-lucide="<?php echo e($item['icon'] ?? 'circle'); ?>" class="w-4 h-4 <?php echo e($iconColor); ?>"></i>
                </div>
                <span class="font-medium flex-1"><?php echo e($item['label'] ?? ''); ?></span>
                <?php if ($badge > 0): ?>
                    <span class="ml-auto px-2 py-0.5 text-xs font-semibold rounded-full <?php echo $isActive ? 'bg-white text-bote-green-600' : 'bg-red-100 text-red-600'; ?>">
                        <?php echo e((string) $badge); ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="p-4 border-t border-bote-stone-100">
        <button type="button" onclick="BoteApp.showLogoutModal()" class="w-full flex items-center justify-center space-x-2 p-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 rounded-xl text-white transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
            <i data-lucide="log-out" class="w-4 h-4"></i>
            <span class="font-medium">Logout</span>
        </button>
    </div>
</div>
