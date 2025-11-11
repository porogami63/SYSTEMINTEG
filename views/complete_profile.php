<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Load current values
$user = $db->fetch("SELECT full_name, email, phone, profile_photo, role FROM users WHERE id = ?", [$userId]);
$patient = $db->fetch("SELECT date_of_birth, gender, address, patient_code FROM patients WHERE user_id = ?", [$userId]);
$clinic = $db->fetch("SELECT clinic_name, medical_license, specialization, address, contact_phone FROM clinics WHERE user_id = ?", [$userId]);
$currentRole = $_SESSION['role'] ?? ($user['role'] ?? 'patient');
$specializations = [
    'General Medicine', 'Cardiology', 'Neurology', 'Pediatrics', 'Orthopedics', 'Dermatology',
    'Psychiatry', 'Oncology', 'Gynecology', 'Emergency Medicine', 'Internal Medicine', 'Surgery'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        SecurityManager::verifyCSRFToken();

        $roleChoice = $_POST['user_type'] ?? $currentRole;
        $roleChoice = $roleChoice === 'clinic_admin' ? 'clinic_admin' : 'patient';

        $fullName = trim($_POST['full_name'] ?? ($user['full_name'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');

        // Optional profile photo upload
        $profilePhoto = null;
        if (!empty($_FILES['profile_photo']['name'])) {
            $saved = FileProcessor::saveUpload($_FILES['profile_photo'], UPLOAD_DIR, ['jpg','jpeg','png','gif'], 2 * 1024 * 1024);
            $profilePhoto = 'uploads/' . basename($saved);
        }

        if ($profilePhoto) {
            $db->execute("UPDATE users SET full_name = ?, phone = ?, profile_photo = ?, role = ? WHERE id = ?", [$fullName, $phone, $profilePhoto, $roleChoice, $userId]);
            $_SESSION['profile_photo'] = $profilePhoto;
        } else {
            $db->execute("UPDATE users SET full_name = ?, phone = ?, role = ? WHERE id = ?", [$fullName, $phone, $roleChoice, $userId]);
        }

        if ($roleChoice === 'clinic_admin') {
            $clinicName = trim($_POST['clinic_name'] ?? '');
            $medicalLicense = trim($_POST['medical_license'] ?? '');
            $specialization = $_POST['specialization'] ?? '';
            $clinicAddress = trim($_POST['clinic_address'] ?? '');
            $clinicPhone = trim($_POST['clinic_phone'] ?? '');

            if (empty($clinicName) || empty($medicalLicense) || empty($specialization) || empty($clinicAddress) || empty($clinicPhone)) {
                throw new Exception('Please complete all clinic information fields.');
            }

            if (!in_array($specialization, $specializations, true)) {
                throw new Exception('Invalid specialization selected.');
            }

            $clinicExists = $db->fetch("SELECT id FROM clinics WHERE user_id = ?", [$userId]);
            if ($clinicExists) {
                $db->execute(
                    "UPDATE clinics SET clinic_name = ?, medical_license = ?, specialization = ?, address = ?, contact_phone = ? WHERE user_id = ?",
                    [$clinicName, $medicalLicense, $specialization, $clinicAddress, $clinicPhone, $userId]
                );
            } else {
                $db->execute(
                    "INSERT INTO clinics (user_id, clinic_name, medical_license, specialization, address, contact_phone, is_available) VALUES (?, ?, ?, ?, ?, ?, 1)",
                    [$userId, $clinicName, $medicalLicense, $specialization, $clinicAddress, $clinicPhone]
                );
            }

            // Remove patient record if it exists
            $db->execute("DELETE FROM patients WHERE user_id = ?", [$userId]);
        } else {
            $dob = $_POST['date_of_birth'] ?? '';
            $gender = $_POST['gender'] ?? '';
            $address = trim($_POST['address'] ?? '');

            if (empty($dob) || empty($gender) || empty($address)) {
                throw new Exception('Please complete all patient information fields.');
            }

            $exists = $db->fetch("SELECT id, patient_code FROM patients WHERE user_id = ?", [$userId]);
            if ($exists) {
                $db->execute("UPDATE patients SET date_of_birth = ?, gender = ?, address = ? WHERE user_id = ?", [$dob, $gender, $address, $userId]);
            } else {
                $code = 'PAT-' . str_pad((string)$userId, 4, '0', STR_PAD_LEFT);
                $db->execute("INSERT INTO patients (user_id, patient_code, date_of_birth, gender, address) VALUES (?, ?, ?, ?, ?)", [$userId, $code, $dob, $gender, $address]);
            }

            // Remove clinic record to avoid conflicts
            $db->execute("DELETE FROM clinics WHERE user_id = ?", [$userId]);
        }

        $_SESSION['full_name'] = $fullName;
        $_SESSION['role'] = $roleChoice;
        $currentRole = $roleChoice;

        redirect('dashboard.php');
    } catch (Exception $e) {
        $error = 'Failed to save profile: ' . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Complete Profile - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
body { background: linear-gradient(135deg, #0f63d6 0%, #0c3f84 100%); min-height: 100vh; display:flex; align-items:center; }
.card { border-radius:18px; box-shadow: 0 20px 60px rgba(5, 19, 53, 0.35); border: none; }
.role-pill { border: 1px solid #d0d7ff; border-radius: 999px; padding: 10px 20px; cursor: pointer; transition: all .2s ease; background: #f8faff; color: #20417c; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
.role-pill input { display: none; }
.role-pill:hover { border-color: #7c9dff; box-shadow: 0 5px 15px rgba(32, 65, 124, 0.15); }
.role-pill.active { background: linear-gradient(135deg, #2150c9 0%, #1f9cd6 100%); color: #fff; border-color: transparent; box-shadow: 0 8px 20px rgba(33, 80, 201, 0.4); }
.info-panel { background: #f4f7ff; border: 1px solid #d6e4ff; border-radius: 16px; }
.badge-icon { width: 46px; height: 46px; border-radius: 50%; background: linear-gradient(135deg, #2150c9 0%, #1f9cd6 100%); color: #fff; display:flex; align-items:center; justify-content:center; font-size: 1.3rem; box-shadow: 0 10px 25px rgba(33, 80, 201, 0.3); }
.role-section { background: #fdfdff; border: 1px solid #e6ecff; border-radius: 16px; padding: 24px 26px; box-shadow: 0 12px 30px rgba(15, 60, 148, 0.08); }
.role-section h6 { letter-spacing: .5px; }
.role-section.d-none { display: none !important; }
.btn-primary { background: linear-gradient(135deg, #2150c9 0%, #1f9cd6 100%); border: none; box-shadow: 0 12px 20px rgba(33, 80, 201, 0.25); }
.btn-primary:hover { background: linear-gradient(135deg, #1f46b0 0%, #1a8ec0 100%); }
.btn-outline-secondary { border-radius: 999px; }
</style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card p-4 p-md-5">
                <h3 class="mb-3 text-primary">Complete Your Profile</h3>
                <p class="text-muted mb-4">Please provide the basic information we need to set up your MediArchive account.</p>
                <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <?php if (!empty($success)): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                     <?php echo SecurityManager::getCSRFField(); ?>
                    <div class="mb-4">
                        <label class="form-label text-uppercase small fw-semibold text-muted">I am a</label>
                        <div class="d-flex flex-wrap gap-3">
                            <?php $selectedRole = $currentRole === 'clinic_admin' ? 'clinic_admin' : 'patient'; ?>
                            <label class="role-pill <?php echo $selectedRole === 'patient' ? 'active' : ''; ?>">
                                <input type="radio" name="user_type" value="patient" <?php echo $selectedRole === 'patient' ? 'checked' : ''; ?>>
                                <span><i class="bi bi-person-heart me-2"></i>Patient</span>
                            </label>
                            <label class="role-pill <?php echo $selectedRole === 'clinic_admin' ? 'active' : ''; ?>">
                                <input type="radio" name="user_type" value="clinic_admin" <?php echo $selectedRole === 'clinic_admin' ? 'checked' : ''; ?>>
                                <span><i class="bi bi-hospital me-2"></i>Doctor / Clinic Admin</span>
                            </label>
                        </div>
                    </div>

                    <?php $isPatientSelected = $selectedRole === 'patient'; $isClinicSelected = $selectedRole === 'clinic_admin'; ?>

                    <div class="info-panel p-3 mb-4">
                        <div class="d-flex gap-3 align-items-start">
                            <div class="badge-icon"><i class="bi bi-file-earmark-medical"></i></div>
                            <div>
                                <h6 class="mb-1 fw-semibold">Why we need this</h6>
                                <p class="mb-0 text-muted small">MediArchive tailors your dashboard based on your role. Patients manage certificates, appointments, and chat. Clinicians issue certificates, sign documents, and oversee patient requests.</p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="e.g. +63 900 000 0000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Profile Photo (optional)</label>
                            <input type="file" class="form-control" name="profile_photo" accept="image/*">
                        </div>
                    </div>

                    <div id="patientFields" class="role-section mt-4<?php echo $isPatientSelected ? '' : ' d-none'; ?>">
                        <h6 class="mb-3 fw-semibold text-primary">Patient Details</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth" value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gender</label>
                                <?php $g = $patient['gender'] ?? ''; ?>
                                <select class="form-select" name="gender">
                                    <option value="" <?php echo empty($g) ? 'selected' : ''; ?>>Select</option>
                                    <option value="Male" <?php echo $g==='Male'?'selected':''; ?>>Male</option>
                                    <option value="Female" <?php echo $g==='Female'?'selected':''; ?>>Female</option>
                                    <option value="Other" <?php echo $g==='Other'?'selected':''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Patient Code</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($patient['patient_code'] ?? 'Will be generated'); ?>" disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Home Address</label>
                                <textarea class="form-control" name="address" rows="3" placeholder="Complete residential address"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div id="clinicFields" class="role-section mt-4<?php echo $isClinicSelected ? '' : ' d-none'; ?>">
                        <h6 class="mb-3 fw-semibold text-success">Clinic Details</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Clinic / Practice Name</label>
                                <input type="text" class="form-control" name="clinic_name" value="<?php echo htmlspecialchars($clinic['clinic_name'] ?? ''); ?>" placeholder="e.g. MediArchive Family Clinic">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Medical License</label>
                                <input type="text" class="form-control" name="medical_license" value="<?php echo htmlspecialchars($clinic['medical_license'] ?? ''); ?>" placeholder="License Number">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Specialization</label>
                                <select class="form-select" name="specialization">
                                    <option value="">Select Specialization</option>
                                    <?php foreach ($specializations as $spec): ?>
                                    <option value="<?php echo htmlspecialchars($spec); ?>" <?php echo (($clinic['specialization'] ?? '') === $spec) ? 'selected' : ''; ?>><?php echo htmlspecialchars($spec); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Clinic Contact</label>
                                <input type="text" class="form-control" name="clinic_phone" value="<?php echo htmlspecialchars($clinic['contact_phone'] ?? ''); ?>" placeholder="e.g. (02) 7000 0000">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Clinic Address</label>
                                <textarea class="form-control" name="clinic_address" rows="3" placeholder="Clinic full address including city and province"><?php echo htmlspecialchars($clinic['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-between align-items-center">
                        <a href="dashboard.php" class="btn btn-outline-secondary">Skip for now</a>
                        <button type="submit" class="btn btn-primary px-4">Save and Continue</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const roleRadios = document.querySelectorAll('input[name="user_type"]');
    const rolePills = document.querySelectorAll('.role-pill');
    const patientSection = document.getElementById('patientFields');
    const clinicSection = document.getElementById('clinicFields');

    function syncRole(value){
        if (patientSection) {
            patientSection.classList.toggle('d-none', value !== 'patient');
        }
        if (clinicSection) {
            clinicSection.classList.toggle('d-none', value !== 'clinic_admin');
        }
        rolePills.forEach(function(pill){
            const input = pill.querySelector('input[name="user_type"]');
            if (input) {
                pill.classList.toggle('active', input.value === value);
            }
        });
    }

    roleRadios.forEach(function(radio){
        radio.addEventListener('change', function(){
            syncRole(this.value);
        });
    });

    const initial = document.querySelector('input[name="user_type"]:checked');
    syncRole(initial ? initial.value : 'patient');
});
</script>
</body>
</html>

