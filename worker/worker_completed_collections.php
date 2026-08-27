<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
use App\Http\Controllers\Worker\CompletedCollectionsController;
$controller = new App\Http\Controllers\Worker\CompletedCollectionsController($container);
$controller->render();
