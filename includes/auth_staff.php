<?php
require_once 'session.php';

// Check if user role is worker or admin (both can verify drop-offs)
if (!in_array($_SESSION['role'], ['worker', 'admin'])) {
    header("Location: ../login.php");
    exit();
}
?>
