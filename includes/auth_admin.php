<?php
require_once 'session.php';

// Check if user role is Admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
?>