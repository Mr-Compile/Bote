<?php

require_once '../config/database.php';


(new \App\Http\Controllers\Resident\DashboardController($container))->render();
