<?php
/**
 * Patient Availability API
 * Allows patients to toggle their availability status
 */
require_once '../config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || !isPatient()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$is_available = isset($_POST['is_available']) ? intval($_POST['is_available']) : 0;
$user_id = $_SESSION['user_id'];

try {
    $db = Database::getInstance();
    
    // Update patient availability
    $db->execute("UPDATE patients SET is_available = ? WHERE user_id = ?", [$is_available, $user_id]);
    
    // Log to audit trail
    AuditLogger::log(
        'UPDATE_AVAILABILITY',
        'patient',
        $user_id,
        ['is_available' => $is_available]
    );
    
    echo json_encode(['success' => true, 'is_available' => $is_available]);
    
} catch (Exception $e) {
    error_log('Patient availability error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
