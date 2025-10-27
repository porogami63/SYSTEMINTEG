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

$conn = getDBConnection();

$stmt = $conn->prepare("SELECT c.*, cl.clinic_name, cl.address as clinic_address,
                       u.full_name as patient_name, u.email as patient_email,
                       p.patient_code, p.date_of_birth, p.gender
                       FROM certificates c
                       JOIN clinics cl ON c.clinic_id = cl.id
                       JOIN patients p ON c.patient_id = p.id
                       JOIN users u ON p.user_id = u.id
                       WHERE c.id = ?");
$stmt->bind_param("i", $cert_id);
$stmt->execute();
$cert = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$cert) {
    die("Certificate not found");
}

// Generate simple HTML certificate for print
?>
<!DOCTYPE html>
<html>
<head>
<style>
body {
    font-family: Arial, sans-serif;
    padding: 40px;
}
.header {
    border-bottom: 3px solid #2e7d32;
    padding-bottom: 20px;
    margin-bottom: 30px;
    text-align: center;
}
h1 {
    color: #2e7d32;
    margin: 0;
}
.info-section {
    margin-bottom: 20px;
}
.info-row {
    display: flex;
    margin-bottom: 10px;
}
.info-label {
    font-weight: bold;
    width: 150px;
}
.footer {
    margin-top: 50px;
    text-align: center;
    color: #666;
}
</style>
</head>
<body>
<div class="header">
    <h1>MEDICAL CERTIFICATE</h1>
    <p>Certificate ID: <?php echo htmlspecialchars($cert['cert_id']); ?></p>
</div>

<div class="info-section">
    <h3>Patient Information</h3>
    <div class="info-row">
        <span class="info-label">Name:</span>
        <span><?php echo htmlspecialchars($cert['patient_name']); ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Patient Code:</span>
        <span><?php echo htmlspecialchars($cert['patient_code']); ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Date of Birth:</span>
        <span><?php echo $cert['date_of_birth']; ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Gender:</span>
        <span><?php echo $cert['gender']; ?></span>
    </div>
</div>

<div class="info-section">
    <h3>Issued By</h3>
    <div class="info-row">
        <span class="info-label">Clinic:</span>
        <span><?php echo htmlspecialchars($cert['clinic_name']); ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Doctor:</span>
        <span><?php echo htmlspecialchars($cert['issued_by']); ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">License No:</span>
        <span><?php echo htmlspecialchars($cert['doctor_license']); ?></span>
    </div>
</div>

<div class="info-section">
    <h3>Certificate Details</h3>
    <div class="info-row">
        <span class="info-label">Issue Date:</span>
        <span><?php echo $cert['issue_date']; ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Expiry Date:</span>
        <span><?php echo $cert['expiry_date'] ?? 'N/A'; ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Purpose:</span>
        <span><?php echo htmlspecialchars($cert['purpose']); ?></span>
    </div>
</div>

<?php if ($cert['diagnosis']): ?>
<div class="info-section">
    <h3>Diagnosis</h3>
    <p><?php echo nl2br(htmlspecialchars($cert['diagnosis'])); ?></p>
</div>
<?php endif; ?>

<?php if ($cert['recommendations']): ?>
<div class="info-section">
    <h3>Recommendations</h3>
    <p><?php echo nl2br(htmlspecialchars($cert['recommendations'])); ?></p>
</div>
<?php endif; ?>

<div class="footer">
    <p>This certificate was issued digitally and can be verified online</p>
    <p>Issued on: <?php echo $cert['created_at']; ?></p>
</div>
</body>
</html>
<script>
window.print();
</script>

