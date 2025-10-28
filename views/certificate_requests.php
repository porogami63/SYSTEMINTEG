<?php
require_once '../config.php';

if (!isLoggedIn() || !isClinicAdmin()) {
    redirect('dashboard.php');
}

$conn = getDBConnection();

// Get clinic id for current admin
$stmt = $conn->prepare("SELECT id FROM clinics WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$clinic = $stmt->get_result()->fetch_assoc();
$stmt->close();

$clinic_id = $clinic ? intval($clinic['id']) : 0;

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['status'])) {
    $rid = intval($_POST['request_id']);
    $status = in_array($_POST['status'], ['pending','approved','rejected','completed']) ? $_POST['status'] : 'pending';
    $up = $conn->prepare("UPDATE certificate_requests SET status = ? WHERE id = ? AND clinic_id = ?");
    $up->bind_param("sii", $status, $rid, $clinic_id);
    $up->execute();
    $up->close();
    // notify patient of status change
    $getp = $conn->prepare("SELECT u.id as user_id, r.status FROM certificate_requests r JOIN patients p ON r.patient_id = p.id JOIN users u ON p.user_id = u.id WHERE r.id = ? AND r.clinic_id = ?");
    $getp->bind_param("ii", $rid, $clinic_id);
    $getp->execute();
    $info = $getp->get_result()->fetch_assoc();
    $getp->close();
    if ($info) {
        $title = 'Request ' . ucfirst($status);
        $msg = 'Your certificate request has been updated to ' . $status . '.';
        notifyUser($conn, intval($info['user_id']), $title, $msg, 'my_certificates.php');
    }
}

// Fetch requests for this clinic
$stmt = $conn->prepare("SELECT r.*, u.full_name as patient_name, u.email as patient_email
                        FROM certificate_requests r
                        JOIN patients p ON r.patient_id = p.id
                        JOIN users u ON p.user_id = u.id
                        WHERE r.clinic_id = ? ORDER BY r.created_at DESC");
$stmt->bind_param("i", $clinic_id);
$stmt->execute();
$requests = $stmt->get_result();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Certificate Requests - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
.sidebar { min-height: 100vh; background: linear-gradient(180deg, #2e7d32 0%, #1b5e20 100%); }
.sidebar .nav-link { color: white; padding: 12px 20px; margin: 5px 0; }
.sidebar .nav-link.active { background: rgba(255,255,255,0.2); }
.main-content { padding: 30px; }
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="main-content">
                <h2 class="mb-4"><i class="bi bi-inbox"></i> Certificate Requests</h2>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if ($requests->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Purpose</th>
                                        <th>Requested Spec.</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($r = $requests->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($r['patient_name']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($r['patient_email']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($r['purpose']); ?></td>
                                        <td><?php echo htmlspecialchars($r['requested_specialization']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $r['status']==='pending'?'warning':($r['status']==='approved'?'success':($r['status']==='completed'?'primary':'secondary')); ?>">
                                                <?php echo ucfirst($r['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-flex gap-2">
                                                <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                                <select name="status" class="form-select form-select-sm" style="width:auto">
                                                    <option value="pending" <?php echo $r['status']==='pending'?'selected':''; ?>>Pending</option>
                                                    <option value="approved" <?php echo $r['status']==='approved'?'selected':''; ?>>Approved</option>
                                                    <option value="rejected" <?php echo $r['status']==='rejected'?'selected':''; ?>>Rejected</option>
                                                    <option value="completed" <?php echo $r['status']==='completed'?'selected':''; ?>>Completed</option>
                                                </select>
                                                <button class="btn btn-sm btn-primary"><i class="bi bi-save"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted text-center py-5">No requests yet.</p>
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


