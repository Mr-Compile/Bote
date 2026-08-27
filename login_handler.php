<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/system_functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        \App\Core\Csrf::validate($_POST['csrf_token'] ?? null);

        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $error = "";
        // Prepare SQL statement
        $sql = "SELECT user_id, first_name, last_name, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $user = $result->fetch_assoc();

        // Verify password
        if ($user !== null && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Prevent session fixation by rotating the session ID
            session_regenerate_id(true);

            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header("Location: admin/admin_dashboard.php");
                    break;
                case 'worker':
                    header("Location: worker/worker_dashboard.php");
                    break;
                case 'resident':
                    header("Location: resident/resident_dashboard.php");
                    break;
                default:
                    throw new Exception("Invalid user role");
            }
            exit();
        }

        $stmt->close();

        // Generic error to prevent email enumeration
        header("Location: login.php?error=" . urlencode("Invalid email or password"));
        exit();

    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        header("Location: login.php?error=" . urlencode("An error occurred during login"));
        exit();
    }
}
