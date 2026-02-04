# Nginx Configuration Guide for Termux/Ubuntu

This guide assumes you are running Nginx on Ubuntu inside Termux.

## 1. Install Nginx and PHP-FPM
```bash
sudo apt update
sudo apt install nginx php-fpm php-mysql
```

## 2. Configure Nginx
Create a new configuration file at `/etc/nginx/sites-available/admin-platform`.

```nginx
server {
    listen 8080 default_server;
    listen [::]:8080 default_server;

    # Adjust the root path to where you cloned the repo
    # For example: /mnt/storage/platform/public
    root /mnt/storage/platform/public;

    index index.php index.html index.htm;

    server_name localhost;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        
        # Check your PHP version socket (e.g., php8.1-fpm.sock)
        fastcgi_pass unix:/run/php/php8.1-fpm.sock; 
    }

    location ~ /\.ht {
        deny all;
    }
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";
}
```

## 3. Enable Site and Restart
```bash
sudo ln -s /etc/nginx/sites-available/admin-platform /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo service nginx restart
sudo service php8.1-fpm restart
```

## 4. Database Setup
Ensure MariaDB is running and you have executed the schema:
```bash
sudo mysql < /path/to/project/sql/schema.sql
php /path/to/project/scripts/create_admin.php
```
