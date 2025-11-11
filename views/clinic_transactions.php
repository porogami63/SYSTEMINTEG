<?php
require_once '../config.php';

if (!isLoggedIn() || !isClinicAdmin()) {
    redirect('dashboard.php');
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Get clinic ID
$clinic = $db->fetch("SELECT id FROM clinics WHERE user_id = ?", [$user_id]);
if (!$clinic) {
    redirect('dashboard.php');
}

// Get all transactions for certificates and appointments related to this clinic
$transactions = $db->fetchAll(
    "SELECT p.*, 
     u.full_name as patient_name,
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
     LEFT JOIN certificates c ON p.payment_type = 'certificate' AND p.reference_id = c.id AND c.clinic_id = ?
     LEFT JOIN appointments a ON p.payment_type = 'appointment' AND p.reference_id = a.id AND a.clinic_id = ?
     LEFT JOIN users u ON p.user_id = u.id
     WHERE (c.id IS NOT NULL OR a.id IS NOT NULL)
     ORDER BY p.created_at DESC",
    [$clinic['id'], $clinic['id']]
);

// Calculate totals
$total_revenue = 0;
$total_count = count($transactions);
foreach ($transactions as $t) {
    if ($t['payment_status'] === 'paid') {
        $total_revenue += $t['amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transactions - MediArchive</title>
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
                <h2 class="mb-4"><i class="bi bi-receipt-cutoff"></i> Transactions</h2>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-cash-stack"></i> Total Revenue</h5>
                                <h3 class="mb-0">₱<?php echo number_format($total_revenue, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-check-circle"></i> Total Transactions</h5>
                                <h3 class="mb-0"><?php echo $total_count; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-currency-exchange"></i> Paid Transactions</h5>
                                <h3 class="mb-0"><?php echo count(array_filter($transactions, function($t) { return $t['payment_status'] === 'paid'; })); ?></h3>
                            </div>
                        </div>
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
                                        <th>Reference</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
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
                                        <td><?php echo htmlspecialchars($t['patient_name'] ?? 'N/A'); ?></td>
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
                                            <?php if ($t['payment_type'] === 'certificate'): ?>
                                            <a href="view_certificate.php?id=<?php echo $t['reference_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <?php elseif ($t['payment_type'] === 'appointment'): ?>
                                            <a href="clinic_appointments.php" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-calendar"></i> View
                                            </a>
                                            <?php endif; ?>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

