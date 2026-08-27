<?php

require_once '../config/database.php';


(new \App\Http\Controllers\Admin\DashboardController($container))->render();
