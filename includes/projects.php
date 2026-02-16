<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/paths.php';

function get_all_projects()
{
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

function validate_slug($slug)
{
    if (empty($slug))
        return false;
    // URL-safe: lowercase, numbers, hyphens only
    return preg_match('/^[a-z0-9-]+$/', $slug);
}

function project_exists($slug)
{
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM projects WHERE slug = ?");
    $stmt->execute([$slug]);
    return (bool)$stmt->fetch();
}

function create_project($name, $slug)
{
    if (!validate_slug($slug)) {
        throw new Exception("Invalid slug. Use only lowercase letters, numbers, and hyphens.");
    }
    if (project_exists($slug)) {
        throw new Exception("Project with this slug already exists.");
    }

    $pdo = getDBConnection();

    // 1. Insert into DB
    $stmt = $pdo->prepare("INSERT INTO projects (name, slug) VALUES (?, ?)");
    if (!$stmt->execute([$name, $slug])) {
        throw new Exception("Database insert failed.");
    }

    // 2. Create Project Folder
    if (!is_dir(PROJECTS_ROOT)) {
        if (!mkdir(PROJECTS_ROOT, 0755, true)) {
            throw new Exception("Failed to create PROJECTS_ROOT: " . PROJECTS_ROOT);
        }
    }

    $projectPath = PROJECTS_ROOT . '/' . $slug;
    if (!file_exists($projectPath)) {
        if (!mkdir($projectPath, 0755, true)) {
            throw new Exception("Failed to create project directory: " . $projectPath);
        }
        // Create a default index.html so it's not empty
        file_put_contents($projectPath . '/index.html', "<h1>$name</h1><p>Project is ready.</p>");
    }

    // 3. Generate Nginx Config
    $nginxConfig = "server {
    listen 8080;
    listen [::]:8080;
    server_name {$slug}.localhost; 
    # Note: In production, server_name should be {$slug}.yourdomain.com

    root " . PROJECTS_ROOT . "/{$slug};
    index index.html index.htm index.php;

    location / {
        try_files \$uri \$uri/ =404;
    }
}";

    $configPath = NGINX_CONFIG_DIR . '/' . $slug . '.conf';
    if (file_put_contents($configPath, $nginxConfig) === false) {
        throw new Exception("Failed to write Nginx config.");
    }

    // 4. Reload Nginx
    reload_nginx();

    return true;
}

