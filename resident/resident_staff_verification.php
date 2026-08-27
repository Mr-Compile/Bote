<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

// Staff verification has been consolidated into the worker verification page.
header('Location: ../worker/worker_verification.php');
exit;
