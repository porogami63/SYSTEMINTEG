<?php
require_once '../config.php';
require_once '../includes/qr_generator.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$cert_id = intval($_GET['id']);
try {
    $db = Database::getInstance();
    // Get certificate details
    $certificate = $db->fetch("SELECT c.*, cl.clinic_name, cl.address as clinic_address, cl.signature_path, cl.seal_path,
                       u.full_name as patient_name, u.email as patient_email, u.phone as patient_phone,
                       p.patient_code, p.date_of_birth, p.gender
                       FROM certificates c
                       JOIN clinics cl ON c.clinic_id = cl.id
                       JOIN patients p ON c.patient_id = p.id
                       JOIN users u ON p.user_id = u.id
                       WHERE c.id = ?", [$cert_id]);

    if (!$certificate) {
        die("Certificate not found");
    }

    // Check if payment is required and if paid
    $paymentRequired = $certificate['payment_required'] ?? 0;
    $paymentAmount = $certificate['payment_amount'] ?? 0;
    $paymentMade = false;
    
    if ($paymentRequired && isPatient()) {
        // Check if payment exists
        $payment = $db->fetch(
            "SELECT * FROM payments WHERE payment_type = 'certificate' AND reference_id = ? AND user_id = ? AND payment_status = 'paid'",
            [$cert_id, $_SESSION['user_id']]
        );
        $paymentMade = !empty($payment);
    }

    // Audit log - certificate viewed
    AuditLogger::log(
        'VIEW_CERTIFICATE',
        'certificate',
        $cert_id,
        ['cert_id' => $certificate['cert_id']]
    );

    $qr_image = getQRCodeImage($certificate['cert_id'], $certificate['id']);
} catch (Exception $e) {
    die('Server error: ' . $e->getMessage());
}
// Handle certificate notes (clinic admins only)
try {
    if (isClinicAdmin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_text'])) {
        $noteText = trim($_POST['note_text']);
        $isInternal = isset($_POST['is_internal']) ? 1 : 0;
        if ($noteText !== '') {
            $db->execute(
                "INSERT INTO certificate_notes (certificate_id, user_id, note, is_internal) VALUES (?, ?, ?, ?)",
                [$certificate['id'], $_SESSION['user_id'], $noteText, $isInternal]
            );
            AuditLogger::log('ADD_CERTIFICATE_NOTE', 'certificate', $cert_id, ['user_id' => $_SESSION['user_id']]);
        }
        // Refresh to prevent resubmission
        redirect('view_certificate.php?id=' . $cert_id);
    }

    // Load notes: patients see only non-internal, clinic admins see all
    if (isClinicAdmin()) {
        $notes = $db->fetchAll(
            "SELECT cn.*, u.full_name FROM certificate_notes cn JOIN users u ON cn.user_id = u.id WHERE certificate_id = ? ORDER BY cn.created_at DESC",
            [$certificate['id']]
        );
    } else {
        $notes = $db->fetchAll(
            "SELECT cn.*, u.full_name FROM certificate_notes cn JOIN users u ON cn.user_id = u.id WHERE certificate_id = ? AND cn.is_internal = 0 ORDER BY cn.created_at DESC",
            [$certificate['id']]
        );
    }
} catch (Exception $e) {
    $notes = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Certificate <?php echo $certificate['cert_id']; ?> - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
.certificate-container {
    background: white;
    padding: 40px 60px;
    max-width: 900px;
    margin: 0 auto;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    font-family: "Times New Roman", Times, serif;
}
.certificate-header {
    text-align: center;
    border-bottom: 3px double #1565c0;
    padding-bottom: 15px;
    margin-bottom: 25px;
}
.hospital-name {
    font-size: 28px;
    font-weight: bold;
    color: #1565c0;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.hospital-subtitle {
    font-size: 11px;
    color: #555;
    margin: 5px 0;
}
.cert-number {
    text-align: right;
    font-size: 11px;
    color: #666;
    margin-bottom: 20px;
}
.certificate-title {
    text-align: center;
    font-size: 22px;
    font-weight: bold;
    text-decoration: underline;
    margin: 30px 0 25px 0;
    color: #1565c0;
}
.certification-text {
    text-align: justify;
    margin: 20px 0;
    font-size: 14px;
    line-height: 1.6;
}
.patient-name-highlight {
    font-weight: bold;
    text-transform: uppercase;
}
.details-section {
    margin: 25px 0;
    padding: 20px;
    background-color: #f8f9fa;
    border-left: 4px solid #1565c0;
}
.detail-row {
    margin: 10px 0;
    font-size: 13px;
}
.detail-label {
    font-weight: bold;
    display: inline-block;
    width: 160px;
    color: #1565c0;
}
.validity-section {
    margin: 20px 0;
    font-size: 13px;
    font-style: italic;
}
.signature-section {
    margin-top: 60px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}
.signature-box {
    text-align: center;
}
.signature-image {
    height: 60px;
    margin-bottom: 10px;
}
.signature-label {
    font-size: 11px;
    color: #666;
    margin-top: 5px;
}
.signature-line {
    border-top: 2px solid #000;
    margin: 10px auto;
    width: 250px;
    padding-top: 8px;
}
.doctor-name {
    font-weight: bold;
    font-size: 14px;
}
.doctor-title {
    font-size: 12px;
    color: #555;
}
.license-number {
    font-size: 11px;
    color: #666;
    margin-top: 3px;
}
.verification-box {
    margin-top: 30px;
    padding: 12px;
    border: 1px dashed #1565c0;
    background-color: #e3f2fd;
    font-size: 11px;
    text-align: center;
}
.footer-note {
    margin-top: 20px;
    font-size: 10px;
    color: #888;
    text-align: center;
}
@media print {
    .no-print {
        display: none;
    }
    body {
        background: white;
    }
}
</style>
</head>
<body class="bg-light">
<div class="container my-5">
    
    <?php if ($paymentRequired && isPatient() && !$paymentMade): ?>
    <!-- Payment Required Notice -->
    <div class="alert alert-warning shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill fs-1 me-3"></i>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-2"><i class="bi bi-lock-fill"></i> Payment Required</h5>
                <p class="mb-2">This certificate requires payment before you can view or download it.</p>
                <p class="mb-3"><strong>Amount Due: ₱<?php echo number_format($paymentAmount, 2); ?></strong></p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal">
                    <i class="bi bi-credit-card"></i> Pay Now
                </button>
            </div>
        </div>
    </div>
    
    <!-- Blurred Certificate Preview -->
    <div style="filter: blur(8px); pointer-events: none; user-select: none; position: relative;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10; background: rgba(255,255,255,0.95); padding: 30px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <i class="bi bi-lock-fill" style="font-size: 48px; color: #dc3545;"></i>
            <h4 class="mt-3">Payment Required</h4>
            <p>Pay ₱<?php echo number_format($paymentAmount, 2); ?> to unlock</p>
        </div>
    <?php endif; ?>
    
    <div class="certificate-container" <?php if ($paymentRequired && isPatient() && !$paymentMade) echo 'style="opacity: 0.3;"'; ?>>
        <!-- Certificate Header -->
        <div class="certificate-header">
            <div class="hospital-name">GREY SLOAN MEMORIAL HOSPITAL</div>
            <div class="hospital-subtitle">OOO</div>
            <div class="hospital-subtitle">Digital Medical Certificate System</div>
        </div>
        
        <div class="cert-number">Certificate No: <?php echo htmlspecialchars($certificate['cert_id']); ?></div>
        
        <div class="certificate-title">MEDICAL CERTIFICATE</div>
        
        <div class="certification-text">
            <p>This is to certify that <span class="patient-name-highlight"><?php echo strtoupper(htmlspecialchars($certificate['patient_name'])); ?></span> 
            (Patient Code: <strong><?php echo htmlspecialchars($certificate['patient_code']); ?></strong>) was examined and treated at this clinic on 
            <strong><?php echo date('F d, Y', strtotime($certificate['issue_date'])); ?></strong>.</p>
        </div>
        
        <div class="details-section">
            <div class="detail-row">
                <span class="detail-label">Patient Name:</span>
                <span><?php echo htmlspecialchars($certificate['patient_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Patient Code:</span>
                <span><?php echo htmlspecialchars($certificate['patient_code']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date of Birth:</span>
                <span><?php echo $certificate['date_of_birth'] ?? 'N/A'; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Gender:</span>
                <span><?php echo $certificate['gender'] ?? 'N/A'; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Purpose:</span>
                <span><?php echo htmlspecialchars($certificate['purpose']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Medical Findings:</span>
                <span><?php echo $certificate['diagnosis'] ? htmlspecialchars($certificate['diagnosis']) : 'yes'; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Medical Advice:</span>
                <span><?php echo $certificate['recommendations'] ? htmlspecialchars($certificate['recommendations']) : 'yes'; ?></span>
            </div>
        </div>
        
        <?php if ($certificate['expiry_date']): ?>
        <div class="validity-section">
            <strong>Validity Period:</strong> This certificate is valid from <?php echo date('F d, Y', strtotime($certificate['issue_date'])); ?> 
            until <?php echo date('F d, Y', strtotime($certificate['expiry_date'])); ?>.
        </div>
        <?php endif; ?>

        <!-- Certificate Notes -->
        <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <h6 class="text-muted mb-0">NOTES</h6>
                <?php if (isClinicAdmin()): ?>
                <button class="btn btn-sm btn-outline-primary no-print" type="button" data-bs-toggle="collapse" data-bs-target="#addNoteForm" aria-expanded="false">
                    <i class="bi bi-plus"></i> Add Note
                </button>
                <?php endif; ?>
            </div>
            <div class="mt-3">
                <?php if (!empty($notes)): ?>
                    <ul class="list-group">
                        <?php foreach ($notes as $n): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?php echo htmlspecialchars($n['full_name']); ?></strong>
                                    <span class="text-muted small ms-2"><?php echo htmlspecialchars($n['created_at']); ?></span>
                                    <?php if (!empty($n['is_internal'])): ?>
                                        <span class="badge bg-secondary ms-2">Internal</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mt-2"><?php echo nl2br(htmlspecialchars($n['note'])); ?></div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-muted">No notes yet.</div>
                <?php endif; ?>
            </div>

            <?php if (isClinicAdmin()): ?>
            <div class="collapse mt-3" id="addNoteForm">
                <form method="post">
                    <div class="mb-2">
                        <textarea class="form-control" name="note_text" rows="3" placeholder="Add a note..." required></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_internal" id="isInternal" checked>
                        <label class="form-check-label" for="isInternal">Internal (visible to clinic staff only)</label>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Save Note</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box" style="text-align: left;">
                <div><strong>Date:</strong> <?php echo date('F d, Y', strtotime($certificate['issue_date'])); ?></div>
            </div>
            <div class="signature-box">
                <?php if (!empty($certificate['signature_path'])): ?>
                <img src="../<?php echo htmlspecialchars($certificate['signature_path']); ?>" alt="Doctor Signature" class="signature-image">
                <div class="signature-label">Doctor's Signature</div>
                <?php else: ?>
                <div style="height: 60px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                    <span style="font-size: 10px; color: #999;">Image not found or type unknown</span>
                </div>
                <div class="signature-label">Doctor's Signature</div>
                <?php endif; ?>
                <div class="signature-line">
                    <div class="doctor-name"><?php echo htmlspecialchars($certificate['issued_by']); ?></div>
                    <div class="doctor-title">Licensed Medical Practitioner</div>
                    <?php if ($certificate['doctor_license']): ?>
                    <div class="license-number">License No: <?php echo htmlspecialchars($certificate['doctor_license']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- QR Code Section -->
        <div style="text-align: center; margin-top: 30px;">
            <p style="font-size: 12px; color: #666; margin-bottom: 10px;">Scan QR code to verify certificate</p>
            <img src="<?php echo $qr_image; ?>" alt="QR Code" style="width: 150px; height: 150px;">
        </div>
        
        <!-- Verification Box -->
        <div class="verification-box">
            <strong>VERIFICATION:</strong> This certificate can be verified online at MediArchive System<br>
            Certificate ID: <strong><?php echo htmlspecialchars($certificate['cert_id']); ?></strong> | Issued: <?php echo date('F d, Y', strtotime($certificate['issue_date'])); ?>
        </div>
        
        <!-- Footer Note -->
        <div class="footer-note">
            This is a computer-generated medical certificate issued through MediArchive Digital Certificate System.<br>
            This document is valid without signature if verified through the system.
        </div>
    </div>

    <!-- Actions -->
    <div class="text-center mt-4 no-print">
        <a href="my_certificates.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Certificates
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer"></i> Print Certificate
        </button>
        <a href="../api/download.php?id=<?php echo $certificate['id']; ?>" class="btn btn-success">
            <i class="bi bi-download"></i> Download PDF
        </a>
        <a href="../api/json.php?cert_id=<?php echo urlencode($certificate['cert_id']); ?>" class="btn btn-info" target="_blank">
            <i class="bi bi-code"></i> View JSON
        </a>
        <a href="../api/xml.php?cert_id=<?php echo urlencode($certificate['cert_id']); ?>" class="btn btn-warning" target="_blank">
            <i class="bi bi-file-code"></i> View XML
        </a>
        <?php if (isClinicAdmin() || isWebAdmin()): ?>
        <button id="deleteCertBtn" class="btn btn-danger" data-cert-id="<?php echo $certificate['id']; ?>" data-cert-name="<?php echo htmlspecialchars($certificate['cert_id']); ?>">
            <i class="bi bi-trash"></i> Delete Certificate
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Modal -->
<?php if ($paymentRequired && isPatient() && !$paymentMade): ?>
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel"><i class="bi bi-credit-card"></i> Process Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Certificate:</strong> <?php echo htmlspecialchars($certificate['cert_id']); ?><br>
                    <strong>Amount:</strong> ₱<?php echo number_format($paymentAmount, 2); ?>
                </div>
                <form id="paymentForm">
                    <?php echo SecurityManager::getCSRFField(); ?>
                    <input type="hidden" name="payment_type" value="certificate">
                    <input type="hidden" name="reference_id" value="<?php echo $cert_id; ?>">
                    <input type="hidden" name="amount" value="<?php echo $paymentAmount; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="gcash">GCash</option>
                            <option value="paymaya">PayMaya</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle"></i> <strong>Demo Mode:</strong> Payment will be marked as paid immediately.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="processPaymentBtn">
                    <i class="bi bi-check-circle"></i> Process Payment
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Handle payment processing
<?php if ($paymentRequired && isPatient() && !$paymentMade): ?>
document.getElementById('processPaymentBtn')?.addEventListener('click', function() {
    const form = document.getElementById('paymentForm');
    const formData = new FormData(form);
    const btn = this;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
    
    fetch('../api/process_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Payment successful! Reloading certificate...');
            window.location.reload();
        } else {
            alert('Payment failed: ' + (data.error || 'Unknown error'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Process Payment';
        }
    })
    .catch(error => {
        alert('Network error: ' + error);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Process Payment';
    });
});
<?php endif; ?>

// Handle certificate deletion
const deleteCertBtn = document.getElementById('deleteCertBtn');
if (deleteCertBtn) {
    deleteCertBtn.addEventListener('click', function() {
        const certId = this.getAttribute('data-cert-id');
        const certName = this.getAttribute('data-cert-name');
        
        if (confirm(`Are you sure you want to delete certificate ${certName}? This action cannot be undone.`)) {
            // Show loading state
            this.disabled = true;
            this.innerHTML = '<i class="bi bi-hourglass-split"></i> Deleting...';
            
            // Send delete request
            fetch('../api/delete_certificate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'cert_id=' + certId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Certificate deleted successfully');
                    window.location.href = 'my_certificates.php';
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete certificate'));
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-trash"></i> Delete Certificate';
                }
            })
            .catch(error => {
                alert('Error deleting certificate');
                console.error(error);
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-trash"></i> Delete Certificate';
            });
        }
    });
}
</script>
</body>
</html>

