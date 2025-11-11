<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Patients can view their own history; clinic admins can optionally view via patient_id param
$db = Database::getInstance();

$viewingPatientId = null;
if (isPatient()) {
    // Get patient id for current user
    $patient = $db->fetch("SELECT id, patient_code FROM patients WHERE user_id = ?", [$_SESSION['user_id']]);
    if (!$patient) {
        die('Patient profile not found');
    }
    $viewingPatientId = $patient['id'];
} else if (isClinicAdmin() && isset($_GET['patient_id'])) {
    $viewingPatientId = intval($_GET['patient_id']);
}

if (!$viewingPatientId) {
    // Default: redirect patients without profile or admins z   without param
    redirect('dashboard.php');
}

// Fetch certificates for history
$certificates = $db->fetchAll(
    "SELECT c.*, cl.clinic_name FROM certificates c JOIN clinics cl ON c.clinic_id = cl.id WHERE c.patient_id = ? ORDER BY c.issue_date DESC, c.created_at DESC",
    [$viewingPatientId]
);

// Fetch appointments for history
$appointments = $db->fetchAll(
    "SELECT a.*, cl.clinic_name FROM appointments a JOIN clinics cl ON a.clinic_id = cl.id WHERE a.patient_id = ? ORDER BY a.appointment_date DESC, a.time_slot DESC",
    [$viewingPatientId]
);

// Basic aggregates
$stats = [
    'total_certificates' => count($certificates),
    'active' => 0,
    'expired' => 0,
    'revoked' => 0,
    'total_appointments' => count($appointments),
    'completed_appointments' => 0,
    'pending_appointments' => 0,
];
foreach ($certificates as $c) {
    $stats[$c['status']] = ($stats[$c['status']] ?? 0) + 1;
}
foreach ($appointments as $a) {
    if ($a['status'] === 'completed') $stats['completed_appointments']++;
    if ($a['status'] === 'pending' || $a['status'] === 'approved') $stats['pending_appointments']++;
}

// Combine certificates and appointments into unified timeline
$timeline = [];
foreach ($certificates as $c) {
    $timeline[] = [
        'type' => 'certificate',
        'date' => $c['issue_date'],
        'data' => $c
    ];
}
foreach ($appointments as $a) {
    $timeline[] = [
        'type' => 'appointment',
        'date' => $a['appointment_date'],
        'data' => $a
    ];
}
// Sort by date descending
usort($timeline, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient History - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<?php include 'includes/role_styles.php'; ?>
<style>
.timeline { position: relative; padding-left: 1.5rem; }
.timeline:before { content: ""; position: absolute; left: 8px; top: 0; bottom: 0; width: 2px; background: #e0e0e0; }
.timeline-item { position: relative; margin-bottom: 1.5rem; }
.timeline-item:before { content: ""; position: absolute; left: 0; top: 4px; width: 18px; height: 18px; background: <?php echo $_SESSION['role'] === 'patient' ? '#1976d2' : '#2e7d32'; ?>; border-radius: 50%; }
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="main-content">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h2 class="mb-0"><i class="bi bi-clock-history"></i> Medical History</h2>
                    <div class="text-muted">
                        <span class="badge bg-primary me-2"><?php echo $stats['total_certificates']; ?> Certificates</span>
                        <span class="badge bg-info me-2"><?php echo $stats['total_appointments']; ?> Appointments</span>
                        <span class="badge bg-success"><?php echo $stats['completed_appointments']; ?> Completed</span>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="timeline">
                            <?php if (!empty($timeline)): ?>
                                <?php foreach ($timeline as $item): ?>
                                <div class="timeline-item">
                                    <div class="ms-4">
                                        <?php if ($item['type'] === 'certificate'): 
                                            $c = $item['data']; ?>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <i class="bi bi-file-medical text-primary me-2"></i>
                                                <strong><?php echo htmlspecialchars($c['purpose']); ?></strong>
                                                <span class="text-muted ms-2"><?php echo htmlspecialchars($c['clinic_name']); ?></span>
                                            </div>
                                            <div>
                                                <span class="badge bg-<?php echo $c['status'] === 'active' ? 'success' : ($c['status'] === 'expired' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($c['status'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-muted small">Issued: <?php echo htmlspecialchars($c['issue_date']); ?><?php if (!empty($c['expiry_date'])): ?> • Valid until: <?php echo htmlspecialchars($c['expiry_date']); ?><?php endif; ?></div>
                                        <?php if (!empty($c['diagnosis'])): ?>
                                        <div class="text-muted small mt-1"><strong>Diagnosis:</strong> <?php echo htmlspecialchars($c['diagnosis']); ?></div>
                                        <?php endif; ?>
                                        <div class="mt-2">
                                            <a href="view_certificate.php?id=<?php echo intval($c['id']); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a>
                                            <?php if (isClinicAdmin()): ?>
                                            <a href="create_certificate.php?patient_id=<?php echo intval($viewingPatientId); ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-plus"></i> New Certificate</a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php else: 
                                            $a = $item['data']; ?>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <i class="bi bi-calendar-check text-info me-2"></i>
                                                <strong>Appointment: <?php echo htmlspecialchars($a['purpose']); ?></strong>
                                                <span class="text-muted ms-2"><?php echo htmlspecialchars($a['clinic_name']); ?></span>
                                            </div>
                                            <div>
                                                <span class="badge bg-<?php 
                                                    echo $a['status'] === 'completed' ? 'success' : 
                                                        ($a['status'] === 'approved' ? 'primary' : 
                                                        ($a['status'] === 'pending' ? 'warning' : 
                                                        ($a['status'] === 'cancelled' ? 'danger' : 'secondary'))); 
                                                ?>">
                                                    <?php echo htmlspecialchars(ucfirst($a['status'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-muted small">
                                            Date: <?php echo htmlspecialchars($a['appointment_date']); ?> at <?php echo htmlspecialchars(date('g:i A', strtotime($a['time_slot']))); ?>
                                            <?php if (!empty($a['requested_specialization'])): ?>
                                            • Specialization: <?php echo htmlspecialchars($a['requested_specialization']); ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($a['details'])): ?>
                                        <div class="text-muted small mt-1"><strong>Details:</strong> <?php echo htmlspecialchars($a['details']); ?></div>
                                        <?php endif; ?>
                                        <div class="mt-2">
                                            <?php if (isPatient()): ?>
                                            <a href="my_appointments.php" class="btn btn-sm btn-outline-info"><i class="bi bi-calendar"></i> View Appointments</a>
                                            <?php else: ?>
                                            <a href="clinic_appointments.php" class="btn btn-sm btn-outline-info"><i class="bi bi-calendar"></i> Manage Appointments</a>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-muted">No medical history found.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <a class="btn btn-secondary" href="<?php echo isPatient() ? 'my_certificates.php' : 'patients.php'; ?>"><i class="bi bi-arrow-left"></i> Back</a>
                </div>
            </div>
        </main>
    </div>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


