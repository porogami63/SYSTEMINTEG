<?php
require_once '../config.php';

if (!isLoggedIn() || !isClinicAdmin()) {
    redirect('dashboard.php');
}

if (!isset($_SESSION)) { session_start(); }
$loadError = '';
try {
    $db = Database::getInstance();
    $clinic = $db->fetch("SELECT id FROM clinics WHERE user_id = ?", [$_SESSION['user_id']]);
    if (!$clinic) { throw new Exception('Clinic profile not found'); }
} catch (Exception $e) {
    $loadError = $e->getMessage();
    $clinic = null;
}

// Handle actions with PRG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['appointment_id'])) {
    $aid = intval($_POST['appointment_id']);
    try {
        if ($_POST['action'] === 'approve') {
            $db->execute("UPDATE appointments SET status='approved' WHERE id=? AND clinic_id=?", [$aid, $clinic['id']]);
            $_SESSION['flash_success'] = 'Appointment approved';
            // Audit log
            AuditLogger::log('APPOINTMENT_APPROVED', 'appointment', $aid, ['clinic_id' => $clinic['id']]);
            // Notify patient
            $app = $db->fetch("SELECT a.appointment_date, a.time_slot, a.purpose, p.user_id AS patient_user_id, c.clinic_name FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN clinics c ON a.clinic_id=c.id WHERE a.id=? AND a.clinic_id=?", [$aid, $clinic['id']]);
            if ($app && isset($app['patient_user_id'])) {
                $conn = getDBConnection();
                $title = 'Appointment Approved';
                $message = 'Your appointment with ' . $app['clinic_name'] . ' on ' . $app['appointment_date'] . ' at ' . substr($app['time_slot'],0,5) . ' has been approved.';
                notifyUser($conn, intval($app['patient_user_id']), $title, $message, 'my_appointments.php');
                $conn->close();
            }
        } elseif ($_POST['action'] === 'reject') {
            $db->execute("UPDATE appointments SET status='cancelled' WHERE id=? AND clinic_id=?", [$aid, $clinic['id']]);
            $_SESSION['flash_success'] = 'Appointment rejected';
            // Audit log
            AuditLogger::log('APPOINTMENT_REJECTED', 'appointment', $aid, ['clinic_id' => $clinic['id']]);
            // Notify patient
            $app = $db->fetch("SELECT a.appointment_date, a.time_slot, a.purpose, p.user_id AS patient_user_id, c.clinic_name FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN clinics c ON a.clinic_id=c.id WHERE a.id=? AND a.clinic_id=?", [$aid, $clinic['id']]);
            if ($app && isset($app['patient_user_id'])) {
                $conn = getDBConnection();
                $title = 'Appointment Rejected';
                $message = 'Your appointment with ' . $app['clinic_name'] . ' on ' . $app['appointment_date'] . ' at ' . substr($app['time_slot'],0,5) . ' has been rejected.';
                notifyUser($conn, intval($app['patient_user_id']), $title, $message, 'my_appointments.php');
                $conn->close();
            }
        } elseif ($_POST['action'] === 'reschedule' && !empty($_POST['new_date']) && !empty($_POST['new_time'])) {
            $nd = $_POST['new_date']; $nt = $_POST['new_time'];
            if ($nd >= date('Y-m-d') && preg_match('/^\d{2}:\d{2}/', $nt)) {
                $db->execute("UPDATE appointments SET appointment_date=?, time_slot=?, status='rescheduled' WHERE id=? AND clinic_id=?", [$nd, $nt, $aid, $clinic['id']]);
                $_SESSION['flash_success'] = 'Appointment rescheduled';
                // Audit log
                AuditLogger::log('APPOINTMENT_RESCHEDULED', 'appointment', $aid, ['clinic_id' => $clinic['id'], 'new_date' => $nd, 'new_time' => $nt]);
                // Notify patient
                $app = $db->fetch("SELECT p.user_id AS patient_user_id, c.clinic_name FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN clinics c ON a.clinic_id=c.id WHERE a.id=? AND a.clinic_id=?", [$aid, $clinic['id']]);
                if ($app && isset($app['patient_user_id'])) {
                    $conn = getDBConnection();
                    $title = 'Appointment Rescheduled';
                    $message = 'Your appointment with ' . $app['clinic_name'] . ' has been rescheduled to ' . $nd . ' at ' . substr($nt,0,5) . '.';
                    notifyUser($conn, intval($app['patient_user_id']), $title, $message, 'my_appointments.php');
                    $conn->close();
                }
            } else {
                $_SESSION['flash_error'] = 'Invalid new date/time';
            }
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = 'Action failed';
    }
    header('Location: clinic_appointments.php');
    exit;
}

