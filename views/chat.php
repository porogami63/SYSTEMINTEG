<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Determine user type and get conversations
$conversations = [];
if (isPatient()) {
    // Get patient's conversations with clinics
    $patient = $db->fetch("SELECT id FROM patients WHERE user_id = ?", [$user_id]);
    if ($patient) {
        $conversations = $db->fetchAll("
            SELECT cc.*, cl.clinic_name, u.full_name as doctor_name,
                   (SELECT COUNT(*) FROM chat_messages cm 
                    WHERE cm.conversation_id = cc.id AND cm.is_read = 0 AND cm.sender_id != ?) as unread_count
            FROM chat_conversations cc
            JOIN clinics cl ON cc.clinic_id = cl.id
            JOIN users u ON cl.user_id = u.id
            WHERE cc.patient_id = ?
            ORDER BY cc.last_message_at DESC
        ", [$user_id, $patient['id']]);
    }
} else if (isClinicAdmin()) {
    // Get clinic's conversations with patients
    $clinic = $db->fetch("SELECT id FROM clinics WHERE user_id = ?", [$user_id]);
    if ($clinic) {
        $conversations = $db->fetchAll("
            SELECT cc.*, u.full_name as patient_name, p.patient_code,
                   (SELECT COUNT(*) FROM chat_messages cm 
                    WHERE cm.conversation_id = cc.id AND cm.is_read = 0 AND cm.sender_id != ?) as unread_count
            FROM chat_conversations cc
            JOIN patients p ON cc.patient_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE cc.clinic_id = ?
            ORDER BY cc.last_message_at DESC
        ", [$user_id, $clinic['id']]);
    }
}

// Get selected conversation
$selected_conversation_id = isset($_GET['conv']) ? intval($_GET['conv']) : null;
$messages = [];
$conversation_partner = '';

if ($selected_conversation_id) {
    // Verify user has access to this conversation
    $conv = $db->fetch("SELECT * FROM chat_conversations WHERE id = ?", [$selected_conversation_id]);
    if ($conv) {
        $has_access = false;
        if (isPatient() && $patient && $conv['patient_id'] == $patient['id']) {
            $has_access = true;
            $clinic_info = $db->fetch("SELECT cl.clinic_name, u.full_name FROM clinics cl JOIN users u ON cl.user_id = u.id WHERE cl.id = ?", [$conv['clinic_id']]);
            $conversation_partner = $clinic_info['clinic_name'] . ' - ' . $clinic_info['full_name'];
        } else if (isClinicAdmin() && $clinic && $conv['clinic_id'] == $clinic['id']) {
            $has_access = true;
            $patient_info = $db->fetch("SELECT u.full_name, p.patient_code FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id = ?", [$conv['patient_id']]);
            $conversation_partner = $patient_info['full_name'] . ' (' . $patient_info['patient_code'] . ')';
        }
        
        if ($has_access) {
            $messages = $db->fetchAll("
                SELECT cm.*, u.full_name as sender_name
                FROM chat_messages cm
                JOIN users u ON cm.sender_id = u.id
                WHERE cm.conversation_id = ?
                ORDER BY cm.created_at ASC
            ", [$selected_conversation_id]);
            
            // Mark messages as read
            $db->execute("UPDATE chat_messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?", [$selected_conversation_id, $user_id]);
        }
    }
}

require_once 'includes/role_styles.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
.chat-container {
    height: calc(100vh - 200px);
    display: flex;
}
.conversations-list {
    width: 300px;
    border-right: 1px solid #ddd;
    overflow-y: auto;
}
.conversation-item {
    padding: 15px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background 0.2s;
}
.conversation-item:hover {
    background: #f8f9fa;
}
.conversation-item.active {
    background: #e3f2fd;
}
.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
}
.messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f8f9fa;
}
.message {
    margin-bottom: 15px;
    display: flex;
}
.message.sent {
    justify-content: flex-end;
}
.message-bubble {
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 15px;
    word-wrap: break-word;
}
.message.received .message-bubble {
    background: white;
    border: 1px solid #ddd;
}
.message.sent .message-bubble {
    background: #1565c0;
    color: white;
}
.message-input-area {
    padding: 15px;
    border-top: 1px solid #ddd;
    background: white;
}
.unread-badge {
    background: #dc3545;
    color: white;
    border-radius: 10px;
    padding: 2px 8px;
    font-size: 12px;
}
</style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-chat-dots"></i> Messages</h2>
            <?php if (isPatient()): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newChatModal">
                <i class="bi bi-plus-circle"></i> New Conversation
            </button>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="chat-container">
                <!-- Conversations List -->
                <div class="conversations-list">
                    <?php if (empty($conversations)): ?>
                        <div class="text-center text-muted p-4">
                            <i class="bi bi-chat-dots" style="font-size: 48px;"></i>
                            <p class="mt-2">No conversations yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                        <div class="conversation-item <?php echo $selected_conversation_id == $conv['id'] ? 'active' : ''; ?>" 
                             onclick="window.location.href='chat.php?conv=<?php echo $conv['id']; ?>'">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>
                                        <?php echo isPatient() ? htmlspecialchars($conv['clinic_name']) : htmlspecialchars($conv['patient_name']); ?>
                                    </strong>
                                    <?php if (isClinicAdmin()): ?>
                                    <div class="text-muted small"><?php echo htmlspecialchars($conv['patient_code']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($conv['unread_count'] > 0): ?>
                                <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted small mt-1">
                                <?php echo date('M d, Y g:i A', strtotime($conv['last_message_at'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Chat Area -->
                <div class="chat-area">
                    <?php if ($selected_conversation_id && !empty($messages)): ?>
                        <div class="p-3 border-bottom bg-white">
                            <h5 class="mb-0"><?php echo htmlspecialchars($conversation_partner); ?></h5>
                        </div>
                        
                        <div class="messages-container" id="messagesContainer">
                            <?php foreach ($messages as $msg): ?>
                            <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                                <div class="message-bubble">
                                    <?php if ($msg['sender_id'] != $user_id): ?>
                                    <div class="small text-muted mb-1"><?php echo htmlspecialchars($msg['sender_name']); ?></div>
                                    <?php endif; ?>
                                    <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                    <div class="small mt-1" style="opacity: 0.7;">
                                        <?php echo date('g:i A', strtotime($msg['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="message-input-area">
                            <form id="sendMessageForm" class="d-flex gap-2">
                                <input type="hidden" name="conversation_id" value="<?php echo $selected_conversation_id; ?>">
                                <input type="text" name="message" class="form-control" placeholder="Type a message..." required autocomplete="off">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send"></i> Send
                                </button>
                            </form>
                        </div>
                    <?php elseif ($selected_conversation_id): ?>
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center text-muted">
                                <i class="bi bi-chat-dots" style="font-size: 64px;"></i>
                                <p class="mt-3">Start a conversation</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center text-muted">
                                <i class="bi bi-chat-left-text" style="font-size: 64px;"></i>
                                <p class="mt-3">Select a conversation to start messaging</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Chat Modal (Patients only) -->
<?php if (isPatient()): ?>
<div class="modal fade" id="newChatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Conversation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="../api/chat_create.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Clinic</label>
                        <select name="clinic_id" class="form-select" required>
                            <option value="">Choose a clinic...</option>
                            <?php
                            $clinics = $db->fetchAll("SELECT cl.id, cl.clinic_name, u.full_name FROM clinics cl JOIN users u ON cl.user_id = u.id WHERE cl.is_available = 1");
                            foreach ($clinics as $cl):
                            ?>
                            <option value="<?php echo $cl['id']; ?>">
                                <?php echo htmlspecialchars($cl['clinic_name'] . ' - ' . $cl['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Initial Message</label>
                        <textarea name="message" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Start Conversation</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-scroll to bottom of messages
const container = document.getElementById('messagesContainer');
if (container) {
    container.scrollTop = container.scrollHeight;
}

// Send message via AJAX
document.getElementById('sendMessageForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('../api/chat_send.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to send message');
        }
    });
});

// Auto-refresh messages every 5 seconds
<?php if ($selected_conversation_id): ?>
setInterval(() => {
    location.reload();
}, 5000);
<?php endif; ?>
</script>
</body>
</html>
