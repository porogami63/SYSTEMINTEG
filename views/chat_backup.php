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
            SELECT cc.*, cl.clinic_name, u.full_name as doctor_name, u.profile_photo,
                   (SELECT COUNT(*) FROM chat_messages cm 
                    WHERE cm.conversation_id = cc.id AND cm.is_read = 0 AND cm.sender_id != ?) as unread_count,
                   (SELECT message FROM chat_messages cm2 
                    WHERE cm2.conversation_id = cc.id 
                    ORDER BY cm2.created_at DESC LIMIT 1) as last_message
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
            SELECT cc.*, u.full_name as patient_name, p.patient_code, u.profile_photo,
                   (SELECT COUNT(*) FROM chat_messages cm 
                    WHERE cm.conversation_id = cc.id AND cm.is_read = 0 AND cm.sender_id != ?) as unread_count,
                   (SELECT message FROM chat_messages cm2 
                    WHERE cm2.conversation_id = cc.id 
                    ORDER BY cm2.created_at DESC LIMIT 1) as last_message
            FROM chat_conversations cc
            JOIN patients p ON cc.patient_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE cc.clinic_id = ?
            ORDER BY cc.last_message_at DESC
        ", [$user_id, $clinic['id']]);
    }
} else if (isWebAdmin()) {
    // Web admin can see all conversations for moderation
    $conversations = $db->fetchAll("
        SELECT cc.*, 
               cl.clinic_name, 
               u1.full_name as doctor_name,
               u2.full_name as patient_name, 
               p.patient_code,
               u2.profile_photo,
               (SELECT COUNT(*) FROM chat_messages cm 
                WHERE cm.conversation_id = cc.id) as message_count,
               (SELECT message FROM chat_messages cm2 
                WHERE cm2.conversation_id = cc.id 
                ORDER BY cm2.created_at DESC LIMIT 1) as last_message
        FROM chat_conversations cc
        JOIN clinics cl ON cc.clinic_id = cl.id
        JOIN users u1 ON cl.user_id = u1.id
        JOIN patients p ON cc.patient_id = p.id
        JOIN users u2 ON p.user_id = u2.id
        ORDER BY cc.last_message_at DESC
    ");
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
                SELECT cm.*, u.full_name as sender_name, u.profile_photo
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
body {
    overflow: hidden;
}
.main-content {
    padding: 0 !important;
    height: calc(100vh - 60px);
}
.chat-wrapper {
    height: 100%;
    display: flex;
    background: white;
}
.conversations-sidebar {
    width: 320px;
    border-right: 1px solid #e0e0e0;
    display: flex;
    flex-direction: column;
    background: #f8f9fa;
}
.conversations-header {
    padding: 20px;
    background: white;
    border-bottom: 1px solid #e0e0e0;
}
.conversations-header h5 {
    margin: 0;
    font-weight: 600;
    color: #333;
}
.conversations-list {
    flex: 1;
    overflow-y: auto;
}
.conversation-item {
    padding: 16px 20px;
    border-bottom: 1px solid #e0e0e0;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.conversation-item:hover {
    background: #f5f5f5;
}
.conversation-item.active {
    background: #e3f2fd;
    border-left: 3px solid #1976d2;
}
.conversation-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #666;
    font-size: 18px;
    flex-shrink: 0;
    overflow: hidden;
}
.conversation-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.conversation-info {
    flex: 1;
    min-width: 0;
}
.conversation-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.conversation-preview {
    font-size: 13px;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.conversation-meta {
    text-align: right;
    flex-shrink: 0;
}
.conversation-time {
    font-size: 11px;
    color: #999;
    margin-bottom: 4px;
}
.unread-badge {
    background: #1976d2;
    color: white;
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}
.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: white;
}
.chat-header {
    padding: 20px 24px;
    background: white;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.chat-header h5 {
    margin: 0;
    font-weight: 600;
    color: #333;
}
.chat-header-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.chat-header-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #666;
    overflow: hidden;
}
.chat-header-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    background: #fafafa;
}
.message {
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.message.sent {
    justify-content: flex-end;
}
.message-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #666;
    font-size: 14px;
    flex-shrink: 0;
    overflow: hidden;
}
.message-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.message.sent .message-avatar {
    order: 2;
}
.message-content {
    max-width: 60%;
}
.message-bubble {
    padding: 12px 16px;
    border-radius: 18px;
    word-wrap: break-word;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
.message.received .message-bubble {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 18px 18px 18px 4px;
}
.message.sent .message-bubble {
    background: #1976d2;
    color: white;
    border-radius: 18px 18px 4px 18px;
}
.message-sender {
    font-size: 12px;
    font-weight: 600;
    color: #666;
    margin-bottom: 4px;
}
.message.sent .message-sender {
    text-align: right;
}
.message-time {
    font-size: 11px;
    color: #999;
    margin-top: 4px;
}
.message.sent .message-time {
    text-align: right;
    color: rgba(255,255,255,0.7);
}
.message-input-area {
    padding: 20px 24px;
    background: white;
    border-top: 1px solid #e0e0e0;
}
.message-input-wrapper {
    display: flex;
    gap: 12px;
    align-items: flex-end;
}
.message-input-wrapper input {
    flex: 1;
    border: 1px solid #e0e0e0;
    border-radius: 24px;
    padding: 12px 20px;
    font-size: 14px;
}
.message-input-wrapper input:focus {
    outline: none;
    border-color: #1976d2;
    box-shadow: 0 0 0 3px rgba(25,118,210,0.1);
}
.btn-send {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #1976d2;
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-send:hover {
    background: #1565c0;
    transform: scale(1.05);
}
.btn-attach {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #f5f5f5;
    color: #666;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-attach:hover {
    background: #e0e0e0;
}
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #999;
}
.empty-state i {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.3;
}
.admin-badge {
    background: #ff9800;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}
.delete-message-btn {
    opacity: 0;
    transition: opacity 0.2s;
    margin-left: 8px;
}
.message:hover .delete-message-btn {
    opacity: 1;
}
@media (max-width: 768px) {
    .conversations-sidebar {
        width: 100%;
        position: absolute;
        z-index: 10;
        height: 100%;
    }
    .conversations-sidebar.hidden {
        display: none;
    }
}
</style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="chat-wrapper">
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
                                <div class="message-avatar">
                                    <?php if (!empty($msg['profile_photo']) && file_exists('../' . $msg['profile_photo'])): ?>
                                        <img src="../<?php echo htmlspecialchars($msg['profile_photo']); ?>" alt="<?php echo htmlspecialchars($msg['sender_name']); ?>">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="message-bubble">
                                    <?php if ($msg['sender_id'] != $user_id): ?>
                                    <div class="small text-muted mb-1"><?php echo htmlspecialchars($msg['sender_name']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($msg['message'])): ?>
                                    <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($msg['attachment_path'])): ?>
                                    <div class="mt-2">
                                        <a href="../<?php echo htmlspecialchars($msg['attachment_path']); ?>" target="_blank" class="text-decoration-none" download>
                                            <i class="bi bi-paperclip"></i> <?php echo htmlspecialchars($msg['attachment_name']); ?>
                                            <span class="small">(<?php echo number_format($msg['attachment_size'] / 1024, 1); ?> KB)</span>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    <div class="small mt-1" style="opacity: 0.7;">
                                        <?php echo date('M d, Y g:i A', strtotime($msg['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="message-input-area">
                            <form id="sendMessageForm" enctype="multipart/form-data">
                                <input type="hidden" name="conversation_id" value="<?php echo $selected_conversation_id; ?>">
                                <div class="d-flex gap-2 align-items-end">
                                    <div class="flex-grow-1">
                                        <input type="text" name="message" id="messageInput" class="form-control" placeholder="Type a message..." autocomplete="off">
                                        <div id="attachmentPreview" class="small text-muted mt-1" style="display: none;">
                                            <i class="bi bi-paperclip"></i> <span id="attachmentName"></span>
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" id="removeAttachment">Remove</button>
                                        </div>
                                    </div>
                                    <input type="file" name="attachment" id="attachmentInput" style="display: none;" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip">
                                    <button type="button" class="btn btn-outline-secondary" id="attachFileBtn" title="Attach file">
                                        <i class="bi bi-paperclip"></i>
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send"></i> Send
                                    </button>
                                </div>
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

// File attachment handling
const attachFileBtn = document.getElementById('attachFileBtn');
const attachmentInput = document.getElementById('attachmentInput');
const attachmentPreview = document.getElementById('attachmentPreview');
const attachmentName = document.getElementById('attachmentName');
const removeAttachment = document.getElementById('removeAttachment');
const messageInput = document.getElementById('messageInput');

attachFileBtn?.addEventListener('click', () => {
    attachmentInput.click();
});

attachmentInput?.addEventListener('change', function() {
    if (this.files.length > 0) {
        attachmentName.textContent = this.files[0].name;
        attachmentPreview.style.display = 'block';
        // Make message optional when file is attached
        messageInput.removeAttribute('required');
    }
});

removeAttachment?.addEventListener('click', function() {
    attachmentInput.value = '';
    attachmentPreview.style.display = 'none';
    // Make message required again
    messageInput.setAttribute('required', 'required');
});

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
            alert('Failed to send message: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error sending message');
        console.error(error);
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
