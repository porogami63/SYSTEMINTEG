<?php
require_once '../config.php';

if (!isLoggedIn() || !isWebAdmin()) {
    redirect('dashboard.php');
}

try {
    $db = Database::getInstance();
    
    // Date range filter
    $date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : date('Y-m-d', strtotime('-6 months'));
    $date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : date('Y-m-d');
    
    // Overall System Statistics
    $total_users = $db->fetch("SELECT COUNT(*) as count FROM users");
    $total_patients = $db->fetch("SELECT COUNT(*) as count FROM patients");
    $total_clinics = $db->fetch("SELECT COUNT(*) as count FROM clinics");
    $active_clinics = $db->fetch("SELECT COUNT(*) as count FROM clinics WHERE is_available = 1");
    $total_certs = $db->fetch("SELECT COUNT(*) as count FROM certificates");
    $active_certs = $db->fetch("SELECT COUNT(*) as count FROM certificates WHERE status = 'active'");
    $expired_certs = $db->fetch("SELECT COUNT(*) as count FROM certificates WHERE status = 'expired'");
    $revoked_certs = $db->fetch("SELECT COUNT(*) as count FROM certificates WHERE status = 'revoked'");
    $total_appts = $db->fetch("SELECT COUNT(*) as count FROM appointments");
    $pending_appts = $db->fetch("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'");
    $confirmed_appts = $db->fetch("SELECT COUNT(*) as count FROM appointments WHERE status = 'confirmed'");
    $completed_appts = $db->fetch("SELECT COUNT(*) as count FROM appointments WHERE status = 'completed'");
    
    // Recent Activity (last 30 days)
    $recent_users = $db->fetch("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $recent_certs = $db->fetch("SELECT COUNT(*) as count FROM certificates WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $recent_appts = $db->fetch("SELECT COUNT(*) as count FROM appointments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    
    // Monthly Certificate Statistics
    $monthly_certs = $db->fetchAll(
        "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
         FROM certificates 
         WHERE created_at BETWEEN ? AND ?
         GROUP BY DATE_FORMAT(created_at, '%Y-%m')
         ORDER BY month ASC",
        [$date_from, $date_to]
    );
    
    // Monthly Appointment Statistics
    $monthly_appts = $db->fetchAll(
        "SELECT DATE_FORMAT(appointment_date, '%Y-%m') as month, COUNT(*) as count
         FROM appointments
         WHERE appointment_date BETWEEN ? AND ?
         GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
         ORDER BY month ASC",
        [$date_from, $date_to]
    );
    
    // Certificate Status Distribution
    $cert_status_dist = $db->fetchAll(
        "SELECT status, COUNT(*) as count 
         FROM certificates 
         GROUP BY status"
    );
    
    // Appointment Status Distribution
    $appt_status_dist = $db->fetchAll(
        "SELECT status, COUNT(*) as count 
         FROM appointments 
         GROUP BY status"
    );
    
    // Top Clinics by Certificate Count
    $top_clinics = $db->fetchAll(
        "SELECT c.clinic_name, c.specialization, COUNT(cert.id) as cert_count
         FROM clinics c
         LEFT JOIN certificates cert ON c.id = cert.clinic_id
         GROUP BY c.id
         ORDER BY cert_count DESC
         LIMIT 10"
    );
    
    // Specialization Distribution
    $spec_dist = $db->fetchAll(
        "SELECT specialization, COUNT(*) as count
         FROM clinics
         GROUP BY specialization
         ORDER BY count DESC"
    );
    
    // Security Events (last 7 days)
    $security_events = $db->fetchAll(
        "SELECT action, COUNT(*) as count
         FROM audit_logs
         WHERE action LIKE 'SECURITY_%' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         GROUP BY action
         ORDER BY count DESC
         LIMIT 10"
    );
    
    // System Health Metrics
    $db_size = $db->fetch("SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
        FROM information_schema.TABLES 
        WHERE table_schema = DATABASE()");
    
    $audit_log_count = $db->fetch("SELECT COUNT(*) as count FROM audit_logs");
    $recent_audit_logs = $db->fetch("SELECT COUNT(*) as count FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    
} catch (Exception $e) {
    $error = 'Error loading system statistics: ' . $e->getMessage();
}

$page_title = 'System Statistics';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($page_title); ?> - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<?php include 'includes/role_styles.php'; ?>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="main-content mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-graph-up"></i> System Statistics</h2>
            <p class="text-muted">Comprehensive system-wide analytics and metrics</p>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <!-- Date Range Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Overall Statistics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-primary">
                <div class="card-body text-center">
                    <i class="bi bi-people fs-1 text-primary"></i>
                    <h3 class="mt-2"><?php echo number_format($total_users['count']); ?></h3>
                    <p class="text-muted mb-0">Total Users</p>
                    <small class="text-success">+<?php echo $recent_users['count']; ?> this month</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-success">
                <div class="card-body text-center">
                    <i class="bi bi-hospital fs-1 text-success"></i>
                    <h3 class="mt-2"><?php echo number_format($total_clinics['count']); ?></h3>
                    <p class="text-muted mb-0">Total Clinics</p>
                    <small class="text-info"><?php echo $active_clinics['count']; ?> active</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-info">
                <div class="card-body text-center">
                    <i class="bi bi-file-earmark-medical fs-1 text-info"></i>
                    <h3 class="mt-2"><?php echo number_format($total_certs['count']); ?></h3>
                    <p class="text-muted mb-0">Total Certificates</p>
                    <small class="text-success">+<?php echo $recent_certs['count']; ?> this month</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-warning">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-check fs-1 text-warning"></i>
                    <h3 class="mt-2"><?php echo number_format($total_appts['count']); ?></h3>
                    <p class="text-muted mb-0">Total Appointments</p>
                    <small class="text-success">+<?php echo $recent_appts['count']; ?> this month</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Certificate & Appointment Status -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-medical"></i> Certificate Status</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <h4 class="text-success"><?php echo $active_certs['count']; ?></h4>
                            <small class="text-muted">Active</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-warning"><?php echo $expired_certs['count']; ?></h4>
                            <small class="text-muted">Expired</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-danger"><?php echo $revoked_certs['count']; ?></h4>
                            <small class="text-muted">Revoked</small>
                        </div>
                    </div>
                    <hr>
                    <canvas id="certStatusChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Appointment Status</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <h4 class="text-warning"><?php echo $pending_appts['count']; ?></h4>
                            <small class="text-muted">Pending</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-info"><?php echo $confirmed_appts['count']; ?></h4>
                            <small class="text-muted">Confirmed</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-success"><?php echo $completed_appts['count']; ?></h4>
                            <small class="text-muted">Completed</small>
                        </div>
                    </div>
                    <hr>
                    <canvas id="apptStatusChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trends -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Monthly Trends</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendsChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Clinics & Specializations -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-trophy"></i> Top Clinics by Certificates</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Clinic Name</th>
                                    <th>Specialization</th>
                                    <th class="text-end">Certificates</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_clinics as $clinic): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($clinic['clinic_name']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($clinic['specialization']); ?></span></td>
                                    <td class="text-end"><strong><?php echo $clinic['cert_count']; ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Specialization Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="specializationChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Security & System Health -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-shield-exclamation"></i> Security Events (Last 7 Days)</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($security_events)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Event Type</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($security_events as $event): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($event['action']); ?></code></td>
                                    <td class="text-end"><span class="badge bg-danger"><?php echo $event['count']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-success"><i class="bi bi-check-circle"></i> No security events detected</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-server"></i> System Health</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <h6 class="text-muted">Database Size</h6>
                            <h4><?php echo $db_size['size_mb']; ?> MB</h4>
                        </div>
                        <div class="col-6 mb-3">
                            <h6 class="text-muted">Total Audit Logs</h6>
                            <h4><?php echo number_format($audit_log_count['count']); ?></h4>
                        </div>
                        <div class="col-6 mb-3">
                            <h6 class="text-muted">Logs (24h)</h6>
                            <h4><?php echo number_format($recent_audit_logs['count']); ?></h4>
                        </div>
                        <div class="col-6 mb-3">
                            <h6 class="text-muted">Total Patients</h6>
                            <h4><?php echo number_format($total_patients['count']); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Certificate Status Chart
const certStatusCtx = document.getElementById('certStatusChart').getContext('2d');
new Chart(certStatusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($cert_status_dist, 'status')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($cert_status_dist, 'count')); ?>,
            backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#6c757d']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Appointment Status Chart
const apptStatusCtx = document.getElementById('apptStatusChart').getContext('2d');
new Chart(apptStatusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($appt_status_dist, 'status')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($appt_status_dist, 'count')); ?>,
            backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545', '#6c757d']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Monthly Trends Chart
const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
new Chart(monthlyTrendsCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($monthly_certs, 'month')); ?>,
        datasets: [{
            label: 'Certificates',
            data: <?php echo json_encode(array_column($monthly_certs, 'count')); ?>,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.4
        }, {
            label: 'Appointments',
            data: <?php echo json_encode(array_column($monthly_appts, 'count')); ?>,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Specialization Chart
const specializationCtx = document.getElementById('specializationChart').getContext('2d');
new Chart(specializationCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($spec_dist, 'specialization')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($spec_dist, 'count')); ?>,
            backgroundColor: [
                '#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8',
                '#6c757d', '#e83e8c', '#fd7e14', '#20c997', '#6f42c1'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right'
            }
        }
    }
});
</script>

            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
