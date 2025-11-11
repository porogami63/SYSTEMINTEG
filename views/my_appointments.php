<?php
require_once '../config.php';

if (!isLoggedIn() || !isPatient()) {
    redirect('dashboard.php');
}

$loadError = '';
$appointments = [];
try {
    $db = Database::getInstance();
    $patient = $db->fetch("SELECT id FROM patients WHERE user_id = ?", [$_SESSION['user_id']]);
    if (!$patient) { throw new Exception('Patient profile not found'); }

    $show = isset($_GET['show']) ? $_GET['show'] : 'all';
    $filterWhere = $show === 'upcoming' ? " AND (a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.time_slot >= CURTIME()))" : '';

    $appointments = $db->fetchAll(
        "SELECT a.*, c.clinic_name, c.specialization FROM appointments a JOIN clinics c ON a.clinic_id = c.id WHERE a.patient_id = ? $filterWhere ORDER BY a.appointment_date ASC, a.time_slot ASC",
        [$patient['id']]
    );
    
    // Fetch payment status for each appointment
    $payment_status = [];
    foreach ($appointments as $appt) {
        $payment = $db->fetch("SELECT * FROM payments WHERE payment_type='appointment' AND reference_id=? AND payment_status='paid'", [$appt['id']]);
        $payment_status[$appt['id']] = $payment ? true : false;
    }
    
    // Fetch results for appointments
    $appointment_results = [];
    if (!empty($appointments)) {
        $appt_ids = array_column($appointments, 'id');
        $placeholders = implode(',', array_fill(0, count($appt_ids), '?'));
        $results = $db->fetchAll("SELECT * FROM appointment_results WHERE appointment_id IN ($placeholders)", $appt_ids);
        foreach ($results as $r) {
            $appointment_results[$r['appointment_id']] = $r;
        }
    }
} catch (Exception $e) {
    $loadError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Appointments - MediArchive</title>
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
                    <h2 class="mb-0"><i class="bi bi-calendar"></i> My Appointments</h2>
                    <div class="d-flex align-items-center gap-2">
                        <a class="btn btn-outline-secondary btn-sm <?php echo $show==='upcoming'?'active':''; ?>" href="?show=upcoming">Upcoming</a>
                        <a class="btn btn-outline-secondary btn-sm <?php echo $show==='all'?'active':''; ?>" href="?show=all">All</a>
                        <a href="request_appointment.php" class="btn btn-primary"><i class="bi bi-calendar-plus"></i> Request Appointment</a>
                    </div>
                </div>
                <?php if (!empty($loadError)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($loadError); ?></div>
                <?php endif; ?>

				<div class="card shadow-sm">
					<div class="card-header d-flex align-items-center gap-2 py-2">
						<span class="small text-muted">View:</span>
						<button class="btn btn-sm btn-outline-primary" id="btnListView">List</button>
						<button class="btn btn-sm btn-outline-primary" id="btnCalendarView">Calendar</button>
					</div>
					<div class="card-body">
						<div id="listContainer" <?php echo empty($appointments) ? '' : ''; ?>>
							<?php if (!empty($appointments)): ?>
							<div class="table-responsive">
								<table class="table table-hover">
									<thead>
										<tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Clinic / Specialization</th>
                                            <th>Purpose</th>
                                            <th>Status</th>
                                            <th>Results</th>
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
                                                <span class="badge bg-<?php echo $a['status']==='approved'?'success':($a['status']==='pending'?'warning':($a['status']==='completed'?'info':'secondary')); ?>"><?php echo htmlspecialchars(ucfirst($a['status'])); ?></span>
                                                <?php if ($a['payment_required'] && $a['payment_amount'] > 0): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php if (isset($payment_status[$a['id']]) && $payment_status[$a['id']]): ?>
                                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Paid</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning"><i class="bi bi-exclamation-triangle"></i> Payment Required: ₱<?php echo number_format($a['payment_amount'], 2); ?></span>
                                                        <?php endif; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($a['payment_required'] && $a['payment_amount'] > 0 && (!isset($payment_status[$a['id']]) || !$payment_status[$a['id']])): ?>
                                                <button class="btn btn-sm btn-warning me-2" type="button" data-bs-toggle="modal" data-bs-target="#paymentModal_<?php echo intval($a['id']); ?>">
                                                    <i class="bi bi-credit-card"></i> Pay Now
                                                </button>
                                                <?php endif; ?>
                                                <?php if (isset($appointment_results[$a['id']])): ?>
                                                <button class="btn btn-sm btn-info" type="button" data-bs-toggle="modal" data-bs-target="#resultModal_<?php echo intval($a['id']); ?>">
                                                    <i class="bi bi-file-medical"></i> View Results
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
							<?php else: ?>
							<div class="text-center py-5">
								<p class="text-muted mb-3">No appointments found for this view.</p>
								<a href="?show=all" class="btn btn-outline-secondary btn-sm me-2">Show All</a>
								<a href="request_appointment.php" class="btn btn-primary btn-sm"><i class="bi bi-calendar-plus"></i> Request Appointment</a>
							</div>
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
						'title' => $a['clinic_name'] . ' — ' . $a['specialization'] . ' (' . substr($a['time_slot'],0,5) . ')',
						'start' => $a['appointment_date'] . 'T' . substr($a['time_slot'],0,5) . ':00',
						'end' => $a['appointment_date'] . 'T' . substr($a['time_slot'],0,5) . ':00',
						'extendedProps' => [
							'purpose' => $a['purpose'],
							'status' => $a['status']
						]
					];
					}, $appointments), JSON_UNESCAPED_UNICODE); ?>,
				eventDidMount: function(info){
					const status = info.event.extendedProps.status || '';
					const color = status === 'approved' ? '#198754' : (status === 'pending' ? '#f0ad4e' : '#6c757d');
					info.el.style.borderLeft = '4px solid ' + color;
					const title = info.event.title + (info.event.extendedProps.purpose ? '\nPurpose: ' + info.event.extendedProps.purpose : '') + (status ? '\nStatus: ' + status : '');
					info.el.title = title;
				}
			});
			calendar.render();
		}
	}

	if (btnList) btnList.addEventListener('click', showList);
	if (btnCal) btnCal.addEventListener('click', showCalendar);

	// default: list view
	showList();
});
</script>

