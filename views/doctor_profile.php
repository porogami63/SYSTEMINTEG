<?php
require_once '../config.php';

// Public page - no login required
$uid = intval($_GET['id'] ?? 0);
if (!$uid) { 
    if (isLoggedIn()) {
        redirect('find_doctors.php');
    } else {
        redirect('../index.php');
    }
}

$db = Database::getInstance();
$user = $db->fetch("SELECT id, full_name, email, phone, profile_photo FROM users WHERE id = ?", [$uid]);
$clinic = $db->fetch("SELECT * FROM clinics WHERE user_id = ?", [$uid]);
if (!$user || !$clinic) { 
    if (isLoggedIn()) {
        redirect('find_doctors.php');
    } else {
        redirect('../index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($user['full_name']); ?> - Doctor Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<?php if (isLoggedIn()): ?>
<?php include 'includes/role_styles.php'; ?>
<?php else: ?>
<style>
:root {
    --brand-primary: #0f63d6;
    --brand-dark: #0b3d91;
}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f7f9ff;
}
.navbar {
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.navbar-brand {
    font-weight: 700;
    color: #0b5394 !important;
}
.nav-link {
    color: #333 !important;
    font-weight: 500;
}
.nav-link:hover {
    color: #0b5394 !important;
}
</style>
<?php endif; ?>
<style>
.hero { background: linear-gradient(135deg, #0f63d6, #0b3d91); color:#fff; border-radius:18px; }
.avatar { width:120px;height:120px;object-fit:cover;border-radius:50%;border:4px solid rgba(255,255,255,.6); }
.info-card { border-radius:16px;border:1px solid #e5edff; }
</style>
</head>
<body>
<?php if (!isLoggedIn()): ?>
<!-- Navigation Bar for Public Users -->
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
                <li class="nav-item"><a class="nav-link" href="../index.php" onclick="event.preventDefault(); document.getElementById('loginModal').style.display='block';">LOGIN</a></li>
                <li class="nav-item"><a class="nav-link" href="../index.php#contact">CONTACT US</a></li>
                <li class="nav-item">
                    <a href="../index.php" class="btn btn-primary" onclick="event.preventDefault(); document.getElementById('registerModal').style.display='block';">GET STARTED</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>
<div class="container-fluid">
    <div class="row">
        <?php if (isLoggedIn()): ?>
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <?php else: ?>
        <main class="col-12 px-md-4" style="padding-top: 100px; max-width: 1200px; margin: 0 auto;">
        <?php endif; ?>
            <div class="main-content">
                <div class="hero p-4 p-md-5 mb-4 d-flex align-items-center gap-4">
                    <img class="avatar" src="../<?php echo htmlspecialchars($user['profile_photo'] ?? ''); ?>" onerror="this.style.display='none'">
                    <div>
                        <h2 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <div class="opacity-75"><?php echo htmlspecialchars($clinic['clinic_name']); ?> • <?php echo htmlspecialchars($clinic['specialization']); ?></div>
                    </div>
                    <div class="ms-auto d-flex gap-2">
                        <?php if (isLoggedIn()): ?>
                        <a href="chat.php?mode=direct&dm=<?php echo $uid; ?>" class="btn btn-light"><i class="bi bi-chat-dots"></i> Message</a>
                        <?php if (isPatient()): ?>
                        <a href="request_appointment.php?clinic_id=<?php echo intval($clinic['id']); ?>" class="btn btn-outline-light"><i class="bi bi-calendar-plus"></i> Book</a>
                        <?php endif; ?>
                        <?php else: ?>
                        <a href="../index.php" class="btn btn-light"><i class="bi bi-house"></i> Home</a>
                        <a href="find_doctors_public.php" class="btn btn-outline-light"><i class="bi bi-arrow-left"></i> Back</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card info-card">
                            <div class="card-header"><strong>Clinic Information</strong></div>
                            <div class="card-body">
                                <p class="mb-1"><strong>Medical License:</strong> <?php echo htmlspecialchars($clinic['medical_license'] ?? 'N/A'); ?></p>
                                <p class="mb-1"><strong>Address:</strong> <?php echo htmlspecialchars($clinic['address'] ?? 'N/A'); ?></p>
                                <p class="mb-0"><strong>Contact:</strong> <?php echo htmlspecialchars($clinic['contact_phone'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card info-card">
                            <div class="card-header"><strong>About</strong></div>
                            <div class="card-body">
                                <?php if (!empty($clinic['about_description'])): ?>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($clinic['about_description'])); ?></p>
                                <?php else: ?>
                                    <p class="text-muted mb-0">This doctor is part of the MediArchive network. You can message the clinic, book an appointment, and securely receive QR‑verified medical certificates.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

