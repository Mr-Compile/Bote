<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
use App\Http\Controllers\Admin\SettingsController;
$controller = new App\Http\Controllers\Admin\SettingsController($container);
$controller->render();
