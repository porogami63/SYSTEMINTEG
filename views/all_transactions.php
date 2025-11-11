<?php
require_once '../config.php';

if (!isLoggedIn() || !isWebAdmin()) {
    redirect('dashboard.php');
}

$db = Database::getInstance();
$error = '';
$success = '';

// Handle delete payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_payment') {
    $payment_id = intval($_POST['payment_id'] ?? 0);
    if ($payment_id) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF'])) . '/api/delete_payment.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['payment_id' => $payment_id]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        if ($result && $result['success']) {
            $success = 'Payment transaction deleted successfully';
        } else {
            $error = $result['error'] ?? 'Failed to delete payment';
        }
    }
}

// Handle search and filters
$search_term = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$type_filter = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
$date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';

// Build query with filters
$where = ["1=1"];
$params = [];

if (!empty($search_term)) {
    $where[] = "(p.transaction_id LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR c.cert_id LIKE ?)";
    $search_param = '%' . $search_term . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $where[] = "p.payment_status = ?";
    $params[] = $status_filter;
}

if (!empty($type_filter)) {
    $where[] = "p.payment_type = ?";
    $params[] = $type_filter;
}

if (!empty($date_from)) {
    $where[] = "DATE(p.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where[] = "DATE(p.created_at) <= ?";
    $params[] = $date_to;
}

// Get all transactions
$transactions = $db->fetchAll(
    "SELECT p.*, 
     u.full_name as patient_name,
     u.email as patient_email,
     cl.clinic_name,
     cl.user_id as clinic_user_id,
     CASE 
         WHEN p.payment_type = 'certificate' THEN c.cert_id
         WHEN p.payment_type = 'appointment' THEN CONCAT('APT-', a.id)
         ELSE 'N/A'
     END as reference_code,
     CASE 
         WHEN p.payment_type = 'certificate' THEN c.purpose
         WHEN p.payment_type = 'appointment' THEN CONCAT(a.purpose, ' - ', a.appointment_date, ' ', SUBSTRING(a.time_slot, 1, 5))
         ELSE 'N/A'
     END as description
     FROM payments p
     LEFT JOIN users u ON p.user_id = u.id
     LEFT JOIN certificates c ON p.payment_type = 'certificate' AND p.reference_id = c.id
     LEFT JOIN appointments a ON p.payment_type = 'appointment' AND p.reference_id = a.id
     LEFT JOIN clinics cl ON (c.clinic_id = cl.id OR a.clinic_id = cl.id)
     WHERE " . implode(' AND ', $where) . "
     ORDER BY p.created_at DESC",
    $params
);

// Calculate totals
$total_revenue = 0;
$total_count = count($transactions);
$paid_count = 0;
foreach ($transactions as $t) {
    if ($t['payment_status'] === 'paid') {
        $total_revenue += $t['amount'];
        $paid_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Transactions - MediArchive</title>
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
                <h2 class="mb-4"><i class="bi bi-receipt-cutoff"></i> All Transactions (Moderation)</h2>

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

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-cash-stack"></i> Total Revenue</h5>
                                <h3 class="mb-0">₱<?php echo number_format($total_revenue, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-check-circle"></i> Paid</h5>
                                <h3 class="mb-0"><?php echo $paid_count; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-list-ul"></i> Total</h5>
                                <h3 class="mb-0"><?php echo $total_count; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-exclamation-triangle"></i> Pending</h5>
                                <h3 class="mb-0"><?php echo count(array_filter($transactions, function($t) { return $t['payment_status'] === 'pending'; })); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Transaction ID, Name, Email">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Type</label>
                                <select class="form-select" name="type">
                                    <option value="">All Types</option>
                                    <option value="certificate" <?php echo $type_filter === 'certificate' ? 'selected' : ''; ?>>Certificate</option>
                                    <option value="appointment" <?php echo $type_filter === 'appointment' ? 'selected' : ''; ?>>Appointment</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                        <?php if (!empty($_GET)): ?>
                        <div class="mt-2">
                            <a href="all_transactions.php" class="btn btn-sm btn-secondary">Clear Filters</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if (!empty($transactions)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Type</th>
                                        <th>Patient</th>
                                        <th>Clinic</th>
                                        <th>Reference</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $t): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($t['transaction_id']); ?></code></td>
                                        <td>
                                            <span class="badge bg-<?php echo $t['payment_type'] === 'certificate' ? 'info' : 'primary'; ?>">
                                                <?php echo ucfirst($t['payment_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($t['patient_name'] ?? 'N/A'); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($t['patient_email'] ?? ''); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($t['clinic_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($t['reference_code']); ?></td>
                                        <td><?php echo htmlspecialchars($t['description']); ?></td>
                                        <td><strong>₱<?php echo number_format($t['amount'], 2); ?></strong></td>
                                        <td>
                                            <?php
                                            $method_labels = [
                                                'cash' => 'Cash',
                                                'credit_card' => 'Credit Card',
                                                'debit_card' => 'Debit Card',
                                                'gcash' => 'GCash',
                                                'paymaya' => 'PayMaya',
                                                'bank_transfer' => 'Bank Transfer'
                                            ];
                                            echo $method_labels[$t['payment_method']] ?? ucfirst($t['payment_method']);
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $t['payment_status'] === 'paid' ? 'success' : ($t['payment_status'] === 'pending' ? 'warning' : ($t['payment_status'] === 'failed' ? 'danger' : 'secondary')); ?>">
                                                <?php echo ucfirst($t['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y g:i A', strtotime($t['payment_date'] ?? $t['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <?php if ($t['payment_type'] === 'certificate'): ?>
                                                <a href="view_certificate.php?id=<?php echo $t['reference_id']; ?>" class="btn btn-sm btn-outline-primary" title="View Certificate">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php elseif ($t['payment_type'] === 'appointment'): ?>
                                                <a href="all_appointments.php" class="btn btn-sm btn-outline-primary" title="View Appointments">
                                                    <i class="bi bi-calendar"></i>
                                                </a>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars($t['transaction_id']); ?>')" title="Delete Transaction">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-receipt-cutoff fs-1 text-muted"></i>
                            <p class="text-muted mt-3">No transactions found.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger"></i> Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this transaction?</p>
                <p><strong>Transaction ID:</strong> <code id="deleteTransactionId"></code></p>
                <p class="text-danger"><small>This action cannot be undone. This will permanently delete the payment record.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteForm" style="display: inline;">
                    <input type="hidden" name="action" value="delete_payment">
                    <input type="hidden" name="payment_id" id="deletePaymentId">
                    <button type="submit" class="btn btn-danger">Delete Transaction</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(paymentId, transactionId) {
    document.getElementById('deletePaymentId').value = paymentId;
    document.getElementById('deleteTransactionId').textContent = transactionId;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>
</body>
</html>

