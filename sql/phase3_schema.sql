USE admin_platform;

-- Add new columns to projects table
ALTER TABLE projects
ADD COLUMN repo_url VARCHAR(255) DEFAULT '',
ADD COLUMN build_cmd TEXT,
ADD COLUMN start_cmd TEXT,
ADD COLUMN runtime_type ENUM('static', 'php', 'node') DEFAULT 'static',
ADD COLUMN last_deploy DATETIME DEFAULT NULL,
ADD COLUMN deploy_status ENUM('pending', 'success', 'failed') DEFAULT NULL,
ADD COLUMN port INT DEFAULT NULL;

-- Create environment variables table
CREATE TABLE IF NOT EXISTS env_vars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    `key` VARCHAR(255) NOT NULL,
    `value` TEXT NOT NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
