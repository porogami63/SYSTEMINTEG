<?php
require_once '../config.php';

if (!isLoggedIn() || !isClinicAdmin()) {
    redirect('dashboard.php');
}

try {
    $db = Database::getInstance();
    $user_id = $_SESSION['user_id'];
    // Get clinic info
    $clinic = $db->fetch("SELECT c.id FROM clinics c WHERE c.user_id = ?", [$user_id]);
    $clinic_id = $clinic['id'] ?? 0;

    // Get certificates
    $certificates = $db->fetchAll("SELECT c.*, u.full_name as patient_name FROM certificates c 
                       JOIN patients p ON c.patient_id = p.id 
                       JOIN users u ON p.user_id = u.id 
                       WHERE c.clinic_id = ? ORDER BY c.created_at DESC", [$clinic_id]);
} catch (Exception $e) {
    $certificates = [];
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
<style>
.sidebar {
    min-height: 100vh;
    background: linear-gradient(180deg, #2e7d32 0%, #1b5e20 100%);
}
.sidebar .nav-link {
    color: white;
    padding: 12px 20px;
    margin: 5px 0;
}
.sidebar .nav-link:hover {
    background: rgba(255,255,255,0.1);
}
.sidebar .nav-link.active {
    background: rgba(255,255,255,0.2);
}
.main-content {
    padding: 30px;
}
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="main-content">
                <h2 class="mb-4"><i class="bi bi-files"></i> All Certificates</h2>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if (!empty($certificates)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Cert ID</th>
                                        <th>Patient</th>
                                        <th>Issue Date</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($certificates as $cert): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cert['cert_id']); ?></td>
                                        <td><?php echo htmlspecialchars($cert['patient_name']); ?></td>
                                        <td><?php echo $cert['issue_date']; ?></td>
                                        <td><?php echo htmlspecialchars($cert['purpose']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $cert['status'] === 'active' ? 'success' : ($cert['status'] === 'expired' ? 'warning' : 'danger'); ?>">
                                                <?php echo strtoupper($cert['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_certificate.php?id=<?php echo $cert['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i>
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

