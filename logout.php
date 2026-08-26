<?php
session_start();

// Destroy the session
session_destroy();

// Clear any cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to index page
header("Location: index.php?logged_out=1");
exit();
?>
