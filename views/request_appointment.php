<?php
require_once '../config.php';

if (!isLoggedIn() || !isPatient()) {
    redirect('dashboard.php');
}

$conn = getDBConnection();
$error = '';
$success = '';

$selected_spec = isset($_GET['specialization']) ? sanitizeInput($_GET['specialization']) : '';

// Load available clinics for the chosen specialization
try {
    $stmt = $selected_spec
        ? $conn->prepare("SELECT id, clinic_name, specialization, address, is_available, available_from, available_to FROM clinics WHERE specialization = ? AND is_available = 1 ORDER BY clinic_name")
        : $conn->prepare("SELECT id, clinic_name, specialization, address, is_available, available_from, available_to FROM clinics WHERE is_available = 1 ORDER BY clinic_name");
    if ($selected_spec) { $stmt->bind_param("s", $selected_spec); }
    $stmt->execute();
    $clinics = $stmt->get_result();
    $stmt->close();
} catch (Exception $e) {
    $error = 'Failed to load clinics.';
    $clinics = new ArrayObject([]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clinic_id = intval($_POST['clinic_id']);
    $requested_specialization = sanitizeInput($_POST['requested_specialization']);
    $purpose = sanitizeInput($_POST['purpose']);
    $details = sanitizeInput($_POST['details']);
    $appointment_date = sanitizeInput($_POST['appointment_date']);
    $time_slot = sanitizeInput($_POST['time_slot']);

    try {
        // Basic validation
        if (empty($clinic_id) || empty($requested_specialization) || empty($purpose) || empty($appointment_date) || empty($time_slot)) {
            throw new Exception('Please complete all required fields.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date)) {
            throw new Exception('Invalid date format.');
        }
        if (!preg_match('/^\d{2}:\d{2}$/', $time_slot)) {
            throw new Exception('Invalid time format.');
        }
        if ($appointment_date < date('Y-m-d')) {
            throw new Exception('Please choose a future date.');
        }

        // Resolve patient id
        $pstmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
        $pstmt->bind_param("i", $_SESSION['user_id']);
        $pstmt->execute();
        $patient = $pstmt->get_result()->fetch_assoc();
        $pstmt->close();

        if (!$patient) {
            throw new Exception('Patient profile not found');
        }

        // Insert appointment
        $ins = $conn->prepare("INSERT INTO appointments (patient_id, clinic_id, requested_specialization, appointment_date, time_slot, purpose, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("iisssss", $patient['id'], $clinic_id, $requested_specialization, $appointment_date, $time_slot, $purpose, $details);
        if (!$ins->execute()) {
            throw new Exception('Failed to submit your request');
        }
        $ins->close();

        // Audit log appointment request
        try {
            AuditLogger::log('APPOINTMENT_REQUESTED', 'appointment', null, [
                'clinic_id' => $clinic_id,
                'appointment_date' => $appointment_date,
                'time_slot' => $time_slot,
                'purpose' => $purpose
            ]);
        } catch (Exception $e) { /* ignore */ }

        // Notify clinic admin
        $cl = $conn->prepare("SELECT user_id FROM clinics WHERE id = ?");
        $cl->bind_param("i", $clinic_id);
        $cl->execute();
        $clid = $cl->get_result()->fetch_assoc();
        $cl->close();
        if ($clid) {
            notifyUser($conn, intval($clid['user_id']), 'New Appointment Request', 'A patient requested an appointment.', 'clinic_appointments.php');
        }

        header('Location: my_appointments.php?show=all');
        exit;
    } catch (Exception $ex) {
        $error = $ex->getMessage();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Request Appointment - MediArchive</title>
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
                <h2 class="mb-4"><i class="bi bi-calendar-plus"></i> Request Appointment</h2>

                <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Specialization <span class="text-danger">*</span></label>
                                    <select class="form-select" name="requested_specialization" id="specSelect" required onchange="onSpecChange(this.value)">
                                        <option value="">Select specialization</option>
                                        <?php $specs = ['General Medicine','Cardiology','Neurology','Pediatrics','Orthopedics','Dermatology','Psychiatry','Oncology','Gynecology','Emergency Medicine','Internal Medicine','Surgery'];
                                        foreach ($specs as $sp): ?>
                                            <option value="<?php echo $sp; ?>" <?php echo $selected_spec===$sp?'selected':''; ?>><?php echo $sp; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Choose Available Doctor/Clinic <span class="text-danger">*</span></label>
                                    <select name="clinic_id" class="form-select" required>
                                        <option value="">Select doctor/clinic</option>
                                        <?php while ($cl = $clinics->fetch_assoc()): ?>
                                        <option value="<?php echo $cl['id']; ?>">
                                            <?php echo htmlspecialchars($cl['clinic_name'] . ' — ' . $cl['specialization']); ?>
                                            <?php if ($cl['available_from'] && $cl['available_to']): ?>
                                                (<?php echo substr($cl['available_from'],0,5); ?> - <?php echo substr($cl['available_to'],0,5); ?>)
                                            <?php endif; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Time Slot <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" name="time_slot" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Purpose <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="purpose" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Details</label>
                                <textarea class="form-control" name="details" rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Submit Request</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function onSpecChange(spec){
  const params = new URLSearchParams(window.location.search);
  if (spec) { params.set('specialization', spec); } else { params.delete('specialization'); }
  const newUrl = window.location.pathname + '?' + params.toString();
  window.location.assign(newUrl);
}
</script>
</body>
</html>


