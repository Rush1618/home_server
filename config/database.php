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
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    }
    catch (PDOException $e) {
        // If it's just "Unknown database", we can handle it in index.php
        throw $e;
    }
}

/**
 * Connects to MySQL without selecting a database
 */
function getRawConnection()
{
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

/**
 * Creates the database and runs schema.sql
 */
function initializeDatabase()
{
    $pdo = getRawConnection();

    // Run schema.sql
    $schemaPath = __DIR__ . '/../sql/schema.sql';
    if (file_exists($schemaPath)) {
        $sql = file_get_contents($schemaPath);

        // Simple split by semicolon. 
        // Note: This won't work if semicolons are inside strings, but for our schema.sql it's fine.
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $stmt) {
            if (!empty($stmt)) {
                $pdo->exec($stmt);
            }
        }
    }
}
