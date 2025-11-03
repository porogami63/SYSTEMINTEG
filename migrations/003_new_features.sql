-- ============================================
-- New Features Migration
-- Adds: Web Admin role, notification preferences, certificate notes, patient history
-- ============================================

USE mediarchive;

-- Update users role ENUM to include web_admin
ALTER TABLE users MODIFY role ENUM('clinic_admin', 'patient', 'web_admin') NOT NULL DEFAULT 'patient';

-- Create notification preferences table
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL COMMENT 'e.g., certificate_created, expiry_warning, system_update',
    enabled BOOLEAN DEFAULT TRUE,
    email_notification BOOLEAN DEFAULT TRUE,
    in_app_notification BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_category (user_id, category),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create certificate notes table
CREATE TABLE IF NOT EXISTS certificate_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    certificate_id INT NOT NULL,
    user_id INT NOT NULL COMMENT 'User who created the note',
    note TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT TRUE COMMENT 'Internal notes only visible to clinic staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (certificate_id) REFERENCES certificates(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_certificate_id (certificate_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add notification category to notifications table
ALTER TABLE notifications ADD COLUMN category VARCHAR(50) DEFAULT NULL COMMENT 'e.g., certificate_created, expiry_warning, system_update';
ALTER TABLE notifications ADD COLUMN is_important BOOLEAN DEFAULT FALSE;
ALTER TABLE notifications ADD INDEX idx_category (category);

-- Add columns to users table for dashboard preferences
ALTER TABLE users ADD COLUMN dashboard_layout TEXT DEFAULT NULL COMMENT 'JSON for widget positions and preferences';

-- Create Web Admin user
INSERT INTO users (username, email, password, full_name, role) 
VALUES ('webadmin', 'webadmin@mediarchive.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Web Administrator', 'web_admin')
ON DUPLICATE KEY UPDATE role='web_admin';

-- Password for webadmin: password

