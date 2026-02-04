<?php
// Simple database configuration
// NOTE: In a real production environment, use environment variables.
// For now, per instructions, we use a simple config file.

define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // Change as needed for Termux/MariaDB setup
define('DB_PASS', '');         // Change as needed
define('DB_NAME', 'admin_platform');

function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        // In production, log this error instead of showing it
        die("Database Connection Failed: " . $e->getMessage());
    }
}
