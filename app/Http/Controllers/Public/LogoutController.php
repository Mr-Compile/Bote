<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\BaseController;

final class LogoutController extends BaseController
{
    protected string $basePath = '';

    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return [];
    }

    public function render(): never
    {
        $_SESSION = [];

        session_destroy();

        if (isset($_COOKIE[session_name()])) {
            $secure = (env('SESSION_SECURE', 'false')) === 'true';
            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        $this->redirect('index.php?logged_out=1');
    }
}
