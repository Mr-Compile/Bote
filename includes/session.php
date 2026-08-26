<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If no user logged in → redirect
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
