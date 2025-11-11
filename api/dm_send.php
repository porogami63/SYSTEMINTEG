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
    $msg = trim($_POST['message'] ?? '');
    $me = $_SESSION['user_id'];

    if (!$to || !$msg) { redirect('../views/chat.php?mode=direct'); }
    if ($to === $me) { redirect('../views/chat.php?mode=direct&dm=' . $to); }

    // ensure table exists
    $db->execute("CREATE TABLE IF NOT EXISTS direct_messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_pair (sender_id, receiver_id),
        INDEX idx_receiver (receiver_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // insert
    $db->execute("INSERT INTO direct_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)", [$me, $to, $msg]);
    $messageId = $db->lastInsertId();

    // simple notification
    $senderName = $_SESSION['full_name'] ?? 'User';
    $preview = substr($msg, 0, 80);
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

