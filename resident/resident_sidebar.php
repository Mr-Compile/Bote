<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

// Legacy sidebar partial; the shared layout now provides the sidebar.
header('Location: resident_dashboard.php');
exit;
