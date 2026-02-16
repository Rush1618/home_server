CREATE DATABASE IF NOT EXISTS admin_platform;
USE admin_platform;

-- 1. Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Projects table for management
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active', 'disabled') DEFAULT 'active',
    
    -- Deployment & Runtime settings
    repo_url VARCHAR(255) DEFAULT '',
    build_cmd TEXT,
    start_cmd TEXT,
    runtime_type ENUM('static', 'php', 'node') DEFAULT 'static',
    last_deploy DATETIME DEFAULT NULL,
    deploy_status ENUM('pending', 'success', 'failed') DEFAULT NULL,
    port INT DEFAULT NULL,
    
    -- Persistent Storage settings
    storage_enabled TINYINT(1) DEFAULT 0,
    storage_path VARCHAR(255) DEFAULT NULL,
    
    -- Traffic & Security settings
    rate_limit_enabled TINYINT(1) DEFAULT 0,
    rate_limit_rpm INT DEFAULT 60,
    rate_limit_burst INT DEFAULT 20,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Environment variables table
CREATE TABLE IF NOT EXISTS env_vars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    `key` VARCHAR(255) NOT NULL,
    `value` TEXT NOT NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
