<?php
require_once 'includes/session.php';

// Clear session data
$_SESSION = [];

// Destroy the session
session_destroy();

// Clear any cookies
if (isset($_COOKIE[session_name()])) {
    $secure = (env('SESSION_SECURE', 'false')) === 'true';
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// Redirect to index page
header("Location: index.php?logged_out=1");
exit();
