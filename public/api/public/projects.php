<?php
// Public API - No Auth Required
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

function get_public_projects()
{
    try {
        $pdo = getDBConnection();
        // Only select safe fields
        $stmt = $pdo->query("SELECT name, slug, status FROM projects WHERE status = 'active' ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    catch (PDOException $e) {
        http_response_code(500);
        return ['error' => 'Database error'];
    }
}

echo json_encode(get_public_projects());
