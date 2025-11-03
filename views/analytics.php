<?php
require_once '../config.php';

if (!isLoggedIn() || !isClinicAdmin()) {
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];

try {
    $db = Database::getInstance();
    $clinic = $db->fetch("SELECT c.* FROM clinics c WHERE c.user_id = ?", [$user_id]);
    $clinic_id = $clinic['id'] ?? 0;
    
    // Date range filter
    $date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : date('Y-m-d', strtotime('-6 months'));
    $date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : date('Y-m-d');
    
    // Overall statistics
    $total_certs = $db->fetch("SELECT COUNT(*) as count FROM certificates WHERE clinic_id = ?", [$clinic_id]);
    $active_certs = $db->fetch("SELECT COUNT(*) as count FROM certificates WHERE clinic_id = ? AND status = 'active'", [$clinic_id]);
    $expired_certs = $db->fetch("SELECT COUNT(*) as count FROM certificates WHERE clinic_id = ? AND status = 'expired'", [$clinic_id]);
    
    // Monthly statistics
    $monthly_data = $db->fetchAll(
        "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
         FROM certificates 
         WHERE clinic_id = ? AND created_at BETWEEN ? AND ?
         GROUP BY DATE_FORMAT(created_at, '%Y-%m')
         ORDER BY month ASC",
        [$clinic_id, $date_from, $date_to]
    );
    
    // Purpose statistics
    $purpose_stats = $db->fetchAll(
        "SELECT purpose, COUNT(*) as count 
         FROM certificates 
         WHERE clinic_id = ? AND created_at BETWEEN ? AND ?
         GROUP BY purpose 
         ORDER BY count DESC 
         LIMIT 10",
        [$clinic_id, $date_from, $date_to]
    );
    
    // Status distribution
    $status_dist = $db->fetchAll(
        "SELECT status, COUNT(*) as count 
         FROM certificates 
         WHERE clinic_id = ?
         GROUP BY status",
        [$clinic_id]
    );
    
    // Patient statistics
    $total_patients = $db->fetch("SELECT COUNT(DISTINCT patient_id) as count FROM certificates WHERE clinic_id = ?", [$clinic_id]);
    
    // Expiry statistics
    $expiry_stats = ExpiryManager::getExpiryStats($clinic_id);
    
    // Recent activity (last 10 actions) - filtered by clinic
    $recent_activity = AuditLogger::getLogs(['entity_type' => 'certificate', 'clinic_id' => $clinic_id], 10, 0);
    
} catch (Exception $e) {
    $total_certs = ['count' => 0];
    $active_certs = ['count' => 0];
    $expired_certs = ['count' => 0];
    $monthly_data = [];
    $purpose_stats = [];
    $status_dist = [];
    $total_patients = ['count' => 0];
    $expiry_stats = ['expiring_this_week' => 0, 'expiring_this_month' => 0, 'already_expired' => 0];
    $recent_activity = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analytics Dashboard - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.sidebar { min-height: 100vh; background: linear-gradient(180deg, #2e7d32 0%, #1b5e20 100%); }
.sidebar .nav-link { color: white; padding: 12px 20px; margin: 5px 0; }
.sidebar .nav-link.active { background: rgba(255,255,255,0.2); }
.main-content { padding: 30px; }
.stats-card { border-left: 4px solid #2e7d32; }
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0"><i class="bi bi-graph-up"></i> Analytics Dashboard</h2>
                    <form method="GET" class="d-flex gap-2">
                        <input type="date" class="form-control form-control-sm" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        <input type="date" class="form-control form-control-sm" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    </form>
                </div>
                
                <!-- Overall Stats -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Total Certificates</h6>
                                <h2 class="text-primary"><?php echo $total_certs['count']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card shadow-sm" style="border-left: 4px solid #28a745;">
                            <div class="card-body">
                                <h6 class="text-muted">Active</h6>
                                <h2 class="text-success"><?php echo $active_certs['count']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card shadow-sm" style="border-left: 4px solid #ffc107;">
                            <div class="card-body">
                                <h6 class="text-muted">Expired</h6>
                                <h2 class="text-warning"><?php echo $expired_certs['count']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card shadow-sm" style="border-left: 4px solid #17a2b8;">
                            <div class="card-body">
                                <h6 class="text-muted">Total Patients</h6>
                                <h2 class="text-info"><?php echo $total_patients['count']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="mb-0">Certificates Issued (Monthly)</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="mb-0">Status Distribution</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="statusChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-12 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="mb-0">Top Purposes</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="purposeChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Expiry Stats -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm bg-warning bg-opacity-10">
                            <div class="card-body">
                                <h6 class="text-muted">Expiring This Week</h6>
                                <h3 class="text-warning"><?php echo $expiry_stats['expiring_this_week']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm bg-info bg-opacity-10">
                            <div class="card-body">
                                <h6 class="text-muted">Expiring This Month</h6>
                                <h3 class="text-info"><?php echo $expiry_stats['expiring_this_month']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm bg-danger bg-opacity-10">
                            <div class="card-body">
                                <h6 class="text-muted">Already Expired</h6>
                                <h3 class="text-danger"><?php echo $expiry_stats['already_expired']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0">Recent Activity</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_activity)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>User</th>
                                        <th>Entity</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_activity as $activity): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($activity['action']); ?></span></td>
                                        <td><?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?></td>
                                        <td><?php echo htmlspecialchars($activity['entity_type']); ?> #<?php echo $activity['entity_id'] ?? 'N/A'; ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">No recent activity</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Monthly Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: [<?php echo implode(',', array_map(function($m) { return "'" . $m['month'] . "'"; }, $monthly_data)); ?>],
        datasets: [{
            label: 'Certificates Issued',
            data: [<?php echo implode(',', array_map(function($m) { return $m['count']; }, $monthly_data)); ?>],
            borderColor: '#2e7d32',
            backgroundColor: 'rgba(46, 125, 50, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php echo implode(',', array_map(function($s) { return "'" . ucfirst($s['status']) . "'"; }, $status_dist)); ?>],
        datasets: [{
            data: [<?php echo implode(',', array_map(function($s) { return $s['count']; }, $status_dist)); ?>],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Purpose Chart
const purposeCtx = document.getElementById('purposeChart').getContext('2d');
new Chart(purposeCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo implode(',', array_map(function($p) { return "'" . htmlspecialchars($p['purpose'], ENT_QUOTES) . "'"; }, $purpose_stats)); ?>],
        datasets: [{
            label: 'Count',
            data: [<?php echo implode(',', array_map(function($p) { return $p['count']; }, $purpose_stats)); ?>],
            backgroundColor: '#2e7d32'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
</body>
</html>

