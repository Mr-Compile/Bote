<?php
require_once 'session.php';

// Check if user role is Worker
if ($_SESSION['role'] !== 'worker') {
    header("Location: ../login.php");
    exit();
}
?>