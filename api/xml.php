<?php
/**
 * XML Export for Certificate Data
 * MediArchive - Digital Medical Certificate System
 */

require_once '../config.php';

header('Content-Type: application/xml');

$cert_id = $_GET['cert_id'] ?? '';

if (empty($cert_id)) {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<error>cert_id parameter is required</error>';
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

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<certificate>';

if ($result->num_rows > 0) {
    $cert = $result->fetch_assoc();
    
    echo '<cert_id>' . htmlspecialchars($cert['cert_id']) . '</cert_id>';
    echo '<patient>';
    echo '<name>' . htmlspecialchars($cert['patient_name']) . '</name>';
    echo '<code>' . htmlspecialchars($cert['patient_code']) . '</code>';
    echo '<email>' . htmlspecialchars($cert['patient_email']) . '</email>';
    echo '<phone>' . htmlspecialchars($cert['patient_phone']) . '</phone>';
    echo '<date_of_birth>' . $cert['date_of_birth'] . '</date_of_birth>';
    echo '<gender>' . htmlspecialchars($cert['gender']) . '</gender>';
    echo '</patient>';
    
    echo '<clinic>';
    echo '<name>' . htmlspecialchars($cert['clinic_name']) . '</name>';
    echo '<address>' . htmlspecialchars($cert['clinic_address']) . '</address>';
    echo '</clinic>';
    
    echo '<issued_by>' . htmlspecialchars($cert['issued_by']) . '</issued_by>';
    echo '<doctor_license>' . htmlspecialchars($cert['doctor_license']) . '</doctor_license>';
    echo '<issue_date>' . $cert['issue_date'] . '</issue_date>';
    echo '<expiry_date>' . $cert['expiry_date'] . '</expiry_date>';
    echo '<purpose>' . htmlspecialchars($cert['purpose']) . '</purpose>';
    echo '<diagnosis>' . htmlspecialchars($cert['diagnosis']) . '</diagnosis>';
    echo '<recommendations>' . htmlspecialchars($cert['recommendations']) . '</recommendations>';
    echo '<status>' . htmlspecialchars($cert['status']) . '</status>';
    echo '<created_at>' . $cert['created_at'] . '</created_at>';
} else {
    echo '<error>Certificate not found</error>';
}

echo '</certificate>';

$stmt->close();
$conn->close();
?>

