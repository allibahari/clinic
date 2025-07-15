<?php

/**
 * File: config.php
 * Description: Secure configuration, database connection, and utility functions.
 */

// --- 1. Configuration Settings ---
// It's best practice to store credentials outside the web-accessible files,
// for example, using environment variables.
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '1');
define('DB_NAME', 'dashboard_db');

// --- 2. Secure Session Initialization ---
// These settings make session hijacking more difficult.
// ini_set('session.use_only_cookies', 1);
// ini_set('session.use_strict_mode', 1);
// ini_set('session.cookie_httponly', 1);
// ini_set('session.cookie_samesite', 'Strict');

// Use secure cookies if your site is on HTTPS
// ini_set('session.cookie_secure', 1);

// Start the session only if it's not already active.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// --- 3. Database Connection ---
// This uses exceptions for cleaner error handling.
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // For developers: Log the detailed error to a file.
    error_log("Database Connection Error: " . $e->getMessage());
    // For users: Show a generic, non-revealing error message.
    die("A temporary error occurred with the database. Please try again later.");
}


// --- 4. Utility Functions ---

/**
 * Securely logs a user's login time and ID into the database.
 *
 * @param mysqli $conn The active database connection object.
 * @param int $user_id The ID of the user who logged in.
 * @return bool True on success, false on failure.
 */
if (!function_exists('log_login')) {
    function log_login(mysqli $conn, int $user_id): bool 
    {
        // The SQL statement to insert a new log entry.
        $sql = "INSERT INTO login_logs (user_id) VALUES (?)";

        try {
            $stmt = $conn->prepare($sql);
            // 'i' specifies that the variable is of type integer.
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            return true;
        } catch (mysqli_sql_exception $e) {
            // Log the specific error for debugging purposes.
            error_log("Failed to log login for user_id {$user_id}: " . $e->getMessage());
            return false;
        }
    }
}
?>