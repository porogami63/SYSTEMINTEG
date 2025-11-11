<?php
require_once '../config.php';

if (!isLoggedIn() || !isClinicAdmin()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

$user_id = $_SESSION['user_id'];
try {
    $db = Database::getInstance();
    // Get clinic info
    $clinic = $db->fetch("SELECT c.* FROM clinics c WHERE c.user_id = ?", [$user_id]);

    // Get all patients
    $patients = $db->fetchAll("SELECT p.id, p.patient_code, u.full_name, u.email FROM patients p JOIN users u ON p.user_id = u.id");
} catch (Exception $e) {
    $clinic = null;
    $patients = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$clinic) {
        $error = 'Clinic profile not found. Please complete your clinic profile first.';
    }
    $patient_id = intval($_POST['patient_id']);
    $issued_by = sanitizeInput($_POST['issued_by']);
    $doctor_license = sanitizeInput($_POST['doctor_license']);
    $issue_date = sanitizeInput($_POST['issue_date']);
    $expiry_date = sanitizeInput($_POST['expiry_date']);
    $purpose = sanitizeInput($_POST['purpose']);
    $diagnosis = sanitizeInput($_POST['diagnosis']);
    $recommendations = sanitizeInput($_POST['recommendations']);
    
    // Payment fields
    $payment_required = isset($_POST['payment_required']) ? 1 : 0;
    $payment_amount = $payment_required ? floatval($_POST['payment_amount'] ?? 0) : 0;
    
    // Verify patient exists in patients table
    $patient_exists = false;
    $res = $db->fetch("SELECT id FROM patients WHERE id = ?", [$patient_id]);
    if ($res) {
        $patient_exists = true;
    }

    if (!$patient_exists) {
        $error = 'Selected patient does not exist. Please refresh and select a valid patient.';
    }

    if (empty($error)) {
    $cert_id = generateCertID();
    $clinic_id = $clinic ? $clinic['id'] : null;

    // attach saved signature if present
    $doctor_signature_path = !empty($clinic['signature_path']) ? $clinic['signature_path'] : null;

    $db->execute("INSERT INTO certificates (cert_id, clinic_id, patient_id, issued_by, doctor_license, issue_date, expiry_date, purpose, diagnosis, recommendations, doctor_signature_path, payment_required, payment_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [$cert_id, $clinic_id, $patient_id, $issued_by, $doctor_license, $issue_date, $expiry_date, $purpose, $diagnosis, $recommendations, $doctor_signature_path, $payment_required, $payment_amount]);
    }
    
    if (empty($error)) {
        $cert_id_db = $db->lastInsertId();
        // Generate QR code
        require_once '../includes/qr_generator.php';
        $qr_path = generateQRCode($cert_id, $cert_id_db);
        
        // Update certificate with QR path
        $db->execute("UPDATE certificates SET file_path = ? WHERE id = ?", [$qr_path, $cert_id_db]);
        
        $success = "Certificate created successfully! Cert ID: " . $cert_id;
        
        // Audit log
        AuditLogger::log(
            'CREATE_CERTIFICATE',
            'certificate',
            $cert_id_db,
            ['cert_id' => $cert_id, 'patient_id' => $patient_id, 'from_request' => false]
        );
        
        // notify patient
        $ud = $db->fetch("SELECT u.id as user_id FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id = ?", [$patient_id]);
        if ($ud) {
            // notifyUser expects a mysqli connection; use existing helper for backward compatibility
            $mysqli = getDBConnection();
            notifyUser($mysqli, intval($ud['user_id']), 'New Medical Certificate', 'A new medical certificate has been issued to you.', 'my_certificates.php');
            $mysqli->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Certificate - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<?php include 'includes/role_styles.php'; ?>
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
                        <form method="POST" id="createCertForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Patient <span class="text-danger">*</span></label>
                                    <select class="form-select" name="patient_id" required>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                        <option value="<?php echo $patient['id']; ?>">
                                            <?php echo htmlspecialchars($patient['patient_code'] . ' - ' . $patient['full_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
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
                                    <input type="text" class="form-control" name="issued_by" value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" required readonly style="background-color: #f8f9fa;">
                                    <small class="text-muted">Auto-filled from your profile</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Doctor License Number</label>
                                    <input type="text" class="form-control" name="doctor_license" value="<?php echo htmlspecialchars($clinic['license_number'] ?? ''); ?>" readonly style="background-color: #f8f9fa;">
                                    <small class="text-muted">Auto-filled from clinic profile</small>
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
                            
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="bi bi-cash-coin"></i> Payment Settings</h6>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="payment_required" id="payment_required">
                                        <label class="form-check-label" for="payment_required">
                                            <strong>Require Payment</strong> - Patient must pay before accessing this certificate
                                        </label>
                                    </div>
                                    <div id="payment_amount_field" style="display:none;">
                                        <label class="form-label">Payment Amount (₱) <span class="text-danger">*</span></label>
                                        <input type="number" name="payment_amount" class="form-control" step="0.01" min="0" placeholder="0.00">
                                        <small class="text-muted">Enter the amount patient needs to pay</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" id="publishBtn">
                                    <i class="bi bi-save"></i> Publish Certificate
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
<!-- Publish Confirmation Modal -->
<div class="modal fade" id="publishConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-shield-lock"></i> Confirm Medical Attestation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info">
            <h6 class="alert-heading"><i class="bi bi-info-circle"></i> Medical Professional's Attestation</h6>
            <p class="mb-0 small">This attestation is a legal declaration of the accuracy of the medical information provided.</p>
        </div>
        
        <div class="border rounded p-3 mb-3" style="background-color: #f8f9fa;">
            <p class="mb-2"><strong>I, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'the undersigned medical professional'); ?>, hereby solemnly declare and attest that:</strong></p>
            <ul class="small mb-2">
                <li>All medical findings, diagnoses, and recommendations contained in this certificate are based on my personal examination and professional medical judgment.</li>
                <li>The information provided herein is true, accurate, and complete to the best of my medical knowledge and professional belief.</li>
                <li>This certificate is issued in good faith, without fraud, misrepresentation, or perjury.</li>
                <li>I understand that any false statement or misrepresentation may subject me to professional disciplinary action and legal consequences.</li>
                <li>I accept full professional responsibility for the contents of this medical certificate.</li>
            </ul>
            <p class="mb-0 small text-muted fst-italic">This attestation is made in accordance with medical ethics and professional standards of practice.</p>
        </div>
        
        <?php if (!empty($clinic['signature_path'])): ?>
        <div class="text-center mb-3 p-3 border rounded">
            <p class="small text-muted mb-2">Digital Signature on File:</p>
            <img src="../<?php echo htmlspecialchars($clinic['signature_path']); ?>" alt="Doctor Signature" style="height:80px; border: 1px solid #dee2e6; padding: 10px; background: white;">
            <div class="text-success small mt-2"><i class="bi bi-check-circle-fill"></i> Signature will be automatically applied to certificate</div>
        </div>
        <?php else: ?>
        <div class="alert alert-danger d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>Signature Required!</strong><br>
                <small>You must upload your digital signature before publishing certificates. Go to Profile → Edit Profile to upload your signature.</small>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="form-check p-3 border rounded" style="background-color: #fff3cd;">
            <input class="form-check-input" type="checkbox" id="attestCheck" style="width: 20px; height: 20px;">
            <label class="form-check-label ms-2" for="attestCheck">
                <strong>I have read and agree to the above attestation.</strong><br>
                <small class="text-muted">By checking this box, I affirm that I understand the legal and professional implications of this attestation.</small>
            </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmPublishBtn" disabled>Confirm & Publish</button>
      </div>
    </div>
  </div>
 </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const publishBtn = document.getElementById('publishBtn');
const attestCheck = document.getElementById('attestCheck');
const confirmPublishBtn = document.getElementById('confirmPublishBtn');

publishBtn.addEventListener('click', function(){
    const modal = new bootstrap.Modal(document.getElementById('publishConfirmModal'));
    modal.show();
});

attestCheck.addEventListener('change', function(){
    confirmPublishBtn.disabled = !this.checked;
});

confirmPublishBtn.addEventListener('click', function(){
    <?php if (empty($clinic['signature_path'])): ?>
    alert('Signature is required. Please upload your signature in Profile → Edit Profile.');
    const modalEl = document.getElementById('publishConfirmModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal.hide();
    return;
    <?php endif; ?>
    document.getElementById('createCertForm').submit();
});

// Payment field toggle
document.getElementById('payment_required').addEventListener('change', function() {
    const amountField = document.getElementById('payment_amount_field');
    const amountInput = document.querySelector('input[name="payment_amount"]');
    if (this.checked) {
        amountField.style.display = 'block';
        amountInput.required = true;
    } else {
        amountField.style.display = 'none';
        amountInput.required = false;
        amountInput.value = '';
    }
});
</script>
</body>
</html>

