<?php
require_once '../config.php';

if (!isLoggedIn() || !isClinicAdmin()) {
    redirect('dashboard.php');
}

$db = Database::getInstance();

// Get clinic id for this admin
$clinic = $db->fetch("SELECT id FROM clinics WHERE user_id = ?", [$_SESSION['user_id']]);
if (!$clinic) {
    die('Clinic profile not found');
}

// Simple flash messages
if (!isset($_SESSION)) { session_start(); }
$message = isset($_SESSION['flash_success']) ? $_SESSION['flash_success'] : null;
$error = isset($_SESSION['flash_error']) ? $_SESSION['flash_error'] : null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Handle actions: approve, reject, reschedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['appointment_id'])) {
    $appointmentId = intval($_POST['appointment_id']);
    try {
        if ($_POST['action'] === 'approve') {
            $db->execute("UPDATE appointments SET status = 'approved' WHERE id = ? AND clinic_id = ?", [$appointmentId, $clinic['id']]);
            $_SESSION['flash_success'] = 'Appointment approved.';
        } elseif ($_POST['action'] === 'reject') {
            $db->execute("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND clinic_id = ?", [$appointmentId, $clinic['id']]);
            $_SESSION['flash_success'] = 'Appointment rejected.';
        } elseif ($_POST['action'] === 'reschedule' && isset($_POST['new_date'], $_POST['new_time'])) {
            $newDate = trim($_POST['new_date']);
            $newTime = trim($_POST['new_time']);
            // Basic validation: ensure not past and is time format
            if ($newDate >= date('Y-m-d') && preg_match('/^\d{2}:\d{2}/', $newTime)) {
                $db->execute("UPDATE appointments SET appointment_date = ?, time_slot = ?, status = 'approved' WHERE id = ? AND clinic_id = ?", [$newDate, $newTime, $appointmentId, $clinic['id']]);
                $_SESSION['flash_success'] = 'Appointment rescheduled.';
            } else {
                $_SESSION['flash_error'] = 'Invalid date or time.';
            }
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = 'Action failed.';
    }
    // Redirect back to the same page (PRG) preserving the current filter
    $redirShow = isset($_GET['show']) ? $_GET['show'] : 'upcoming';
    header('Location: clinic_appointments.php?show=' . urlencode($redirShow));
    exit;
}

// Upcoming filter (default shows upcoming first)
$show = isset($_GET['show']) ? $_GET['show'] : 'upcoming';
$whereExtra = $show === 'upcoming' ? " AND (a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.time_slot >= CURTIME()))" : '';

$appointments = $db->fetchAll(
    "SELECT a.*, p.patient_code, u.full_name as patient_name FROM appointments a
     JOIN patients p ON a.patient_id = p.id
     JOIN users u ON p.user_id = u.id
     WHERE a.clinic_id = ? $whereExtra ORDER BY a.appointment_date ASC, a.time_slot ASC",
    [$clinic['id']]
);
?>
<!DOCTYPE html>
<html lang=\"en\">
<head>
<meta charset=\"UTF-8\">
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
<title>Appointments - MediArchive</title>
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
                    <h2 class=\"mb-0\"><i class=\"bi bi-calendar-event\"></i> Appointments</h2>
                    <div>
                        <a class=\"btn btn-outline-secondary btn-sm <?php echo $show==='upcoming'?'active':''; ?>\" href=\"?show=upcoming\">Upcoming</a>
                        <a class=\"btn btn-outline-secondary btn-sm <?php echo $show==='all'?'active':''; ?>\" href=\"?show=all\">All</a>
                    </div>
                </div>

                <?php if ($message): ?><div class=\"alert alert-success\"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class=\"alert alert-danger\"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

                <div class=\"card shadow-sm\">
                    <div class=\"card-body\">
                        <?php if (!empty($appointments)): ?>
                        <div class=\"table-responsive\">
                            <table class=\"table table-hover\">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Patient</th>
                                        <th>Patient Code</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $a): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($a['appointment_date']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($a['time_slot'],0,5)); ?></td>
                                        <td><?php echo htmlspecialchars($a['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($a['patient_code']); ?></td>
                                        <td><?php echo htmlspecialchars($a['purpose']); ?></td>
                                        <td>
                                            <span class=\"badge bg-<?php echo $a['status']==='approved'?'success':($a['status']==='pending'?'warning':'secondary'); ?>\"><?php echo htmlspecialchars(ucfirst($a['status'])); ?></span>
                                        </td>
                                        <td class=\"d-flex gap-2\">
                                            <a class=\"btn btn-sm btn-outline-secondary\" href=\"patient_history.php?patient_id=<?php echo intval($a['patient_id']); ?>\"><i class=\"bi bi-clock-history\"></i> History</a>
                                            <form method=\"post\" class=\"d-inline\">
                                                <input type=\"hidden\" name=\"appointment_id\" value=\"<?php echo intval($a['id']); ?>\">
                                                <input type=\"hidden\" name=\"action\" value=\"approve\">
                                                <button type=\"submit\" class=\"btn btn-sm btn-success\" <?php echo $a['status']==='approved'?'disabled':''; ?>><i class=\"bi bi-check\"></i> Accept</button>
                                            </form>
                                            <form method=\"post\" class=\"d-inline\" onsubmit=\"return confirm('Reject this appointment?');\">
                                                <input type=\"hidden\" name=\"appointment_id\" value=\"<?php echo intval($a['id']); ?>\">
                                                <input type=\"hidden\" name=\"action\" value=\"reject\">
                                                <button type=\"submit\" class=\"btn btn-sm btn-outline-danger\"><i class=\"bi bi-x\"></i> Reject</button>
                                            </form>
                                            <button class=\"btn btn-sm btn-outline-primary\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#resched_<?php echo intval($a['id']); ?>\"><i class=\"bi bi-calendar2-event\"></i> Reschedule</button>
                                        </td>
                                    </tr>
                                    <tr class=\"collapse\" id=\"resched_<?php echo intval($a['id']); ?>\">
                                        <td colspan=\"7\">
                                            <form method=\"post\" class=\"row g-2 align-items-end\">
                                                <input type=\"hidden\" name=\"appointment_id\" value=\"<?php echo intval($a['id']); ?>\">
                                                <input type=\"hidden\" name=\"action\" value=\"reschedule\">
                                                <div class=\"col-md-4\">
                                                    <label class=\"form-label\">New Date</label>
                                                    <input type=\"date\" class=\"form-control\" name=\"new_date\" value=\"<?php echo htmlspecialchars($a['appointment_date']); ?>\" min=\"<?php echo date('Y-m-d'); ?>\" required>
                                                </div>
                                                <div class=\"col-md-3\">
                                                    <label class=\"form-label\">New Time</label>
                                                    <input type=\"time\" class=\"form-control\" name=\"new_time\" value=\"<?php echo htmlspecialchars(substr($a['time_slot'],0,5)); ?>\" required>
                                                </div>
                                                <div class=\"col-md-3\">
                                                    <button type=\"submit\" class=\"btn btn-primary\"><i class=\"bi bi-save\"></i> Save</button>
                                                </div>
                                            </form>
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


