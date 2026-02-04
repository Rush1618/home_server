USE admin_platform;

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active', 'disabled') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
