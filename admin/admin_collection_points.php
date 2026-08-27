<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
use App\Http\Controllers\Admin\CollectionPointsController;
$controller = new App\Http\Controllers\Admin\CollectionPointsController($container);
$controller->render();
