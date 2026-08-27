<?php

require_once '../config/database.php';
require_once '../includes/system_functions.php';

ensureSystemSettingsTable($conn);

(new \App\Http\Controllers\Admin\DashboardController($container))->render();
