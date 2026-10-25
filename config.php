<?php
/**
 * Library Management System - Database Connection & Configuration (config.php)
 * Bridges PHP (XAMPP) and MySQL/MariaDB using mysqli with prepared statements.
 */

session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'library_db');

// Connect to MySQL
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Database Connection Failed: " . htmlspecialchars($conn->connect_error));
}

$conn->set_charset("utf8mb4");

/**
 * Escapes output for safe HTML rendering (prevents XSS)
 */
function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * Enforces staff authentication on protected pages
 */
function require_login() {
    if (empty($_SESSION['staff_id'])) {
        header("Location: login.php");
        exit;
    }
}
?>
