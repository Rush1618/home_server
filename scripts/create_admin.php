<?php
require_once __DIR__ . '/../config/database.php';

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

echo "Create Admin User\n";
echo "=================\n";

$username = readline("Enter username: ");
$password = readline("Enter password: ");

if (empty($username) || empty($password)) {
    die("Username and password are required.\n");
}

$pdo = getDBConnection();

// Check if user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    die("User '$username' already exists.\n");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
if ($stmt->execute([$username, $hash])) {
    echo "User created successfully!\n";
}
else {
    echo "Error creating user.\n";
}