function delete_project($id)
{
    $pdo = getDBConnection();

    // Get project details first
    $stmt = $pdo->prepare("SELECT slug FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();

    if (!$project)
        return false;

    $slug = $project['slug'];

    // 1. Remove from DB
    $delStmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    if (!$delStmt->execute([$id])) {
        throw new Exception("Failed to delete from database.");
    }

    // 2. Disable Nginx Config (rename or remove)
    $configPath = NGINX_CONFIG_DIR . '/' . $slug . '.conf';
    if (file_exists($configPath)) {
        // Rename to .disabled to be safe, or delete if preferred.
        // User asked to "Disable Nginx config (do not hard-delete yet)" implies keeping it but making it inactive? 
        // Or "Disable" logic usually means deleting the symlink in sites-enabled. 
        // Here we are likely using an `include /mnt/storage/platform/nginx/*.conf;` directive in the main nginx.conf?
        // If so, renaming extension prevents inclusion.
        rename($configPath, $configPath . '.disabled');
    }

    // 3. Reload Nginx
    reload_nginx();

    // SAFETY: Do NOT delete the project folder directory.
    return true;
}

function get_project($id)
{
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function update_project_settings($id, $repo_url, $build_cmd, $start_cmd, $runtime_type)
{
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE projects SET repo_url = ?, build_cmd = ?, start_cmd = ?, runtime_type = ? WHERE id = ?");
    return $stmt->execute([$repo_url, $build_cmd, $start_cmd, $runtime_type, $id]);
}

function get_env_vars($project_id)
{
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM env_vars WHERE project_id = ?");
    $stmt->execute([$project_id]);
    return $stmt->fetchAll();
}

function save_env_var($project_id, $key, $value)
{
    $pdo = getDBConnection();
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM env_vars WHERE project_id = ? AND `key` = ?");
    $stmt->execute([$project_id, $key]);
    if ($stmt->fetch()) {
        $upd = $pdo->prepare("UPDATE env_vars SET `value` = ? WHERE project_id = ? AND `key` = ?");
        return $upd->execute([$value, $project_id, $key]);
    }
    else {
        $ins = $pdo->prepare("INSERT INTO env_vars (project_id, `key`, `value`) VALUES (?, ?, ?)");
        return $ins->execute([$project_id, $key, $value]);
    }
}

function delete_env_var($id)
{
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM env_vars WHERE id = ?");
    return $stmt->execute([$id]);
}

function toggle_storage_enabled($project_id, $enabled)
{
    global $pdo; // Or getDBConnection() if global not used in context
    if (!isset($pdo))
        $pdo = getDBConnection();

    // Get slug
    $proj = get_project($project_id);
    if (!$proj)
        return false;
    $slug = $proj['slug'];

    $storagePath = PROJECT_STORAGE_ROOT . '/' . $slug;
    $dbPath = $storagePath;

    if ($enabled) {
        // Create directory
        if (!is_dir($storagePath)) {
            if (!mkdir($storagePath, 0755, true)) {
                throw new Exception("Failed to create storage directory.");
            }
            // Ensure permissions (if running as www-data, usually automatic, but good to ensure)
            chmod($storagePath, 0755);
        }
        $stmt = $pdo->prepare("UPDATE projects SET storage_enabled = 1, storage_path = ? WHERE id = ?");
        return $stmt->execute([$dbPath, $project_id]);
    }
    else {
        // Disable: Update DB only. Do NOT delete folder.
        $stmt = $pdo->prepare("UPDATE projects SET storage_enabled = 0 WHERE id = ?");
        return $stmt->execute([$project_id]);
    }
}

function update_rate_limits($project_id, $enabled, $rpm, $burst)
{
    global $pdo;
    if (!isset($pdo))
        $pdo = getDBConnection();

    $stmt = $pdo->prepare("UPDATE projects SET rate_limit_enabled = ?, rate_limit_rpm = ?, rate_limit_burst = ? WHERE id = ?");
    $result = $stmt->execute([$enabled ? 1 : 0, $rpm, $burst, $project_id]);

    if ($result) {
        $project = get_project($project_id);
        require_once __DIR__ . '/nginx_helper.php';
        generate_nginx_config($project);
    }
    return $result;
}

function reload_nginx()
{
    // This assumes the user has set up sudoers rule for this specific command:
    // www-data ALL=(root) NOPASSWD: /usr/sbin/service nginx reload
    // On Termux/Ubuntu, it might be `service nginx reload` or `systemctl reload nginx`.
    $output = [];
    $return_var = 0;
    exec('sudo service nginx reload', $output, $return_var);

    if ($return_var !== 0) {
    // Log error?
    // error_log("Nginx reload failed: " . implode("\n", $output));
    }
}

function get_project_stats()
{
    $pdo = getDBConnection();

    $stats = [
        'total' => 0,
        'active' => 0,
        'disabled' => 0,
        'latest' => []
    ];

    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM projects GROUP BY status");
    foreach ($stmt->fetchAll() as $row) {
        if ($row['status'] === 'active')
            $stats['active'] = (int)$row['count'];
        if ($row['status'] === 'disabled')
            $stats['disabled'] = (int)$row['count'];
    }
    $stats['total'] = $stats['active'] + $stats['disabled'];

    $stmt = $pdo->query("SELECT name, slug, created_at FROM projects ORDER BY created_at DESC LIMIT 5");
    $stats['latest'] = $stmt->fetchAll();

    return $stats;
}
