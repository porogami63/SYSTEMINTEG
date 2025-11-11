<?php
require_once '../config.php';

// Require login
if (!isLoggedIn()) {
    redirect('login.php');
}

$q = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';

if ($q) {
    $like = '%' . $q . '%';
    $sql = "SELECT c.*, u.full_name FROM clinics c JOIN users u ON c.user_id = u.id WHERE (c.clinic_name LIKE ? OR c.specialization LIKE ? OR u.full_name LIKE ? OR c.about_description LIKE ?) ORDER BY c.is_available DESC, c.clinic_name";
    $doctors = DB()->fetchAll($sql, [$like, $like, $like, $like]);
} else {
    $sql = "SELECT c.*, u.full_name FROM clinics c JOIN users u ON c.user_id = u.id ORDER BY c.is_available DESC, c.clinic_name";
    $doctors = DB()->fetchAll($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Find Doctors - MediArchive</title>
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
                <h2 class="mb-4"><i class="bi bi-search"></i> Find Doctors</h2>

                <form class="mb-3" method="GET">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search by name, clinic, or specialization" name="q" value="<?php echo htmlspecialchars($q); ?>">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button>
                    </div>
                </form>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if (!empty($doctors)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Doctor</th>
                                        <th>Clinic</th>
                                        <th>Specialization</th>
                                        <th>About</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($doctors as $doc): ?>
                                    <tr>
                                        <td>
                                            <a href="doctor_profile.php?id=<?php echo intval($doc['user_id']); ?>" class="text-decoration-none fw-semibold">
                                                <?php echo htmlspecialchars($doc['full_name']); ?>
                                            </a>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($doc['clinic_name']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($doc['clinic_name']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($doc['specialization']); ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($doc['about_description'])): ?>
                                                <p class="mb-0 small text-muted" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($doc['about_description']); ?>">
                                                    <?php echo htmlspecialchars(substr($doc['about_description'], 0, 80)); ?>...
                                                </p>
                                            <?php else: ?>
                                                <span class="text-muted small">No description available</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $doc['is_available'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $doc['is_available'] ? 'Available' : 'Unavailable'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="doctor_profile.php?id=<?php echo intval($doc['user_id']); ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View Profile
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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


