<?php
/**
 * Fix Chat Tables - Run this file in your browser
 * URL: http://localhost/SYSTEMINTEG/fix_chat_tables.php
 */

require_once 'config.php';

echo "<h2>Chat Tables Fix Script</h2>";
echo "<p>Creating chat tables if they don't exist...</p>";

try {
    $db = Database::getInstance();
    
    // Create chat_conversations table
    $sql1 = "CREATE TABLE IF NOT EXISTS `chat_conversations` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `patient_id` INT NOT NULL,
      `clinic_id` INT NOT NULL,
      `last_message_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`clinic_id`) REFERENCES `clinics`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `unique_conversation` (`patient_id`, `clinic_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->execute($sql1);
    echo "<p style='color: green;'>✓ chat_conversations table created/verified</p>";
    
    // Create chat_messages table
    $sql2 = "CREATE TABLE IF NOT EXISTS `chat_messages` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->execute($sql2);
    echo "<p style='color: green;'>✓ chat_messages table created/verified</p>";
    
    // Verify tables exist
    $tables = $db->fetchAll("SHOW TABLES LIKE 'chat%'");
    echo "<h3>Verification:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        echo "<li style='color: blue;'>✓ Table exists: <strong>$tableName</strong></li>";
    }
    echo "</ul>";
    
    echo "<h3 style='color: green;'>SUCCESS! Chat tables are ready.</h3>";
    echo "<p><a href='views/chat.php'>Go to Messages</a> | <a href='views/dashboard.php'>Go to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and permissions.</p>";
}
?>
