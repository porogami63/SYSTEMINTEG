<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$uid = intval($_GET['id'] ?? 0);
if (!$uid) { redirect('find_doctors.php'); }

$db = Database::getInstance();
$user = $db->fetch("SELECT id, full_name, email, phone, profile_photo FROM users WHERE id = ?", [$uid]);
$clinic = $db->fetch("SELECT * FROM clinics WHERE user_id = ?", [$uid]);
if (!$user || !$clinic) { redirect('find_doctors.php'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($user['full_name']); ?> - Doctor Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<?php include 'includes/role_styles.php'; ?>
<style>
.hero { background: linear-gradient(135deg, #0f63d6, #0b3d91); color:#fff; border-radius:18px; }
.avatar { width:120px;height:120px;object-fit:cover;border-radius:50%;border:4px solid rgba(255,255,255,.6); }
.info-card { border-radius:16px;border:1px solid #e5edff; }
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="main-content">
                <div class="hero p-4 p-md-5 mb-4 d-flex align-items-center gap-4">
                    <img class="avatar" src="../<?php echo htmlspecialchars($user['profile_photo'] ?? ''); ?>" onerror="this.style.display='none'">
                    <div>
                        <h2 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <div class="opacity-75"><?php echo htmlspecialchars($clinic['clinic_name']); ?> • <?php echo htmlspecialchars($clinic['specialization']); ?></div>
                    </div>
                    <div class="ms-auto d-flex gap-2">
                        <a href="chat.php?mode=direct&dm=<?php echo $uid; ?>" class="btn btn-light"><i class="bi bi-chat-dots"></i> Message</a>
                        <?php if (isPatient()): ?>
                        <a href="request_appointment.php?clinic_id=<?php echo intval($clinic['id']); ?>" class="btn btn-outline-light"><i class="bi bi-calendar-plus"></i> Book</a>
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

