<?php
require_once __DIR__ . '/../includes/auth.php';

try {
    $pdo = getDBConnection();

    // Check if any user exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();

    if ($userCount == 0) {
        // No users found, redirect to setup
        header('Location: /setup.php');
        exit;
    }

    // Standard routing
    if (is_logged_in()) {
        header('Location: /dashboard.php');
    }
    else {
        header('Location: /login.php');
    }
    exit;

}
catch (Exception $e) {
    // Database connection failed or other issue
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>System Error</title>
        <style>
            body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f8f9fa; margin: 0; }
            .error-box { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 500px; width: 100%; border-top: 4px solid #dc3545; }
            h1 { color: #dc3545; margin-top: 0; }
            .details { background: #f1f3f5; padding: 1rem; border-radius: 4px; font-family: monospace; font-size: 0.9rem; overflow-x: auto; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h1>Database Error</h1>
            <p>We encountered a problem connecting to the database. Please check your configuration in <code>config/database.php</code>.</p>
            <div class="details"><?php echo htmlspecialchars($e->getMessage()); ?></div>
            <p><small>If you are in Termux, ensure <code>mariadb</code> is running.</small></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
