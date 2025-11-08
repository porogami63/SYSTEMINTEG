<?php
require_once '../config.php';

// Only Web Admin can access this page
if (!isLoggedIn() || !isWebAdmin()) {
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];

try {
    $db = Database::getInstance();
    
    // Handle export
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'User', 'Action', 'Entity Type', 'Entity ID', 'IP Address', 'User Agent', 'Created At']);
        
        $logs = AuditLogger::getLogs($_GET, 10000, 0);
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['user_name'] ?? 'System',
                $log['action'],
                $log['entity_type'],
                $log['entity_id'] ?? 'N/A',
                $log['ip_address'] ?? 'N/A',
                $log['user_agent'] ?? 'N/A',
                $log['created_at']
            ]);
        }
        fclose($output);
        exit;
    }
    
    // Filters
    $filters = [];
    if (!empty($_GET['user_id'])) {
        $filters['user_id'] = intval($_GET['user_id']);
    }
    if (!empty($_GET['action'])) {
        $filters['action'] = sanitizeInput($_GET['action']);
    }
    if (!empty($_GET['entity_type'])) {
        $filters['entity_type'] = sanitizeInput($_GET['entity_type']);
    }
    if (!empty($_GET['date_from'])) {
        $filters['date_from'] = sanitizeInput($_GET['date_from']);
    }
    if (!empty($_GET['date_to'])) {
        $filters['date_to'] = sanitizeInput($_GET['date_to']);
    }
    
    // Pagination
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $per_page = 50;
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $total_logs = AuditLogger::getLogCount($filters);
    $total_pages = ceil($total_logs / $per_page);
    
    // Get logs
    $logs = AuditLogger::getLogs($filters, $per_page, $offset);
    
    // Get all users for filter dropdown
    $users = $db->fetchAll("SELECT id, username, full_name FROM users ORDER BY full_name");
    
    // Get all actions for filter dropdown
    $actions = $db->fetchAll("SELECT DISTINCT action FROM audit_logs ORDER BY action");
    
    // Get entity types
    $entity_types = $db->fetchAll("SELECT DISTINCT entity_type FROM audit_logs ORDER BY entity_type");
    
} catch (Exception $e) {
    $logs = [];
    $users = [];
    $actions = [];
    $entity_types = [];
    $total_logs = 0;
    $total_pages = 0;
    $page = 1;
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Audit Logs - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<?php include 'includes/role_styles.php'; ?>
<style>
.stats-card { border-left: 4px solid <?php echo $_SESSION['role'] === 'web_admin' ? '#00ff88' : '#dc3545'; ?>; }
.table-container { max-height: 600px; overflow-y: auto; }
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0"><i class="bi bi-shield-check"></i> Audit Logs</h2>
                        <p class="text-muted">System activity and security logs</p>
                    </div>
                    <?php if ($total_logs > 0): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-danger">
                        <i class="bi bi-download"></i> Export CSV
                    </a>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card stats-card shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Total Logs</h6>
                                <h2 class="text-danger"><?php echo number_format($total_logs); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm" style="border-left: 4px solid #28a745;">
                            <div class="card-body">
                                <h6 class="text-muted">This Week</h6>
                                <h2 class="text-success">
                                    <?php 
                                    $week_logs = $db->fetch("SELECT COUNT(*) as count FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                                    echo number_format($week_logs['count']); 
                                    ?>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm" style="border-left: 4px solid #17a2b8;">
                            <div class="card-body">
                                <h6 class="text-muted">Today</h6>
                                <h2 class="text-info">
                                    <?php 
                                    $today_logs = $db->fetch("SELECT COUNT(*) as count FROM audit_logs WHERE DATE(created_at) = CURDATE()");
                                    echo number_format($today_logs['count']); 
                                    ?>
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">User</label>
                                <select class="form-select" name="user_id">
                                    <option value="">All Users</option>
                                    <?php foreach ($users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>" <?php echo (isset($filters['user_id']) && $filters['user_id'] == $u['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($u['full_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Action</label>
                                <select class="form-select" name="action">
                                    <option value="">All Actions</option>
                                    <?php foreach ($actions as $action): ?>
                                    <option value="<?php echo htmlspecialchars($action['action']); ?>" <?php echo (isset($filters['action']) && $filters['action'] == $action['action']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($action['action']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Entity Type</label>
                                <select class="form-select" name="entity_type">
                                    <option value="">All Types</option>
                                    <?php foreach ($entity_types as $et): ?>
                                    <option value="<?php echo htmlspecialchars($et['entity_type']); ?>" <?php echo (isset($filters['entity_type']) && $filters['entity_type'] == $et['entity_type']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($et['entity_type']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" name="date_from" value="<?php echo isset($filters['date_from']) ? htmlspecialchars($filters['date_from']) : ''; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" name="date_to" value="<?php echo isset($filters['date_to']) ? htmlspecialchars($filters['date_to']) : ''; ?>">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                        <?php if (!empty($_GET)): ?>
                        <div class="mt-2">
                            <a href="audit_logs.php" class="btn btn-sm btn-secondary">Clear Filters</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Audit Logs Table -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive table-container">
                            <table class="table table-hover table-sm">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>ID</th>
                                        <th>Time</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Entity</th>
                                        <th>IP Address</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-1"></i>
                                            <p class="mt-2">No audit logs found</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo $log['id']; ?></td>
                                        <td>
                                            <small>
                                                <?php echo date('M d, Y', strtotime($log['created_at'])); ?><br>
                                                <?php echo date('H:i:s', strtotime($log['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if (!empty($log['user_name'])): ?>
                                                <strong><?php echo htmlspecialchars($log['user_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($log['username']); ?></small>
                                            <?php else: ?>
                                                <em class="text-muted">System</em>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($log['action']); ?></span>
                                        </td>
                                        <td>
                                            <small>
                                                <strong><?php echo htmlspecialchars($log['entity_type']); ?></strong><br>
                                                <?php echo $log['entity_id'] ? '#' . $log['entity_id'] : 'N/A'; ?>
                                            </small>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></small></td>
                                        <td>
                                            <?php if (!empty($log['details'])): ?>
                                                <?php 
                                                $details = json_decode($log['details'], true);
                                                if ($details) {
                                                    echo '<small class="text-muted">';
                                                    foreach ($details as $key => $value) {
                                                        if (is_string($key) && strlen($value) < 50) {
                                                            echo htmlspecialchars($key) . ': ' . htmlspecialchars($value) . '<br>';
                                                        }
                                                    }
                                                    echo '</small>';
                                                }
                                                ?>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page == 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                                </li>
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page == $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
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

