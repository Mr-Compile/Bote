<?php

declare(strict_types=1);

namespace App\View;

final class Navigation
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function forRole(string $role): array
    {
        /** @var array<string, list<array<string, mixed>>> $navigation */
        $navigation = require __DIR__ . '/../../config/navigation.php';

        return $navigation[$role] ?? [];
    }
}
