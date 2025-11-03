-- ============================================
-- Feature Enhancements Migration
-- Adds tables for audit logging and analytics
-- ============================================

USE mediarchive;

-- Audit logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL COMMENT 'e.g., CREATE_CERTIFICATE, VIEW_CERTIFICATE, DELETE_CERTIFICATE',
    entity_type VARCHAR(50) NOT NULL COMMENT 'e.g., certificate, patient, user',
    entity_id INT COMMENT 'ID of the entity affected',
    details TEXT COMMENT 'JSON or text details of the action',
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity_type (entity_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add index for expiry date checking
ALTER TABLE certificates ADD INDEX idx_expiry_date (expiry_date);
ALTER TABLE certificates ADD INDEX idx_status (status);

