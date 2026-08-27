<?php

require_once '../config/database.php';


(new \App\Http\Controllers\Worker\DashboardController($container))->render();
