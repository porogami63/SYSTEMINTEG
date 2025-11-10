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

// Verify CSRF token for API requests (if provided)
if (isset($_POST['csrf_token'])) {
    if (!SecurityManager::validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'CSRF token validation failed']);
        exit;
    }
}

// Rate limiting for chat messages
$clientIP = SecurityManager::getClientIP();
$userId = $_SESSION['user_id'];
if (!SecurityManager::checkRateLimit('chat_send', 30, 60, $userId . ':' . $clientIP)) {
    echo json_encode(['success' => false, 'error' => 'Rate limit exceeded. Please wait before sending more messages.']);
    exit;
}

// Validate and sanitize input
$conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
$messageResult = InputValidator::validate($_POST['message'] ?? '', 'string', ['max_length' => 5000, 'allow_html' => false]);
$message = $messageResult['valid'] ? $messageResult['value'] : '';

// Allow empty message if there's an attachment
$has_attachment = !empty($_FILES['attachment']['name']);

if (!$conversation_id || (!$message && !$has_attachment)) {
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
    
    // Handle file attachment
    $attachment_path = null;
    $attachment_name = null;
    $attachment_type = null;
    $attachment_size = null;
    
    if ($has_attachment) {
        require_once '../includes/FileProcessor.php';
        try {
            // Allow common file types with security validation
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'];
            $max_size = 10 * 1024 * 1024; // 10MB
            
            // Validate file upload with SecurityManager
            $uploadValidation = SecurityManager::validateFileUpload($_FILES['attachment'], $allowed_extensions, $max_size);
            if (!$uploadValidation['valid']) {
                echo json_encode(['success' => false, 'error' => 'File upload validation failed: ' . implode(', ', $uploadValidation['errors'])]);
                exit;
            }
            
            $saved_path = FileProcessor::saveUpload($_FILES['attachment'], UPLOAD_DIR, $allowed_extensions, $max_size);
            $attachment_path = 'uploads/' . basename($saved_path);
            $attachment_name = SecurityManager::escapeOutput($_FILES['attachment']['name']);
            $attachment_type = SecurityManager::escapeOutput($_FILES['attachment']['type']);
            $attachment_size = $_FILES['attachment']['size'];
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'File upload failed: ' . $e->getMessage()]);
            SecurityManager::logSecurityEvent('FILE_UPLOAD_ERROR', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'ip' => $clientIP
            ]);
            exit;
        }
    }
    
    // Insert message
    $db->execute("INSERT INTO chat_messages (conversation_id, sender_id, message, attachment_path, attachment_name, attachment_type, attachment_size) VALUES (?, ?, ?, ?, ?, ?, ?)", 
                 [$conversation_id, $user_id, $message, $attachment_path, $attachment_name, $attachment_type, $attachment_size]);
    
    $message_id = $db->lastInsertId();
    
    // Update conversation timestamp
    $db->execute("UPDATE chat_conversations SET last_message_at = NOW() WHERE id = ?", [$conversation_id]);
    
    // Log to audit trail
    AuditLogger::log(
        'SEND_MESSAGE',
        'chat_message',
        $message_id,
        [
            'conversation_id' => $conversation_id,
            'message_preview' => substr($message, 0, 50),
            'has_attachment' => !empty($attachment_path)
        ]
    );
    
    // Send notification to the other party
    $recipient_id = null;
    $sender_name = $_SESSION['full_name'] ?? 'Someone';
    
    if (isPatient()) {
        // Get clinic admin user ID
        $clinic_user = $db->fetch("SELECT user_id FROM clinics WHERE id = ?", [$conv['clinic_id']]);
        $recipient_id = $clinic_user['user_id'] ?? null;
    } else if (isClinicAdmin()) {
        // Get patient user ID
        $patient_user = $db->fetch("SELECT user_id FROM patients WHERE id = ?", [$conv['patient_id']]);
        $recipient_id = $patient_user['user_id'] ?? null;
    }
    
    if ($recipient_id) {
        $preview = !empty($message) ? substr($message, 0, 50) : 'Sent an attachment';
        $db->execute(
            "INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)",
            [$recipient_id, "New message from " . $sender_name, $preview, "chat.php?conv=" . $conversation_id]
        );
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log('Chat send error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
