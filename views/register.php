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
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitizeInput($_POST['full_name']);
    $role = sanitizeInput($_POST['role']);
    $phone = sanitizeInput($_POST['phone']);
    
    // Clinic admin specific fields
    $medical_license = isset($_POST['medical_license']) ? sanitizeInput($_POST['medical_license']) : '';
    $specialization = isset($_POST['specialization']) ? sanitizeInput($_POST['specialization']) : '';
    $clinic_name = isset($_POST['clinic_name']) ? sanitizeInput($_POST['clinic_name']) : '';
    
    // Patient specific fields
    $date_of_birth = isset($_POST['date_of_birth']) ? sanitizeInput($_POST['date_of_birth']) : '';
    $gender = isset($_POST['gender']) ? sanitizeInput($_POST['gender']) : '';
    $address = isset($_POST['address']) ? sanitizeInput($_POST['address']) : '';
    
    // Validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($role === 'clinic_admin' && empty($medical_license)) {
        $error = "Medical license is required for clinic admins";
    } elseif ($role === 'clinic_admin' && empty($specialization)) {
        $error = "Specialization is required for clinic admins";
    } else {
        $conn = getDBConnection();
        
        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role, phone) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $email, $hashed_password, $full_name, $role, $phone);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Create patient profile if patient role
                if ($role === 'patient') {
                    $patient_code = 'PAT-' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
                    $stmt2 = $conn->prepare("INSERT INTO patients (user_id, patient_code, date_of_birth, gender, address) VALUES (?, ?, ?, ?, ?)");
                    $stmt2->bind_param("issss", $user_id, $patient_code, $date_of_birth, $gender, $address);
                    $stmt2->execute();
                    $stmt2->close();
                } elseif ($role === 'clinic_admin') {
                    // Create clinic profile with medical license and specialization
                    $final_clinic_name = !empty($clinic_name) ? $clinic_name : $full_name . "'s Clinic";
                    $stmt2 = $conn->prepare("INSERT INTO clinics (user_id, clinic_name, medical_license, specialization, contact_phone) VALUES (?, ?, ?, ?, ?)");
                    $stmt2->bind_param("issss", $user_id, $final_clinic_name, $medical_license, $specialization, $phone);
                    $stmt2->execute();
                    $stmt2->close();
                }
                
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed";
            }
        }
        $stmt->close();
        $conn->close();
    }
}
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 40px 0;
}
.register-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    max-width: 700px;
    width: 100%;
}
.role-specific {
    display: none;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-top: 10px;
}
</style>
</head>
<body>
<div class="container">
    <div class="register-card p-5 mx-auto">
        <h2 class="text-center mb-4 text-primary">
            <i class="bi bi-heart-pulse-fill"></i> MediArchive
        </h2>
        <h4 class="text-center mb-4">Create Account</h4>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" id="registerForm">
            <div class="mb-3">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="full_name" required>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" name="phone" placeholder="+1 (555) 123-4567">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Role <span class="text-danger">*</span></label>
                <select class="form-select" name="role" id="roleSelect" required onchange="toggleRoleSpecific()">
                    <option value="">Select Role</option>
                    <option value="patient">Patient</option>
                    <option value="clinic_admin">Clinic Admin / Doctor</option>
                </select>
            </div>
            
            <!-- Clinic Admin Specific Fields -->
            <div id="clinicAdminFields" class="role-specific">
                <h6 class="mb-3 text-primary"><i class="bi bi-hospital"></i> Medical Credentials</h6>
                <div class="mb-3">
                    <label class="form-label">Medical License Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="medical_license" placeholder="MD-LIC-2024001">
                </div>
                <div class="mb-3">
                    <label class="form-label">Specialization <span class="text-danger">*</span></label>
                    <select class="form-select" name="specialization">
                        <option value="">Select Specialization</option>
                        <?php foreach ($specializations as $spec): ?>
                        <option value="<?php echo $spec; ?>"><?php echo $spec; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Clinic Name</label>
                    <input type="text" class="form-control" name="clinic_name" placeholder="Leave blank to auto-generate">
                </div>
            </div>
            
            <!-- Patient Specific Fields -->
            <div id="patientFields" class="role-specific">
                <h6 class="mb-3 text-success"><i class="bi bi-person"></i> Personal Information</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" name="date_of_birth">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="2"></textarea>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-person-plus"></i> Create Account
            </button>
        </form>
        
        <div class="text-center mt-3">
            <a href="login.php">Already have an account? Login here</a>
        </div>
        <div class="text-center mt-2">
            <a href="../index.php">Back to Home</a>
        </div>
    </div>
</div>

<script>
function toggleRoleSpecific() {
    const role = document.getElementById('roleSelect').value;
    const clinicFields = document.getElementById('clinicAdminFields');
    const patientFields = document.getElementById('patientFields');
    
    if (role === 'clinic_admin') {
        clinicFields.style.display = 'block';
        patientFields.style.display = 'none';
    } else if (role === 'patient') {
        clinicFields.style.display = 'none';
        patientFields.style.display = 'block';
    } else {
        clinicFields.style.display = 'none';
        patientFields.style.display = 'none';
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
