<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

try {
    SecurityManager::verifyCSRFToken();
    $db = Database::getInstance();
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    if ($role !== 'clinic_admin') {
        throw new Exception('Only doctors can add appointment results');
    }

    $appointment_id = intval($_POST['appointment_id'] ?? 0);
    $diagnosis = sanitizeInput($_POST['diagnosis'] ?? '');
    $treatment_notes = sanitizeInput($_POST['treatment_notes'] ?? '');
    $prescriptions = sanitizeInput($_POST['prescriptions'] ?? '');
    $follow_up_instructions = sanitizeInput($_POST['follow_up_instructions'] ?? '');

    if (!$appointment_id) {
        throw new Exception('Appointment ID required');
    }

    // Verify appointment belongs to this clinic
    $appointment = $db->fetch(
        "SELECT a.*, c.id as clinic_id FROM appointments a 
         JOIN clinics c ON a.clinic_id = c.id 
         WHERE a.id = ? AND c.user_id = ?",
        [$appointment_id, $user_id]
    );

    if (!$appointment) {
        throw new Exception('Appointment not found or unauthorized');
    }

    // Check if result already exists
    $existing = $db->fetch("SELECT id FROM appointment_results WHERE appointment_id = ?", [$appointment_id]);

    if ($existing) {
        // Update existing
        $db->execute(
            "UPDATE appointment_results SET diagnosis = ?, treatment_notes = ?, prescriptions = ?, follow_up_instructions = ? WHERE appointment_id = ?",
            [$diagnosis, $treatment_notes, $prescriptions, $follow_up_instructions, $appointment_id]
        );
    } else {
        // Create new
        $db->execute(
            "INSERT INTO appointment_results (appointment_id, doctor_id, patient_id, diagnosis, treatment_notes, prescriptions, follow_up_instructions) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$appointment_id, $appointment['clinic_id'], $appointment['patient_id'], $diagnosis, $treatment_notes, $prescriptions, $follow_up_instructions]
        );
    }

    // Mark appointment as completed
    $db->execute("UPDATE appointments SET status = 'completed' WHERE id = ?", [$appointment_id]);

    // Notify patient
    $patient = $db->fetch("SELECT user_id FROM patients WHERE id = ?", [$appointment['patient_id']]);
    if ($patient) {
        $conn = getDBConnection();
        notifyUser($conn, intval($patient['user_id']), 
            'Appointment Results Available', 
            'Your appointment results have been added. View them in your appointments page.',
            'my_appointments.php');
        $conn->close();
    }

    AuditLogger::log('APPOINTMENT_RESULT_ADDED', 'appointment', $appointment_id, ['clinic_id' => $appointment['clinic_id']]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

