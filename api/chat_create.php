<?php
require_once '../config.php';

if (!isLoggedIn() || !isPatient()) {
    redirect('../views/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/chat.php');
}

$clinic_id = intval($_POST['clinic_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if (!$clinic_id || !$message) {
    redirect('../views/chat.php');
}

try {
    $db = Database::getInstance();
    $user_id = $_SESSION['user_id'];
    
    // Get patient ID
    $patient = $db->fetch("SELECT id FROM patients WHERE user_id = ?", [$user_id]);
    if (!$patient) {
        redirect('../views/chat.php');
    }
    
    // Check if conversation already exists
    $existing = $db->fetch("SELECT id FROM chat_conversations WHERE patient_id = ? AND clinic_id = ?", 
                           [$patient['id'], $clinic_id]);
    
    if ($existing) {
        $conversation_id = $existing['id'];
    } else {
        // Create new conversation
        $db->execute("INSERT INTO chat_conversations (patient_id, clinic_id) VALUES (?, ?)", 
                     [$patient['id'], $clinic_id]);
        $conversation_id = $db->lastInsertId();
    }
    
    // Send initial message
    $db->execute("INSERT INTO chat_messages (conversation_id, sender_id, message) VALUES (?, ?, ?)", 
                 [$conversation_id, $user_id, $message]);
    
    // Update conversation timestamp
    $db->execute("UPDATE chat_conversations SET last_message_at = NOW() WHERE id = ?", [$conversation_id]);
    
    redirect('../views/chat.php?conv=' . $conversation_id);
    
} catch (Exception $e) {
    error_log('Chat create error: ' . $e->getMessage());
    redirect('../views/chat.php');
}
