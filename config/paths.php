<?php
// Path Configuration

// Where all persistent data lives
define('STORAGE_ROOT', '/srv/platform/storage');

// Sub-folders for specific data types
define('PROJECTS_ROOT', STORAGE_ROOT . '/projects');
define('LOGS_DIR', STORAGE_ROOT . '/admin/logs');
define('UPLOADS_ROOT', STORAGE_ROOT . '/admin/uploads');
define('IMAGES_ROOT', STORAGE_ROOT . '/admin/images');
define('ENV_ROOT', STORAGE_ROOT . '/admin/env');

// Nginx configuration (usually /etc/nginx/sites-available in Ubuntu)
define('NGINX_CONFIG_DIR', '/etc/nginx/sites-available');
