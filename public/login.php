<?php
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (login($username, $password)) {
        header('Location: /dashboard.php');
        exit;
    }
    else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Platform</title>
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

        body { 
            font-family: 'Inter', sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            background-color: var(--bg-main); 
            margin: 0; 
            color: var(--text-main);
        }

        .login-box { 
            background: var(--card-bg); 
            padding: 2.5rem; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            width: 100%;
            max-width: 380px; 
            border: 1px solid var(--border);
        }

        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-header h2 { margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--text-main); }
        .login-header p { margin: 0.5rem 0 0; font-size: 0.875rem; color: var(--text-muted); }

        .form-group { margin-bottom: 1.25rem; }
        label { display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 500; font-size: 0.875rem; }
        input { 
            width: 100%; 
            padding: 0.75rem; 
            border: 1px solid var(--border); 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-size: 1rem;
            transition: all 0.2s;
        }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        
        button { 
            width: 100%; 
            padding: 0.875rem; 
            background-color: var(--primary); 
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 1rem; 
            font-weight: 600;
            transition: background 0.2s;
            margin-top: 1rem;
        }
        button:hover { background-color: var(--primary-hover); }
        
        .error { 
            background-color: #fef2f2; 
            color: #991b1b; 
            padding: 0.75rem; 
            border-radius: 8px; 
            border: 1px solid #fee2e2;
            text-align: center; 
            margin-bottom: 1.5rem; 
            font-size: 0.875rem; 
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <h2>Admin Login</h2>
            <p>Sign in to manage your projects</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php
endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus placeholder="admin">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit">Sign In</button>
        </form>
    </div>
</body>
</html>
