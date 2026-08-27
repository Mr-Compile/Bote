<?php

declare(strict_types=1);

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\BaseController;

final class HelpController extends BaseController
{
    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return ['worker'];
    }

    public function render(): void
    {
        $this->view('worker.help', [
            'title' => 'Worker Support',
            'bodyClass' => 'bg-bote-stone-50 min-h-screen',
        ]);
    }
}
