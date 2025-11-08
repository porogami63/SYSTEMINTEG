<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
try {
    $db = Database::getInstance();
    // Get user details
    $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$user_id]);

    // Get profile details based on role
    if ($_SESSION['role'] === 'clinic_admin') {
        $profile = $db->fetch("SELECT * FROM clinics WHERE user_id = ?", [$user_id]);
    } else {
        $profile = $db->fetch("SELECT * FROM patients WHERE user_id = ?", [$user_id]);
    }
} catch (Exception $e) {
    // fallback to empty
    $user = null;
    $profile = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - MediArchive</title>
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
                <h2 class="mb-4"><i class="bi bi-person"></i> Profile</h2>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">User Information</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($user['profile_photo'])): ?>
                                    <div class="mb-3 text-center">
                                        <img src="../<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile Photo" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></p>
                                <p><strong>Role:</strong> <?php echo str_replace('_', ' ', ucwords($user['role'])); ?></p>
                                <p><strong>Member Since:</strong> <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><?php echo $_SESSION['role'] === 'clinic_admin' ? 'Clinic Information' : 'Patient Information'; ?></h5>
                            </div>
                            <div class="card-body">
                                <?php if ($profile): ?>
                                    <?php if ($_SESSION['role'] === 'clinic_admin'): ?>
                                        <p><strong>Clinic Name:</strong> <?php echo htmlspecialchars($profile['clinic_name']); ?></p>
                                        <p><strong>Medical License:</strong> <?php echo htmlspecialchars($profile['medical_license'] ?? 'N/A'); ?></p>
                                        <p><strong>License Number:</strong> <?php echo htmlspecialchars($profile['license_number'] ?? 'N/A'); ?></p>
                                        <p><strong>Address:</strong> <?php echo htmlspecialchars($profile['address'] ?? 'N/A'); ?></p>
                                        <p><strong>Contact Phone:</strong> <?php echo htmlspecialchars($profile['contact_phone'] ?? 'N/A'); ?></p>
                                    <?php else: ?>
                                        <p><strong>Patient Code:</strong> <?php echo htmlspecialchars($profile['patient_code']); ?></p>
                                        <p><strong>Date of Birth:</strong> <?php echo $profile['date_of_birth'] ?? 'N/A'; ?></p>
                                        <p><strong>Gender:</strong> <?php echo htmlspecialchars($profile['gender'] ?? 'N/A'); ?></p>
                                        <p><strong>Address:</strong> <?php echo htmlspecialchars($profile['address'] ?? 'N/A'); ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-muted">No additional information available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="edit_profile.php" class="btn btn-primary">
                        <i class="bi bi-pencil-square"></i> Edit Profile
                    </a>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

