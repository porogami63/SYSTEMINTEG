<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

try {
    $db = Database::getInstance();
    $user_id = $_SESSION['user_id'];

    // Get patient info
    if (isPatient()) {
        $patient = $db->fetch("SELECT p.id FROM patients p WHERE p.user_id = ?", [$user_id]);
        $patient_id = $patient['id'] ?? 0;
    } else {
        $patient_id = intval($_GET['patient_id'] ?? 0);
    }

    // Get certificates
    $certificates = $db->fetchAll("SELECT c.*, cl.clinic_name FROM certificates c 
                       JOIN clinics cl ON c.clinic_id = cl.id 
                       WHERE c.patient_id = ? ORDER BY c.created_at DESC", [$patient_id]);
} catch (Exception $e) {
    $certificates = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Certificates - MediArchive</title>
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
                <h2 class="mb-4"><i class="bi bi-file-earmark-medical"></i> My Certificates</h2>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if (!empty($certificates)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Cert ID</th>
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
                                        <td><?php echo htmlspecialchars($cert['cert_id']); ?></td>
                                        <td><?php echo htmlspecialchars($cert['clinic_name']); ?></td>
                                        <td><?php echo $cert['issue_date']; ?></td>
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
                                            <a href="../api/download.php?id=<?php echo $cert['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                            <?php if (isClinicAdmin() || isWebAdmin()): ?>
                                            <button class="btn btn-sm btn-danger delete-cert-btn" data-cert-id="<?php echo $cert['id']; ?>" data-cert-name="<?php echo htmlspecialchars($cert['cert_id']); ?>">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                            <?php endif; ?>
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
<script>
// Handle certificate deletion
document.querySelectorAll('.delete-cert-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const certId = this.getAttribute('data-cert-id');
        const certName = this.getAttribute('data-cert-name');
        
        if (confirm(`Are you sure you want to delete certificate ${certName}? This action cannot be undone.`)) {
            // Show loading state
            this.disabled = true;
            this.innerHTML = '<i class="bi bi-hourglass-split"></i> Deleting...';
            
            // Send delete request
            fetch('../api/delete_certificate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'cert_id=' + certId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Certificate deleted successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete certificate'));
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-trash"></i> Delete';
                }
            })
            .catch(error => {
                alert('Error deleting certificate');
                console.error(error);
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-trash"></i> Delete';
            });
        }
    });
});
</script>
</body>
</html>

