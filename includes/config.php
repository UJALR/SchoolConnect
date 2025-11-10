<?php
session_start();

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration - 
define('DB_HOST', 'localhost:3307');
define('DB_USER', 'root');
define('DB_PASS', '');  
define('DB_NAME', 'CST8257');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Sanitize input function
function sanitizeInput($data, $conn) {
    if (is_array($data)) {
        return array_map(function($item) use ($conn) {
            return $conn->real_escape_string(htmlspecialchars(trim($item)));
        }, $data);
    }
    return $conn->real_escape_string(htmlspecialchars(trim($data)));
}
?>