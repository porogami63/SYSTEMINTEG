<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$conversation_id = intval($_POST['conversation_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if (!$conversation_id || !$message) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    $db = Database::getInstance();
    $user_id = $_SESSION['user_id'];
    
    // Verify user has access to this conversation
    $conv = $db->fetch("SELECT * FROM chat_conversations WHERE id = ?", [$conversation_id]);
    if (!$conv) {
        echo json_encode(['success' => false, 'error' => 'Conversation not found']);
        exit;
    }
    
    $has_access = false;
    if (isPatient()) {
        $patient = $db->fetch("SELECT id FROM patients WHERE user_id = ?", [$user_id]);
        if ($patient && $conv['patient_id'] == $patient['id']) {
            $has_access = true;
        }
    } else if (isClinicAdmin()) {
        $clinic = $db->fetch("SELECT id FROM clinics WHERE user_id = ?", [$user_id]);
        if ($clinic && $conv['clinic_id'] == $clinic['id']) {
            $has_access = true;
        }
    }
    
    if (!$has_access) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    
    // Insert message
    $db->execute("INSERT INTO chat_messages (conversation_id, sender_id, message) VALUES (?, ?, ?)", 
                 [$conversation_id, $user_id, $message]);
    
    // Update conversation timestamp
    $db->execute("UPDATE chat_conversations SET last_message_at = NOW() WHERE id = ?", [$conversation_id]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log('Chat send error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
