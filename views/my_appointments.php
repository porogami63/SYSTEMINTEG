<?php
require_once '../config.php';

if (!isLoggedIn() || !isPatient()) {
    redirect('dashboard.php');
}

$db = Database::getInstance();

// Get patient id
$patient = $db->fetch("SELECT id FROM patients WHERE user_id = ?", [$_SESSION['user_id']]);
if (!$patient) {
    die('Patient profile not found');
}

// Upcoming first by default
$show = isset($_GET['show']) ? $_GET['show'] : 'upcoming';
$whereExtra = $show === 'upcoming' ? " AND (a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.time_slot >= CURTIME()))" : '';
$appointments = $db->fetchAll(
    "SELECT a.*, c.clinic_name, c.specialization FROM appointments a JOIN clinics c ON a.clinic_id = c.id WHERE a.patient_id = ? $whereExtra ORDER BY a.appointment_date ASC, a.time_slot ASC",
    [$patient['id']]
);
?>
<!DOCTYPE html>
<html lang=\"en\">
<head>
<meta charset=\"UTF-8\">
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
<title>My Appointments - MediArchive</title>
<link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">
<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css\">
<style>
.sidebar { min-height: 100vh; background: linear-gradient(180deg, #2e7d32 0%, #1b5e20 100%); }
.sidebar .nav-link { color: white; padding: 12px 20px; margin: 5px 0; }
.sidebar .nav-link.active { background: rgba(255,255,255,0.2); }
.main-content { padding: 30px; }
</style>
</head>
<body>
<div class=\"container-fluid\">
    <div class=\"row\">
        <?php include 'includes/sidebar.php'; ?>

        <main class=\"col-md-9 ms-sm-auto col-lg-10 px-md-4\">
            <div class=\"main-content\">
                <div class=\"d-flex align-items-center justify-content-between mb-4\">
                    <h2 class=\"mb-0\"><i class=\"bi bi-calendar\"></i> My Appointments</h2>
                    <div class=\"d-flex align-items-center gap-2\">
                        <a class=\"btn btn-outline-secondary btn-sm <?php echo $show==='upcoming'?'active':''; ?>\" href=\"?show=upcoming\">Upcoming</a>
                        <a class=\"btn btn-outline-secondary btn-sm <?php echo $show==='all'?'active':''; ?>\" href=\"?show=all\">All</a>
                        <a href=\"request_appointment.php\" class=\"btn btn-primary\"><i class=\"bi bi-calendar-plus\"></i> Request Appointment</a>
                    </div>
                </div>

                <div class=\"card shadow-sm\">
                    <div class=\"card-body\">
                        <?php if (!empty($appointments)): ?>
                        <div class=\"table-responsive\">
                            <table class=\"table table-hover\">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Clinic / Specialization</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $a): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($a['appointment_date']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($a['time_slot'],0,5)); ?></td>
                                        <td><?php echo htmlspecialchars($a['clinic_name'] . ' — ' . $a['specialization']); ?></td>
                                        <td><?php echo htmlspecialchars($a['purpose']); ?></td>
                                        <td>
                                            <span class=\"badge bg-<?php echo $a['status']==='approved'?'success':($a['status']==='pending'?'warning':'secondary'); ?>\"><?php echo htmlspecialchars(ucfirst($a['status'])); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class=\"text-muted text-center py-5\">No appointments yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js\"></script>
</body>
</html>


