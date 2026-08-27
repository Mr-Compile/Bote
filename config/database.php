<?php
// Database configuration
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/system_functions.php';
require_once __DIR__ . '/env_loader.php';

$requiredEnv = [
    'DB_HOST' => env('DB_HOST'),
    'DB_USER' => env('DB_USER'),
    'DB_PASS' => env('DB_PASS'),
    'DB_NAME' => env('DB_NAME'),
    'DB_CHARSET' => env('DB_CHARSET'),
];
foreach ($requiredEnv as $key => $value) {
    if ($value === null) {
        error_log("Missing required environment variable: {$key}");
        http_response_code(503);
        die('Service misconfigured. Please check the environment configuration.');
    }
}

define('DB_HOST', $requiredEnv['DB_HOST']);
define('DB_USER', $requiredEnv['DB_USER']);
define('DB_PASS', $requiredEnv['DB_PASS']);
define('DB_NAME', $requiredEnv['DB_NAME']);
define('DB_CHARSET', $requiredEnv['DB_CHARSET']);

// Connection options for better security and performance
$options = [
    MYSQLI_OPT_CONNECT_TIMEOUT => 30,
    MYSQLI_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'",
    MYSQLI_OPT_INT_AND_FLOAT_NATIVE => true,
];

// Initialize connection with error handling
$conn = null;
try {
    // Initialize mysqli with options
    $conn = mysqli_init();
    
    if (!$conn) {
        throw new Exception("mysqli_init failed");
    }

    // Set connection options
    foreach ($options as $option => $value) {
        mysqli_options($conn, $option, $value);
    }

    // Establish connection
    if (!mysqli_real_connect($conn, DB_HOST, DB_USER, DB_PASS, DB_NAME)) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Set charset for proper unicode handling
    if (!mysqli_set_charset($conn, DB_CHARSET)) {
        throw new Exception("Error setting charset: " . mysqli_error($conn));
    }

    // Enable error reporting for debugging
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

} catch (Exception $e) {
    // Log error for debugging
    error_log("Database connection error: " . $e->getMessage());
    
    // Display user-friendly error message
    http_response_code(503);
    die("
    <div style='font-family: Arial, sans-serif; text-align: center; padding: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; min-height: 100vh; display: flex; align-items: center; justify-content: center;'>
        <div style='background: white; color: #333; padding: 40px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); max-width: 500px;'>
            <h2 style='margin: 0 0 20px 0; color: #e74c3c;'>⚠️ Database Connection Error</h2>
            <p style='margin: 0 0 20px 0; color: #666;'>We're experiencing technical difficulties. Please try again later.</p>
            <small style='color: #999;'>If this issue persists, please contact the system administrator.</small>
        </div>
    </div>
    ");
}

/**
 * Enhanced function to safely prepare statements with better error handling
 * @param mysqli $conn Database connection
 * @param string $sql SQL statement
 * @param array $log_context Additional context for logging
 * @return mysqli_stmt|false
 */
function prepare_statement($conn, $sql, $log_context = []) {
    try {
        if (!$conn) {
            throw new Exception("Database connection is null");
        }

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Error preparing statement: " . $conn->error . " | SQL: " . $sql);
        }

        return $stmt;
    } catch (Exception $e) {
        // Enhanced logging with context
        $error_message = $e->getMessage();
        if (!empty($log_context)) {
            $error_message .= " | Context: " . json_encode($log_context);
        }
        
        error_log("Database prepare statement error: " . $error_message);
        return false;
    }
}

/**
 * Enhanced function to execute queries with better error handling
 * @param mysqli $conn Database connection
 * @param string $sql SQL statement
 * @param array $params Parameters for prepared statement
 * @param string $types Parameter types for bind_param
 * @return mysqli_result|bool
 */
function execute_query($conn, $sql, $params = [], $types = '') {
    try {
        $stmt = prepare_statement($conn, $sql);
        if (!$stmt) {
            return false;
        }

        if (!empty($params) && !empty($types)) {
            if (!$stmt->bind_param($types, ...$params)) {
                throw new Exception("Error binding parameters: " . $stmt->error);
            }
        }

        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Database execution error: " . $e->getMessage());
        return false;
    }
}

/**
 * Function to check database connection health
 * @param mysqli $conn Database connection
 * @return bool
 */
function check_db_health($conn) {
    try {
        if (!$conn || $conn->ping() === false) {
            return false;
        }
        
        // Test with a simple query
        $result = $conn->query("SELECT 1");
        return $result !== false;
    } catch (Exception $e) {
        error_log("Database health check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Function to safely close database connection
 * @param mysqli $conn Database connection
 */
function close_db_connection($conn) {
    if ($conn && !$conn->connect_error) {
        $conn->close();
    }
}

// Register shutdown function to close connection
register_shutdown_function(function() use ($conn) {
    close_db_connection($conn);
});

// Wire the new service container.
$container = new \App\Core\Container(new \App\Core\Database($conn));
?>
