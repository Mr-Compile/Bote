<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/system_functions.php';

ensureSystemSettingsTable($conn);

use App\Http\Controllers\Admin\HelpController;

$controller = new HelpController($container);
$controller->render();
