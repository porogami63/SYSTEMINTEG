<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('login.php');
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

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get profile data
$profile = null;
if ($role === 'clinic_admin') {
    $stmt = $conn->prepare("SELECT * FROM clinics WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    
    // Handle profile photo upload
    $profile_photo_path = null;
    if (!empty($_FILES['profile_photo']['name'])) {
        $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $user_id . '_' . time() . '.' . strtolower($ext);
        $dest = UPLOAD_DIR . $filename;
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dest)) {
            $profile_photo_path = 'uploads/' . $filename;
        }
    }
    
    // Update user info
    if ($profile_photo_path) {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, profile_photo = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $full_name, $email, $phone, $profile_photo_path, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
    }
    
    if ($stmt->execute()) {
        // Update role-specific info
        if ($role === 'clinic_admin') {
            $medical_license = sanitizeInput($_POST['medical_license']);
            $specialization = sanitizeInput($_POST['specialization']);
            $clinic_name = sanitizeInput($_POST['clinic_name']);
            $license_number = sanitizeInput($_POST['license_number']);
            $address = sanitizeInput($_POST['address']);
            $contact_phone = sanitizeInput($_POST['contact_phone']);
            $is_available = isset($_POST['is_available']) ? 1 : 0;
            $available_from = sanitizeInput($_POST['available_from']);
            $available_to = sanitizeInput($_POST['available_to']);
            
            // Handle signature and seal uploads
            $signature_path = null;
            if (!empty($_FILES['signature_image']['name'])) {
                $ext = pathinfo($_FILES['signature_image']['name'], PATHINFO_EXTENSION);
                $filename = 'signature_' . $user_id . '_' . time() . '.' . strtolower($ext);
                $dest = UPLOAD_DIR . $filename;
                if (move_uploaded_file($_FILES['signature_image']['tmp_name'], $dest)) {
                    $signature_path = 'uploads/' . $filename;
                }
            }
            $seal_path = null;
            if (!empty($_FILES['seal_image']['name'])) {
                $ext = pathinfo($_FILES['seal_image']['name'], PATHINFO_EXTENSION);
                $filename = 'seal_' . $user_id . '_' . time() . '.' . strtolower($ext);
                $dest = UPLOAD_DIR . $filename;
                if (move_uploaded_file($_FILES['seal_image']['tmp_name'], $dest)) {
                    $seal_path = 'uploads/' . $filename;
                }
            }
            
            if ($signature_path || $seal_path) {
                $stmt2 = $conn->prepare("UPDATE clinics SET clinic_name = ?, license_number = ?, medical_license = ?, specialization = ?, address = ?, contact_phone = ?, is_available = ?, available_from = ?, available_to = ?, signature_path = IFNULL(?, signature_path), seal_path = IFNULL(?, seal_path) WHERE user_id = ?");
                $stmt2->bind_param("ssssssissssi", $clinic_name, $license_number, $medical_license, $specialization, $address, $contact_phone, $is_available, $available_from, $available_to, $signature_path, $seal_path, $user_id);
            } else {
                $stmt2 = $conn->prepare("UPDATE clinics SET clinic_name = ?, license_number = ?, medical_license = ?, specialization = ?, address = ?, contact_phone = ?, is_available = ?, available_from = ?, available_to = ? WHERE user_id = ?");
                $stmt2->bind_param("ssssssissi", $clinic_name, $license_number, $medical_license, $specialization, $address, $contact_phone, $is_available, $available_from, $available_to, $user_id);
            }
            $stmt2->execute();
            $stmt2->close();
        } else {
            $date_of_birth = sanitizeInput($_POST['date_of_birth']);
            $gender = sanitizeInput($_POST['gender']);
            $address = sanitizeInput($_POST['address']);
            
            $stmt2 = $conn->prepare("UPDATE patients SET date_of_birth = ?, gender = ?, address = ? WHERE user_id = ?");
            $stmt2->bind_param("sssi", $date_of_birth, $gender, $address, $user_id);
            $stmt2->execute();
            $stmt2->close();
        }
        
        $success = "Profile updated successfully!";
    } else {
        $error = "Failed to update profile";
    }
    
    $stmt->close();
}

// Refresh data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($role === 'clinic_admin') {
    $stmt = $conn->prepare("SELECT * FROM clinics WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
.sidebar {
    min-height: 100vh;
    background: linear-gradient(180deg, #2e7d32 0%, #1b5e20 100%);
}
.sidebar .nav-link {
    color: white;
    padding: 12px 20px;
    margin: 5px 0;
}
.sidebar .nav-link:hover {
    background: rgba(255,255,255,0.1);
}
.sidebar .nav-link.active {
    background: rgba(255,255,255,0.2);
}
.main-content {
    padding: 30px;
}
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="main-content">
                <h2 class="mb-4"><i class="bi bi-pencil-square"></i> Edit Profile</h2>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <h5 class="card-title mb-4">Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Profile Photo</label>
                                <input type="file" class="form-control" name="profile_photo" accept="image/*">
                            </div>
                            
                            <?php if ($role === 'clinic_admin' && $profile): ?>
                            <hr>
                            <h5 class="card-title mb-4">Medical Credentials</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Clinic Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="clinic_name" value="<?php echo htmlspecialchars($profile['clinic_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Medical License <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="medical_license" value="<?php echo htmlspecialchars($profile['medical_license']); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Specialization <span class="text-danger">*</span></label>
                                    <select class="form-select" name="specialization" required>
                                        <?php foreach ($specializations as $spec): ?>
                                        <option value="<?php echo $spec; ?>" <?php echo ($profile['specialization'] == $spec) ? 'selected' : ''; ?>><?php echo $spec; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Clinic License Number</label>
                                    <input type="text" class="form-control" name="license_number" value="<?php echo htmlspecialchars($profile['license_number'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Available From</label>
                                    <input type="time" class="form-control" name="available_from" value="<?php echo $profile['available_from'] ?? '09:00'; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Available To</label>
                                    <input type="time" class="form-control" name="available_to" value="<?php echo $profile['available_to'] ?? '17:00'; ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Phone</label>
                                <input type="text" class="form-control" name="contact_phone" value="<?php echo htmlspecialchars($profile['contact_phone'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Doctor Signature Image</label>
                                    <input type="file" class="form-control" name="signature_image" accept="image/*">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Clinic Seal Image</label>
                                    <input type="file" class="form-control" name="seal_image" accept="image/*">
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_available" id="is_available" <?php echo $profile['is_available'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_available">Currently Available for Appointments</label>
                                </div>
                            </div>
                            <?php else: ?>
                            <hr>
                            <h5 class="card-title mb-4">Personal Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" name="date_of_birth" value="<?php echo $profile['date_of_birth'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo ($profile['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ($profile['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo ($profile['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Profile
                                </button>
                                <a href="profile.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

