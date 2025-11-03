<?php
/**
 * Notifications API Endpoint
 * Handles fetching notifications and marking them as read
 */
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    $db = Database::getInstance();
    
    if ($method === 'GET') {
        // Fetch unread notifications count
        if ($action === 'count') {
            $count = $db->fetch("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0", [$user_id]);
            echo json_encode(['count' => intval($count['count'] ?? 0)]);
        }
        // Fetch recent notifications
        else {
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $notifications = $db->fetchAll(
                "SELECT id, title, message, link, is_read, created_at 
                 FROM notifications 
                 WHERE user_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT ?",
                [$user_id, $limit]
            );
            
            // Format timestamps
            foreach ($notifications as &$notif) {
                $notif['created_at'] = date('M d, Y h:i A', strtotime($notif['created_at']));
                $notif['is_read'] = (bool)$notif['is_read'];
            }
            
            echo json_encode(['notifications' => $notifications]);
        }
    }
    elseif ($method === 'POST') {
        // Mark notification as read
        if ($action === 'mark_read') {
            $notif_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
            if ($notif_id > 0) {
                $db->execute("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", [$notif_id, $user_id]);
                echo json_encode(['success' => true]);
            } else {
                // Mark all as read
                $db->execute("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$user_id]);
                echo json_encode(['success' => true]);
            }
        }
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>

