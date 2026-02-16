<?php
require_once __DIR__ . '/../includes/auth.php';

require_login();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Verify current password
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current_password, $user['password_hash'])) {
        $error = "Current password is incorrect.";
    }
    elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    }
    elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    }
    else {
        if (update_password($_SESSION['user_id'], $new_password)) {
            $success = "Password updated successfully!";
        }
        else {
            $error = "Failed to update password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <style>
        body { font-family: sans-serif; margin: 0; display: flex; height: 100vh; background-color: #f4f4f4; }
        .sidebar { width: 250px; background-color: #333; color: white; display: flex; flex-direction: column; }
        .sidebar-header { padding: 1.5rem; background-color: #222; text-align: center; }
        .nav-links { list-style: none; padding: 0; margin: 0; flex: 1; }
        .nav-links li a { display: block; padding: 1rem 1.5rem; color: #ccc; text-decoration: none; border-bottom: 1px solid #444; }
        .nav-links li a:hover { background-color: #444; color: white; }
        .main-content { flex: 1; padding: 2rem; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #ddd; padding-bottom: 1rem; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 500px; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: #555; }
        input { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; color: white; background-color: #007bff; font-size: 1rem; }
        .btn:hover { background-color: #0056b3; }
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
        .alert-success { background-color: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Admin Platform</h3>
        </div>
        <ul class="nav-links">
            <li><a href="/dashboard.php">Dashboard</a></li>
            <li><a href="/projects.php">Projects</a></li>
            <li><a href="/account.php">Account Settings</a></li>
            <li><a href="/logout.php">Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Account Settings</h1>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </div>
        
        <div class="card">
            <h2>Change Password</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php
endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php
endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <hr style="margin: 2rem 0; border: 0; border-top: 1px solid #eee;">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                <button type="submit" class="btn">Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>
