<?php
/**
 * Delete Payment/Transaction API
 * Allows web admins to delete/refund transactions
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

$payment_id = intval($_POST['payment_id'] ?? 0);

if (!$payment_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Payment ID is required']);
    exit;
}

try {
    $db = Database::getInstance();
    $admin_id = $_SESSION['user_id'];
    
    // Get payment details before deletion
    $payment = $db->fetch("SELECT * FROM payments WHERE id = ?", [$payment_id]);
    
    if (!$payment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Payment not found']);
        exit;
    }
    
    // Log the deletion before deleting
    AuditLogger::log(
        'DELETE_PAYMENT',
        'payment',
        $payment_id,
        [
            'transaction_id' => $payment['transaction_id'],
            'payment_type' => $payment['payment_type'],
            'reference_id' => $payment['reference_id'],
            'amount' => $payment['amount'],
            'payment_status' => $payment['payment_status'],
            'deleted_by' => $admin_id
        ]
    );
    
    // Delete the payment
    $db->execute("DELETE FROM payments WHERE id = ?", [$payment_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment transaction deleted successfully'
    ]);
    
} catch (Exception $e) {
    error_log('Payment deletion error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: Unable to delete payment'
    ]);
}

