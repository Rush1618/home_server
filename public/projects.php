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
    <title>Manage Projects</title>
    <style>
        body { font-family: sans-serif; margin: 0; display: flex; height: 100vh; background-color: #f4f4f4; }
        .sidebar { width: 250px; background-color: #333; color: white; display: flex; flex-direction: column; }
        .sidebar-header { padding: 1.5rem; background-color: #222; text-align: center; }
        .nav-links { list-style: none; padding: 0; margin: 0; flex: 1; }
        .nav-links li a { display: block; padding: 1rem 1.5rem; color: #ccc; text-decoration: none; border-bottom: 1px solid #444; }
        .nav-links li a:hover, .nav-links li a.active { background-color: #444; color: white; }
        .main-content { flex: 1; padding: 2rem; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #ddd; padding-bottom: 1rem; }
        .card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { text-align: left; padding: 0.75rem; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.9rem; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-danger { background-color: #dc3545; color: white; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; }
        input { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
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
            <li><a href="#" style="color: #666;">Storage (Soon)</a></li>
            <li><a href="#" style="color: #666;">Settings (Soon)</a></li>
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
                <div class="form-group">
                    <label for="name">Project Name</label>
                    <input type="text" id="name" name="name" required placeholder="My Awesome Project">
                </div>
                <div class="form-group">
                    <label for="slug">Subdomain Slug</label>
                    <input type="text" id="slug" name="slug" required placeholder="my-project" pattern="[a-z0-9-]+" title="Lowercase letters, numbers, and hyphens only">
                </div>
                <button type="submit" class="btn btn-primary">Create Project</button>
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($project['name']); ?></td>
                        <td><?php echo htmlspecialchars($project['slug']); ?>.yourdomain.com</td>
                        <td><?php echo htmlspecialchars($project['status']); ?></td>
                        <td>
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
