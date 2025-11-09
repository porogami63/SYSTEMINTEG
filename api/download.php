<?php
/**
 * Download Certificate as PDF
 * MediArchive - Digital Medical Certificate System
 */

require_once '../config.php';

if (!isLoggedIn()) {
    die("Unauthorized access");
}

$cert_id = intval($_GET['id'] ?? 0);

try {
    $db = Database::getInstance();
    $cert = $db->fetch("SELECT c.*, cl.clinic_name, cl.address as clinic_address, cl.signature_path,
                       u.full_name as patient_name, u.email as patient_email,
                       p.patient_code, p.date_of_birth, p.gender
                       FROM certificates c
                       JOIN clinics cl ON c.clinic_id = cl.id
                       JOIN patients p ON c.patient_id = p.id
                       JOIN users u ON p.user_id = u.id
                       WHERE c.id = ?", [$cert_id]);
    if (!$cert) {
        die("Certificate not found");
    }
    
    // Audit log
    AuditLogger::log('DOWNLOAD_CERTIFICATE', 'certificate', $cert_id, ['cert_id' => $cert['cert_id']]);
    
    // Generate PDF using PdfGenerator
    require_once '../includes/PdfGenerator.php';
    $temp_file = TEMP_DIR . 'cert_' . $cert_id . '_' . time() . '.pdf';
    $pdf_path = PdfGenerator::generateCertificate($cert, $temp_file);
    
    if ($pdf_path && file_exists($pdf_path)) {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="certificate_' . $cert['cert_id'] . '.pdf"');
        header('Content-Length: ' . filesize($pdf_path));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Output file
        readfile($pdf_path);
        
        // Clean up temp file
        @unlink($pdf_path);
        exit;
    } else {
        die('PDF generation failed. Please contact administrator.');
    }
    
} catch (Exception $e) {
    error_log('Certificate download error: ' . $e->getMessage());
    die('Server error: Unable to generate certificate. Please try again later.');
}