<!-- Appointment Results Modals -->
<?php foreach ($appointments as $a): 
    $result = $appointment_results[$a['id']] ?? null;
    if ($result):
?>
<div class="modal fade" id="resultModal_<?php echo intval($a['id']); ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Appointment Results - <?php echo htmlspecialchars($a['clinic_name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Diagnosis</label>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($result['diagnosis'])); ?></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Treatment Notes</label>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($result['treatment_notes'])); ?></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Prescriptions</label>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($result['prescriptions'])); ?></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Follow-up Instructions</label>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($result['follow_up_instructions'])); ?></p>
                </div>
                <small class="text-muted">Results added on <?php echo date('F d, Y g:i A', strtotime($result['created_at'])); ?></small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; endforeach; ?>

<!-- Payment Modals for Appointments -->
<?php foreach ($appointments as $a): ?>
<?php if ($a['payment_required'] && $a['payment_amount'] > 0 && (!isset($payment_status[$a['id']]) || !$payment_status[$a['id']])): ?>
<div class="modal fade" id="paymentModal_<?php echo intval($a['id']); ?>" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel"><i class="bi bi-credit-card"></i> Process Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Appointment:</strong> <?php echo htmlspecialchars($a['clinic_name']); ?><br>
                    <strong>Date:</strong> <?php echo htmlspecialchars($a['appointment_date']); ?> at <?php echo htmlspecialchars(substr($a['time_slot'], 0, 5)); ?><br>
                    <strong>Amount:</strong> ₱<?php echo number_format($a['payment_amount'], 2); ?>
                </div>
                <form id="paymentForm_<?php echo intval($a['id']); ?>">
                    <?php echo SecurityManager::getCSRFField(); ?>
                    <input type="hidden" name="payment_type" value="appointment">
                    <input type="hidden" name="reference_id" value="<?php echo intval($a['id']); ?>">
                    <input type="hidden" name="amount" value="<?php echo $a['payment_amount']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="gcash">GCash</option>
                            <option value="paymaya">PayMaya</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle"></i> <strong>Demo Mode:</strong> Payment will be marked as paid immediately.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="processPaymentBtn_<?php echo intval($a['id']); ?>">
                    <i class="bi bi-check-circle"></i> Process Payment
                </button>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('processPaymentBtn_<?php echo intval($a['id']); ?>')?.addEventListener('click', function() {
    const form = document.getElementById('paymentForm_<?php echo intval($a['id']); ?>');
    const formData = new FormData(form);
    
    fetch('../api/process_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Payment processed successfully! Transaction ID: ' + data.transaction_id);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to process payment'));
        }
    })
    .catch(error => {
        alert('Error processing payment');
        console.error(error);
    });
});
</script>
<?php endif; ?>
<?php endforeach; ?>
</body>
</html>


