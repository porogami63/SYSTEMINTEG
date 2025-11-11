<?php
/**
 * Delete User API
 * Allows web admins to delete users (doctors, patients)
 * MediArchive - Digital Medical Certificate System
 */

require_once '../config.php';

// Only allow web admins
if (!isLoggedIn() || !isWebAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$user_id = intval($_POST['user_id'] ?? 0);

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'User ID is required']);
    exit;
}

// Prevent deleting yourself
if ($user_id == $_SESSION['user_id']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'You cannot delete your own account']);
    exit;
}

try {
    $db = Database::getInstance();
    $admin_id = $_SESSION['user_id'];
    
    // Get user details before deletion
    $user = $db->fetch("SELECT id, username, full_name, role FROM users WHERE id = ?", [$user_id]);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    // Prevent deleting web admins
    if ($user['role'] === 'web_admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Cannot delete web admin accounts']);
        exit;
    }
    
    // Log the deletion before deleting
    AuditLogger::log(
        'DELETE_USER',
        'user',
        $user_id,
        [
            'deleted_user' => $user['username'],
            'deleted_user_name' => $user['full_name'],
            'deleted_user_role' => $user['role'],
            'deleted_by' => $admin_id
        ]
    );
    
    // Delete related records (cascading will handle most, but we log first)
    // The database foreign keys with ON DELETE CASCADE will handle:
    // - patients table
    // - clinics table
    // - certificates (via clinic_id)
    // - appointments (via patient_id)
    // - payments (via user_id)
    // - notifications (via user_id)
    // - chat messages and conversations
    // - audit logs (ON DELETE SET NULL)
    
    // Delete the user (cascading will handle related records)
    $db->execute("DELETE FROM users WHERE id = ?", [$user_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);
    
} catch (Exception $e) {
    error_log('User deletion error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: Unable to delete user'
    ]);
}

