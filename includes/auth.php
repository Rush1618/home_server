<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

// Redirect to login if not logged in
function require_login()
{
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

// Check credentials and login
function login($username, $password)
{
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Prevent session fixation
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        return true;
    }

    return false;
}

// Logout
function logout()
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// Update password
function update_password($user_id, $new_password)
{
    $pdo = getDBConnection();
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    return $stmt->execute([$hash, $user_id]);
}
