<?php
session_start();

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'time_tracker');
define('APP_ENV', getenv('APP_ENV') ?: 'production');

/**
 * Get database connection with error handling
 */
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(['error' => 'Database connection failed', 'details' => APP_ENV === 'development' ? $conn->connect_error : 'Internal server error']));
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * Validate and sanitize user input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate color format (hex code)
 */
function isValidColor($color) {
    return preg_match('/^#[a-f0-9]{6}$/i', $color) === 1;
}

/**
 * Send JSON response with proper headers
 */
function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Log actions for audit trail
 */
function logAction($action, $details = []) {
    if (APP_ENV === 'development') {
        error_log(date('Y-m-d H:i:s') . ' | ' . $action . ' | ' . json_encode($details));
    }
}
?>