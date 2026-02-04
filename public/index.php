<?php
require_once __DIR__ . '/../includes/auth.php';

// Simple router logic:
// If logged in, go to dashboard.
// If not, go to login.
// All specific pages (login.php, dashboard.php) handle their own access control internally too.

if (is_logged_in()) {
    header('Location: /dashboard.php');
}
else {
    header('Location: /login.php');
}
exit;
