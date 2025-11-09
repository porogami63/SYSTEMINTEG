<?php
/**
 * Chat Widget Component
 * Displays a floating chat widget accessible from any page
 */

if (!isLoggedIn()) {
    return;
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Get recent conversations with unread count
$conversations = [];
if (isPatient()) {
    $patient = $db->fetch("SELECT id FROM patients WHERE user_id = ?", [$user_id]);
    if ($patient) {
        $conversations = $db->fetchAll("
            SELECT cc.*, cl.clinic_name, cl.is_available, u.full_name as doctor_name, u.profile_photo,
                   (SELECT COUNT(*) FROM chat_messages cm 
                    WHERE cm.conversation_id = cc.id AND cm.is_read = 0 AND cm.sender_id != ?) as unread_count
            FROM chat_conversations cc
            JOIN clinics cl ON cc.clinic_id = cl.id
            JOIN users u ON cl.user_id = u.id
            WHERE cc.patient_id = ?
            ORDER BY cc.last_message_at DESC
            LIMIT 5
        ", [$user_id, $patient['id']]);
    }
} else if (isClinicAdmin()) {
    $clinic = $db->fetch("SELECT id FROM clinics WHERE user_id = ?", [$user_id]);
    if ($clinic) {
        $conversations = $db->fetchAll("
            SELECT cc.*, u.full_name as patient_name, p.patient_code, u.profile_photo,
                   (SELECT COUNT(*) FROM chat_messages cm 
                    WHERE cm.conversation_id = cc.id AND cm.is_read = 0 AND cm.sender_id != ?) as unread_count
            FROM chat_conversations cc
            JOIN patients p ON cc.patient_id = p.id
            JOIN users u ON p.user_id = u.id
            WHERE cc.clinic_id = ?
            ORDER BY cc.last_message_at DESC
            LIMIT 5
        ", [$user_id, $clinic['id']]);
    }
}

// Calculate total unread messages
$total_unread = array_sum(array_column($conversations, 'unread_count'));
?>

<style>
.chat-widget-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}

.chat-widget-toggle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    <?php if (isWebAdmin()): ?>
    background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
    <?php elseif (isClinicAdmin()): ?>
    background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
    <?php else: ?>
    background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
    <?php endif; ?>
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    transition: all 0.3s ease;
    position: relative;
}

.chat-widget-toggle:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(0,0,0,0.4);
}

.chat-widget-toggle .badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 12px;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: bold;
    min-width: 20px;
}

.chat-widget-panel {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 350px;
    max-height: 500px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    display: none;
    flex-direction: column;
    overflow: hidden;
}

.chat-widget-panel.active {
    display: flex;
}

.chat-widget-header {
    <?php if (isWebAdmin()): ?>
    background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
    <?php elseif (isClinicAdmin()): ?>
    background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
    <?php else: ?>
    background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
    <?php endif; ?>
    color: white;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-widget-header h6 {
    margin: 0;
    font-weight: 600;
}

.chat-widget-body {
    flex: 1;
    overflow-y: auto;
    max-height: 400px;
}

.chat-conversation-item {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    gap: 10px;
}

.chat-conversation-item:hover {
    background: #f8f9fa;
}

.chat-conversation-item.has-unread {
    background: #e3f2fd;
}

.chat-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #666;
    flex-shrink: 0;
    overflow: hidden;
}

.chat-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.chat-conversation-info {
    flex: 1;
    min-width: 0;
}

.chat-conversation-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chat-conversation-time {
    font-size: 11px;
    color: #999;
}

.chat-unread-badge {
    background: #dc3545;
    color: white;
    border-radius: 10px;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: bold;
    flex-shrink: 0;
}

.chat-widget-footer {
    padding: 12px 15px;
    border-top: 1px solid #eee;
    text-align: center;
}

.chat-widget-footer a {
    color: #1976d2;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
}

.chat-widget-footer a:hover {
    text-decoration: underline;
}

.chat-empty-state {
    padding: 40px 20px;
    text-align: center;
    color: #999;
}

.chat-empty-state i {
    font-size: 48px;
    margin-bottom: 10px;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .chat-widget-panel {
        width: calc(100vw - 40px);
        right: -10px;
    }
}
</style>

<div class="chat-widget-container">
    <button class="chat-widget-toggle" id="chatWidgetToggle" onclick="toggleChatWidget()">
        <i class="bi bi-chat-dots-fill"></i>
        <?php if ($total_unread > 0): ?>
        <span class="badge"><?php echo $total_unread > 99 ? '99+' : $total_unread; ?></span>
        <?php endif; ?>
    </button>
    
    <div class="chat-widget-panel" id="chatWidgetPanel">
        <div class="chat-widget-header">
            <h6><i class="bi bi-chat-dots"></i> Messages</h6>
            <button class="btn btn-sm btn-link text-white p-0" onclick="toggleChatWidget()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <div class="chat-widget-body">
            <?php if (empty($conversations)): ?>
                <div class="chat-empty-state">
                    <i class="bi bi-chat-dots"></i>
                    <p class="mb-0">No conversations yet</p>
                    <?php if (isPatient()): ?>
                    <small class="text-muted">Start a conversation with a clinic</small>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                <div class="chat-conversation-item <?php echo $conv['unread_count'] > 0 ? 'has-unread' : ''; ?>" 
                     onclick="window.location.href='chat.php?conv=<?php echo $conv['id']; ?>'">
                    <div class="chat-avatar">
                        <?php if (!empty($conv['profile_photo']) && file_exists('../' . $conv['profile_photo'])): ?>
                            <img src="../<?php echo htmlspecialchars($conv['profile_photo']); ?>" alt="Avatar">
                        <?php else: ?>
                            <?php 
                            $name = isPatient() ? $conv['clinic_name'] : $conv['patient_name'];
                            echo strtoupper(substr($name, 0, 1)); 
                            ?>
                        <?php endif; ?>
                    </div>
                    <div class="chat-conversation-info">
                        <div class="chat-conversation-name">
                            <?php echo isPatient() ? htmlspecialchars($conv['clinic_name']) : htmlspecialchars($conv['patient_name']); ?>
                            <?php if (isPatient() && isset($conv['is_available'])): ?>
                                <span class="availability-indicator" style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: <?php echo $conv['is_available'] ? '#2ecc71' : '#95a5a6'; ?>; margin-left: 6px;"></span>
                            <?php endif; ?>
                        </div>
                        <div class="chat-conversation-time">
                            <?php echo date('M d, g:i A', strtotime($conv['last_message_at'])); ?>
                        </div>
                    </div>
                    <?php if ($conv['unread_count'] > 0): ?>
                    <span class="chat-unread-badge"><?php echo $conv['unread_count']; ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="chat-widget-footer">
            <a href="chat.php">View All Messages</a>
        </div>
    </div>
</div>

<script>
function toggleChatWidget() {
    const panel = document.getElementById('chatWidgetPanel');
    panel.classList.toggle('active');
}

// Close chat widget when clicking outside
document.addEventListener('click', function(event) {
    const container = document.querySelector('.chat-widget-container');
    const toggle = document.getElementById('chatWidgetToggle');
    
    if (container && !container.contains(event.target)) {
        document.getElementById('chatWidgetPanel').classList.remove('active');
    }
});

// Refresh chat widget every 30 seconds
setInterval(function() {
    // Only refresh if widget is open
    if (document.getElementById('chatWidgetPanel').classList.contains('active')) {
        location.reload();
    }
}, 30000);
</script>
