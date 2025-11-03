<?php
/**
 * XML Export for Certificate Data
 * MediArchive - Digital Medical Certificate System
 * Uses XmlHandler OOP class for XML generation
 */

require_once '../config.php';

header('Content-Type: application/xml');

$cert_id = $_GET['cert_id'] ?? '';

if (empty($cert_id)) {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<error>cert_id parameter is required</error>';
    exit;
}

try {
    $db = Database::getInstance();
    $cert = $db->fetch("SELECT c.*, cl.clinic_name, cl.address as clinic_address,
                       u.full_name as patient_name, u.email as patient_email, u.phone as patient_phone,
                       p.patient_code, p.date_of_birth, p.gender
                       FROM certificates c
                       JOIN clinics cl ON c.clinic_id = cl.id
                       JOIN patients p ON c.patient_id = p.id
                       JOIN users u ON p.user_id = u.id
                       WHERE c.cert_id = ?", [$cert_id]);

    if ($cert) {
        // Build structured array for XML conversion using OOP XmlHandler
        $certData = [
            'cert_id' => $cert['cert_id'],
            'patient' => [
                'name' => $cert['patient_name'],
                'code' => $cert['patient_code'],
                'email' => $cert['patient_email'],
                'phone' => $cert['patient_phone'],
                'date_of_birth' => $cert['date_of_birth'],
                'gender' => $cert['gender']
            ],
            'clinic' => [
                'name' => $cert['clinic_name'],
                'address' => $cert['clinic_address']
            ],
            'issued_by' => $cert['issued_by'],
            'doctor_license' => $cert['doctor_license'],
            'issue_date' => $cert['issue_date'],
            'expiry_date' => $cert['expiry_date'],
            'purpose' => $cert['purpose'],
            'diagnosis' => $cert['diagnosis'],
            'recommendations' => $cert['recommendations'],
            'status' => $cert['status'],
            'created_at' => $cert['created_at']
        ];
        
        // Use XmlHandler OOP class to generate XML
        echo XmlHandler::arrayToXml($certData, 'certificate');
    } else {
        $errorData = ['error' => 'Certificate not found'];
        echo XmlHandler::arrayToXml($errorData, 'certificate');
    }
} catch (Exception $e) {
    $errorData = ['error' => 'Server error: ' . $e->getMessage()];
    echo XmlHandler::arrayToXml($errorData, 'certificate');
}
?>

