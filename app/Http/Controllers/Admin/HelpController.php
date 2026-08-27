<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;

final class HelpController extends BaseController
{
    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return ['admin'];
    }

    public function render(): void
    {
        $this->view('admin.help', [
            'title' => 'Help & Support',
            'bodyClass' => 'bg-gradient-to-br from-bote-stone-50 to-bote-stone-100 min-h-screen',
        ]);
    }
}
