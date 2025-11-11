<?php
require_once '../config.php';

if (!isLoggedIn() || !isWebAdmin()) {
    redirect('dashboard.php');
}

$db = Database::getInstance();
$error = '';
$success = '';

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $user_id = intval($_POST['user_id'] ?? 0);
    if ($user_id) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF'])) . '/api/delete_user.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['user_id' => $user_id]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        if ($result && $result['success']) {
            $success = 'User deleted successfully';
        } else {
            $error = $result['error'] ?? 'Failed to delete user';
        }
    }
}

// Handle search and filters
$search_term = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';

// Build query with filters
$where = ["1=1"];
$params = [];

if (!empty($search_term)) {
    $where[] = "(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $search_param = '%' . $search_term . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($role_filter)) {
    $where[] = "u.role = ?";
    $params[] = $role_filter;
}

// Get all users
$users = $db->fetchAll(
    "SELECT u.*, 
     p.patient_code,
     c.clinic_name,
     (SELECT COUNT(*) FROM certificates WHERE clinic_id = c.id) as cert_count,
     (SELECT COUNT(*) FROM appointments WHERE patient_id = p.id) as appt_count
     FROM users u
     LEFT JOIN patients p ON u.id = p.user_id
     LEFT JOIN clinics c ON u.id = c.user_id
     WHERE " . implode(' AND ', $where) . "
     ORDER BY u.created_at DESC",
    $params
);

// Calculate stats
$total_users = count($users);
$doctors_count = count(array_filter($users, function($u) { return $u['role'] === 'clinic_admin'; }));
$patients_count = count(array_filter($users, function($u) { return $u['role'] === 'patient'; }));
$admins_count = count(array_filter($users, function($u) { return $u['role'] === 'web_admin'; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management - MediArchive</title>
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
                <h2 class="mb-4"><i class="bi bi-people-fill"></i> User Management</h2>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-people"></i> Total Users</h5>
                                <h3 class="mb-0"><?php echo $total_users; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-heart-pulse"></i> Doctors</h5>
                                <h3 class="mb-0"><?php echo $doctors_count; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-person"></i> Patients</h5>
                                <h3 class="mb-0"><?php echo $patients_count; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-shield-check"></i> Admins</h5>
                                <h3 class="mb-0"><?php echo $admins_count; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Username, Name, Email">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role">
                                    <option value="">All Roles</option>
                                    <option value="clinic_admin" <?php echo $role_filter === 'clinic_admin' ? 'selected' : ''; ?>>Doctor</option>
                                    <option value="patient" <?php echo $role_filter === 'patient' ? 'selected' : ''; ?>>Patient</option>
                                    <option value="web_admin" <?php echo $role_filter === 'web_admin' ? 'selected' : ''; ?>>Web Admin</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <a href="user_management.php" class="btn btn-secondary w-100">Clear Filters</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if (!empty($users)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Details</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?php echo $u['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td>
                                            <?php
                                            $role_badges = [
                                                'clinic_admin' => ['bg' => 'success', 'text' => 'Doctor'],
                                                'patient' => ['bg' => 'info', 'text' => 'Patient'],
                                                'web_admin' => ['bg' => 'warning', 'text' => 'Admin']
                                            ];
                                            $badge = $role_badges[$u['role']] ?? ['bg' => 'secondary', 'text' => ucfirst($u['role'])];
                                            ?>
                                            <span class="badge bg-<?php echo $badge['bg']; ?>"><?php echo $badge['text']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($u['role'] === 'patient' && $u['patient_code']): ?>
                                                <small>Code: <strong><?php echo htmlspecialchars($u['patient_code']); ?></strong></small><br>
                                                <small>Appointments: <?php echo intval($u['appt_count'] ?? 0); ?></small>
                                            <?php elseif ($u['role'] === 'clinic_admin' && $u['clinic_name']): ?>
                                                <small>Clinic: <strong><?php echo htmlspecialchars($u['clinic_name']); ?></strong></small><br>
                                                <small>Certificates: <?php echo intval($u['cert_count'] ?? 0); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo date('M d, Y', strtotime($u['created_at'])); ?></small></td>
                                        <td>
                                            <?php if ($u['id'] != $_SESSION['user_id'] && $u['role'] !== 'web_admin'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>', '<?php echo htmlspecialchars($u['full_name']); ?>')" title="Delete User">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                            <?php else: ?>
                                            <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-people fs-1 text-muted"></i>
                            <p class="text-muted mt-3">No users found.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger"></i> Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user?</p>
                <p><strong>Username:</strong> <code id="deleteUsername"></code></p>
                <p><strong>Name:</strong> <span id="deleteFullName"></span></p>
                <p class="text-danger"><small><strong>Warning:</strong> This will permanently delete the user account and all associated data including certificates, appointments, payments, and messages. This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteForm" style="display: inline;">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(userId, username, fullName) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteUsername').textContent = username;
    document.getElementById('deleteFullName').textContent = fullName;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>
</body>
</html>

