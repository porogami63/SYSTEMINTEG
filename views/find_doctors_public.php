<?php
require_once '../config.php';

// Public page - no login required
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
<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding-top: 80px;
}
.main-content {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    margin: 20px auto;
    max-width: 1200px;
}
.navbar {
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.navbar-brand {
    font-weight: bold;
    color: #667eea !important;
}
.nav-link {
    color: #333 !important;
    font-weight: 500;
}
.nav-link:hover {
    color: #667eea !important;
}
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.table {
    margin-bottom: 0;
}
.table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.table thead th {
    border: none;
    padding: 15px;
    font-weight: 600;
}
.table tbody tr {
    transition: all 0.3s;
}
.table tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}
.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
}
.btn-outline-primary {
    border-width: 2px;
    font-weight: 500;
    transition: all 0.3s;
}
.btn-outline-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
</style>
</head>
<body>
<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <i class="bi bi-hospital-fill"></i>
            <span>MediArchive</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="../index.php#about">ABOUT US</a></li>
                <li class="nav-item"><a class="nav-link" href="../index.php#services">OUR SERVICES</a></li>
                <li class="nav-item"><a class="nav-link active" href="find_doctors_public.php">FIND A DOCTOR</a></li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item"><a class="nav-link" href="dashboard.php">DASHBOARD</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php?redirect=<?php echo urlencode('/SYSTEMINTEG/index.php'); ?>">LOGOUT</a></li>
                <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="../index.php" onclick="event.preventDefault(); document.getElementById('loginModal').style.display='block';">LOGIN</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="../index.php#contact">CONTACT US</a></li>
                <?php if (!isLoggedIn()): ?>
                <li class="nav-item">
                    <a href="../index.php" class="btn btn-primary" onclick="event.preventDefault(); document.getElementById('registerModal').style.display='block';">GET STARTED</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="main-content">
        <h2 class="mb-4"><i class="bi bi-search"></i> Find Doctors</h2>

        <form class="mb-4" method="GET">
            <div class="input-group input-group-lg">
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
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

