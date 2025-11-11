<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Get all transactions for the user
$transactions = $db->fetchAll(
    "SELECT p.*, 
     CASE 
         WHEN p.payment_type = 'certificate' THEN c.cert_id
         WHEN p.payment_type = 'appointment' THEN CONCAT('APT-', a.id)
         ELSE 'N/A'
     END as reference_code,
     CASE 
         WHEN p.payment_type = 'certificate' THEN c.purpose
         WHEN p.payment_type = 'appointment' THEN CONCAT(a.purpose, ' - ', cl.clinic_name)
         ELSE 'N/A'
     END as description
     FROM payments p
     LEFT JOIN certificates c ON p.payment_type = 'certificate' AND p.reference_id = c.id
     LEFT JOIN appointments a ON p.payment_type = 'appointment' AND p.reference_id = a.id
     LEFT JOIN clinics cl ON a.clinic_id = cl.id
     WHERE p.user_id = ?
     ORDER BY p.created_at DESC",
    [$user_id]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Transactions - MediArchive</title>
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
                <h2 class="mb-4"><i class="bi bi-receipt-cutoff"></i> My Transactions</h2>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if (!empty($transactions)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Type</th>
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
                                            <a href="my_appointments.php" class="btn btn-sm btn-outline-primary">
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

