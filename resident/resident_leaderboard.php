<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
use App\Http\Controllers\Resident\LeaderboardController;
$controller = new App\Http\Controllers\Resident\LeaderboardController($container);
$controller->render();
