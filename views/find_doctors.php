<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getDBConnection();
$q = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';

if ($q) {
    $like = '%' . $q . '%';
    $stmt = $conn->prepare("SELECT c.*, u.full_name FROM clinics c JOIN users u ON c.user_id = u.id WHERE (c.clinic_name LIKE ? OR c.specialization LIKE ? OR u.full_name LIKE ?) ORDER BY c.is_available DESC, c.clinic_name");
    $stmt->bind_param("sss", $like, $like, $like);
} else {
    $stmt = $conn->prepare("SELECT c.*, u.full_name FROM clinics c JOIN users u ON c.user_id = u.id ORDER BY c.is_available DESC, c.clinic_name");
}
$stmt->execute();
$doctors = $stmt->get_result();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Find Doctors - MediArchive</title>
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
                <h2 class="mb-4"><i class="bi bi-search"></i> Find Doctors</h2>

                <form class="mb-3" method="GET">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search by name, clinic, or specialization" name="q" value="<?php echo htmlspecialchars($q); ?>">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button>
                    </div>
                </form>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if ($doctors->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Doctor</th>
                                        <th>Clinic</th>
                                        <th>Specialization</th>
                                        <th>Medical License</th>
                                        <th>Address</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($doc = $doctors->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($doc['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($doc['clinic_name']); ?></td>
                                        <td><?php echo htmlspecialchars($doc['specialization']); ?></td>
                                        <td><?php echo htmlspecialchars($doc['medical_license'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($doc['address'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $doc['is_available'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $doc['is_available'] ? 'Available' : 'Unavailable'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted text-center py-5">No doctors found.</p>
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


