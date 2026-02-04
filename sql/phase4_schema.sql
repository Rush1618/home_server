USE admin_platform;

-- Add storage fields to projects
ALTER TABLE projects
ADD COLUMN storage_enabled TINYINT(1) DEFAULT 0,
ADD COLUMN storage_path VARCHAR(255) DEFAULT NULL;
