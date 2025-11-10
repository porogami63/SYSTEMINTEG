-- ============================================
-- Security Enhancements Migration
-- Adds tables for security auditing and rate limiting
-- ============================================

USE mediarchive;

-- Rate limits table
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    action_key VARCHAR(255) UNIQUE NOT NULL,
    action VARCHAR(100) NOT NULL,
    identifier VARCHAR(255) NOT NULL COMMENT 'IP address or user ID',
    attempts INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_action_key (action_key),
    INDEX idx_created_at (created_at),
    INDEX idx_action (action),
    INDEX idx_identifier (identifier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security audits table
CREATE TABLE IF NOT EXISTS security_audits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    audit_data TEXT NOT NULL COMMENT 'JSON audit data',
    score INT NOT NULL COMMENT 'Security score (0-100)',
    status VARCHAR(20) NOT NULL COMMENT 'excellent, good, fair, poor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status),
    INDEX idx_score (score),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add security-related columns to users table if they don't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS account_locked_until TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP NULL;

-- Add index for account locking
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_account_locked (account_locked_until);

-- Add security event types to audit_logs if needed
-- (This is handled by the application, but we ensure the table structure supports it)

