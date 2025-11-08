<?php
require_once '../config.php';

if (!isLoggedIn() || !isWebAdmin()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

try {
    $db = Database::getInstance();
    
    // Handle search and filters
    $search_term = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
    $date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';
    $show = isset($_GET['show']) ? $_GET['show'] : 'all';
    
    // Build appointment query with filters (no clinic filter for webadmin)
    $appt_where = ["1=1"];
    $appt_params = [];
    
    if (!empty($search_term)) {
        $appt_where[] = "(u.full_name LIKE ? OR cl.clinic_name LIKE ? OR a.purpose LIKE ? OR p.patient_code LIKE ?)";
        $search_param = '%' . $search_term . '%';
        $appt_params[] = $search_param;
        $appt_params[] = $search_param;
        $appt_params[] = $search_param;
        $appt_params[] = $search_param;
    }
    
    if (!empty($status_filter)) {
        $appt_where[] = "a.status = ?";
        $appt_params[] = $status_filter;
    }
    
    if ($show === 'upcoming') {
        $appt_where[] = "(a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.time_slot >= CURTIME()))";
    }
    
    if (!empty($date_from)) {
        $appt_where[] = "a.appointment_date >= ?";
        $appt_params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $appt_where[] = "a.appointment_date <= ?";
        $appt_params[] = $date_to;
    }
    
    // Get all appointments with filters (all clinics)
    $appointments = $db->fetchAll(
        "SELECT a.*, p.patient_code, u.full_name AS patient_name, cl.clinic_name, cl.specialization
         FROM appointments a
         JOIN patients p ON a.patient_id = p.id
         JOIN users u ON p.user_id = u.id
         LEFT JOIN clinics cl ON a.clinic_id = cl.id
         WHERE " . implode(' AND ', $appt_where) . " 
         ORDER BY a.appointment_date ASC, a.time_slot ASC", 
        $appt_params
    );
} catch (Exception $e) {
    $appointments = [];
    $error = 'Failed to load appointments: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Appointments - MediArchive</title>
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
                <h2 class="mb-4"><i class="bi bi-calendar-event"></i> All Appointments</h2>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Search and Filters -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-funnel"></i> Search & Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" action="all_appointments.php">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Search</label>
                                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Patient, Clinic, Purpose...">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="">All</option>
                                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        <option value="rescheduled" <?php echo $status_filter === 'rescheduled' ? 'selected' : ''; ?>>Rescheduled</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">View</label>
                                    <select class="form-select" name="show">
                                        <option value="all" <?php echo $show === 'all' ? 'selected' : ''; ?>>All</option>
                                        <option value="upcoming" <?php echo $show === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">From Date</label>
                                    <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">To Date</label>
                                    <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                                </div>
                                <div class="col-md-1 mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Appointments Table -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">Appointments (<?php echo count($appointments); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($appointments)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Patient</th>
                                        <th>Clinic</th>
                                        <th>Specialization</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $appt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appt['appointment_date']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($appt['time_slot'], 0, 5)); ?></td>
                                        <td><?php echo htmlspecialchars($appt['patient_name']); ?> <span class="text-muted small">(<?php echo htmlspecialchars($appt['patient_code']); ?>)</span></td>
                                        <td><?php echo htmlspecialchars($appt['clinic_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($appt['specialization'] ?? 'N/A'); ?></td>
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
                        <?php else: ?>
                        <p class="text-muted text-center py-5">No appointments found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

