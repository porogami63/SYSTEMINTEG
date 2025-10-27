<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user info
$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get clinic or patient info
if ($role === 'clinic_admin') {
    $stmt = $conn->prepare("SELECT c.* FROM clinics c WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Get certificate count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM certificates WHERE clinic_id = ?");
    $stmt->bind_param("i", $profile['id']);
    $stmt->execute();
    $cert_count = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Get recent certificates
    $stmt = $conn->prepare("SELECT * FROM certificates WHERE clinic_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("i", $profile['id']);
    $stmt->execute();
    $recent_certs = $stmt->get_result();
    $stmt->close();
} else {
    // Patient dashboard
    $stmt = $conn->prepare("SELECT p.* FROM patients p WHERE p.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Get certificate count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM certificates WHERE patient_id = ?");
    $stmt->bind_param("i", $profile['id']);
    $stmt->execute();
    $cert_count = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Get recent certificates
    $stmt = $conn->prepare("SELECT c.*, cl.clinic_name FROM certificates c 
                           JOIN clinics cl ON c.clinic_id = cl.id 
                           WHERE c.patient_id = ? ORDER BY c.created_at DESC LIMIT 5");
    $stmt->bind_param("i", $profile['id']);
    $stmt->execute();
    $recent_certs = $stmt->get_result();
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - MediArchive</title>
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
.stats-card {
    border-left: 4px solid #2e7d32;
}
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <h5 class="px-3 text-white"><strong>MediArchive</strong></h5>
                <hr class="text-white">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <?php if ($role === 'clinic_admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="create_certificate.php">
                            <i class="bi bi-file-earmark-plus"></i> Create Certificate
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="certificates.php">
                            <i class="bi bi-files"></i> All Certificates
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="patients.php">
                            <i class="bi bi-people"></i> Patients
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="my_certificates.php">
                            <i class="bi bi-file-earmark-medical"></i> My Certificates
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="main-content">
                <h2 class="mb-4">Welcome, <?php echo $_SESSION['full_name']; ?>!</h2>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="card stats-card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Total Certificates</h5>
                                <h2 class="text-primary"><?php echo $cert_count['total']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card stats-card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Role</h5>
                                <h4 class="text-capitalize"><?php echo str_replace('_', ' ', $role); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Certificates -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5>Recent Certificates</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_certs->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Cert ID</th>
                                        <?php if ($role === 'patient'): ?>
                                        <th>Clinic</th>
                                        <?php endif; ?>
                                        <th>Issue Date</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($cert = $recent_certs->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cert['cert_id']); ?></td>
                                        <?php if ($role === 'patient'): ?>
                                        <td><?php echo htmlspecialchars($cert['clinic_name']); ?></td>
                                        <?php endif; ?>
                                        <td><?php echo $cert['issue_date']; ?></td>
                                        <td><?php echo htmlspecialchars($cert['purpose']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $cert['status'] === 'active' ? 'success' : ($cert['status'] === 'expired' ? 'warning' : 'danger'); ?>">
                                                <?php echo strtoupper($cert['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_certificate.php?id=<?php echo $cert['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">No certificates yet.</p>
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

