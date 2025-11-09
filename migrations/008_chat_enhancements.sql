-- Chat System Enhancements
-- Adds support for file attachments and improved message features

-- Add attachment support to chat_messages
ALTER TABLE `chat_messages` 
ADD COLUMN `attachment_path` VARCHAR(255) DEFAULT NULL AFTER `message`,
ADD COLUMN `attachment_name` VARCHAR(255) DEFAULT NULL AFTER `attachment_path`,
ADD COLUMN `attachment_type` VARCHAR(50) DEFAULT NULL AFTER `attachment_name`,
ADD COLUMN `attachment_size` INT DEFAULT NULL AFTER `attachment_type`;

-- Add index for better performance
ALTER TABLE `chat_messages`
ADD INDEX `idx_sender` (`sender_id`);
