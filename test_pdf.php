<?php
/**
 * Test PDF Generation
 * URL: http://localhost/SYSTEMINTEG/test_pdf.php?id=1
 */

require_once 'config.php';

$cert_id = intval($_GET['id'] ?? 1);

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
        die("Certificate not found. Try: test_pdf.php?id=1");
    }
    
    echo "<h2>Testing PDF Generation</h2>";
    echo "<p>Certificate ID: " . htmlspecialchars($cert['cert_id']) . "</p>";
    echo "<p>Patient: " . htmlspecialchars($cert['patient_name']) . "</p>";
    
    // Test PDF generation
    require_once 'includes/PdfGenerator.php';
    $temp_file = TEMP_DIR . 'test_cert_' . time() . '.pdf';
    
    echo "<p>Generating PDF...</p>";
    
    $pdf_path = PdfGenerator::generateCertificate($cert, $temp_file);
    
    if ($pdf_path && file_exists($pdf_path)) {
        $size = filesize($pdf_path);
        echo "<p style='color: green;'>✓ PDF generated successfully!</p>";
        echo "<p>File size: " . number_format($size / 1024, 2) . " KB</p>";
        echo "<p><a href='api/download.php?id=" . $cert_id . "' target='_blank'>Download PDF</a></p>";
        
        // Clean up
        @unlink($pdf_path);
    } else {
        echo "<p style='color: red;'>✗ PDF generation failed</p>";
        echo "<p>Check error log: C:\\xampp\\apache\\logs\\error.log</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
