<?php
// Database configuration
$db_config = array(
    'host' => 'localhost',
    'username' => 'u666915587_line_02',
    'password' => '[THZ=wQyX8h>',
    'database' => 'u666915587_line_02'
);

// Create connection
$conn = new mysqli(
    $db_config['host'],
    $db_config['username'],
    $db_config['password'],
    $db_config['database']
);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Site configuration
define('SITE_URL', 'https://sudo-reboot.me/login');
define('SITE_NAME', 'AB Ai');
define('ADMIN_EMAIL', 'admin@sudo-reboot.me');

// Session configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds

// Token configuration
define('MIN_TOKEN_PURCHASE', 100);
define('MAX_TOKEN_PURCHASE', 10000);

// Affiliate configuration
define('DEFAULT_COMMISSION_RATE', 10); // 10%
define('MIN_PAYOUT_AMOUNT', 1000); // 1000 THB

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('Asia/Bangkok');

// Function to get base URL
function getBaseURL() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    return $protocol . '://' . $host . $path;
}

// Function to sanitize input
function sanitize($input) {
    global $conn;
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return $conn->real_escape_string(trim($input));
}

// Function to generate random string
function generateRandomString($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}
?> 