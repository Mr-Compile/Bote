<?php

declare(strict_types=1);

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\BaseController;

final class HelpController extends BaseController
{
    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return ['resident'];
    }

    public function render(): void
    {
        $this->view('resident.help', [
            'title' => 'Help & Support',
            'bodyClass' => 'bg-bote-stone-50 min-h-screen',
        ]);
    }
}
