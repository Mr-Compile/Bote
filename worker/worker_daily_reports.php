<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/system_functions.php';
use App\Http\Controllers\Worker\DailyReportsController;
$controller = new App\Http\Controllers\Worker\DailyReportsController($container);
$controller->render();