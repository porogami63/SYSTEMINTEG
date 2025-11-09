-- Fix Chat Tables
-- Run this to ensure chat tables exist in the database

USE mediarchive;

-- Create chat_conversations table
CREATE TABLE IF NOT EXISTS `chat_conversations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `clinic_id` INT NOT NULL,
  `last_message_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`clinic_id`) REFERENCES `clinics`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_conversation` (`patient_id`, `clinic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create chat_messages table
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `conversation_id` INT NOT NULL,
  `sender_id` INT NOT NULL COMMENT 'user_id of sender',
  `message` TEXT,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `attachment_path` VARCHAR(255) DEFAULT NULL,
  `attachment_name` VARCHAR(255) DEFAULT NULL,
  `attachment_type` VARCHAR(50) DEFAULT NULL,
  `attachment_size` INT DEFAULT NULL,
  FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_conversation` (`conversation_id`),
  INDEX `idx_created` (`created_at`),
  INDEX `idx_sender` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
