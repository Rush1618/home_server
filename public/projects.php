<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/projects.php';

require_login();

$error = '';
$success = '';

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');

    try {
        create_project($name, $slug);
        $success = "Project '$name' created successfully!";
    }
    catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['project_id'] ?? 0;
    try {
        if (delete_project($id)) {
            $success = "Project deleted successfully.";
        }
        else {
            $error = "Failed to delete project.";
        }
    }
    catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$projects = get_all_projects();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Projects - Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-main: #f9fafb;
            --sidebar-bg: #111827;
            --card-bg: #ffffff;
            --text-main: #111827;
            --text-muted: #6b7280;
            --border: #e5e7eb;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            display: flex; 
            height: 100vh; 
            background-color: var(--bg-main);
            color: var(--text-main);
        }

        /* Sidebar */
        .sidebar { 
            width: 260px; 
            background-color: var(--sidebar-bg); 
            color: white; 
            display: flex; 
            flex-direction: column;
        }
        .sidebar-header { 
            padding: 2rem 1.5rem; 
            border-bottom: 1px solid #374151;
            text-align: center;
        }
        .sidebar-header h3 { margin: 0; font-weight: 700; }
        
        .nav-links { list-style: none; padding: 1rem 0; margin: 0; flex: 1; }
        .nav-links li a { 
            display: flex; 
            align-items: center;
            padding: 0.875rem 1.5rem; 
            color: #9ca3af; 
            text-decoration: none; 
            transition: all 0.2s;
            font-weight: 500;
        }
        .nav-links li a:hover, .nav-links li a.active { 
            background-color: #1f2937; 
            color: white; 
        }

        /* Main Content */
        .main-content { flex: 1; padding: 2.5rem; overflow-y: auto; }
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 2.5rem; 
        }
        .header h1 { margin: 0; font-size: 1.875rem; font-weight: 700; }
        
        .card { 
            background: var(--card-bg); 
            padding: 2rem; 
            border-radius: 12px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
        }
        .card h2 { margin-top: 0; font-size: 1.25rem; margin-bottom: 1.5rem; font-weight: 600; }

        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid var(--border); }
        th { background-color: #f9fafb; font-weight: 600; font-size: 0.875rem; color: var(--text-muted); }
        
        .btn { 
            display: inline-block;
            padding: 0.5rem 1rem; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            text-decoration: none; 
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-primary { background-color: var(--primary); color: white; }
        .btn-primary:hover { background-color: var(--primary-hover); }
        .btn-danger { background-color: #ef4444; color: white; }
        .btn-danger:hover { background-color: #dc2626; }
        
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 6px; font-size: 0.875rem; }
        .alert-error { background-color: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }
        .alert-success { background-color: #ecfdf5; color: #065f46; border: 1px solid #d1fae5; }
        
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; }
        input { 
            width: 100%; 
            padding: 0.625rem; 
            border: 1px solid var(--border); 
            border-radius: 6px; 
            box-sizing: border-box;
            background: #fff;
        }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Admin Platform</h3>
        </div>
        <ul class="nav-links">
            <li><a href="/dashboard.php">Dashboard</a></li>
            <li><a href="/projects.php" class="active">Projects</a></li>
            <li><a href="/account.php">Account Settings</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Manage Projects</h1>
            <a href="/logout.php" class="btn btn-danger">Logout</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php
endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php
endif; ?>

        <div class="card">
            <h2>Create New Project</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="name">Project Name</label>
                        <input type="text" id="name" name="name" required placeholder="My Awesome Project">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="slug">Subdomain Slug</label>
                        <input type="text" id="slug" name="slug" required placeholder="my-project" pattern="[a-z0-9-]+" title="Lowercase letters, numbers, and hyphens only">
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding: 0.625rem 1.5rem;">Create Project</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Existing Projects</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Subdomain</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($project['name']); ?></strong></td>
                        <td><code><?php echo htmlspecialchars($project['slug']); ?>.localhost</code></td>
                        <td>
                            <span style="display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.75rem; font-weight: 600; color: #059669; padding: 0.125rem 0.5rem; background: #ecfdf5; border-radius: 9999px;">
                                <span style="width: 6px; height: 6px; background: currentColor; border-radius: 50%;"></span>
                                <?php echo htmlspecialchars(ucfirst($project['status'])); ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <a href="/project_settings.php?id=<?php echo $project['id']; ?>" class="btn btn-primary" style="margin-right: 5px;">Settings</a>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this project?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php
endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
