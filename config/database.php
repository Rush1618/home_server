<?php
// Simple database configuration
// NOTE: In a real production environment, use environment variables.
// For now, per instructions, we use a simple config file.

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root'); // Change as needed for Termux/MariaDB setup
define('DB_PASS', ''); // Change as needed
define('DB_NAME', 'admin_platform');

function getDBConnection()
{
    try {
        // In Termux, if TCP fails, you might need unix_socket attribute instead:
        // $dsn = "mysql:unix_socket=/data/data/com.termux/files/usr/var/run/mysqld.sock;dbname=" . DB_NAME;
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    }
    catch (PDOException $e) {
        throw new Exception("Database Connection Failed: " . $e->getMessage());
    }
}
