<?php
require_once '../config.php';

if (!isLoggedIn() || !isPatient()) {
    redirect('dashboard.php');
}

$conn = getDBConnection();
$error = '';
$success = '';

// Fetch available clinics
$selected_spec = isset($_GET['specialization']) ? sanitizeInput($_GET['specialization']) : '';
$stmt = $selected_spec
    ? $conn->prepare("SELECT c.id, c.clinic_name, c.specialization, c.address, c.is_available FROM clinics c WHERE c.specialization = ? AND c.is_available = 1 ORDER BY c.clinic_name")
    : $conn->prepare("SELECT c.id, c.clinic_name, c.specialization, c.address, c.is_available FROM clinics c WHERE c.is_available = 1 ORDER BY c.clinic_name");
if ($selected_spec) { $stmt->bind_param("s", $selected_spec); }
$stmt->execute();
$clinics = $stmt->get_result();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clinic_id = intval($_POST['clinic_id']);
    $requested_specialization = sanitizeInput($_POST['requested_specialization']);
    $purpose = sanitizeInput($_POST['purpose']);
    $details = sanitizeInput($_POST['details']);
    $answers_json = isset($_POST['spec_answers']) ? json_encode($_POST['spec_answers']) : null;

    $patientStmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
    $patientStmt->bind_param("i", $_SESSION['user_id']);
    $patientStmt->execute();
    $patient = $patientStmt->get_result()->fetch_assoc();
    $patientStmt->close();

    if ($patient) {
        // Check if spec_answers column exists, if not, add it
        $checkCol = $conn->query("SHOW COLUMNS FROM certificate_requests LIKE 'spec_answers'");
        if ($checkCol->num_rows == 0) {
            $conn->query("ALTER TABLE certificate_requests ADD COLUMN spec_answers TEXT DEFAULT NULL");
        }
        
        $ins = $conn->prepare("INSERT INTO certificate_requests (patient_id, clinic_id, requested_specialization, purpose, details, spec_answers) VALUES (?, ?, ?, ?, ?, ?)");
        $ins->bind_param("iissss", $patient['id'], $clinic_id, $requested_specialization, $purpose, $details, $answers_json);
        if ($ins->execute()) {
            $success = 'Request submitted successfully';
            // notify clinic admin
            $cl = $conn->prepare("SELECT user_id FROM clinics WHERE id = ?");
            $cl->bind_param("i", $clinic_id);
            $cl->execute();
            $clid = $cl->get_result()->fetch_assoc();
            $cl->close();
            if ($clid) {
                notifyUser($conn, intval($clid['user_id']), 'New Certificate Request', 'A patient submitted a new certificate request.', 'certificates.php');
            }
        } else {
            $error = 'Failed to submit request';
        }
        $ins->close();
    } else {
        $error = 'Patient profile not found';
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Request Certificate - MediArchive</title>
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
                <h2 class="mb-4"><i class="bi bi-file-earmark-plus"></i> Request Certificate</h2>

                <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

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
                                    <label class="form-label">Choose Clinic <span class="text-danger">*</span></label>
                                    <select name="clinic_id" class="form-select" required>
                                        <option value="">Select clinic</option>
                                        <?php while ($cl = $clinics->fetch_assoc()): ?>
                                        <option value="<?php echo $cl['id']; ?>">
                                            <?php echo htmlspecialchars($cl['clinic_name'] . ' — ' . $cl['specialization']); ?>
                                            <?php echo !$cl['is_available'] ? ' (Unavailable)' : ''; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Purpose <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="purpose" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Details</label>
                                <textarea class="form-control" name="details" rows="4"></textarea>
                            </div>
                            <div id="specQuestions"></div>

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
const specToQuestions = {
  'General Medicine': [
    {name:'duration', label:'How long have symptoms been present?', type:'text', required:true},
    {name:'fever', label:'Do you have fever?', type:'select', options:['No','Mild','High']}
  ],
  'Cardiology': [
    {name:'chest_pain', label:'Chest pain present?', type:'select', options:['No','Occasional','Frequent'], required:true},
    {name:'bp_history', label:'History of hypertension?', type:'select', options:['No','Yes']}
  ],
  'Neurology': [
    {name:'headache', label:'Headache severity', type:'select', options:['None','Mild','Moderate','Severe']},
    {name:'neuro_deficits', label:'Any weakness/numbness?', type:'select', options:['No','Yes']}
  ],
  'Pediatrics': [
    {name:'child_age', label:'Child age', type:'number', required:true},
    {name:'vaccinations', label:'Vaccination up to date?', type:'select', options:['Unknown','No','Yes']}
  ],
  'Orthopedics': [
    {name:'injury', label:'Recent injury?', type:'select', options:['No','Yes']},
    {name:'pain_scale', label:'Pain scale (0-10)', type:'number'}
  ],
  'Dermatology': [
    {name:'rash', label:'Rash present?', type:'select', options:['No','Yes']},
    {name:'duration', label:'Duration (days)', type:'number'}
  ],
  'Psychiatry': [
    {name:'mood', label:'Mood', type:'select', options:['Stable','Low','Anxious']},
    {name:'sleep', label:'Sleep issues?', type:'select', options:['No','Yes']}
  ],
  'Oncology': [
    {name:'weight_loss', label:'Unintentional weight loss?', type:'select', options:['No','Yes']},
    {name:'family_history', label:'Family history of cancer?', type:'select', options:['No','Yes']}
  ],
  'Gynecology': [
    {name:'lmp', label:'Last menstrual period', type:'date'},
    {name:'pregnant', label:'Pregnant?', type:'select', options:['No','Yes','Unsure']}
  ],
  'Emergency Medicine': [
    {name:'life_threat', label:'Life-threatening symptoms?', type:'select', options:['No','Yes'], required:true}
  ],
  'Internal Medicine': [
    {name:'chronic', label:'Existing chronic illnesses', type:'text'}
  ],
  'Surgery': [
    {name:'prior_surgery', label:'Prior surgeries?', type:'text'},
    {name:'bleeding', label:'Bleeding disorders?', type:'select', options:['No','Yes']}
  ]
};

function renderSpecQuestions(spec){
  const container = document.getElementById('specQuestions');
  container.innerHTML = '';
  const qs = specToQuestions[spec] || [];
  if (qs.length === 0) return;
  const wrapper = document.createElement('div');
  wrapper.className = 'mb-3';
  const h = document.createElement('h6');
  h.className = 'mb-2 text-primary';
  h.textContent = 'Additional Questions';
  wrapper.appendChild(h);
  qs.forEach(q => {
    const div = document.createElement('div');
    div.className = 'mb-2';
    const label = document.createElement('label');
    label.className = 'form-label';
    label.textContent = q.label + (q.required?' *':'');
    div.appendChild(label);
    let input;
    if (q.type === 'select') {
      input = document.createElement('select');
      input.className = 'form-select';
      input.name = 'spec_answers['+ q.name +']';
      (q.options||[]).forEach(opt => {
        const o = document.createElement('option');
        o.value = opt; o.textContent = opt; input.appendChild(o);
      });
    } else {
      input = document.createElement('input');
      input.className = 'form-control';
      input.type = q.type || 'text';
      input.name = 'spec_answers['+ q.name +']';
    }
    if (q.required) input.required = true;
    div.appendChild(input);
    wrapper.appendChild(div);
  });
  container.appendChild(wrapper);
}

function onSpecChange(spec){
  renderSpecQuestions(spec);
  const params = new URLSearchParams(window.location.search);
  if (spec) { params.set('specialization', spec); } else { params.delete('specialization'); }
  const newUrl = window.location.pathname + '?' + params.toString();
  window.history.replaceState({}, '', newUrl);
}

// initial render if specialization in query
renderSpecQuestions('<?php echo addslashes($selected_spec); ?>');
</script>
</body>
</html>


