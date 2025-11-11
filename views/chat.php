<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Determine user type and get conversations
$conversations = [];
$patient = null;
$clinic = null;

if (isPatient()) {
    // Get patient's conversations with clinics
    $patient = $db->fetch("SELECT id FROM patients WHERE user_id = ?", [$user_id]);
    if ($patient) {
        $conversations = $db->fetchAll("
            SELECT cc.*, cl.clinic_name, cl.is_available, u.full_name as doctor_name, u.profile_photo,
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
            SELECT cc.*, u.full_name as patient_name, p.patient_code, p.is_available, u.profile_photo,
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
$partner_avatar = '';

if ($selected_conversation_id) {
    // Verify user has access to this conversation
    $conv = $db->fetch("SELECT * FROM chat_conversations WHERE id = ?", [$selected_conversation_id]);
    if ($conv) {
        $has_access = false;
        if (isPatient() && $patient && $conv['patient_id'] == $patient['id']) {
            $has_access = true;
            $clinic_info = $db->fetch("SELECT cl.clinic_name, u.full_name, u.profile_photo FROM clinics cl JOIN users u ON cl.user_id = u.id WHERE cl.id = ?", [$conv['clinic_id']]);
            $conversation_partner = $clinic_info['clinic_name'] . ' - ' . $clinic_info['full_name'];
            $partner_avatar = $clinic_info['profile_photo'] ?? '';
        } else if (isClinicAdmin() && $clinic && $conv['clinic_id'] == $clinic['id']) {
            $has_access = true;
            $patient_info = $db->fetch("SELECT u.full_name, p.patient_code, u.profile_photo FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id = ?", [$conv['patient_id']]);
            $conversation_partner = $patient_info['full_name'] . ' (' . $patient_info['patient_code'] . ')';
            $partner_avatar = $patient_info['profile_photo'] ?? '';
        } else if (isWebAdmin()) {
            $has_access = true;
            $clinic_info = $db->fetch("SELECT cl.clinic_name, u1.full_name as doctor_name FROM clinics cl JOIN users u1 ON cl.user_id = u1.id WHERE cl.id = ?", [$conv['clinic_id']]);
            $patient_info = $db->fetch("SELECT u.full_name, p.patient_code, u.profile_photo FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id = ?", [$conv['patient_id']]);
            $conversation_partner = $patient_info['full_name'] . ' ↔ ' . $clinic_info['clinic_name'];
            $partner_avatar = $patient_info['profile_photo'] ?? '';
        }
        
        if ($has_access) {
            $messages = $db->fetchAll("
                SELECT cm.*, u.full_name as sender_name, u.profile_photo
                FROM chat_messages cm
                JOIN users u ON cm.sender_id = u.id
                WHERE cm.conversation_id = ?
                ORDER BY cm.created_at ASC
            ", [$selected_conversation_id]);
            
            // Mark messages as read (not for web admin)
            if (!isWebAdmin()) {
                $db->execute("UPDATE chat_messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?", [$selected_conversation_id, $user_id]);
            }
        }
    }
}

// Support direct messages between any users
$mode = 'direct'; // Always use direct messaging
$selected_dm_user = isset($_GET['dm']) ? intval($_GET['dm']) : null;
$selected_dm_pair = null;
if (isWebAdmin() && isset($_GET['dm_pair'])) {
    $pairParts = explode('-', $_GET['dm_pair']);
    if (count($pairParts) === 2) {
        $a = intval($pairParts[0]);
        $b = intval($pairParts[1]);
        if ($a > 0 && $b > 0) {
            $normalizedA = min($a, $b);
            $normalizedB = max($a, $b);
            $selected_dm_pair = $normalizedA . '-' . $normalizedB;
        }
    }
}

try {
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
} catch (Exception $e) {
    // ignore
}

if (isWebAdmin()) {
    $dmPartners = $db->fetchAll("
        SELECT 
            pairs.user_a,
            pairs.user_b,
            pairs.last_at,
            ua.full_name AS user_a_name,
            ua.role AS user_a_role,
            ub.full_name AS user_b_name,
            ub.role AS user_b_role,
            (SELECT message FROM direct_messages dm2
             WHERE (dm2.sender_id = pairs.user_a AND dm2.receiver_id = pairs.user_b)
                OR (dm2.sender_id = pairs.user_b AND dm2.receiver_id = pairs.user_a)
             ORDER BY dm2.created_at DESC LIMIT 1) AS last_message
        FROM (
            SELECT 
                LEAST(sender_id, receiver_id) AS user_a,
                GREATEST(sender_id, receiver_id) AS user_b,
                MAX(created_at) AS last_at
            FROM direct_messages
            GROUP BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)
        ) pairs
        JOIN users ua ON ua.id = pairs.user_a
        JOIN users ub ON ub.id = pairs.user_b
        ORDER BY pairs.last_at DESC
    ");
} else {
    $dmPartners = $db->fetchAll("
        SELECT u.id, u.full_name, u.role, u.profile_photo,
            (SELECT message FROM direct_messages d2
             WHERE (d2.sender_id = u.id AND d2.receiver_id = ?) OR (d2.sender_id = ? AND d2.receiver_id = u.id)
             ORDER BY d2.created_at DESC LIMIT 1) AS last_message,
            (SELECT created_at FROM direct_messages d3
             WHERE (d3.sender_id = u.id AND d3.receiver_id = ?) OR (d3.sender_id = ? AND d3.receiver_id = u.id)
             ORDER BY d3.created_at DESC LIMIT 1) AS last_at,
            (SELECT COUNT(*) FROM direct_messages d4
             WHERE d4.sender_id = u.id AND d4.receiver_id = ? AND d4.is_read = 0) AS unread
        FROM users u
        WHERE u.id <> ?
          AND EXISTS (
              SELECT 1 FROM direct_messages d
              WHERE (d.sender_id = u.id AND d.receiver_id = ?) OR (d.sender_id = ? AND d.receiver_id = u.id)
          )
        ORDER BY last_at DESC
    ", [$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
}

$allUsers = $db->fetchAll("SELECT id, full_name, role FROM users WHERE id <> ? ORDER BY full_name", [$user_id]);

$dmPartner = null;
$dmMessages = [];
if (isWebAdmin()) {
    if ($selected_dm_pair) {
        $pairInfo = null;
        foreach ($dmPartners as $pair) {
            $key = $pair['user_a'] . '-' . $pair['user_b'];
            if ($key === $selected_dm_pair) {
                $pairInfo = $pair;
                break;
            }
        }
        if ($pairInfo) {
            $userA = intval($pairInfo['user_a']);
            $userB = intval($pairInfo['user_b']);
            $dmMessages = $db->fetchAll("
                SELECT dm.*, u.full_name as sender_name, u.profile_photo
                FROM direct_messages dm
                JOIN users u ON dm.sender_id = u.id
                WHERE (dm.sender_id = ? AND dm.receiver_id = ?) OR (dm.sender_id = ? AND dm.receiver_id = ?)
                ORDER BY dm.created_at ASC
            ", [$userA, $userB, $userB, $userA]);
            $dmPartner = [
                'display_name' => ($pairInfo['user_a_name'] ?? 'User') . ' ↔ ' . ($pairInfo['user_b_name'] ?? 'User'),
                'role_label' => str_replace('_', ' ', $pairInfo['user_a_role'] ?? '') . ' ↔ ' . str_replace('_', ' ', $pairInfo['user_b_role'] ?? ''),
                'is_moderation' => true,
                'user_a' => $userA,
                'user_b' => $userB
            ];
            $mode = 'direct';
        }
    }
} elseif ($selected_dm_user) {
    $dmPartner = $db->fetch("SELECT id, full_name, role, profile_photo FROM users WHERE id = ?", [$selected_dm_user]);
    if ($dmPartner) {
        $dmMessages = $db->fetchAll("
            SELECT dm.*, u.full_name as sender_name, u.profile_photo
            FROM direct_messages dm
            JOIN users u ON dm.sender_id = u.id
            WHERE (dm.sender_id = ? AND dm.receiver_id = ?) OR (dm.sender_id = ? AND dm.receiver_id = ?)
            ORDER BY dm.created_at ASC
        ", [$user_id, $selected_dm_user, $selected_dm_user, $user_id]);
        $db->execute("UPDATE direct_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?", [$selected_dm_user, $user_id]);
        $mode = 'direct';
    } else {
        $selected_dm_user = null;
    }
}

require_once 'includes/role_styles.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
body {
    overflow: hidden;
}
.chat-wrapper {
    height: 100vh;
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
    display: flex;
    justify-content: space-between;
    align-items: center;
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
.chat-header h5 {
    margin: 0;
    font-weight: 600;
    color: #333;
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
/* Chat widget still visible on chat page but positioned differently */
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-0">
            <div class="chat-wrapper">
        <!-- Conversations Sidebar -->
        <div class="conversations-sidebar">
            <div class="conversations-header">
                <div>
                    <h5 class="mb-0"><i class="bi bi-chat-dots"></i> Messages</h5>
                </div>
                <div>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newDirectModal">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
            </div>
            
            <div class="conversations-list">
                <?php if (empty($dmPartners)): ?>
                    <div class="empty-state p-4">
                        <i class="bi bi-people"></i>
                        <p>No direct messages yet</p>
                    </div>
                    <?php else: ?>
                        <?php if (isWebAdmin()): ?>
                            <?php foreach ($dmPartners as $pair): ?>
                                <?php
                                    $pairKey = $pair['user_a'] . '-' . $pair['user_b'];
                                    $activeClass = ($selected_dm_pair === $pairKey) ? 'active' : '';
                                    $label = ($pair['user_a_name'] ?? 'User') . ' ↔ ' . ($pair['user_b_name'] ?? 'User');
                                    $lastMessage = $pair['last_message'] ?? 'No messages yet';
                                    $lastAt = $pair['last_at'] ?? null;
                                ?>
                                <div class="conversation-item <?php echo $activeClass; ?>"
                                     onclick="window.location.href='chat.php?mode=direct&amp;dm_pair=<?php echo htmlspecialchars($pairKey); ?>'">
                                    <div class="conversation-avatar">
                                        <?php echo strtoupper(substr($pair['user_a_name'] ?? 'U', 0, 1)); ?>
                                    </div>
                                    <div class="conversation-info">
                                        <div class="conversation-name"><?php echo htmlspecialchars($label); ?></div>
                                        <div class="conversation-preview"><?php echo htmlspecialchars(substr($lastMessage, 0, 40)); ?></div>
                                    </div>
                                    <div class="conversation-meta">
                                        <?php if (!empty($lastAt)): ?>
                                        <div class="conversation-time"><?php echo date('M d', strtotime($lastAt)); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php foreach ($dmPartners as $dm): ?>
                            <div class="conversation-item <?php echo $selected_dm_user == $dm['id'] ? 'active' : ''; ?>"
                                 onclick="window.location.href='chat.php?mode=direct&dm=<?php echo $dm['id']; ?>'">
                                <div class="conversation-avatar">
                                    <?php if (!empty($dm['profile_photo']) && file_exists('../' . $dm['profile_photo'])): ?>
                                    <img src="../<?php echo htmlspecialchars($dm['profile_photo']); ?>" alt="Avatar">
                                    <?php else: ?>
                                    <?php echo strtoupper(substr($dm['full_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="conversation-info">
                                    <div class="conversation-name"><?php echo htmlspecialchars($dm['full_name']); ?> <span class="text-muted small">(<?php echo str_replace('_',' ', $dm['role']); ?>)</span></div>
                                    <div class="conversation-preview"><?php echo htmlspecialchars(substr($dm['last_message'] ?? 'No messages yet', 0, 40)); ?></div>
                                </div>
                                <div class="conversation-meta">
                                    <?php if (!empty($dm['last_at'])): ?>
                                    <div class="conversation-time"><?php echo date('M d', strtotime($dm['last_at'])); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($dm['unread'])): ?>
                                    <span class="unread-badge"><?php echo $dm['unread']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Main Area -->
        <div class="chat-main">
            <?php if ($dmPartner): ?>
                <div class="chat-header">
                    <div class="chat-header-info">
                        <?php if (!empty($dmPartner['is_moderation'])): ?>
                        <div class="chat-header-avatar">
                            <i class="bi bi-shield-eye"></i>
                        </div>
                        <h5><?php echo htmlspecialchars($dmPartner['display_name']); ?> <span class="text-muted small">Moderation View</span></h5>
                        <?php else: ?>
                        <div class="chat-header-avatar">
                            <?php if (!empty($dmPartner['profile_photo']) && file_exists('../' . $dmPartner['profile_photo'])): ?>
                            <img src="../<?php echo htmlspecialchars($dmPartner['profile_photo']); ?>" alt="Avatar">
                            <?php else: ?>
                            <?php echo strtoupper(substr($dmPartner['full_name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <h5><?php echo htmlspecialchars($dmPartner['full_name']); ?> <span class="text-muted small">(<?php echo str_replace('_',' ', $dmPartner['role']); ?>)</span></h5>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="messages-container" id="messagesContainer">
                    <?php if (empty($dmMessages)): ?>
                    <div class="empty-state">
                        <i class="bi bi-chat"></i>
                        <p>Say hello to start the conversation.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($dmMessages as $msg): ?>
                        <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                            <div class="message-avatar">
                                <?php if (!empty($msg['profile_photo']) && file_exists('../' . $msg['profile_photo'])): ?>
                                <img src="../<?php echo htmlspecialchars($msg['profile_photo']); ?>" alt="<?php echo htmlspecialchars($msg['sender_name']); ?>">
                                <?php else: ?>
                                <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="message-content">
                                <?php if ($msg['sender_id'] != $user_id): ?>
                                <div class="message-sender"><?php echo htmlspecialchars($msg['sender_name']); ?></div>
                                <?php endif; ?>
                                <div class="message-bubble">
                                    <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                </div>
                                <div class="message-time"><?php echo date('M d, g:i A', strtotime($msg['created_at'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($dmPartner['is_moderation'])): ?>
                <div class="border-top bg-light text-muted small px-3 py-2">
                    Moderation mode – sending messages is disabled.
                </div>
                <?php else: ?>
                <div class="message-input-area">
                    <form method="post" action="../api/dm_send.php" class="message-input-wrapper">
                        <?php echo SecurityManager::getCSRFField(); ?>
                        <input type="hidden" name="to" value="<?php echo $dmPartner['id']; ?>">
                        <input type="text" name="message" placeholder="Type a message" required>
                        <button class="btn-send" type="submit"><i class="bi bi-send"></i></button>
                    </form>
                    <?php if (isPatient() && $dmPartner && $dmPartner['role'] === 'clinic_admin'): ?>
                    <p class="text-muted small mt-2 mb-0">Please be mindful of the doctor's working hours (9:00&nbsp;AM – 5:00&nbsp;PM) when sending messages.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-chat"></i>
                    <p>Select a conversation to view messages.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
            </div>
        </main>
    </div>
</div>


        <!-- New Direct Message Modal -->
        <div class="modal fade" id="newDirectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">New Message</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="post" action="../api/dm_send.php">
                        <div class="modal-body">
                            <?php echo SecurityManager::getCSRFField(); ?>
                            <div class="mb-3">
                                <label class="form-label">Select User</label>
                                <select name="to" class="form-select" required>
                                    <option value="">Choose user...</option>
                                    <?php foreach ($allUsers as $u): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name'] . ' (' . str_replace('_',' ', $u['role']) . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-control" rows="3" required placeholder="Type your message here..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

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
    }
});

removeAttachment?.addEventListener('click', function() {
    attachmentInput.value = '';
    attachmentPreview.style.display = 'none';
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
            window.location.href = 'chat.php?conv=' + data.conversation_id;
        } else {
            alert('Failed to send message: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error sending message');
        console.error(error);
    });
});

// Auto-refresh messages every 10 seconds
<?php if ($selected_conversation_id): ?>
setInterval(() => {
    location.reload();
}, 10000);
<?php endif; ?>
</script>
</body>
</html>
