<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../views/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/chat.php?mode=direct');
}

try {
    SecurityManager::verifyCSRFToken();
    $db = Database::getInstance();

    $to = intval($_POST['to'] ?? 0);
    $msg = isset($_POST['message']) ? trim($_POST['message']) : '';
    $me = $_SESSION['user_id'];

    if (!$to) { 
        error_log('DM send: No recipient specified');
        redirect('../views/chat.php?mode=direct'); 
    }
    if ($to === $me) { 
        error_log('DM send: Cannot send to self');
        redirect('../views/chat.php?mode=direct&dm=' . $to); 
    }

    // Handle file attachment
    $attachment_path = null;
    if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'];
        $max_size = 10 * 1024 * 1024; // 10MB
        
        $file_extension = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        $file_size = $_FILES['attachment']['size'];
        
        error_log("DM send: File upload attempt - {$_FILES['attachment']['name']}, size: $file_size, ext: $file_extension");
        
        if (!in_array($file_extension, $allowed_extensions)) {
            error_log("DM send: Invalid file extension: $file_extension");
            $_SESSION['error'] = 'Invalid file type. Allowed: ' . implode(', ', $allowed_extensions);
            redirect('../views/chat.php?mode=direct&dm=' . $to);
        }
        
        if ($file_size > $max_size) {
            error_log("DM send: File too large: $file_size bytes");
            $_SESSION['error'] = 'File too large. Maximum size: 10MB';
            redirect('../views/chat.php?mode=direct&dm=' . $to);
        }
        
        try {
            $upload_dir = '../uploads/chat/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
                error_log("DM send: Created upload directory: $upload_dir");
            }
            
            $filename = uniqid() . '_' . basename($_FILES['attachment']['name']);
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_path)) {
                $attachment_path = 'uploads/chat/' . $filename;
                error_log("DM send: File uploaded successfully: $attachment_path");
            } else {
                error_log("DM send: Failed to move uploaded file to $target_path");
            }
        } catch (Exception $e) {
            error_log('DM send: File upload exception: ' . $e->getMessage());
        }
    } elseif (!empty($_FILES['attachment']['name'])) {
        error_log('DM send: File upload error code: ' . $_FILES['attachment']['error']);
    }

    // Message or attachment required
    if (!$msg && !$attachment_path) { 
        redirect('../views/chat.php?mode=direct&dm=' . $to); 
    }

    // ensure table exists with attachment column
    $db->execute("CREATE TABLE IF NOT EXISTS direct_messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT DEFAULT NULL,
        attachment VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_pair (sender_id, receiver_id),
        INDEX idx_receiver (receiver_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Check if attachment column exists, add if not
    try {
        $db->execute("ALTER TABLE direct_messages ADD COLUMN IF NOT EXISTS attachment VARCHAR(255) DEFAULT NULL");
        // Also modify message column to allow NULL
        $db->execute("ALTER TABLE direct_messages MODIFY COLUMN message TEXT DEFAULT NULL");
    } catch (Exception $e) {
        // Columns might already exist or be modified
    }

    // insert - use empty string for message if empty
    $messageToSave = $msg !== '' ? $msg : null;
    $db->execute("INSERT INTO direct_messages (sender_id, receiver_id, message, attachment) VALUES (?, ?, ?, ?)", 
        [$me, $to, $messageToSave, $attachment_path]);
    
    error_log("DM send: Message inserted - from: $me, to: $to, has_message: " . ($msg ? 'yes' : 'no') . ", has_attachment: " . ($attachment_path ? 'yes' : 'no'));
    $messageId = $db->lastInsertId();

    // simple notification
    $senderName = $_SESSION['full_name'] ?? 'User';
    $preview = $msg ? substr($msg, 0, 80) : ($attachment_path ? '📎 Sent an attachment' : 'New message');
    $db->execute("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)", [$to, "New message from $senderName", $preview, "chat.php?mode=direct&dm=$me"]);

    // Audit trail
    AuditLogger::log('SEND_DIRECT_MESSAGE', 'direct_message', $messageId, [
        'sender_id' => $me,
        'receiver_id' => $to,
        'message_preview' => substr($msg, 0, 50)
    ]);

    header('Location: ../views/chat.php?mode=direct&dm=' . $to);
    exit;
} catch (Exception $e) {
    error_log('DM send error: ' . $e->getMessage());
    redirect('../views/chat.php?mode=direct');
}
?>

