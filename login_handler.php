<?php
session_start();
require_once 'config/database.php';
require_once 'includes/system_functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $error = "";

    try {
        // Prepare SQL statement
        $sql = "SELECT user_id, first_name, last_name, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                

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
            } else {
                $error = "Invalid password";
                
                
            }
        } else {
            $error = "User not found";
        }

        $stmt->close();

        if (!empty($error)) {
            header("Location: login.php?error=" . urlencode($error));
            exit();
        }

    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        header("Location: login.php?error=" . urlencode("An error occurred during login"));
        exit();
    }
}
?>
