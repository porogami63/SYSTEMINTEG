<?php
require_once '../config.php';

if (!isLoggedIn() || !isClinicAdmin()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get clinic info
$stmt = $conn->prepare("SELECT c.* FROM clinics c WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$clinic = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get all patients
$stmt = $conn->prepare("SELECT p.id, p.patient_code, u.full_name, u.email FROM patients p JOIN users u ON p.user_id = u.id");
$stmt->execute();
$patients = $stmt->get_result();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $issued_by = sanitizeInput($_POST['issued_by']);
    $doctor_license = sanitizeInput($_POST['doctor_license']);
    $issue_date = sanitizeInput($_POST['issue_date']);
    $expiry_date = sanitizeInput($_POST['expiry_date']);
    $purpose = sanitizeInput($_POST['purpose']);
    $diagnosis = sanitizeInput($_POST['diagnosis']);
    $recommendations = sanitizeInput($_POST['recommendations']);
    
    $cert_id = generateCertID();
    $clinic_id = $clinic['id'];
    
    $stmt = $conn->prepare("INSERT INTO certificates (cert_id, clinic_id, patient_id, issued_by, doctor_license, issue_date, expiry_date, purpose, diagnosis, recommendations) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siisssssss", $cert_id, $clinic_id, $patient_id, $issued_by, $doctor_license, $issue_date, $expiry_date, $purpose, $diagnosis, $recommendations);
    
    if ($stmt->execute()) {
        $cert_id_db = $conn->insert_id;
        // Generate QR code
        require_once '../includes/qr_generator.php';
        $qr_path = generateQRCode($cert_id, $cert_id_db);
        
        // Update certificate with QR path
        $stmt2 = $conn->prepare("UPDATE certificates SET file_path = ? WHERE id = ?");
        $stmt2->bind_param("si", $qr_path, $cert_id_db);
        $stmt2->execute();
        $stmt2->close();
        
        $success = "Certificate created successfully! Cert ID: " . $cert_id;
    } else {
        $error = "Failed to create certificate";
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Certificate - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
.sidebar {
    min-height: 100vh;
    background: linear-gradient(180deg, #2e7d32 0%, #1b5e20 100%);
}
.sidebar .nav-link {
    color: white;
    padding: 12px 20px;
    margin: 5px 0;
}
.sidebar .nav-link:hover {
    background: rgba(255,255,255,0.1);
}
.sidebar .nav-link.active {
    background: rgba(255,255,255,0.2);
}
.main-content {
    padding: 30px;
}
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="main-content">
                <h2 class="mb-4"><i class="bi bi-file-earmark-plus"></i> Create Medical Certificate</h2>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Patient <span class="text-danger">*</span></label>
                                    <select class="form-select" name="patient_id" required>
                                        <option value="">Select Patient</option>
                                        <?php while ($patient = $patients->fetch_assoc()): ?>
                                        <option value="<?php echo $patient['id']; ?>">
                                            <?php echo htmlspecialchars($patient['patient_code'] . ' - ' . $patient['full_name']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="issue_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Issued By (Doctor Name) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="issued_by" value="<?php echo $_SESSION['full_name']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Doctor License Number</label>
                                    <input type="text" class="form-control" name="doctor_license">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="date" class="form-control" name="expiry_date">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Purpose <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="purpose" placeholder="e.g., Sick Leave, Medical Clearance" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Diagnosis</label>
                                <textarea class="form-control" name="diagnosis" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Recommendations</label>
                                <textarea class="form-control" name="recommendations" rows="3"></textarea>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Create Certificate
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

