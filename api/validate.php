<?php
/**
 * QR Code Validation Page
 * This page is accessed when users scan the QR code
 */

require_once '../config.php';

$cert_id = $_GET['cert_id'] ?? '';

if (empty($cert_id)) {
    die("Invalid certificate ID");
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
    
    // Log verification
    $stmt2 = $conn->prepare("INSERT INTO verifications (cert_id, ip_address, user_agent) VALUES (?, ?, ?)");
    $stmt2->bind_param("iss", $cert['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
    $stmt2->execute();
    $stmt2->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Certificate Validation - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 40px 0;
}
.validation-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    max-width: 600px;
    margin: 0 auto;
}
.valid-badge {
    background: #28a745;
    color: white;
    padding: 10px 20px;
    border-radius: 50px;
    display: inline-block;
    margin-bottom: 20px;
}
</style>
</head>
<body>
<div class="container">
    <div class="validation-card p-5">
        <div class="text-center">
            <div class="valid-badge">
                <i class="bi bi-check-circle-fill"></i> CERTIFICATE VALID
            </div>
        </div>
        
        <h2 class="text-center mb-4">Medical Certificate Verification</h2>
        
        <div class="alert alert-success">
            <strong>Certificate ID:</strong> <?php echo htmlspecialchars($cert['cert_id']); ?>
        </div>
        
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title">Patient Information</h6>
                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($cert['patient_name']); ?></p>
                <p class="mb-1"><strong>Patient Code:</strong> <?php echo htmlspecialchars($cert['patient_code']); ?></p>
                <p class="mb-1"><strong>Date of Birth:</strong> <?php echo $cert['date_of_birth']; ?></p>
                <p class="mb-0"><strong>Gender:</strong> <?php echo $cert['gender']; ?></p>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title">Certificate Details</h6>
                <p class="mb-1"><strong>Clinic:</strong> <?php echo htmlspecialchars($cert['clinic_name']); ?></p>
                <p class="mb-1"><strong>Doctor:</strong> <?php echo htmlspecialchars($cert['issued_by']); ?></p>
                <p class="mb-1"><strong>Issue Date:</strong> <?php echo $cert['issue_date']; ?></p>
                <p class="mb-1"><strong>Purpose:</strong> <?php echo htmlspecialchars($cert['purpose']); ?></p>
                <p class="mb-0"><strong>Status:</strong> <span class="badge bg-success"><?php echo strtoupper($cert['status']); ?></span></p>
            </div>
        </div>
        
        <div class="text-center">
            <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">Visit MediArchive</a>
        </div>
    </div>
</div>
</body>
</html>
<?php
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Certificate Validation - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 40px 0;
}
.validation-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    max-width: 600px;
    margin: 0 auto;
}
.invalid-badge {
    background: #dc3545;
    color: white;
    padding: 10px 20px;
    border-radius: 50px;
    display: inline-block;
    margin-bottom: 20px;
}
</style>
</head>
<body>
<div class="container">
    <div class="validation-card p-5">
        <div class="text-center">
            <div class="invalid-badge">
                <i class="bi bi-x-circle-fill"></i> CERTIFICATE INVALID
            </div>
        </div>
        
        <h2 class="text-center mb-4">Medical Certificate Verification</h2>
        
        <div class="alert alert-danger">
            <strong>Certificate ID:</strong> <?php echo htmlspecialchars($cert_id); ?><br>
            This certificate could not be found in our system or may have been revoked.
        </div>
        
        <div class="text-center">
            <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">Visit MediArchive</a>
        </div>
    </div>
</div>
</body>
</html>
<?php
}

$stmt->close();
$conn->close();
?>

