<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user info
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    $db = Database::getInstance();
    // Get clinic, patient, or webadmin info
    if ($role === 'clinic_admin') {
        $profile = $db->fetch("SELECT c.* FROM clinics c WHERE c.user_id = ?", [$user_id]);
        $clinic_id = $profile['id'] ?? 0;

        // Get certificate counts
        $cert_count = $db->fetch("SELECT COUNT(*) as total FROM certificates WHERE clinic_id = ?", [$clinic_id]);
        $active_count = $db->fetch("SELECT COUNT(*) as total FROM certificates WHERE clinic_id = ? AND status = 'active'", [$clinic_id]);
        $expired_count = $db->fetch("SELECT COUNT(*) as total FROM certificates WHERE clinic_id = ? AND status = 'expired'", [$clinic_id]);
        
        // Get expiry statistics
        $expiry_stats = ExpiryManager::getExpiryStats($clinic_id);
        
        // Get pending requests count
        $pending_requests_count = $db->fetch("SELECT COUNT(*) as total FROM certificate_requests WHERE clinic_id = ? AND status = 'pending'", [$clinic_id]);
        
        // Get certificates expiring soon for this clinic
        $expiring_soon = ExpiryManager::getExpiringSoon(7, $clinic_id);

        // Get recent certificates
        $recent_certs = $db->fetchAll("SELECT * FROM certificates WHERE clinic_id = ? ORDER BY created_at DESC LIMIT 5", [$clinic_id]);
        
        // Get monthly certificate statistics (last 6 months)
        $monthly_stats = $db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
             FROM certificates 
             WHERE clinic_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month ASC",
            [$clinic_id]
        );
        
        // Get recent activity feed (removed from clinic_admin, moved to webadmin)
        $recent_activity = [];
    } elseif ($role === 'web_admin') {
        // Web Admin Dashboard - All system data
        $profile = null;
        
        // Get all certificate counts
        $cert_count = $db->fetch("SELECT COUNT(*) as total FROM certificates");
        $active_count = $db->fetch("SELECT COUNT(*) as total FROM certificates WHERE status = 'active'");
        $expired_count = $db->fetch("SELECT COUNT(*) as total FROM certificates WHERE status = 'expired'");
        
        // Get all appointments count
        $appt_count = $db->fetch("SELECT COUNT(*) as total FROM appointments");
        $upcoming_appts = $db->fetch("SELECT COUNT(*) as total FROM appointments WHERE appointment_date >= CURDATE() AND status = 'approved'");
        
        // Get all clinics count
        $clinic_count = $db->fetch("SELECT COUNT(*) as total FROM clinics");
        
        // Get all patients count
        $patient_count = $db->fetch("SELECT COUNT(*) as total FROM patients");
        
        // Get recent certificates (all clinics)
        $recent_certs = $db->fetchAll(
            "SELECT c.*, cl.clinic_name 
             FROM certificates c 
             LEFT JOIN clinics cl ON c.clinic_id = cl.id 
             ORDER BY c.created_at DESC LIMIT 5"
        );
        
        // Get recent appointments (all clinics)
        $recent_appointments = $db->fetchAll(
            "SELECT a.*, cl.clinic_name, u.full_name as patient_name, p.patient_code
             FROM appointments a
             LEFT JOIN clinics cl ON a.clinic_id = cl.id
             LEFT JOIN patients p ON a.patient_id = p.id
             LEFT JOIN users u ON p.user_id = u.id
             ORDER BY a.appointment_date DESC, a.time_slot DESC LIMIT 5"
        );
        
        // Get recent activity feed (all activities, not just certificates)
        $recent_activity = AuditLogger::getLogs([], 10, 0);
        
        $monthly_stats = [];
        $expiring_soon = [];
        $expiry_stats = ['expiring_this_week' => 0, 'expiring_this_month' => 0, 'already_expired' => 0];
        $pending_requests_count = ['total' => 0];
    } else {
        // Patient dashboard
        $profile = $db->fetch("SELECT p.* FROM patients p WHERE p.user_id = ?", [$user_id]);
        $patient_id = $profile['id'] ?? 0;

        // Get certificate counts
        $cert_count = $db->fetch("SELECT COUNT(*) as total FROM certificates WHERE patient_id = ?", [$patient_id]);
        $active_count = $db->fetch("SELECT COUNT(*) as total FROM certificates WHERE patient_id = ? AND status = 'active'", [$patient_id]);
        $expired_count = $db->fetch("SELECT COUNT(*) as total FROM certificates WHERE patient_id = ? AND status = 'expired'", [$patient_id]);
        
        // Get certificates expiring soon for this patient
        $expiring_soon = $db->fetchAll(
            "SELECT c.*, cl.clinic_name
             FROM certificates c
             JOIN clinics cl ON c.clinic_id = cl.id
             WHERE c.patient_id = ?
             AND c.status = 'active'
             AND c.expiry_date IS NOT NULL
             AND c.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             ORDER BY c.expiry_date ASC",
            [$patient_id]
        );

        // Get recent certificates
        $recent_certs = $db->fetchAll("SELECT c.*, cl.clinic_name FROM certificates c 
                           JOIN clinics cl ON c.clinic_id = cl.id 
                           WHERE c.patient_id = ? ORDER BY c.created_at DESC LIMIT 5", [$patient_id]);
        
        $monthly_stats = [];
        $pending_requests_count = ['total' => 0];
        $expiry_stats = ['expiring_this_week' => count($expiring_soon), 'expiring_this_month' => 0, 'already_expired' => intval($expired_count['total'] ?? 0)];
        $recent_activity = [];
    }
} catch (Exception $e) {
    $profile = null;
    $cert_count = ['total' => 0];
    $active_count = ['total' => 0];
    $expired_count = ['total' => 0];
    $pending_requests_count = ['total' => 0];
    $recent_certs = [];
    $expiring_soon = [];
    $expiry_stats = ['expiring_this_week' => 0, 'expiring_this_month' => 0, 'already_expired' => 0];
    $monthly_stats = [];
    $recent_activity = [];
    $appt_count = ['total' => 0];
    $upcoming_appts = ['total' => 0];
    $clinic_count = ['total' => 0];
    $patient_count = ['total' => 0];
    $recent_appointments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
<?php if ($role === 'web_admin'): ?>
/* Web Admin - Black Motif */
body {
    background: #0a0a0a;
    color: #e0e0e0;
}
.sidebar {
    min-height: 100vh;
    background: linear-gradient(180deg, #1a1a1a 0%, #000000 100%);
    border-right: 1px solid #333;
}
.sidebar .nav-link {
    color: #e0e0e0;
    padding: 12px 20px;
    margin: 5px 0;
}
.sidebar .nav-link:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}
.sidebar .nav-link.active {
    background: rgba(255,255,255,0.15);
    color: #fff;
    border-left: 3px solid #00ff88;
}
.main-content {
    padding: 30px;
    background: #0a0a0a;
}
.card {
    background: #1a1a1a;
    border: 1px solid #333;
    color: #e0e0e0;
}
.card-header {
    background: #252525;
    border-bottom: 1px solid #333;
    color: #fff;
}
.stats-card {
    border-left: 4px solid #00ff88;
    background: #1a1a1a;
}
.table {
    color: #e0e0e0;
}
.table thead th {
    border-bottom: 2px solid #333;
    color: #fff;
}
.table tbody tr {
    border-bottom: 1px solid #333;
}
.table tbody tr:hover {
    background: #252525;
}
.badge {
    color: #000;
}
.text-muted {
    color: #999 !important;
}
.btn-primary {
    background: #00ff88;
    border-color: #00ff88;
    color: #000;
}
.btn-primary:hover {
    background: #00cc6f;
    border-color: #00cc6f;
    color: #000;
}
.btn-success {
    background: #00ff88;
    border-color: #00ff88;
    color: #000;
}
.btn-success:hover {
    background: #00cc6f;
    border-color: #00cc6f;
    color: #000;
}
.alert {
    background: #1a1a1a;
    border: 1px solid #333;
    color: #e0e0e0;
}
<?php elseif ($role === 'clinic_admin'): ?>
/* Clinic Admin - Green Motif */
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
.stats-card {
    border-left: 4px solid #2e7d32;
}
<?php else: ?>
/* Patient - Blue Motif */
.sidebar {
    min-height: 100vh;
    background: linear-gradient(180deg, #1976d2 0%, #0d47a1 100%);
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
.stats-card {
    border-left: 4px solid #1976d2;
}
<?php endif; ?>
.notification-dropdown {
    position: fixed;
    top: 70px;
    left: 20px;
    width: 350px;
    max-height: 500px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    z-index: 1050;
    display: none;
    overflow: hidden;
}
.clock-widget {
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}
.clock-widget:hover {
    transform: translateY(-2px);
}
<?php if ($role === 'web_admin'): ?>
.clock-widget {
    background: #1a1a1a;
    border: 1px solid #333;
}
.clock-widget #clock-time {
    color: #00ff88;
}
.clock-widget #clock-date {
    color: #999;
}
<?php elseif ($role === 'clinic_admin'): ?>
.clock-widget {
    background: #f0f8f0;
    border: 1px solid #2e7d32;
}
.clock-widget #clock-time {
    color: #2e7d32;
}
.clock-widget #clock-date {
    color: #666;
}
<?php else: ?>
.clock-widget {
    background: #e3f2fd;
    border: 1px solid #1976d2;
}
.clock-widget #clock-time {
    color: #1976d2;
}
.clock-widget #clock-date {
    color: #666;
}
<?php endif; ?>
@media (max-width: 768px) {
    .clock-widget {
        margin-top: 10px;
        width: 100%;
    }
    .d-flex.justify-content-between.align-items-center.mb-4 {
        flex-direction: column;
        align-items: flex-start !important;
    }
    .notification-dropdown {
        width: calc(100vw - 40px);
        left: 20px;
        right: 20px;
    }
}
.notification-item {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background 0.2s;
}
.notification-item:hover {
    background: #f8f9fa;
}
.notification-item.unread {
    background: #e3f2fd;
    font-weight: 500;
}
.notification-item .notification-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
}
.notification-item .notification-message {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 4px;
}
.notification-item .notification-time {
    color: #999;
    font-size: 0.75rem;
}
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Welcome, <?php echo $_SESSION['full_name']; ?>!</h2>
                    <!-- Clock Widget -->
                    <div class="clock-widget">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-clock fs-4"></i>
                            <div>
                                <div id="clock-time" class="fw-bold fs-5">--:--:--</div>
                                <div id="clock-date" class="small text-muted">-- -- ----</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Total Certificates</h6>
                                <h2 class="<?php echo $role === 'web_admin' ? 'text-success' : 'text-primary'; ?> mb-0"><?php echo $cert_count['total']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card shadow-sm" style="border-left: 4px solid #28a745;">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Active</h6>
                                <h2 class="text-success mb-0"><?php echo $active_count['total'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card shadow-sm" style="border-left: 4px solid #ffc107;">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Expired</h6>
                                <h2 class="text-warning mb-0"><?php echo $expired_count['total'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <?php if ($role === 'clinic_admin'): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card shadow-sm" style="border-left: 4px solid #17a2b8;">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Pending Requests</h6>
                                <h2 class="text-info mb-0"><?php echo $pending_requests_count['total'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($role === 'web_admin'): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card shadow-sm" style="border-left: 4px solid #00ff88;">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Total Appointments</h6>
                                <h2 class="text-success mb-0"><?php echo $appt_count['total'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="col-md-3 mb-3">
                        <div class="card shadow-sm" style="border-left: 4px solid #6c757d;">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Expiring Soon</h6>
                                <h2 class="text-secondary mb-0"><?php echo count($expiring_soon); ?></h2>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($role === 'web_admin'): ?>
                <!-- Additional Stats for Web Admin -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card shadow-sm" style="border-left: 4px solid #00ff88;">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Upcoming Appointments</h6>
                                <h2 class="text-success mb-0"><?php echo $upcoming_appts['total'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card shadow-sm" style="border-left: 4px solid #00ff88;">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Total Clinics</h6>
                                <h2 class="text-success mb-0"><?php echo $clinic_count['total'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card shadow-sm" style="border-left: 4px solid #00ff88;">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Total Patients</h6>
                                <h2 class="text-success mb-0"><?php echo $patient_count['total'] ?? 0; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card shadow-sm" style="border-left: 4px solid #00ff88;">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Recent Activity</h6>
                                <h2 class="text-success mb-0"><?php echo count($recent_activity); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <?php if ($role === 'clinic_admin'): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-lightning-charge"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="create_certificate.php" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-circle"></i> New Certificate
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="certificates.php?status=pending" class="btn btn-info w-100">
                                    <i class="bi bi-inbox"></i> View Requests
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="analytics.php" class="btn btn-success w-100">
                                    <i class="bi bi-graph-up"></i> Analytics
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="patients.php" class="btn btn-warning w-100">
                                    <i class="bi bi-people"></i> Manage Patients
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php elseif ($role === 'web_admin'): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-lightning-charge"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="all_certificates.php" class="btn btn-primary w-100">
                                    <i class="bi bi-files"></i> All Certificates
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="all_appointments.php" class="btn btn-success w-100">
                                    <i class="bi bi-calendar-event"></i> All Appointments
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="audit_logs.php" class="btn btn-info w-100">
                                    <i class="bi bi-shield-check"></i> Audit Logs
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="system_stats.php" class="btn btn-warning w-100">
                                    <i class="bi bi-graph-up"></i> System Stats
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-lightning-charge"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <a href="request_certificate.php" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-circle"></i> Request Certificate
                                </a>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="my_certificates.php" class="btn btn-success w-100">
                                    <i class="bi bi-files"></i> My Certificates
                                </a>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="find_doctors.php" class="btn btn-info w-100">
                                    <i class="bi bi-search"></i> Find Doctors
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Expiry Warnings -->
                <?php if (!empty($expiring_soon)): ?>
                <div class="alert alert-warning alert-dismissible fade show mb-4">
                    <h6><i class="bi bi-exclamation-triangle"></i> Certificates Expiring Soon</h6>
                    <p class="mb-2">The following certificates will expire within 7 days:</p>
                    <ul class="mb-0">
                        <?php foreach ($expiring_soon as $cert): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($cert['cert_id']); ?></strong>
                            <?php if ($role === 'patient'): ?>
                            - <?php echo htmlspecialchars($cert['clinic_name']); ?>
                            <?php endif; ?>
                            (Expires: <?php echo date('M d, Y', strtotime($cert['expiry_date'])); ?>)
                            <a href="view_certificate.php?id=<?php echo $cert['id']; ?>" class="btn btn-sm btn-outline-warning ms-2">View</a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Recent Certificates -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5>Recent Certificates</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_certs)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Cert ID</th>
                                        <?php if ($role === 'patient' || $role === 'web_admin'): ?>
                                        <th>Clinic</th>
                                        <?php endif; ?>
                                        <th>Issue Date</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_certs as $cert): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cert['cert_id']); ?></td>
                                        <?php if ($role === 'patient' || $role === 'web_admin'): ?>
                                        <td><?php echo htmlspecialchars($cert['clinic_name'] ?? 'N/A'); ?></td>
                                        <?php endif; ?>
                                        <td><?php echo $cert['issue_date']; ?></td>
                                        <td><?php echo htmlspecialchars($cert['purpose']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $cert['status'] === 'active' ? 'success' : ($cert['status'] === 'expired' ? 'warning' : 'danger'); ?>">
                                                <?php echo strtoupper($cert['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_certificate.php?id=<?php echo $cert['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">No certificates yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($role === 'web_admin' && !empty($recent_appointments)): ?>
                <!-- Recent Appointments (For Web Admin) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-calendar-event"></i> Recent Appointments</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Patient</th>
                                        <th>Clinic</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_appointments as $appt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appt['appointment_date']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($appt['time_slot'], 0, 5)); ?></td>
                                        <td><?php echo htmlspecialchars($appt['patient_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($appt['clinic_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($appt['purpose'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $appt['status'] === 'approved' ? 'success' : ($appt['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                                <?php echo strtoupper($appt['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Activity Feed (For Web Admin - moved from Clinic Admin) -->
                <?php if ($role === 'web_admin' && !empty($recent_activity)): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-activity"></i> Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($recent_activity as $activity): ?>
                        <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                            <div class="me-3">
                                <span class="badge bg-<?php echo $activity['action'] === 'CREATE_CERTIFICATE' ? 'success' : 'info'; ?> rounded-circle" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                                    <i class="bi bi-<?php echo $activity['action'] === 'CREATE_CERTIFICATE' ? 'plus-circle' : 'eye'; ?>"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?></div>
                                <div class="text-muted small">
                                    <?php echo htmlspecialchars($activity['action']); ?> - <?php echo htmlspecialchars($activity['entity_type']); ?>
                                    <?php if ($activity['entity_id']): ?>
                                        #<?php echo $activity['entity_id']; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small mt-1">
                                    <i class="bi bi-clock"></i> <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Notification Dropdown -->
<div class="notification-dropdown" id="notificationDropdown">
    <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light">
        <h6 class="mb-0 fw-bold">Notifications</h6>
        <div>
            <button class="btn btn-sm btn-link text-primary" onclick="markAllAsRead()">Mark all as read</button>
            <button class="btn btn-sm btn-link text-muted" onclick="closeNotifications()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
    <div id="notificationList" style="max-height: 400px; overflow-y: auto;">
        <div class="text-center p-4 text-muted">
            <i class="bi bi-bell-slash fs-1"></i>
            <p class="mt-2 mb-0">No notifications</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let notificationCheckInterval;

// Fetch notification count
async function fetchNotificationCount() {
    try {
        const response = await fetch('../api/notifications.php?action=count');
        const data = await response.json();
        const badge = document.getElementById('notificationBadge');
        if (data.count > 0) {
            badge.textContent = data.count > 99 ? '99+' : data.count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    } catch (error) {
        console.error('Error fetching notification count:', error);
    }
}

// Fetch notifications
async function fetchNotifications() {
    try {
        const response = await fetch('../api/notifications.php?limit=10');
        const data = await response.json();
        const list = document.getElementById('notificationList');
        
        if (!data.notifications || data.notifications.length === 0) {
            list.innerHTML = `
                <div class="text-center p-4 text-muted">
                    <i class="bi bi-bell-slash fs-1"></i>
                    <p class="mt-2 mb-0">No notifications</p>
                </div>
            `;
            return;
        }
        
        list.innerHTML = data.notifications.map(notif => `
            <div class="notification-item ${notif.is_read ? '' : 'unread'}" data-notification-id="${notif.id}" data-link="${escapeHtml(notif.link || '')}" onclick="handleNotificationClick(${notif.id}, ${notif.link ? "'" + escapeHtml(notif.link) + "'" : "null"})">
                <div class="notification-title">${escapeHtml(notif.title)}</div>
                <div class="notification-message">${escapeHtml(notif.message)}</div>
                <div class="notification-time">${notif.created_at}</div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error fetching notifications:', error);
    }
}

// Toggle notifications dropdown
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown.style.display === 'block') {
        closeNotifications();
    } else {
        dropdown.style.display = 'block';
        fetchNotifications();
    }
}

// Close notifications
function closeNotifications() {
    document.getElementById('notificationDropdown').style.display = 'none';
}

// Handle notification click
async function handleNotificationClick(notificationId, link) {
    // Mark as read
    try {
        const formData = new FormData();
        formData.append('notification_id', notificationId);
        await fetch('../api/notifications.php?action=mark_read', {
            method: 'POST',
            body: formData
        });
        
        // Update badge count
        fetchNotificationCount();
        
        // Update notification item
        const item = document.querySelector(`[data-notification-id="${notificationId}"]`);
        if (item) {
            item.classList.remove('unread');
        }
        
        // Navigate if link provided
        if (link) {
            window.location.href = link;
        } else {
            closeNotifications();
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
        if (link) {
            window.location.href = link;
        }
    }
}

// Mark all as read
async function markAllAsRead() {
    try {
        const formData = new FormData();
        await fetch('../api/notifications.php?action=mark_read', {
            method: 'POST',
            body: formData
        });
        
        fetchNotificationCount();
        fetchNotifications();
    } catch (error) {
        console.error('Error marking all as read:', error);
    }
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notificationDropdown');
    const bell = document.getElementById('notificationBell');
    
    if (dropdown && bell && !dropdown.contains(event.target) && !bell.contains(event.target)) {
        closeNotifications();
    }
});

// Initialize and refresh count every 30 seconds
fetchNotificationCount();
notificationCheckInterval = setInterval(fetchNotificationCount, 30000);

// Clean up interval on page unload
window.addEventListener('beforeunload', () => {
    if (notificationCheckInterval) {
        clearInterval(notificationCheckInterval);
    }
});

// Clock Widget - Philippines Timezone (Asia/Manila)
function updateClock() {
    const now = new Date();
    
    // Get Philippines time using Intl.DateTimeFormat
    const timeFormatter = new Intl.DateTimeFormat('en-US', {
        timeZone: 'Asia/Manila',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    });
    
    const dateFormatter = new Intl.DateTimeFormat('en-US', {
        timeZone: 'Asia/Manila',
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    const timeParts = timeFormatter.formatToParts(now);
    const timeString = timeParts.map(part => {
        if (part.type === 'hour' || part.type === 'minute' || part.type === 'second') {
            return part.value;
        }
        return '';
    }).filter(Boolean).join(':');
    
    const dateString = dateFormatter.format(now);
    
    const clockTime = document.getElementById('clock-time');
    const clockDate = document.getElementById('clock-date');
    
    if (clockTime) clockTime.textContent = timeString;
    if (clockDate) clockDate.textContent = dateString;
}

// Update clock immediately and then every second
updateClock();
setInterval(updateClock, 1000);
</script>
</body>
</html>

