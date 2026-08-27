<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\BaseController;

final class LoginController extends BaseController
{
    protected string $basePath = '';

    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return [];
    }

    public function render(): void
    {
        $loginImage = $this->setting('login_image', '');

        $this->view->render('public.login', [
            'title' => 'System Login',
            'login_image' => $loginImage,
        ], null);
    }
}
