<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDBConnection();

// Check if any user exists
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$userCount = $stmt->fetchColumn();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    if ($userCount > 0) {
        $error = "Admin user already exists. Setup cannot be run again.";
    }
    else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = "Username and password are required.";
        }
        elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        }
        else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
            if ($stmt->execute([$username, $hash])) {
                $message = "Admin user <strong>" . htmlspecialchars($username) . "</strong> created successfully!<br><br>Please delete this file (setup.php) after logging in.";
                $userCount = 1;
            }
            else {
                $error = "Failed to create admin user.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Setup</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-main: #f0f2f5;
            --card-bg: #ffffff;
            --text-main: #111827;
            --text-muted: #6b7280;
            --border: #e5e7eb;
        }
        body { font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: var(--bg-main); margin: 0; color: var(--text-main); }
        .setup-box { background: var(--card-bg); padding: 2.5rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 400px; border: 1px solid var(--border); }
        .success { color: #065f46; background: #ecfdf5; padding: 1.25rem; border-radius: 8px; border: 1px solid #d1fae5; margin-bottom: 1.5rem; text-align: center; font-size: 0.875rem; }
        .error { color: #991b1b; background: #fef2f2; padding: 1rem; border-radius: 8px; border: 1px solid #fee2e2; margin-bottom: 1.5rem; text-align: center; font-size: 0.875rem; }
        .btn { display: inline-block; width: 100%; padding: 0.875rem; background-color: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; font-size: 1rem; font-weight: 600; transition: background 0.2s; text-align: center; box-sizing: border-box; }
        .btn:hover { background-color: var(--primary-hover); }
        .info { color: var(--text-muted); margin-bottom: 2rem; line-height: 1.5; font-size: 0.875rem; text-align: center; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; }
        input { width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; box-sizing: border-box; font-size: 1rem; transition: all 0.2s; }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        h2 { margin: 0 0 1rem; text-align: center; font-size: 1.5rem; font-weight: 700; }
    </style>
</head>
<body>
    <div class="setup-box">
        <h2>System Setup</h2>
        
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
            <a href="/login.php" class="btn">Go to Login</a>
        <?php
elseif ($error): ?>
            <div class="error"><?php echo $error; ?></div>
            <?php if ($userCount > 0): ?>
                <a href="/login.php" class="btn">Go to Login</a>
            <?php
    endif; ?>
        <?php
endif; ?>

        <?php if (!$message && $userCount == 0): ?>
            <p class="info">Initialize your administrator account with a custom username and password.</p>
            <form method="POST">
                <input type="hidden" name="setup" value="1">
                <div class="form-group">
                    <label for="username">Admin Username</label>
                    <input type="text" id="username" name="username" required placeholder="e.g. rushabh" autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="6" placeholder="At least 6 characters">
                </div>
                <button type="submit" class="btn">Create Account</button>
            </form>
        <?php
elseif (!$message && $userCount > 0): ?>
            <div class="error">Admin user already exists.</div>
            <a href="/login.php" class="btn">Go to Login</a>
        <?php
endif; ?>
    </div>
</body>
</html>
