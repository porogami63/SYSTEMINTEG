<?php
require_once '../config.php';

if (!isLoggedIn() || !isWebAdmin()) {
    redirect('dashboard.php');
}

$db = Database::getInstance();

// Date range filter
$date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : date('Y-m-d');

// System-wide statistics - optimized with single query where possible
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM clinics) as total_clinics,
    (SELECT COUNT(*) FROM patients) as total_patients,
    (SELECT COUNT(*) FROM certificates) as total_certificates,
    (SELECT COUNT(*) FROM appointments) as total_appointments,
    (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'paid') as total_payments";
$stats = $db->fetch($stats_query);

$total_users = ['count' => $stats['total_users']];
$total_clinics = ['count' => $stats['total_clinics']];
$total_patients = ['count' => $stats['total_patients']];
$total_certificates = ['count' => $stats['total_certificates']];
$total_appointments = ['count' => $stats['total_appointments']];
$total_payments = ['total' => $stats['total_payments']];

// Certificate statistics by status
$cert_by_status = $db->fetchAll("SELECT status, COUNT(*) as count FROM certificates GROUP BY status LIMIT 10");

// Appointment statistics by status  
$appt_by_status = $db->fetchAll("SELECT status, COUNT(*) as count FROM appointments GROUP BY status LIMIT 10");

// Top clinics by certificates issued
$top_clinics = $db->fetchAll(
    "SELECT cl.clinic_name, cl.specialization, COUNT(c.id) as cert_count 
     FROM clinics cl 
     LEFT JOIN certificates c ON cl.id = c.clinic_id 
     GROUP BY cl.id 
     ORDER BY cert_count DESC 
     LIMIT 10"
);

// Monthly certificate trends - limit to last 12 months for performance
$monthly_certs = $db->fetchAll(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
     FROM certificates 
     WHERE created_at BETWEEN ? AND ?
     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
     ORDER BY month DESC
     LIMIT 12",
    [$date_from, $date_to]
);

// Monthly appointment trends - limit to last 12 months
$monthly_appts = $db->fetchAll(
    "SELECT DATE_FORMAT(appointment_date, '%Y-%m') as month, COUNT(*) as count 
     FROM appointments 
     WHERE appointment_date BETWEEN ? AND ?
     GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
     ORDER BY month DESC
     LIMIT 12",
    [$date_from, $date_to]
);

// Payment statistics
$payment_stats = $db->fetchAll(
    "SELECT payment_method, COUNT(*) as count, SUM(amount) as total 
     FROM payments 
     WHERE payment_status = 'paid' AND payment_date BETWEEN ? AND ?
     GROUP BY payment_method",
    [$date_from, $date_to]
);

// Security events
$security_events = $db->fetchAll(
    "SELECT event_type, COUNT(*) as count 
     FROM security_events 
     WHERE created_at BETWEEN ? AND ?
     GROUP BY event_type 
     ORDER BY count DESC 
     LIMIT 10",
    [$date_from, $date_to]
);

// Chat activity
$chat_stats = $db->fetch(
    "SELECT COUNT(*) as total_messages, COUNT(DISTINCT conversation_id) as total_conversations 
     FROM chat_messages 
     WHERE created_at BETWEEN ? AND ?",
    [$date_from, $date_to]
);

// Verification logs (QR scans)
$verification_stats = $db->fetch(
    "SELECT COUNT(*) as total_scans 
     FROM verifications 
     WHERE verified_at BETWEEN ? AND ?",
    [$date_from, $date_to]
);

// Active users (logged in within last 7 days) - with limit
$active_users = $db->fetch(
    "SELECT COUNT(DISTINCT user_id) as count 
     FROM audit_logs 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     LIMIT 1000"
);

// System health metrics - sample recent data only
$avg_response_time = $db->fetch(
    "SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_time 
     FROM certificates 
     WHERE updated_at IS NOT NULL 
     AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     LIMIT 1000"
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Web Admin Analytics - MediArchive</title>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-graph-up"></i> System Analytics Dashboard</h2>
                    <form method="GET" class="d-flex gap-2">
                        <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                        <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Filter</button>
                    </form>
                </div>

                <!-- Key Metrics -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Users</h6>
                                        <h3 class="mb-0"><?php echo number_format($total_users['count']); ?></h3>
                                        <small class="text-muted"><?php echo $active_users['count']; ?> active (7 days)</small>
                                    </div>
                                    <div class="fs-1 text-primary"><i class="bi bi-people"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Certificates</h6>
                                        <h3 class="mb-0"><?php echo number_format($total_certificates['count']); ?></h3>
                                        <small class="text-muted"><?php echo $total_clinics['count']; ?> clinics</small>
                                    </div>
                                    <div class="fs-1 text-success"><i class="bi bi-file-medical"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Appointments</h6>
                                        <h3 class="mb-0"><?php echo number_format($total_appointments['count']); ?></h3>
                                        <small class="text-muted"><?php echo $total_patients['count']; ?> patients</small>
                                    </div>
                                    <div class="fs-1 text-info"><i class="bi bi-calendar-check"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Revenue</h6>
                                        <h3 class="mb-0">₱<?php echo number_format($total_payments['total'] ?? 0, 2); ?></h3>
                                        <small class="text-muted">From payments</small>
                                    </div>
                                    <div class="fs-1 text-warning"><i class="bi bi-cash-coin"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Tables (Charts disabled for performance) -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Certificate Trends (Last 12 Months)</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Month</th>
                                                <th class="text-end">Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($monthly_certs as $cert): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($cert['month']); ?></td>
                                                <td class="text-end"><?php echo number_format($cert['count']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Certificate Status Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Status</th>
                                                <th class="text-end">Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cert_by_status as $status): ?>
                                            <tr>
                                                <td><span class="badge bg-<?php echo $status['status'] === 'active' ? 'success' : ($status['status'] === 'expired' ? 'warning' : 'secondary'); ?>"><?php echo htmlspecialchars(ucfirst($status['status'])); ?></span></td>
                                                <td class="text-end"><?php echo number_format($status['count']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Metrics -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-shield-check"></i> Security Events</h5>
                            </div>
                            <div class="card-body">
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
                                                <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                                                <td class="text-end"><?php echo number_format($event['count']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Payment Methods</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Method</th>
                                                <th class="text-end">Count</th>
                                                <th class="text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payment_stats as $payment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(ucfirst($payment['payment_method'])); ?></td>
                                                <td class="text-end"><?php echo number_format($payment['count']); ?></td>
                                                <td class="text-end">₱<?php echo number_format($payment['total'], 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-activity"></i> System Activity</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Chat Messages</span>
                                        <strong><?php echo number_format($chat_stats['total_messages'] ?? 0); ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Conversations</span>
                                        <strong><?php echo number_format($chat_stats['total_conversations'] ?? 0); ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>QR Verifications</span>
                                        <strong><?php echo number_format($verification_stats['total_scans'] ?? 0); ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Avg Response Time</span>
                                        <strong><?php echo number_format($avg_response_time['avg_time'] ?? 0); ?>s</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Clinics -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-trophy"></i> Top Performing Clinics</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Clinic Name</th>
                                        <th>Specialization</th>
                                        <th class="text-end">Certificates Issued</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $rank = 1; foreach ($top_clinics as $clinic): ?>
                                    <tr>
                                        <td><?php echo $rank++; ?></td>
                                        <td><?php echo htmlspecialchars($clinic['clinic_name']); ?></td>
                                        <td><?php echo htmlspecialchars($clinic['specialization']); ?></td>
                                        <td class="text-end"><?php echo number_format($clinic['cert_count']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
