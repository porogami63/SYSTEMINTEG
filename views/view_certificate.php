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
    padding: 40px;
    max-width: 900px;
    margin: 0 auto;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}
.certificate-header {
    border-bottom: 3px solid #2e7d32;
    padding-bottom: 20px;
    margin-bottom: 30px;
}
.qr-code {
    text-align: center;
    margin-top: 30px;
}
@media print {
    .no-print {
        display: none;
    }
}
</style>
</head>
<body class="bg-light">
<div class="container my-5">
    <div class="certificate-container">
        <!-- Certificate Header -->
        <div class="certificate-header text-center">
            <h3 class="text-primary mb-2"><strong>MEDICAL CERTIFICATE</strong></h3>
            <p class="text-muted">Certificate ID: <?php echo htmlspecialchars($certificate['cert_id']); ?></p>
        </div>

        <!-- Certificate Details -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="text-muted">PATIENT INFORMATION</h6>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($certificate['patient_name']); ?></p>
                <p><strong>Patient Code:</strong> <?php echo htmlspecialchars($certificate['patient_code']); ?></p>
                <p><strong>Date of Birth:</strong> <?php echo $certificate['date_of_birth'] ?? 'N/A'; ?></p>
                <p><strong>Gender:</strong> <?php echo $certificate['gender'] ?? 'N/A'; ?></p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted">ISSUED BY</h6>
                <p><strong>Clinic:</strong> <?php echo htmlspecialchars($certificate['clinic_name']); ?></p>
                <p><strong>Doctor:</strong> <?php echo htmlspecialchars($certificate['issued_by']); ?></p>
                <?php if ($certificate['doctor_license']): ?>
                <p><strong>License No:</strong> <?php echo htmlspecialchars($certificate['doctor_license']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <hr>

        <div class="mb-4">
            <p><strong>Purpose:</strong> <?php echo htmlspecialchars($certificate['purpose']); ?></p>
            <p><strong>Issue Date:</strong> <?php echo $certificate['issue_date']; ?></p>
            <?php if ($certificate['expiry_date']): ?>
            <p><strong>Valid Until:</strong> <?php echo $certificate['expiry_date']; ?></p>
            <?php endif; ?>
        </div>

        <?php if ($certificate['diagnosis']): ?>
        <div class="mb-4">
            <h6 class="text-muted">DIAGNOSIS</h6>
            <p><?php echo nl2br(htmlspecialchars($certificate['diagnosis'])); ?></p>
        </div>
        <?php endif; ?>

        <?php if ($certificate['recommendations']): ?>
        <div class="mb-4">
            <h6 class="text-muted">RECOMMENDATIONS</h6>
            <p><?php echo nl2br(htmlspecialchars($certificate['recommendations'])); ?></p>
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

        <!-- Signature/Seal and QR Code -->
        <div class="qr-code">
            <?php if (!empty($certificate['signature_path'])): ?>
            <div class="mb-3">
                <img src="../<?php echo htmlspecialchars($certificate['signature_path']); ?>" alt="Doctor Signature" style="height:80px;">
                <div class="text-muted small">Doctor's Signature</div>
            </div>
            <?php endif; ?>
            <?php if (!empty($certificate['seal_path'])): ?>
            <div class="mb-3">
                <img src="../<?php echo htmlspecialchars($certificate['seal_path']); ?>" alt="Clinic Seal" style="height:80px;">
                <div class="text-muted small">Clinic Seal</div>
            </div>
            <?php endif; ?>
            <p class="text-muted small">Scan QR code to verify certificate</p>
            <img src="<?php echo $qr_image; ?>" alt="QR Code" class="img-fluid" style="width: 200px;">
        </div>

        <!-- Certificate Footer -->
        <div class="mt-5 text-center text-muted small">
            <p>This certificate was issued digitally and can be verified online</p>
            <p class="mb-0">Issued on: <?php echo $certificate['created_at']; ?></p>
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

