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
    
    // Build certificate query with filters (no clinic filter for webadmin)
    $cert_where = ["1=1"];
    $cert_params = [];
    
    if (!empty($search_term)) {
        $cert_where[] = "(c.cert_id LIKE ? OR u.full_name LIKE ? OR c.purpose LIKE ? OR c.issued_by LIKE ? OR cl.clinic_name LIKE ?)";
        $search_param = '%' . $search_term . '%';
        $cert_params[] = $search_param;
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
    
    // Get all certificates with filters (all clinics)
    $certificates = $db->fetchAll("SELECT c.*, u.full_name as patient_name, cl.clinic_name 
                       FROM certificates c 
                       JOIN patients p ON c.patient_id = p.id 
                       JOIN users u ON p.user_id = u.id
                       LEFT JOIN clinics cl ON c.clinic_id = cl.id
                       WHERE " . implode(' AND ', $cert_where) . " ORDER BY c.created_at DESC", $cert_params);
} catch (Exception $e) {
    $certificates = [];
    $error = 'Failed to load certificates: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Certificates - MediArchive</title>
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
                <h2 class="mb-4"><i class="bi bi-files"></i> All Certificates</h2>
                
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
                        <form method="get" action="all_certificates.php">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Search</label>
                                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Cert ID, Patient, Purpose, Clinic...">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="">All</option>
                                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                                        <option value="revoked" <?php echo $status_filter === 'revoked' ? 'selected' : ''; ?>>Revoked</option>
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
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-search"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Certificates Table -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">Certificates (<?php echo count($certificates); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($certificates)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Cert ID</th>
                                        <th>Patient</th>
                                        <th>Clinic</th>
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
                                        <td><?php echo htmlspecialchars($cert['clinic_name'] ?? 'N/A'); ?></td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

