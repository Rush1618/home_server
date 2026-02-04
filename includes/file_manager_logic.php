<?php
// includes/file_manager_logic.php

function list_storage_files($project_id, $subdir = '')
{
    $project = get_project($project_id);
    if (!$project || !$project['storage_enabled']) {
        throw new Exception("Storage not enabled for this project.");
    }

    $basePath = realpath($project['storage_path']);
    // Sanitize subdir
    $subdir = trim($subdir, '/');
    if (strpos($subdir, '..') !== false) {
        throw new Exception("Invalid path.");
    }

    $targetPath = $basePath;
    if ($subdir) {
        $targetPath .= '/' . $subdir;
    }

    // Security check: ensure target path is inside base path
    if (strpos(realpath($targetPath), $basePath) !== 0) {
    // This might fail if subdir doesn't exist yet, so check existence first
    }

    if (!is_dir($targetPath))
        return [];

    $items = scandir($targetPath);
    $results = [];

    foreach ($items as $item) {
        if ($item === '.' || $item === '..')
            continue;

        $fullPath = $targetPath . '/' . $item;
        $results[] = [
            'name' => $item,
            'is_dir' => is_dir($fullPath),
            'size' => is_dir($fullPath) ? '-' : filesize($fullPath),
            'date' => date('Y-m-d H:i:s', filemtime($fullPath)),
            'path' => ($subdir ? $subdir . '/' : '') . $item
        ];
    }
    return $results;
}

function handle_file_upload($project_id, $subdir, $files)
{
    $project = get_project($project_id);
    if (!$project || !$project['storage_enabled'])
        return false;

    $basePath = $project['storage_path'];
    $targetDir = $basePath;
    if ($subdir) {
        // Sanitize and append
        $subdir = str_replace('..', '', $subdir);
        $targetDir .= '/' . trim($subdir, '/');
    }

    if (!is_dir($targetDir))
        mkdir($targetDir, 0755, true);

    // Support single or multiple files
    // If $files['name'] is array, loop. For simple implementation, assuming single file input often.
    // HTML5 multiple: $files['name'][0]...

    if (is_array($files['name'])) {
        for ($i = 0; $i < count($files['name']); $i++) {
            $name = basename($files['name'][$i]);
            $tmp = $files['tmp_name'][$i];
            move_uploaded_file($tmp, $targetDir . '/' . $name);
        }
    }
    else {
        $name = basename($files['name']);
        move_uploaded_file($files['tmp_name'], $targetDir . '/' . $name);
    }
    return true;
}

function delete_storage_item($project_id, $item_path)
{
    $project = get_project($project_id);
    $basePath = $project['storage_path'];
    // Sanitize
    $item_path = str_replace('..', '', $item_path);
    $fullPath = $basePath . '/' . trim($item_path, '/');

    if (is_dir($fullPath)) {
        // Recursive delete? Or just rmdir (must be empty).
        // Let's use system rm -rf for convenience (Caution!)
        // Ensure path starts with basepath
        if (strpos(realpath($fullPath), realpath($basePath)) === 0 && $fullPath !== $basePath) {
            exec("rm -rf " . escapeshellarg($fullPath));
        }
    }
    else {
        if (file_exists($fullPath))
            unlink($fullPath);
    }
    return true;
}
