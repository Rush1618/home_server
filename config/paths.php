<?php
// Path Configuration

// 1. Transient Root (Code, Builds, Repo Clones) - NOT permanent
define('PROJECTS_ROOT', '/srv/platform/projects');

// 2. Persistent Root (Database, Uploads, Logs, Env) - PERMANENT
define('STORAGE_ROOT', '/srv/platform/storage');

// Sub-folders for Persistent Admin data
define('ADMIN_STORAGE', STORAGE_ROOT . '/admin');
define('LOGS_DIR', ADMIN_STORAGE . '/logs');
define('UPLOADS_ROOT', ADMIN_STORAGE . '/uploads');
define('IMAGES_ROOT', ADMIN_STORAGE . '/images');
define('ENV_ROOT', ADMIN_STORAGE . '/env');

// Sub-folder for Persistent Project data
define('PROJECT_STORAGE_ROOT', STORAGE_ROOT . '/projects');

// Nginx configuration (usually /etc/nginx/sites-available in Ubuntu)
define('NGINX_CONFIG_DIR', '/etc/nginx/sites-available');
