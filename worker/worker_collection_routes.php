<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
use App\Http\Controllers\Worker\CollectionRoutesController;
$controller = new App\Http\Controllers\Worker\CollectionRoutesController($container);
$controller->render();
