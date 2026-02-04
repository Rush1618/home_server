<?php
require_once __DIR__ . '/../config/paths.php';

// Ensure rate limits dir exists
if (!defined('NGINX_RATELIMITS_DIR')) {
    define('NGINX_RATELIMITS_DIR', '/mnt/storage/platform/nginx/ratelimits');
}

function generate_nginx_config($project)
{
    // Expects $project to be an associative array from DB

    $slug = $project['slug'];
    $domain = $slug . '.localhost'; // Phase 2 convention
    $runtimeDir = PROJECTS_ROOT . '/' . $slug . '/runtime';
    $runtimeType = $project['runtime_type'];

    // 1. Generate Rate Limit Config (Zone)
    $rateLimitZoneFile = NGINX_RATELIMITS_DIR . '/' . $slug . '.conf';
    $limitDirective = ""; // Used inside server block

    if ($project['rate_limit_enabled']) {
        $rpm = (int)$project['rate_limit_rpm'];
        $burst = (int)$project['rate_limit_burst'];
        if ($rpm < 1)
            $rpm = 60;

        // binary_remote_addr is standard for IP-based limiting
        // We need a unique zone name: project_slug
        $zoneConfig = "limit_req_zone \$binary_remote_addr zone={$slug}:10m rate={$rpm}r/m;\n";

        if (file_put_contents($rateLimitZoneFile, $zoneConfig) === false) {
            error_log("Failed to write rate limit config for $slug");
        }

        // Directive to apply the limit
        // nodelay = fail fast (429) instead of queueing
        $limitDirective = "limit_req zone={$slug} burst={$burst} nodelay;";
    }
    else {
        // Remove zone file if disabled
        if (file_exists($rateLimitZoneFile))
            unlink($rateLimitZoneFile);
    }

    // 2. Generate Main Config
    $nginxConfig = "";

    // Include the zone definition at HTTP context level?
    // WARNING: Nginx 'limit_req_zone' must be in http context.
    // If we are generating per-site configs included in http context, we can put it there.
    // However, usually sites-enabled are inside http block.
    // So we can Include the rate limit config file at the top of this file?
    // Or, we assume the main nginx.conf includes /mnt/storage/platform/nginx/ratelimits/*.conf;
    // Let's assuming we include it here.

    if ($project['rate_limit_enabled']) {
        $nginxConfig .= "include " . NGINX_RATELIMITS_DIR . "/" . $slug . ".conf;\n\n";
    }

    $nginxConfig .= "server {\n";
    $nginxConfig .= "    listen 8080;\n";
    $nginxConfig .= "    listen [::]:8080;\n";
    $nginxConfig .= "    server_name $domain;\n\n";

    // Apply Rate Limit
    if ($limitDirective) {
        $nginxConfig .= "    $limitDirective\n\n";
    }

    // Proxy or Root
    if ($runtimeType === 'node') {
        $port = $project['port'];
        // Fallback or error if no port?
        if (!$port)
            $port = 3000;

        $nginxConfig .= "    location / {\n";
        $nginxConfig .= "        proxy_pass http://127.0.0.1:$port;\n";
        $nginxConfig .= "        proxy_http_version 1.1;\n";
        $nginxConfig .= "        proxy_set_header Upgrade \$http_upgrade;\n";
        $nginxConfig .= "        proxy_set_header Connection 'upgrade';\n";
        $nginxConfig .= "        proxy_set_header Host \$host;\n";
        $nginxConfig .= "        proxy_cache_bypass \$http_upgrade;\n";
        $nginxConfig .= "    }\n";
    }
    else {
        // PHP or Static
        $root = $runtimeDir;
        $nginxConfig .= "    root $root;\n";
        $nginxConfig .= "    index index.php index.html index.htm;\n\n";

        $nginxConfig .= "    location / {\n";
        $nginxConfig .= "        try_files \$uri \$uri/ /index.php?\$query_string;\n";
        $nginxConfig .= "    }\n";

        if ($runtimeType === 'php') {
            $nginxConfig .= "\n    location ~ \.php$ {\n";
            $nginxConfig .= "        include snippets/fastcgi-php.conf;\n";
            $nginxConfig .= "        fastcgi_pass unix:/run/php/php8.1-fpm.sock;\n";
            $nginxConfig .= "    }\n";
        }
    }

    $nginxConfig .= "}\n";

    $confPath = NGINX_CONFIG_DIR . '/' . $slug . '.conf';

    if (file_put_contents($confPath, $nginxConfig) === false) {
        throw new Exception("Failed to write Nginx config.");
    }

    reload_nginx();
}
