<?php
require_once '../config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];

    try {
        $db = Database::getInstance();
        $sql = "SELECT id, username, password, full_name, role FROM users WHERE username = ?";
        $user = $db->fetch($sql, [$username]);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                redirect('dashboard.php');
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "Invalid username";
        }
    } catch (Exception $e) {
        $error = 'Server error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - MediArchive</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.login-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    max-width: 400px;
    width: 100%;
}
</style>
</head>
<body>
<div class="container">
    <div class="login-card p-5 mx-auto">
        <h2 class="text-center mb-4 text-primary">MediArchive</h2>
        <h4 class="text-center mb-4">Login</h4>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        
        <div class="text-center mt-3">
            <a href="register.php">Don't have an account? Register here</a>
        </div>
        <div class="text-center mt-2">
            <a href="../index.php">Back to Home</a>
        </div>
        
        <hr class="my-4">
        <p class="text-muted small">Demo Accounts:</p>
        <ul class="small text-muted">
            <li>Clinic Admin: <strong>admin</strong> / password</li>
            <li>Patient: <strong>patient1</strong> / password</li>
        </ul>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