$message = isset($_SESSION['flash_success']) ? $_SESSION['flash_success'] : null;
$error = isset($_SESSION['flash_error']) ? $_SESSION['flash_error'] : null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$appointments = [];
if (empty($loadError)) {
    try {
        $show = isset($_GET['show']) ? $_GET['show'] : 'all';
        $filterWhere = $show === 'upcoming' ? " AND (a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.time_slot >= CURTIME()))" : '';
        $appointments = $db->fetchAll(
            "SELECT a.*, p.patient_code, u.full_name AS patient_name FROM appointments a
             JOIN patients p ON a.patient_id = p.id
             JOIN users u ON p.user_id = u.id
             WHERE a.clinic_id = ? $filterWhere ORDER BY a.appointment_date ASC, a.time_slot ASC",
            [$clinic['id']]
        );
    } catch (Exception $e) {
        $loadError = 'Failed to load appointments.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Appointments - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<?php include 'includes/role_styles.php'; ?>
<style>
#calendar { min-height: 600px; }
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="main-content">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h2 class="mb-0"><i class="bi bi-calendar-event"></i> Appointments</h2>
                    <div>
                        <a class="btn btn-outline-secondary btn-sm <?php echo $show==='upcoming'?'active':''; ?>" href="?show=upcoming">Upcoming</a>
                        <a class="btn btn-outline-secondary btn-sm <?php echo $show==='all'?'active':''; ?>" href="?show=all">All</a>
                    </div>
                </div>

                <?php if (!empty($loadError)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($loadError); ?></div>
                <?php endif; ?>

                <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header d-flex align-items-center gap-2 py-2">
                        <span class="small text-muted">View:</span>
                        <button class="btn btn-sm btn-outline-primary" id="btnListView">List</button>
                        <button class="btn btn-sm btn-outline-primary" id="btnCalendarView">Calendar</button>
                    </div>
                    <div class="card-body">
                        <div id="listContainer">
                            <?php if (!empty($appointments)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
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
                                            <td><span class="badge bg-<?php echo $a['status']==='approved'?'success':($a['status']==='pending'?'warning':($a['status']==='rescheduled'?'info':'secondary')); ?>"><?php echo htmlspecialchars(ucfirst($a['status'])); ?></span></td>
                                            <td class="d-flex gap-2">
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="appointment_id" value="<?php echo intval($a['id']); ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-sm btn-success" <?php echo $a['status']==='approved'?'disabled':''; ?>><i class="bi bi-check"></i> Accept</button>
                                                </form>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Reject this appointment?');">
                                                    <input type="hidden" name="appointment_id" value="<?php echo intval($a['id']); ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i> Reject</button>
                                                </form>
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#res_<?php echo intval($a['id']); ?>"><i class="bi bi-calendar2-event"></i> Reschedule</button>
                                            </td>
                                        </tr>
                                        <tr class="collapse" id="res_<?php echo intval($a['id']); ?>">
                                            <td colspan="7">
                                                <form method="post" class="row g-2 align-items-end">
                                                    <input type="hidden" name="appointment_id" value="<?php echo intval($a['id']); ?>">
                                                    <input type="hidden" name="action" value="reschedule">
                                                    <div class="col-md-4">
                                                        <label class="form-label">New Date</label>
                                                        <input type="date" class="form-control" name="new_date" value="<?php echo htmlspecialchars($a['appointment_date']); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">New Time</label>
                                                        <input type="time" class="form-control" name="new_time" value="<?php echo htmlspecialchars(substr($a['time_slot'],0,5)); ?>" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5"><p class="text-muted">No appointments found.</p></div>
                            <?php endif; ?>
                        </div>
                        <div id="calendarContainer" style="display:none;">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnList = document.getElementById('btnListView');
    const btnCal = document.getElementById('btnCalendarView');
    const list = document.getElementById('listContainer');
    const cal = document.getElementById('calendarContainer');
    let calendar;

    function setActive(button){
        [btnList, btnCal].forEach(b => { if (b) { b.classList.remove('btn-primary'); b.classList.add('btn-outline-primary'); } });
        if (button) { button.classList.remove('btn-outline-primary'); button.classList.add('btn-primary'); }
    }

    function showList(){
        if (list) list.style.display = '';
        if (cal) cal.style.display = 'none';
        setActive(btnList);
    }

    function showCalendar(){
        if (list) list.style.display = 'none';
        if (cal) cal.style.display = '';
        setActive(btnCal);
        if (!calendar) {
            const el = document.getElementById('calendar');
            if (!el) return;
            calendar = new FullCalendar.Calendar(el, {
                initialView: 'dayGridMonth',
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
                height: 'auto',
                events: <?php echo json_encode(array_map(function($a){
                    return [
                        'title' => $a['patient_name'] . ' (' . substr($a['time_slot'],0,5) . ')',
                        'start' => $a['appointment_date'] . 'T' . substr($a['time_slot'],0,5) . ':00',
                        'end' => $a['appointment_date'] . 'T' . substr($a['time_slot'],0,5) . ':00',
                        'extendedProps' => [
                            'purpose' => $a['purpose'],
                            'status' => $a['status'],
                            'patientCode' => $a['patient_code']
                        ]
                    ];
                }, $appointments), JSON_UNESCAPED_UNICODE); ?>,
                eventDidMount: function(info){
                    const status = info.event.extendedProps.status || '';
                    const color = status === 'approved' ? '#198754' : (status === 'pending' ? '#f0ad4e' : (status === 'rescheduled' ? '#0dcaf0' : '#6c757d'));
                    info.el.style.borderLeft = '4px solid ' + color;
                    const title = info.event.title
                        + (info.event.extendedProps.purpose ? '\nPurpose: ' + info.event.extendedProps.purpose : '')
                        + (info.event.extendedProps.patientCode ? '\nCode: ' + info.event.extendedProps.patientCode : '')
                        + (status ? '\nStatus: ' + status : '');
                    info.el.title = title;
                }
            });
            calendar.render();
        }
    }

    if (btnList) btnList.addEventListener('click', showList);
    if (btnCal) btnCal.addEventListener('click', showCalendar);

    showList();
});
</script>
</body>
</html>


