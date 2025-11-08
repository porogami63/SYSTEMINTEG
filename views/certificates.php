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
    $clinic_id = $clinic['id'] ?? 0;

    // Handle search and filters
    $search_term = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
    $date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';
    
    // Build certificate query with filters
    $cert_where = ["c.clinic_id = ?"];
    $cert_params = [$clinic_id];
    
    if (!empty($search_term)) {
        $cert_where[] = "(c.cert_id LIKE ? OR u.full_name LIKE ? OR c.purpose LIKE ? OR c.issued_by LIKE ?)";
        $search_param = '%' . $search_term . '%';
        $cert_params[] = $search_param;
        $cert_params[] = $search_param;
        $cert_params[] = $search_param;
        $cert_params[] = $search_param;
    }
    
    if (!empty($status_filter)) {
        $cert_where[] = "c.status = ?";
        $cert_params[] = $status_filter;
    }
    
    if (!empty($date_from)) {
        $cert_where[] = "c.issue_date >= ?";
        $cert_params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $cert_where[] = "c.issue_date <= ?";
        $cert_params[] = $date_to;
    }
    
    // Get pending certificate requests (also filter by search if provided)
    $req_where = ["r.clinic_id = ?", "r.status = 'pending'"];
    $req_params = [$clinic_id];
    
    if (!empty($search_term)) {
        $req_where[] = "(u.full_name LIKE ? OR u.email LIKE ? OR r.purpose LIKE ?)";
        $search_param = '%' . $search_term . '%';
        $req_params[] = $search_param;
        $req_params[] = $search_param;
        $req_params[] = $search_param;
    }
    
    $pending_requests = $db->fetchAll("SELECT r.*, u.full_name as patient_name, u.email as patient_email, u.phone as patient_phone, 
                        u.profile_photo, p.patient_code, p.date_of_birth, p.gender, p.address
                        FROM certificate_requests r
                        JOIN patients p ON r.patient_id = p.id
                        JOIN users u ON p.user_id = u.id
                        WHERE " . implode(' AND ', $req_where) . " ORDER BY r.created_at DESC", $req_params);
    
    // Decode spec_answers JSON for each request
    foreach ($pending_requests as &$request) {
        if (!empty($request['spec_answers'])) {
            $request['spec_answers'] = json_decode($request['spec_answers'], true);
        }
    }
    unset($request);

    // Get all certificates with filters
    $certificates = $db->fetchAll("SELECT c.*, u.full_name as patient_name FROM certificates c 
                       JOIN patients p ON c.patient_id = p.id 
                       JOIN users u ON p.user_id = u.id 
                       WHERE " . implode(' AND ', $cert_where) . " ORDER BY c.created_at DESC", $cert_params);

    // Get all patients for manual certificate creation
    $patients = $db->fetchAll("SELECT p.id, p.patient_code, u.full_name, u.email FROM patients p JOIN users u ON p.user_id = u.id");
} catch (Exception $e) {
    $clinic = null;
    $pending_requests = [];
    $certificates = [];
    $patients = [];
}

// Handle certificate creation from request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_from_request') {
        $request_id = intval($_POST['request_id']);
        
        // Get request details
        $request = $db->fetch("SELECT r.*, p.id as patient_id FROM certificate_requests r 
                              JOIN patients p ON r.patient_id = p.id 
                              WHERE r.id = ? AND r.clinic_id = ?", [$request_id, $clinic_id]);
        
        if ($request && $clinic) {
            $patient_id = $request['patient_id'];
            $issued_by = sanitizeInput($_POST['issued_by'] ?? $_SESSION['full_name']);
            $doctor_license = sanitizeInput($_POST['doctor_license'] ?? '');
            $issue_date = sanitizeInput($_POST['issue_date'] ?? date('Y-m-d'));
            $expiry_date = sanitizeInput($_POST['expiry_date'] ?? '');
            $purpose = sanitizeInput($_POST['purpose'] ?? $request['purpose']);
            $diagnosis = sanitizeInput($_POST['diagnosis'] ?? '');
            $recommendations = sanitizeInput($_POST['recommendations'] ?? '');
            
            try {
                $db->beginTransaction();
                
                // Create certificate
                $cert_id = generateCertID();
                $doctor_signature_path = !empty($clinic['signature_path']) ? $clinic['signature_path'] : null;
                
                $db->execute("INSERT INTO certificates (cert_id, clinic_id, patient_id, issued_by, doctor_license, issue_date, expiry_date, purpose, diagnosis, recommendations, doctor_signature_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                    [$cert_id, $clinic_id, $patient_id, $issued_by, $doctor_license, $issue_date, $expiry_date, $purpose, $diagnosis, $recommendations, $doctor_signature_path]);
                
                $cert_id_db = $db->lastInsertId();
                
                // Generate QR code
                require_once '../includes/qr_generator.php';
                $qr_path = generateQRCode($cert_id, $cert_id_db);
                
                // Update certificate with QR path
                $db->execute("UPDATE certificates SET file_path = ? WHERE id = ?", [$qr_path, $cert_id_db]);
                
                // Auto-approve and mark request as completed
                $db->execute("UPDATE certificate_requests SET status = 'completed' WHERE id = ? AND clinic_id = ?", [$request_id, $clinic_id]);
                
                $db->commit();
                
                $success = "Certificate created successfully! Cert ID: " . $cert_id;
                
                // Audit log
                AuditLogger::log(
                    'CREATE_CERTIFICATE',
                    'certificate',
                    $cert_id_db,
                    ['cert_id' => $cert_id, 'patient_id' => $patient_id, 'from_request' => true]
                );
                
                // Notify patient (in-app and email)
                $ud = $db->fetch("SELECT u.id as user_id, u.email, u.full_name FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id = ?", [$patient_id]);
                if ($ud) {
                    $mysqli = getDBConnection();
                    notifyUser($mysqli, intval($ud['user_id']), 'New Medical Certificate', 'A new medical certificate has been issued to you.', 'my_certificates.php');
                    $mysqli->close();
                    
                    // Send email notification
                    try {
                        require_once '../includes/EmailNotifier.php';
                        EmailNotifier::sendCertificateIssued(
                            $ud['email'],
                            $ud['full_name'],
                            $cert_id,
                            [
                                'purpose' => $purpose,
                                'issue_date' => $issue_date,
                                'expiry_date' => $expiry_date
                            ]
                        );
                    } catch (Exception $e) {
                        // Email failure shouldn't break the flow
                        error_log('Email notification failed: ' . $e->getMessage());
                    }
                }
                
                // Refresh data
                $pending_requests = $db->fetchAll("SELECT r.*, u.full_name as patient_name, u.email as patient_email, p.patient_code
                                    FROM certificate_requests r
                                    JOIN patients p ON r.patient_id = p.id
                                    JOIN users u ON p.user_id = u.id
                                    WHERE r.clinic_id = ? AND r.status = 'pending' ORDER BY r.created_at DESC", [$clinic_id]);
                $certificates = $db->fetchAll("SELECT c.*, u.full_name as patient_name FROM certificates c 
                                   JOIN patients p ON c.patient_id = p.id 
                                   JOIN users u ON p.user_id = u.id 
                                   WHERE c.clinic_id = ? ORDER BY c.created_at DESC", [$clinic_id]);
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Failed to create certificate: ' . $e->getMessage();
            }
        } else {
            $error = 'Request not found or clinic profile incomplete.';
        }
    } elseif ($_POST['action'] === 'create_manual') {
        // Manual certificate creation (not from request)
        if (!$clinic) {
            $error = 'Clinic profile not found. Please complete your clinic profile first.';
        } else {
            $patient_id = intval($_POST['patient_id']);
            $issued_by = sanitizeInput($_POST['issued_by']);
            $doctor_license = sanitizeInput($_POST['doctor_license']);
            $issue_date = sanitizeInput($_POST['issue_date']);
            $expiry_date = sanitizeInput($_POST['expiry_date']);
            $purpose = sanitizeInput($_POST['purpose']);
            $diagnosis = sanitizeInput($_POST['diagnosis']);
            $recommendations = sanitizeInput($_POST['recommendations']);
            
            // Verify patient exists
            $patient_exists = $db->fetch("SELECT id FROM patients WHERE id = ?", [$patient_id]);
            
            if (!$patient_exists) {
                $error = 'Selected patient does not exist.';
            } else {
                try {
                    $db->beginTransaction();
                    
                    $cert_id = generateCertID();
                    $doctor_signature_path = !empty($clinic['signature_path']) ? $clinic['signature_path'] : null;
                    
                    $db->execute("INSERT INTO certificates (cert_id, clinic_id, patient_id, issued_by, doctor_license, issue_date, expiry_date, purpose, diagnosis, recommendations, doctor_signature_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                        [$cert_id, $clinic_id, $patient_id, $issued_by, $doctor_license, $issue_date, $expiry_date, $purpose, $diagnosis, $recommendations, $doctor_signature_path]);
                    
                    $cert_id_db = $db->lastInsertId();
                    
                    // Generate QR code
                    require_once '../includes/qr_generator.php';
                    $qr_path = generateQRCode($cert_id, $cert_id_db);
                    
                    // Update certificate with QR path
                    $db->execute("UPDATE certificates SET file_path = ? WHERE id = ?", [$qr_path, $cert_id_db]);
                    
                    $db->commit();
                    
                    $success = "Certificate created successfully! Cert ID: " . $cert_id;
                    
                    // Audit log
                    AuditLogger::log(
                        'CREATE_CERTIFICATE',
                        'certificate',
                        $cert_id_db,
                        ['cert_id' => $cert_id, 'patient_id' => $patient_id, 'from_request' => false]
                    );
                    
                    // Notify patient (in-app and email)
                    $ud = $db->fetch("SELECT u.id as user_id, u.email, u.full_name FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id = ?", [$patient_id]);
                    if ($ud) {
                        $mysqli = getDBConnection();
                        notifyUser($mysqli, intval($ud['user_id']), 'New Medical Certificate', 'A new medical certificate has been issued to you.', 'my_certificates.php');
                        $mysqli->close();
                        
                        // Send email notification
                        try {
                            require_once '../includes/EmailNotifier.php';
                            EmailNotifier::sendCertificateIssued(
                                $ud['email'],
                                $ud['full_name'],
                                $cert_id,
                                [
                                    'purpose' => $purpose,
                                    'issue_date' => $issue_date,
                                    'expiry_date' => $expiry_date
                                ]
                            );
                        } catch (Exception $e) {
                            error_log('Email notification failed: ' . $e->getMessage());
                        }
                    }
                    
                    // Refresh data
                    $pending_requests = $db->fetchAll("SELECT r.*, u.full_name as patient_name, u.email as patient_email, p.patient_code
                                        FROM certificate_requests r
                                        JOIN patients p ON r.patient_id = p.id
                                        JOIN users u ON p.user_id = u.id
                                        WHERE r.clinic_id = ? AND r.status = 'pending' ORDER BY r.created_at DESC", [$clinic_id]);
    $certificates = $db->fetchAll("SELECT c.*, u.full_name as patient_name FROM certificates c 
                       JOIN patients p ON c.patient_id = p.id 
                       JOIN users u ON p.user_id = u.id 
                       WHERE c.clinic_id = ? ORDER BY c.created_at DESC", [$clinic_id]);
} catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Failed to create certificate: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Certificates & Requests - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<?php include 'includes/role_styles.php'; ?>
<style>
.request-card {
    border-left: 4px solid #ffc107;
    margin-bottom: 15px;
}
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0"><i class="bi bi-files"></i> Certificates & Requests</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCertModal">
                        <i class="bi bi-plus-circle"></i> Create New Certificate
                    </button>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Search and Filter Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-search"></i> Search & Filter</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="certificates.php" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" placeholder="Cert ID, Patient Name, Purpose..." value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                                    <option value="revoked" <?php echo $status_filter === 'revoked' ? 'selected' : ''; ?>>Revoked</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                            </div>
                            <?php if (!empty($search_term) || !empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                            <div class="col-12">
                                <a href="certificates.php" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Clear Filters
                                </a>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Pending Requests Section -->
                <?php if (!empty($pending_requests)): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Pending Certificate Requests (<?php echo count($pending_requests); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($pending_requests as $request): ?>
                        <div class="card request-card mb-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h6 class="fw-bold"><?php echo htmlspecialchars($request['patient_name']); ?> 
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($request['patient_code']); ?></span>
                                        </h6>
                                        <p class="text-muted mb-1"><strong>Purpose:</strong> <?php echo htmlspecialchars($request['purpose']); ?></p>
                                        <p class="text-muted mb-1"><strong>Specialization:</strong> <?php echo htmlspecialchars($request['requested_specialization']); ?></p>
                                        <?php if (!empty($request['details'])): ?>
                                        <p class="text-muted mb-1"><strong>Details:</strong> <?php echo htmlspecialchars($request['details']); ?></p>
                                        <?php endif; ?>
                                        <small class="text-muted">Requested: <?php echo date('M d, Y', strtotime($request['created_at'])); ?></small>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <button class="btn btn-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#requestDetailsModal" 
                                                onclick='loadRequestDetails(<?php echo json_encode($request); ?>)'>
                                            <i class="bi bi-info-circle"></i> More Details
                                        </button>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createFromRequestModal" 
                                                onclick='loadRequestData(<?php echo json_encode($request); ?>)'>
                                            <i class="bi bi-file-earmark-plus"></i> Create Certificate
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Existing Certificates Section -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-check"></i> All Certificates</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($certificates)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Cert ID</th>
                                        <th>Patient</th>
                                        <th>Issue Date</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($certificates as $cert): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($cert['cert_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($cert['patient_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($cert['issue_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($cert['purpose']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $cert['status'] === 'active' ? 'success' : ($cert['status'] === 'expired' ? 'warning' : 'danger'); ?>">
                                                <?php echo strtoupper($cert['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_certificate.php?id=<?php echo $cert['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted text-center py-5">No certificates found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Request Details Modal -->
<div class="modal fade" id="requestDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-info-circle"></i> Patient Details & Request Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Patient Profile Picture -->
                    <div class="col-md-4 text-center mb-4">
                        <div id="details-profile-photo" class="mb-3">
                            <!-- Profile picture will be loaded dynamically -->
                        </div>
                        <h5 id="details-patient-name" class="fw-bold"></h5>
                        <span id="details-patient-code" class="badge bg-secondary"></span>
                    </div>
                    
                    <!-- Patient Personal Details -->
                    <div class="col-md-8">
                        <h6 class="text-primary mb-3"><i class="bi bi-person"></i> Personal Information</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Email:</strong> <span id="details-email"></span></p>
                                <p class="mb-2"><strong>Phone:</strong> <span id="details-phone"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Date of Birth:</strong> <span id="details-dob"></span></p>
                                <p class="mb-2"><strong>Gender:</strong> <span id="details-gender"></span></p>
                            </div>
                        </div>
                        <p class="mb-3"><strong>Address:</strong> <span id="details-address"></span></p>
                        
                        <hr>
                        
                        <!-- Request Information -->
                        <h6 class="text-primary mb-3"><i class="bi bi-file-earmark-text"></i> Request Information</h6>
                        <div class="mb-3">
                            <p class="mb-2"><strong>Purpose:</strong> <span id="details-purpose"></span></p>
                            <p class="mb-2"><strong>Specialization:</strong> <span id="details-specialization"></span></p>
                            <p class="mb-2"><strong>Details:</strong> <span id="details-request-details"></span></p>
                            <p class="mb-2"><strong>Requested Date:</strong> <span id="details-requested-date"></span></p>
                        </div>
                        
                        <!-- Additional Questions Answers -->
                        <div id="details-answers-section" style="display: none;">
                            <hr>
                            <h6 class="text-primary mb-3"><i class="bi bi-question-circle"></i> Additional Questions & Answers</h6>
                            <div id="details-answers-list"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="createCertificateFromDetails()">
                    <i class="bi bi-file-earmark-plus"></i> Create Certificate
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create Certificate from Request Modal -->
<div class="modal fade" id="createFromRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-plus"></i> Create Certificate from Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="createFromRequestForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_from_request">
                    <input type="hidden" name="request_id" id="modal_request_id">
                    <div class="mb-3">
                        <label class="form-label">Patient</label>
                        <input type="text" class="form-control" id="modal_patient_name" readonly>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="issue_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" name="expiry_date">
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
                    <div class="mb-3">
                        <label class="form-label">Purpose <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="purpose" id="modal_purpose" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Diagnosis</label>
                        <textarea class="form-control" name="diagnosis" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Recommendations</label>
                        <textarea class="form-control" name="recommendations" rows="3"></textarea>
                    </div>
                    <?php if (empty($clinic['signature_path'])): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> No signature on file. Please upload your signature in Profile → Edit Profile.
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="publishFromRequestBtn">
                        <i class="bi bi-save"></i> Publish Certificate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Manual Certificate Modal -->
<div class="modal fade" id="createCertModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-plus"></i> Create New Certificate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="createManualForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_manual">
                    <div class="mb-3">
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="issue_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" name="expiry_date">
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
                    <div class="mb-3">
                        <label class="form-label">Purpose <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="purpose" placeholder="e.g., Sick Leave, Medical Clearance" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Diagnosis</label>
                        <textarea class="form-control" name="diagnosis" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Recommendations</label>
                        <textarea class="form-control" name="recommendations" rows="3"></textarea>
                    </div>
                    <?php if (empty($clinic['signature_path'])): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> No signature on file. Please upload your signature in Profile → Edit Profile.
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Publish Certificate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentRequestData = null;

// Load request details into the modal
function loadRequestDetails(request) {
    currentRequestData = request;
    
    // Patient Profile Picture
    const profilePhoto = document.getElementById('details-profile-photo');
    if (request.profile_photo && request.profile_photo.trim() !== '') {
        const imgPath = request.profile_photo.startsWith('../') ? request.profile_photo : '../' + request.profile_photo;
        profilePhoto.innerHTML = `<img src="${imgPath}" alt="Profile Picture" class="img-thumbnail rounded-circle border-2" style="width: 150px; height: 150px; object-fit: cover;" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\\'bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center\\' style=\\'width: 150px; height: 150px;\\'><i class=\\'bi bi-person fs-1 text-white\\'></i></div>';" />`;
    } else {
        profilePhoto.innerHTML = `<div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 150px; height: 150px;"><i class="bi bi-person fs-1 text-white"></i></div>`;
    }
    
    // Patient Information
    document.getElementById('details-patient-name').textContent = request.patient_name || 'N/A';
    document.getElementById('details-patient-code').textContent = request.patient_code || 'N/A';
    document.getElementById('details-email').textContent = request.patient_email || 'N/A';
    document.getElementById('details-phone').textContent = request.patient_phone || 'N/A';
    document.getElementById('details-dob').textContent = request.date_of_birth ? new Date(request.date_of_birth).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
    document.getElementById('details-gender').textContent = request.gender || 'N/A';
    document.getElementById('details-address').textContent = request.address || 'N/A';
    
    // Request Information
    document.getElementById('details-purpose').textContent = request.purpose || 'N/A';
    document.getElementById('details-specialization').textContent = request.requested_specialization || 'N/A';
    document.getElementById('details-request-details').textContent = request.details || 'N/A';
    document.getElementById('details-requested-date').textContent = request.created_at ? new Date(request.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
    
    // Additional Questions Answers
    const answersSection = document.getElementById('details-answers-section');
    const answersList = document.getElementById('details-answers-list');
    
    if (request.spec_answers) {
        try {
            const answers = typeof request.spec_answers === 'string' ? JSON.parse(request.spec_answers) : request.spec_answers;
            if (answers && Object.keys(answers).length > 0) {
                answersList.innerHTML = '';
                Object.keys(answers).forEach(key => {
                    const questionLabel = formatQuestionLabel(key);
                    const answer = answers[key];
                    const answerDiv = document.createElement('div');
                    answerDiv.className = 'mb-3 p-3 border rounded';
                    answerDiv.innerHTML = `
                        <strong class="text-primary">${questionLabel}:</strong>
                        <span class="ms-2">${answer || 'N/A'}</span>
                    `;
                    answersList.appendChild(answerDiv);
                });
                answersSection.style.display = 'block';
            } else {
                answersSection.style.display = 'none';
            }
        } catch (e) {
            answersSection.style.display = 'none';
        }
    } else {
        answersSection.style.display = 'none';
    }
}

// Format question label from key
function formatQuestionLabel(key) {
    const labels = {
        'duration': 'How long have symptoms been present?',
        'fever': 'Do you have fever?',
        'chest_pain': 'Chest pain present?',
        'bp_history': 'History of hypertension?',
        'headache': 'Headache severity',
        'neuro_deficits': 'Any weakness/numbness?',
        'child_age': 'Child age',
        'vaccinations': 'Vaccination up to date?',
        'injury': 'Recent injury?',
        'pain_scale': 'Pain scale (0-10)',
        'rash': 'Rash present?',
        'mood': 'Mood',
        'sleep': 'Sleep issues?',
        'weight_loss': 'Unintentional weight loss?',
        'family_history': 'Family history of cancer?',
        'lmp': 'Last menstrual period',
        'pregnant': 'Pregnant?',
        'life_threat': 'Life-threatening symptoms?',
        'chronic': 'Existing chronic illnesses',
        'prior_surgery': 'Prior surgeries?',
        'bleeding': 'Bleeding disorders?'
    };
    return labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

// Create certificate from details modal
function createCertificateFromDetails() {
    if (currentRequestData) {
        loadRequestData(currentRequestData);
        const modal = new bootstrap.Modal(document.getElementById('createFromRequestModal'));
        modal.show();
    }
}

function loadRequestData(request) {
    document.getElementById('modal_request_id').value = request.id;
    document.getElementById('modal_patient_name').value = request.patient_name + ' (' + request.patient_code + ')';
    document.getElementById('modal_purpose').value = request.purpose || '';
}
</script>
</body>
</html>
