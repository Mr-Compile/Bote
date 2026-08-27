<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

(new \App\Http\Controllers\Public\LoginController($container))->render();
