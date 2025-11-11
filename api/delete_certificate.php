<?php
/**
 * Delete Certificate API
 * Allows doctors and web admins to delete certificates
 * MediArchive - Digital Medical Certificate System
 */

require_once '../config.php';

// Allow clinic admins, web admins, and patients (for their own certificates)
if (!isLoggedIn() || (!isClinicAdmin() && !isWebAdmin() && !isPatient())) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$cert_id = intval($_POST['cert_id'] ?? 0);

if (!$cert_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Certificate ID is required']);
    exit;
}

try {
    $db = Database::getInstance();
    $user_id = $_SESSION['user_id'];
    
    // Get certificate details
    $certificate = $db->fetch("SELECT c.*, cl.user_id as clinic_user_id 
                               FROM certificates c 
                               JOIN clinics cl ON c.clinic_id = cl.id 
                               WHERE c.id = ?", [$cert_id]);
    
    if (!$certificate) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Certificate not found']);
        exit;
    }
    
    // Check permissions
    // Clinic admins can only delete their own certificates
    // Web admins can delete any certificate
    // Patients can only delete their own certificates
    if (isClinicAdmin() && $certificate['clinic_user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You can only delete your own certificates']);
        exit;
    }
    
    if (isPatient()) {
        // Check if the certificate belongs to this patient
        $patient = $db->fetch("SELECT id FROM patients WHERE user_id = ?", [$user_id]);
        if (!$patient || $certificate['patient_id'] != $patient['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'You can only delete your own certificates']);
            exit;
        }
    }
    
    // Log the deletion before deleting
    AuditLogger::log(
        'DELETE_CERTIFICATE',
        'certificate',
        $cert_id,
        [
            'cert_id' => $certificate['cert_id'],
            'patient_id' => $certificate['patient_id'],
            'deleted_by' => $user_id
        ]
    );
    
    // Delete related records first (due to foreign key constraints)
    $db->execute("DELETE FROM certificate_notes WHERE certificate_id = ?", [$cert_id]);
    
    // Delete the certificate
    $db->execute("DELETE FROM certificates WHERE id = ?", [$cert_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Certificate deleted successfully'
    ]);
    
} catch (Exception $e) {
    error_log('Certificate deletion error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: Unable to delete certificate'
    ]);
}
