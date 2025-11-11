<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Verify CSRF token
SecurityManager::verifyCSRFToken();

// Rate limiting
$clientIP = SecurityManager::getClientIP();
$userId = $_SESSION['user_id'];
if (!SecurityManager::checkRateLimit('payment', 10, 60, $userId . ':' . $clientIP)) {
    echo json_encode(['success' => false, 'error' => 'Rate limit exceeded']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Validate input
    $paymentType = InputValidator::validate($_POST['payment_type'] ?? '', 'string', ['allowed_values' => ['certificate', 'appointment']]);
    $referenceId = InputValidator::validate($_POST['reference_id'] ?? '', 'int', ['min' => 1]);
    $amount = InputValidator::validate($_POST['amount'] ?? '', 'float', ['min' => 0.01]);
    $paymentMethod = InputValidator::validate($_POST['payment_method'] ?? 'cash', 'string', ['allowed_values' => ['cash', 'credit_card', 'debit_card', 'gcash', 'paymaya', 'bank_transfer']]);
    
    if (!$paymentType['valid'] || !$referenceId['valid'] || !$amount['valid'] || !$paymentMethod['valid']) {
        echo json_encode(['success' => false, 'error' => 'Invalid payment parameters']);
        exit;
    }
    
    $type = $paymentType['value'];
    $refId = $referenceId['value'];
    $amt = $amount['value'];
    $method = $paymentMethod['value'];
    
    // Verify the reference exists and belongs to user
    if ($type === 'certificate') {
        $cert = $db->fetch("SELECT c.*, p.user_id FROM certificates c JOIN patients p ON c.patient_id = p.id WHERE c.id = ?", [$refId]);
        if (!$cert || ($cert['user_id'] != $userId && !isClinicAdmin())) {
            echo json_encode(['success' => false, 'error' => 'Certificate not found or access denied']);
            exit;
        }
        // Check if payment already exists
        $existingPayment = $db->fetch("SELECT * FROM payments WHERE payment_type = 'certificate' AND reference_id = ? AND payment_status = 'paid'", [$refId]);
        if ($existingPayment) {
            echo json_encode(['success' => false, 'error' => 'Payment already completed for this certificate']);
            exit;
        }
    } else {
        $appt = $db->fetch("SELECT a.*, p.user_id FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.id = ?", [$refId]);
        if (!$appt || ($appt['user_id'] != $userId && !isClinicAdmin())) {
            echo json_encode(['success' => false, 'error' => 'Appointment not found or access denied']);
            exit;
        }
        // Check if payment already exists
        $existingPayment = $db->fetch("SELECT * FROM payments WHERE payment_type = 'appointment' AND reference_id = ? AND payment_status = 'paid'", [$refId]);
        if ($existingPayment) {
            echo json_encode(['success' => false, 'error' => 'Payment already completed for this appointment']);
            exit;
        }
    }
    
    // Generate transaction ID
    $transactionId = 'TXN-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
    
    // For demo purposes, we'll mark as paid immediately
    // In production, integrate with payment gateway (GCash, PayMaya, Stripe, etc.)
    $paymentStatus = 'paid';
    $paymentDate = date('Y-m-d H:i:s');
    
    // Payment details
    $paymentDetails = json_encode([
        'method' => $method,
        'ip_address' => $clientIP,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'timestamp' => $paymentDate
    ]);
    
    // Insert payment record
    $db->execute(
        "INSERT INTO payments (user_id, payment_type, reference_id, amount, payment_method, payment_status, transaction_id, payment_date, payment_details) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [$userId, $type, $refId, $amt, $method, $paymentStatus, $transactionId, $paymentDate, $paymentDetails]
    );
    
    $paymentId = $db->lastInsertId();
    
    // Log audit
    AuditLogger::log(
        'PAYMENT_PROCESSED',
        'payment',
        $paymentId,
        [
            'payment_type' => $type,
            'reference_id' => $refId,
            'amount' => $amt,
            'method' => $method,
            'transaction_id' => $transactionId
        ]
    );
    
    // Send notification to patient
    $title = $type === 'certificate' ? 'Certificate Payment Successful' : 'Appointment Payment Successful';
    $message = "Payment of ₱" . number_format($amt, 2) . " has been processed successfully. Transaction ID: " . $transactionId;
    $link = $type === 'certificate' ? 'view_certificate.php?id=' . $refId : 'my_appointments.php';
    
    $db->execute(
        "INSERT INTO notifications (user_id, title, message, link, category) VALUES (?, ?, ?, ?, 'payment')",
        [$userId, $title, $message, $link]
    );
    
    // Notify doctor/clinic when payment is received
    if ($type === 'certificate') {
        $cert = $db->fetch("SELECT c.clinic_id, c.cert_id FROM certificates c WHERE c.id = ?", [$refId]);
        if ($cert && $cert['clinic_id']) {
            $clinic = $db->fetch("SELECT user_id FROM clinics WHERE id = ?", [$cert['clinic_id']]);
            if ($clinic && $clinic['user_id']) {
                $patient = $db->fetch("SELECT u.full_name FROM patients p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?", [$userId]);
                $patientName = $patient ? $patient['full_name'] : 'A patient';
                $doctorTitle = 'Payment Received - Certificate';
                $doctorMessage = $patientName . " has paid ₱" . number_format($amt, 2) . " for certificate " . $cert['cert_id'] . ". Transaction ID: " . $transactionId;
                $doctorLink = 'certificates.php';
                
                $db->execute(
                    "INSERT INTO notifications (user_id, title, message, link, category) VALUES (?, ?, ?, ?, 'payment')",
                    [$clinic['user_id'], $doctorTitle, $doctorMessage, $doctorLink]
                );
            }
        }
    } else {
        // Appointment payment notification to doctor
        $appt = $db->fetch("SELECT a.clinic_id, a.appointment_date, a.time_slot FROM appointments a WHERE a.id = ?", [$refId]);
        if ($appt && $appt['clinic_id']) {
            $clinic = $db->fetch("SELECT user_id FROM clinics WHERE id = ?", [$appt['clinic_id']]);
            if ($clinic && $clinic['user_id']) {
                $patient = $db->fetch("SELECT u.full_name FROM patients p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?", [$userId]);
                $patientName = $patient ? $patient['full_name'] : 'A patient';
                $doctorTitle = 'Payment Received - Appointment';
                $doctorMessage = $patientName . " has paid ₱" . number_format($amt, 2) . " for appointment on " . $appt['appointment_date'] . " at " . substr($appt['time_slot'], 0, 5) . ". Transaction ID: " . $transactionId;
                $doctorLink = 'clinic_appointments.php';
                
                $db->execute(
                    "INSERT INTO notifications (user_id, title, message, link, category) VALUES (?, ?, ?, ?, 'payment')",
                    [$clinic['user_id'], $doctorTitle, $doctorMessage, $doctorLink]
                );
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'transaction_id' => $transactionId,
        'payment_id' => $paymentId,
        'message' => 'Payment processed successfully'
    ]);
    
} catch (Exception $e) {
    error_log('Payment processing error: ' . $e->getMessage());
    SecurityManager::logSecurityEvent('PAYMENT_ERROR', [
        'error' => $e->getMessage(),
        'user_id' => $userId,
        'ip' => $clientIP
    ]);
    echo json_encode(['success' => false, 'error' => 'Payment processing failed']);
}
