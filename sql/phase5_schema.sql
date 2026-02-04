USE admin_platform;

-- Add rate limiting fields to projects
ALTER TABLE projects
ADD COLUMN rate_limit_enabled TINYINT(1) DEFAULT 0,
ADD COLUMN rate_limit_rpm INT DEFAULT 60,
ADD COLUMN rate_limit_burst INT DEFAULT 20;
