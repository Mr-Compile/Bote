<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
use App\Http\Controllers\Admin\SmsSettingsController;
$controller = new App\Http\Controllers\Admin\SmsSettingsController($container);
$controller->render();
