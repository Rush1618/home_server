<?php
require_once __DIR__ . '/../includes/auth.php';

require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: sans-serif; margin: 0; display: flex; height: 100vh; background-color: #f4f4f4; }
        .sidebar { width: 250px; background-color: #333; color: white; display: flex; flex-direction: column; }
        .sidebar-header { padding: 1.5rem; background-color: #222; text-align: center; }
        .nav-links { list-style: none; padding: 0; margin: 0; flex: 1; }
        .nav-links li a { display: block; padding: 1rem 1.5rem; color: #ccc; text-decoration: none; border-bottom: 1px solid #444; }
        .nav-links li a:hover { background-color: #444; color: white; }
        .nav-links li a.disabled { color: #666; cursor: not-allowed; pointer-events: none; }
        .main-content { flex: 1; padding: 2rem; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #ddd; padding-bottom: 1rem; }
        .logout-btn { padding: 0.5rem 1rem; background-color: #dc3545; color: white; text-decoration: none; border-radius: 4px; font-size: 0.9rem; }
        .logout-btn:hover { background-color: #c82333; }
        .card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
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
            <li><a href="#" class="disabled">Storage (Soon)</a></li>
            <li><a href="#" class="disabled">Settings (Soon)</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <a href="/logout.php" class="logout-btn">Logout</a>
        </div>
        
        <div class="card">
            <h2>Welcome Admin</h2>
            <p>You have successfully logged in.</p>
            <p><strong>System Status:</strong> Operational</p>
            <p>Current Server Time: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
