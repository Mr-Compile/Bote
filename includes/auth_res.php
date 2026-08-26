<?php
require_once 'session.php';

// Check if user role is Resident
if ($_SESSION['role'] !== 'resident') {
    header("Location: ../login.php");
    exit();
}
?>