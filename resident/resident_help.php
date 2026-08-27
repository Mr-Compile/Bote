<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

use App\Http\Controllers\Resident\HelpController;

$controller = new HelpController($container);
$controller->render();
