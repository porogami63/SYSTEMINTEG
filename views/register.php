<?php
require_once '../config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

$specializations = [
    'General Medicine',
    'Cardiology',
    'Neurology',
    'Pediatrics',
    'Orthopedics',
    'Dermatology',
    'Psychiatry',
    'Oncology',
    'Gynecology',
    'Emergency Medicine',
    'Internal Medicine',
    'Surgery'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    SecurityManager::verifyCSRFToken();
    
    // Rate limiting for registration
    $clientIP = SecurityManager::getClientIP();
    if (!SecurityManager::checkRateLimit('register', 3, 3600, $clientIP)) {
        $error = "Too many registration attempts. Please try again later.";
    } else {
        $usernameResult = InputValidator::validate($_POST['username'] ?? '', 'string', ['min_length' => 3, 'max_length' => 50]);
        $emailResult = InputValidator::validate($_POST['email'] ?? '', 'email');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $fullNameResult = InputValidator::validate($_POST['full_name'] ?? '', 'string', ['min_length' => 2, 'max_length' => 100]);
        $roleResult = InputValidator::validate($_POST['role'] ?? '', 'string', ['min_length' => 1, 'max_length' => 20]);
        
        if (!$usernameResult['valid']) {
            $error = "Invalid username: " . ($usernameResult['error'] ?? 'Invalid format');
        } elseif (!$emailResult['valid']) {
            $error = "Invalid email: " . ($emailResult['error'] ?? 'Invalid format');
        } elseif (!$fullNameResult['valid']) {
            $error = "Invalid full name: " . ($fullNameResult['error'] ?? 'Invalid format');
        } elseif (!$roleResult['valid']) {
            $error = "Invalid role";
        } else {
            $username = $usernameResult['value'];
            $email = $emailResult['value'];
            $full_name = $fullNameResult['value'];
            $role = $roleResult['value'];
            $phone = isset($_POST['phone']) ? InputValidator::validate($_POST['phone'], 'phone')['value'] ?? '' : '';
            $home_address = isset($_POST['home_address']) ? sanitizeInput($_POST['home_address']) : '';
            
            // Handle profile photo upload via FileProcessor with security validation
            $profile_photo_path = '';
            if (!empty($_FILES['profile_photo']['name'])) {
                $uploadValidation = SecurityManager::validateFileUpload(
                    $_FILES['profile_photo'],
                    ['jpg', 'jpeg', 'png', 'gif'],
                    2 * 1024 * 1024 // 2MB
                );
                if ($uploadValidation['valid']) {
                    try {
                        $saved = FileProcessor::saveUpload($_FILES['profile_photo'], UPLOAD_DIR, ['jpg','jpeg','png','gif'], 2 * 1024 * 1024);
                        $profile_photo_path = 'uploads/' . basename($saved);
                    } catch (Exception $e) {
                        $error = 'Profile photo upload failed: ' . $e->getMessage();
                    }
                } else {
                    $error = 'Invalid file upload: ' . implode(', ', $uploadValidation['errors']);
                }
            }
            
            // Clinic admin specific fields
            $medical_license = isset($_POST['medical_license']) ? sanitizeInput($_POST['medical_license']) : '';
            $specialization = isset($_POST['specialization']) ? sanitizeInput($_POST['specialization']) : '';
            $clinic_name = isset($_POST['clinic_name']) ? sanitizeInput($_POST['clinic_name']) : '';
            $clinic_address = isset($_POST['clinic_address']) ? sanitizeInput($_POST['clinic_address']) : '';
            
            // Patient specific fields
            $date_of_birth = isset($_POST['date_of_birth']) ? sanitizeInput($_POST['date_of_birth']) : '';
            $gender = isset($_POST['gender']) ? sanitizeInput($_POST['gender']) : '';
            $address = isset($_POST['address']) ? sanitizeInput($_POST['address']) : '';
            
            // Password validation
            $passwordResult = InputValidator::validatePassword($password, 6);
            if (!$passwordResult['valid']) {
                $error = $passwordResult['error'] ?? "Password does not meet requirements";
            } elseif ($password !== $confirm_password) {
                $error = "Passwords do not match";
            } elseif ($role === 'clinic_admin' && empty($medical_license)) {
                $error = "Medical license is required for clinic admins";
            } elseif ($role === 'clinic_admin' && empty($specialization)) {
                $error = "Specialization is required for clinic admins";
            } else {
                try {
                    $db = Database::getInstance();

                    // Check if username or email exists
                    $existing = $db->fetch("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
                    if ($existing) {
                        $error = "Username or email already exists";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                        // Insert user (include profile_photo and home_address columns; pass empty strings if not provided)
                        $sql = "INSERT INTO users (username, email, password, full_name, role, phone, profile_photo, home_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $db->execute($sql, [$username, $email, $hashed_password, $full_name, $role, $phone, $profile_photo_path, $home_address]);
                        $user_id = $db->lastInsertId();

                        if ($role === 'patient') {
                            $patient_code = 'PAT-' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
                            $db->execute("INSERT INTO patients (user_id, patient_code, date_of_birth, gender, address) VALUES (?, ?, ?, ?, ?)", [$user_id, $patient_code, $date_of_birth, $gender, $address]);
                        } elseif ($role === 'clinic_admin') {
                            $final_clinic_name = !empty($clinic_name) ? $clinic_name : $full_name . "'s Clinic";
                            $db->execute("INSERT INTO clinics (user_id, clinic_name, medical_license, specialization, address, contact_phone) VALUES (?, ?, ?, ?, ?, ?)", [$user_id, $final_clinic_name, $medical_license, $specialization, $clinic_address, $phone]);
                        }

                        // Log registration
                        AuditLogger::log('USER_REGISTERED', 'user', $user_id, [
                            'role' => $role,
                            'ip_address' => SecurityManager::getClientIP()
                        ]);
                        
                        // Reset rate limit on successful registration
                        SecurityManager::resetRateLimit('register', $clientIP);
                        
                        $success = "Registration successful! You can now login.";
                    }
                } catch (Exception $e) {
                    $error = 'Server error: ' . $e->getMessage();
                    error_log('Registration error: ' . $e->getMessage());
                    SecurityManager::logSecurityEvent('REGISTRATION_ERROR', [
                        'error' => $e->getMessage(),
                        'ip' => $clientIP
                    ]);
                }
            }
        }
    }
}

$selectedRole = $_POST['role'] ?? 'patient';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
body {
    background: linear-gradient(135deg, #0f63d6 0%, #0b3d91 100%);
    min-height: 100vh;
    padding: 60px 0;
    color: #1f2a44;
}
.register-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 24px 60px rgba(11, 61, 145, 0.35);
    max-width: 880px;
}
.role-pill {
    border: 1px solid #d0d7ff;
    border-radius: 999px;
    padding: 10px 18px;
    cursor: pointer;
    background: #f8faff;
    color: #20417c;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all .2s ease;
}
.role-pill input { display: none; }
.role-pill.active {
    background: linear-gradient(135deg, #2150c9 0%, #1f9cd6 100%);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 8px 24px rgba(33, 80, 201, 0.35);
}
.role-section {
    background: #fdfdff;
    border: 1px solid #e6ecff;
    border-radius: 16px;
    padding: 22px 24px;
    box-shadow: 0 14px 28px rgba(30, 82, 184, 0.08);
}
.role-section.d-none { display: none !important; }
.btn-primary {
    background: linear-gradient(135deg, #2150c9 0%, #1f9cd6 100%);
    border: none;
    padding: 12px 18px;
    border-radius: 14px;
    box-shadow: 0 14px 28px rgba(33, 80, 201, 0.25);
}
.btn-primary:hover { background: linear-gradient(135deg, #1f46b0 0%, #1a8ec0 100%); }
.muted-text { color: #5f6b8b; }
</style>
</head>
<body>
<div class="container">
    <div class="register-card p-4 p-md-5 mx-auto">
        <div class="text-center mb-4">
            <span class="badge rounded-pill text-bg-light px-3 py-2 mb-2">Secure Digital Enrollment</span>
            <h2 class="text-primary fw-bold mb-1"><i class="bi bi-heart-pulse-fill"></i> MediArchive</h2>
            <p class="muted-text">Create your MediArchive identity to access certificates, appointments, and real-time messaging.</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" id="registerForm" enctype="multipart/form-data" class="mt-4">
            <?php echo SecurityManager::getCSRFField(); ?>

            <div class="mb-4">
                <label class="form-label text-uppercase small fw-semibold text-muted">I am a</label>
                <div class="d-flex flex-wrap gap-2">
                    <label class="role-pill <?php echo $selectedRole === 'patient' ? 'active' : ''; ?>">
                        <input type="radio" name="role" value="patient" <?php echo $selectedRole === 'patient' ? 'checked' : ''; ?>>
                        <span><i class="bi bi-person-heart"></i> Patient</span>
                    </label>
                    <label class="role-pill <?php echo $selectedRole === 'clinic_admin' ? 'active' : ''; ?>">
                        <input type="radio" name="role" value="clinic_admin" <?php echo $selectedRole === 'clinic_admin' ? 'checked' : ''; ?>>
                        <span><i class="bi bi-hospital"></i> Doctor / Clinic Admin</span>
                    </label>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-control" name="phone" placeholder="+63 900 000 0000" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Home Address</label>
                    <input type="text" class="form-control" name="home_address" placeholder="Your residential address" value="<?php echo htmlspecialchars($_POST['home_address'] ?? ''); ?>">
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Profile Photo</label>
                <input type="file" class="form-control" name="profile_photo" accept="image/*">
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
            </div>

            <div id="patientFields" class="role-section mt-4<?php echo $selectedRole === 'patient' ? '' : ' d-none'; ?>">
                <h6 class="fw-semibold text-primary mb-3"><i class="bi bi-person"></i> Patient Essentials</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" name="date_of_birth" value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender">
                            <option value="" <?php echo empty($_POST['gender']) ? 'selected' : ''; ?>>Select</option>
                            <option value="Male" <?php echo (($_POST['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (($_POST['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (($_POST['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Home Address</label>
                        <input type="text" class="form-control" name="address" placeholder="Full address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div id="clinicAdminFields" class="role-section mt-4<?php echo $selectedRole === 'clinic_admin' ? '' : ' d-none'; ?>">
                <h6 class="fw-semibold text-success mb-3"><i class="bi bi-activity"></i> Clinic Credentials</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Clinic / Practice Name</label>
                        <input type="text" class="form-control" name="clinic_name" placeholder="MediArchive Family Clinic" value="<?php echo htmlspecialchars($_POST['clinic_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Medical License Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="medical_license" value="<?php echo htmlspecialchars($_POST['medical_license'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Specialization <span class="text-danger">*</span></label>
                        <select class="form-select" name="specialization">
                            <option value="">Select specialization</option>
                            <?php foreach ($specializations as $spec): ?>
                            <option value="<?php echo $spec; ?>" <?php echo (($_POST['specialization'] ?? '') === $spec) ? 'selected' : ''; ?>><?php echo $spec; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Clinic Address</label>
                        <input type="text" class="form-control" name="clinic_address" placeholder="Clinic full address" value="<?php echo htmlspecialchars($_POST['clinic_address'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-4">
                <i class="bi bi-person-plus"></i> Create Account
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="login.php">Already have an account? Login here</a>
        </div>
        <div class="text-center mt-1">
            <a href="../index.php">Back to Home</a>
        </div>
    </div>
</div>
<script>
const rolePills = document.querySelectorAll('.role-pill');
const patientSection = document.getElementById('patientFields');
const clinicSection = document.getElementById('clinicAdminFields');

function syncRole(value){
    rolePills.forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.role-pill input').forEach(input => {
        if (input.value === value) {
            input.checked = true;
            input.parentElement.classList.add('active');
        }
    });
    patientSection.classList.toggle('d-none', value !== 'patient');
    clinicSection.classList.toggle('d-none', value !== 'clinic_admin');
}

rolePills.forEach(pill => {
    pill.addEventListener('click', () => {
        const val = pill.querySelector('input').value;
        syncRole(val);
    });
});

document.querySelectorAll('.role-pill input').forEach(input => {
    input.addEventListener('change', () => syncRole(input.value));
});

const currentRole = document.querySelector('.role-pill input:checked');
if (currentRole) {
    syncRole(currentRole.value);
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
