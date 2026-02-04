<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/projects.php';
require_once __DIR__ . '/../includes/file_manager_logic.php';

require_login();

$project_id = $_GET['project_id'] ?? 0;
$subdir = $_GET['path'] ?? '';

$project = get_project($project_id);
if (!$project || !$project['storage_enabled']) {
    die("Storage not enabled for this project.");
}

$error = '';
$success = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'upload') {
        if (!empty($_FILES['files'])) {
            if (handle_file_upload($project_id, $subdir, $_FILES['files'])) {
                $success = "Files uploaded successfully.";
            }
            else {
                $error = "Upload failed.";
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $item_path = $_POST['item_path'];
        // Combine subdir + item
        $full_rel_path = ($subdir ? $subdir . '/' : '') . $item_path;
        if (delete_storage_item($project_id, $full_rel_path)) {
            $success = "Item deleted.";
        }
        else {
            $error = "Delete failed.";
        }
    }
}

try {
    $files = list_storage_files($project_id, $subdir);
}
catch (Exception $e) {
    $error = $e->getMessage();
    $files = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager - <?php echo htmlspecialchars($project['name']); ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; display: flex; height: 100vh; background-color: #f4f4f4; }
        .main-content { flex: 1; padding: 2rem; overflow-y: auto; max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #ddd; padding-bottom: 1rem; }
        .card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.9rem; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-danger { background-color: #dc3545; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
        .alert-success { background-color: #d4edda; color: #155724; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 0.75rem; border-bottom: 1px solid #ddd; }
        .folder-icon { color: #ffc107; margin-right: 5px; }
        .file-icon { color: #6c757d; margin-right: 5px; }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="header">
            <div>
                <h1>File Manager</h1>
                <h3><?php echo htmlspecialchars($project['name']); ?></h3>
            </div>
            <div>
                <a href="/project_settings.php?id=<?php echo $project['id']; ?>" class="btn btn-secondary">Back to Settings</a>
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

        <!-- Breadcrumbs (Simple) -->
        <div style="margin-bottom: 1rem;">
            <strong>Path:</strong> /<?php echo htmlspecialchars($subdir); ?>
            <?php if ($subdir): ?>
                 <a href="?project_id=<?php echo $project['id']; ?>&path=<?php echo urlencode(dirname($subdir) == '.' ? '' : dirname($subdir)); ?>">⬆ Up</a>
            <?php
endif; ?>
        </div>

        <div class="card">
            <form method="POST" enctype="multipart/form-data" style="display:flex; gap: 1rem; align-items: center;">
                <input type="hidden" name="action" value="upload">
                <input type="file" name="files[]" multiple required>
                <button type="submit" class="btn btn-primary">Upload Files</button>
            </form>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Size</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($files)): ?>
                        <tr><td colspan="4">Empty directory.</td></tr>
                    <?php
else: ?>
                        <?php foreach ($files as $file): ?>
                        <tr>
                            <td>
                                <?php if ($file['is_dir']): ?>
                                    <span class="folder-icon">📁</span>
                                    <a href="?project_id=<?php echo $project_id; ?>&path=<?php echo urlencode($file['path']); ?>">
                                        <?php echo htmlspecialchars($file['name']); ?>
                                    </a>
                                <?php
        else: ?>
                                    <span class="file-icon">📄</span>
                                    <?php echo htmlspecialchars($file['name']); ?>
                                <?php
        endif; ?>
                            </td>
                            <td><?php echo $file['size']; ?></td>
                            <td><?php echo $file['date']; ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Delete this item?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="item_path" value="<?php echo htmlspecialchars($file['name']); // Relative to current subdir ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php
    endforeach; ?>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
