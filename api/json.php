<?php
/**
 * JSON REST API for Certificate Data
 * MediArchive - Digital Medical Certificate System
 */

require_once '../config.php';

header('Content-Type: application/json');

$cert_id = $_GET['cert_id'] ?? '';

if (empty($cert_id)) {
    echo json_encode([
        'error' => 'cert_id parameter is required'
    ]);
    exit;
}

$conn = getDBConnection();

$stmt = $conn->prepare("SELECT c.*, cl.clinic_name, cl.address as clinic_address,
                       u.full_name as patient_name, u.email as patient_email, u.phone as patient_phone,
                       p.patient_code, p.date_of_birth, p.gender
                       FROM certificates c
                       JOIN clinics cl ON c.clinic_id = cl.id
                       JOIN patients p ON c.patient_id = p.id
                       JOIN users u ON p.user_id = u.id
                       WHERE c.cert_id = ?");
$stmt->bind_param("s", $cert_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $cert = $result->fetch_assoc();
    
    // Format the response
    $response = [
        'status' => 'success',
        'certificate' => [
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
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Certificate not found'
    ], JSON_PRETTY_PRINT);
}

$stmt->close();
$conn->close();
?>

