<?php
require_once '../config.php';

if (!isLoggedIn() || (!isClinicAdmin() && !isWebAdmin())) {
    redirect('dashboard.php');
}

$conn = getDBConnection();

// Get all patients
$stmt = $conn->prepare("SELECT p.*, u.full_name, u.email, u.phone 
                       FROM patients p
                       JOIN users u ON p.user_id = u.id
                       ORDER BY p.id DESC");
$stmt->execute();
$patients = $stmt->get_result();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patients - MediArchive</title>
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
                <h2 class="mb-4"><i class="bi bi-people"></i> Patients</h2>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if ($patients->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient Code</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Date of Birth</th>
                                        <th>Gender</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($patient = $patients->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($patient['patient_code']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo $patient['date_of_birth'] ?? 'N/A'; ?></td>
                                        <td><?php echo $patient['gender'] ?? 'N/A'; ?></td>
                                        <td class="d-flex gap-2">
                                            <a href="patient_history.php?patient_id=<?php echo intval($patient['id']); ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-clock-history"></i> History
                                            </a>
                                            <?php if (isClinicAdmin()): ?>
                                            <a href="certificates.php" class="btn btn-sm btn-primary">
                                                <i class="bi bi-file-earmark-plus"></i> Create Cert
                                            </a>
                                            <?php endif; ?>
                                            <?php if (isWebAdmin()): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo intval($patient['user_id']); ?>, '<?php echo htmlspecialchars($patient['full_name']); ?>')" title="Delete Patient">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted text-center py-5">No patients registered yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php if (isWebAdmin()): ?>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger"></i> Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this patient?</p>
                <p><strong>Name:</strong> <span id="deletePatientName"></span></p>
                <p class="text-danger"><small><strong>Warning:</strong> This will permanently delete the patient account and all associated data including certificates, appointments, payments, and messages. This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="../api/delete_user.php" id="deleteForm" style="display: inline;">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="submit" class="btn btn-danger">Delete Patient</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function confirmDelete(userId, patientName) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deletePatientName').textContent = patientName;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

