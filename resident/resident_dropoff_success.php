<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
use App\Http\Controllers\Resident\DropoffSuccessController;
$controller = new App\Http\Controllers\Resident\DropoffSuccessController($container);
$controller->render();
