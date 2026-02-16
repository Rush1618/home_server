<?php
require_once __DIR__ . '/projects.php';
require_once __DIR__ . '/nginx_helper.php';

function deploy_project($project_id)
{
    global $pdo; // Assumes $pdo is available or we get it
    if (!isset($pdo))
        $pdo = getDBConnection();

    // 1. Get Project Data
    $project = get_project($project_id);
    if (!$project)
        throw new Exception("Project not found");

    $slug = $project['slug'];
    $repoUrl = $project['repo_url'];
    $buildCmd = $project['build_cmd'];
    $runtimeType = $project['runtime_type'];

    if (empty($repoUrl))
        throw new Exception("Repository URL is required for deployment.");

    // Define Paths
    $baseDir = PROJECTS_ROOT . '/' . $slug;
    $sourceDir = $baseDir . '/source';
    $runtimeDir = $baseDir . '/runtime';
    $logDir = LOGS_DIR . '/' . $slug;

    if (!is_dir($logDir))
        mkdir($logDir, 0755, true);

    $logFile = $logDir . '/deploy_' . time() . '.log';
    $log = function ($msg) use ($logFile) {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
    };

    $log("Starting deployment for $slug...");

    try {
        // Update status to pending
        $pdo->prepare("UPDATE projects SET deploy_status = 'pending' WHERE id = ?")->execute([$project_id]);

        // 2. Clean Previous Build (Source)
        // We do NOT touch runtime yet.
        if (is_dir($sourceDir)) {
            $log("Cleaning source directory...");
            exec("rm -rf " . escapeshellarg($sourceDir));
        }
        mkdir($sourceDir, 0755, true);

        // 3. Clone Repository
        $log("Cloning repository: $repoUrl");
        // NOTE: This assumes public repo or cached credentials
        $cmd = "git clone " . escapeshellarg($repoUrl) . " " . escapeshellarg($sourceDir) . " 2>&1";
        exec($cmd, $output, $returnVar);
        if ($returnVar !== 0) {
            throw new Exception("Git clone failed:\n" . implode("\n", $output));
        }

        // 4. Run Build Command
        if (!empty($buildCmd)) {
            $log("Running build command: $buildCmd");
            // Run inside source directory
            $cmd = "cd " . escapeshellarg($sourceDir) . " && $buildCmd 2>&1";
            exec($cmd, $output, $returnVar);
            if ($returnVar !== 0) {
                throw new Exception("Build command failed:\n" . implode("\n", $output));
            }
        }

        // 5. Prepare Runtime
        // We are about to switch.
        $log("Preparing runtime...");

        // Remove old runtime
        if (is_dir($runtimeDir)) {
            exec("rm -rf " . escapeshellarg($runtimeDir));
        }

        // Move source to runtime
        rename($sourceDir, $runtimeDir);

        // 5.5 Mount Storage (Phase 4)
        if ($project['storage_enabled']) {
            $log("Mounting persistent storage...");
            $persistentPath = $project['storage_path'];

            // Link runtime/storage -> /mnt/storage/platform/storage/slug
            $linkPath = $runtimeDir . '/storage';

            // Check if directory exists at $linkPath (e.g. from git clone), if so, warn or remove?
            // Ideally source code shouldn't have 'storage' folder if it expects it to be mounted.
            // But if it maintains a .gitkeep, we might need to remove the placeholder folder.
            if (is_dir($linkPath)) {
                $log("Warning: 'storage' directory exists in source. Replacing with symlink.");
                exec("rm -rf " . escapeshellarg($linkPath));
            }
            elseif (file_exists($linkPath)) {
                unlink($linkPath);
            }

            if (!symlink($persistentPath, $linkPath)) {
                $log("Warning: Failed to create storage symlink.");
            }
        }

        // 6. Inject ENV Variables
        $log("Injecting environment variables...");
        $vars = get_env_vars($project_id);

        $envContent = "";
        foreach ($vars as $v) {
            $envContent .= "{$v['key']}={$v['value']}\n";
        }

        // Inject STORAGE_PATH
        if ($project['storage_enabled']) {
            $envContent .= "STORAGE_PATH=" . $project['storage_path'] . "\n";
        }

        file_put_contents($runtimeDir . '/.env', $envContent);

        // 7. Update Nginx Config
        $log("Updating Nginx configuration...");
        generate_nginx_config($project);
        reload_nginx();

        // 8. Success
        $pdo->prepare("UPDATE projects SET deploy_status = 'success', last_deploy = NOW() WHERE id = ?")->execute([$project_id]);
        $log("Deployment successful!");
        return true;

    }
    catch (Exception $e) {
        $log("Deployment failed: " . $e->getMessage());
        $pdo->prepare("UPDATE projects SET deploy_status = 'failed' WHERE id = ?")->execute([$project_id]);
        throw $e;
    }
}
