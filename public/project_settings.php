<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/projects.php';
require_once __DIR__ . '/../includes/deploy.php';

require_login();

$id = $_GET['id'] ?? 0;
$project = get_project($id);

if (!$project) {
    header('Location: /projects.php');
    exit;
}

$error = '';
$success = '';
$deployOutput = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Update Settings
    if (isset($_POST['action']) && $_POST['action'] === 'update_settings') {
        $repo_url = trim($_POST['repo_url']);
        $build_cmd = trim($_POST['build_cmd']);
        $start_cmd = trim($_POST['start_cmd']);
        $runtime_type = $_POST['runtime_type'];

        if (update_project_settings($id, $repo_url, $build_cmd, $start_cmd, $runtime_type)) {
            $success = "Settings updated successfully.";
            // Refresh project data
            $project = get_project($id);
        }
        else {
            $error = "Failed to update settings.";
        }
    }

    // 2. Add ENV Var
    if (isset($_POST['action']) && $_POST['action'] === 'add_env') {
        $key = trim($_POST['key']);
        $value = trim($_POST['value']);
        if ($key && $value) {
            save_env_var($id, $key, $value);
            $success = "Environment variable added.";
        }
    }

    // 3. Delete ENV Var
    if (isset($_POST['action']) && $_POST['action'] === 'delete_env') {
        $env_id = $_POST['env_id'];
        delete_env_var($env_id);
        $success = "Environment variable deleted.";
    }

    // 4. Deploy
    if (isset($_POST['action']) && $_POST['action'] === 'deploy') {
        try {
            // Force synchronous deployment for Phase 3
            // In production, queue this!
            deploy_project($id);
            $success = "Deployment triggered successfully!";
            // Refresh project data
            $project = get_project($id);
        }
        catch (Exception $e) {
            $error = "Deployment Failed: " . $e->getMessage();
        }
    }

    // 5. Toggle Storage
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_storage') {
        $enabled = isset($_POST['storage_enabled']);
        try {
            toggle_storage_enabled($id, $enabled);
            $success = "Storage settings updated.";
            $project = get_project($id);
        }
        catch (Exception $e) {
            $error = $e->getMessage();
        }
    }

    // 6. Update Rate Limits
    if (isset($_POST['action']) && $_POST['action'] === 'update_rate_limits') {
        $enabled = isset($_POST['rate_limit_enabled']);
        $rpm = (int)$_POST['rate_limit_rpm'];
        $burst = (int)$_POST['rate_limit_burst'];

        try {
            if (update_rate_limits($id, $enabled, $rpm, $burst)) {
                $success = "Traffic protection settings updated.";
                $project = get_project($id);
            }
            else {
                $error = "Failed to update settings.";
            }
        }
        catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$env_vars = get_env_vars($id);

// Get latest logs if available
$logDir = LOGS_DIR . '/' . $project['slug'];
$latestLog = '';
if (is_dir($logDir)) {
    $files = scandir($logDir, SCANDIR_SORT_DESCENDING);
    if (count($files) > 2) { // . and ..
        $latestLog = file_get_contents($logDir . '/' . $files[0]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Settings - <?php echo htmlspecialchars($project['name']); ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; display: flex; height: 100vh; background-color: #f4f4f4; }
        .sidebar { width: 250px; background-color: #333; color: white; display: flex; flex-direction: column; }
        .sidebar-header { padding: 1.5rem; background-color: #222; text-align: center; }
        .nav-links { list-style: none; padding: 0; margin: 0; flex: 1; }
        .nav-links li a { display: block; padding: 1rem 1.5rem; color: #ccc; text-decoration: none; border-bottom: 1px solid #444; }
        .nav-links li a:hover { background-color: #444; color: white; }
        .main-content { flex: 1; padding: 2rem; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #ddd; padding-bottom: 1rem; }
        .card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.9rem; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-success { background-color: #28a745; color: white; }
        .btn-danger { background-color: #dc3545; color: white; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        code { background: #eee; padding: 0.2rem 0.4rem; border-radius: 3px; }
        pre { background: #333; color: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; font-size: 0.9rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 0.75rem; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Admin Platform</h3>
        </div>
        <ul class="nav-links">
            <li><a href="/dashboard.php">Dashboard</a></li>
            <li><a href="/projects.php" style="color: white; background-color: #444;">Projects</a></li>
            <li><a href="#" style="color: #666;">Storage (Soon)</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <div>
                <h1><?php echo htmlspecialchars($project['name']); ?></h1>
                <small>Slug: <?php echo htmlspecialchars($project['slug']); ?></small>
            </div>
            <div>
                <a href="/projects.php" class="btn">Back to Projects</a>
                <a href="/logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php
endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php
endif; ?>

        <!-- Deploy Section -->
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2>Deployment</h2>
                    <p>Status: <strong><?php echo ucfirst($project['deploy_status'] ?? 'Never Deployed'); ?></strong></p>
                    <p>Last Deploy: <?php echo $project['last_deploy'] ?? 'N/A'; ?></p>
                </div>
                <!-- Deploy Form -->
                <form method="POST" onsubmit="return confirm('Start deployment? This will overwrite the current runtime.');">
                    <input type="hidden" name="action" value="deploy">
                    <button type="submit" class="btn btn-success" style="font-size: 1.1rem;">🚀 Deploy Now</button>
                </form>
            </div>
            
            <?php if ($latestLog): ?>
            <h3 style="margin-top: 1.5rem;">Last Deployment Log</h3>
            <pre><?php echo htmlspecialchars($latestLog); ?></pre>
            <?php
endif; ?>
        </div>

        <!-- Configuration Form -->
        <div class="card">
            <h2>Configuration</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_settings">
                
                <div class="form-group">
                    <label for="repo_url">GitHub Repository URL</label>
                    <input type="url" id="repo_url" name="repo_url" value="<?php echo htmlspecialchars($project['repo_url'] ?? ''); ?>" placeholder="https://github.com/username/repo.git">
                </div>
                
                <div class="form-group">
                    <label for="runtime_type">Runtime Type</label>
                    <select id="runtime_type" name="runtime_type">
                        <option value="static" <?php echo($project['runtime_type'] == 'static') ? 'selected' : ''; ?>>Static HTML</option>
                        <option value="php" <?php echo($project['runtime_type'] == 'php') ? 'selected' : ''; ?>>PHP</option>
                        <option value="node" <?php echo($project['runtime_type'] == 'node') ? 'selected' : ''; ?>>Node.js</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="build_cmd">Build Command</label>
                    <input type="text" id="build_cmd" name="build_cmd" value="<?php echo htmlspecialchars($project['build_cmd'] ?? ''); ?>" placeholder="e.g. npm install && npm run build">
                    <small>Run inside the cloned repository.</small>
                </div>

                <div class="form-group">
                    <label for="start_cmd">Start Command (Node.js only)</label>
                    <input type="text" id="start_cmd" name="start_cmd" value="<?php echo htmlspecialchars($project['start_cmd'] ?? ''); ?>" placeholder="e.g. node server.js">
                    <small>Must bind to <code>process.env.PORT</code> or similar.</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>

        <!-- Persistent Storage -->
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h2>Persistent Storage</h2>
                <?php if ($project['storage_enabled']): ?>
                    <a href="/file_manager.php?project_id=<?php echo $project['id']; ?>" class="btn btn-primary">📂 Open File Manager</a>
                <?php
endif; ?>
            </div>
            
            <form method="POST">
                 <input type="hidden" name="action" value="toggle_storage">
                 <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                     <input type="checkbox" id="storage_enabled" name="storage_enabled" style="width: auto;" <?php echo $project['storage_enabled'] ? 'checked' : ''; ?>>
                     <label for="storage_enabled" style="margin: 0;">Enable Persistent Storage</label>
                 </div>
                 <?php if ($project['storage_enabled']): ?>
                     <p><strong>Path:</strong> <code><?php echo htmlspecialchars($project['storage_path']); ?></code></p>
                     <p><small>Available in app at: <code>./storage</code> or via <code>STORAGE_PATH</code> env var.</small></p>
                 <?php
endif; ?>
                 <button type="submit" class="btn btn-primary">Update Storage Settings</button>
            </form>
        </div>
        
        <!-- Traffic Protection (Rate Limiting) -->
        <div class="card">
            <h2>Traffic Protection</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_rate_limits">
                
                <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" id="rate_limit_enabled" name="rate_limit_enabled" style="width: auto;" <?php echo $project['rate_limit_enabled'] ? 'checked' : ''; ?>>
                    <label for="rate_limit_enabled" style="margin: 0;">Enable Rate Limiting (DOS Protection)</label>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <div class="form-group" style="flex: 1;">
                        <label for="rate_limit_rpm">Requests Per Minute (RPM)</label>
                        <input type="number" id="rate_limit_rpm" name="rate_limit_rpm" value="<?php echo htmlspecialchars($project['rate_limit_rpm'] ?? 60); ?>" min="1">
                        <small>Max requests a user can make in a minute.</small>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="rate_limit_burst">Burst Allowance</label>
                        <input type="number" id="rate_limit_burst" name="rate_limit_burst" value="<?php echo htmlspecialchars($project['rate_limit_burst'] ?? 20); ?>" min="1">
                        <small>Extra requests allowed in a sudden spike.</small>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Traffic Settings</button>
            </form>
        </div>

        <!-- ENV Variables -->
        <div class="card">
            <h2>Environment Variables</h2>
            <table>
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Value</th>
                        <th width="50">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($env_vars as $var): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($var['key']); ?></code></td>
                        <td>********</td> <!-- Mask value for security -->
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_env">
                                <input type="hidden" name="env_id" value="<?php echo $var['id']; ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem;">&times;</button>
                            </form>
                        </td>
                    </tr>
                    <?php
endforeach; ?>
                </tbody>
            </table>
            
            <h3 style="margin-top: 1rem; font-size: 1rem;">Add New Variable</h3>
            <form method="POST" style="display: flex; gap: 0.5rem;">
                <input type="hidden" name="action" value="add_env">
                <input type="text" name="key" placeholder="KEY" required style="flex: 1;">
                <input type="text" name="value" placeholder="VALUE" required style="flex: 2;">
                <button type="submit" class="btn btn-primary">Add</button>
            </form>
        </div>
    </div>
</body>
</html>
