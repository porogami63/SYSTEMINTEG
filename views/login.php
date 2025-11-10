<?php
require_once '../config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    SecurityManager::verifyCSRFToken();
    
    // Rate limiting for login attempts
    $clientIP = SecurityManager::getClientIP();
    if (!SecurityManager::checkRateLimit('login', 5, 300, $clientIP)) {
        $error = "Too many login attempts. Please try again in 5 minutes.";
        SecurityManager::logSecurityEvent('LOGIN_RATE_LIMIT_EXCEEDED', ['ip' => $clientIP]);
    } else {
        // Validate and sanitize input
        $usernameResult = InputValidator::validate($_POST['username'] ?? '', 'string', ['min_length' => 1, 'max_length' => 50]);
        if (!$usernameResult['valid']) {
            $error = "Invalid username format";
        } else {
            $username = $usernameResult['value'];
            $password = $_POST['password'] ?? '';

            try {
                $db = Database::getInstance();
                $sql = "SELECT id, username, password, full_name, role, failed_login_attempts, account_locked_until 
                        FROM users WHERE username = ?";
                $user = $db->fetch($sql, [$username]);

                if ($user) {
                    // Check if account is locked
                    if ($user['account_locked_until'] && strtotime($user['account_locked_until']) > time()) {
                        $error = "Account is temporarily locked. Please try again later.";
                        SecurityManager::logSecurityEvent('LOGIN_ATTEMPT_LOCKED_ACCOUNT', [
                            'username' => $username,
                            'ip' => $clientIP
                        ]);
                    } elseif (password_verify($password, $user['password'])) {
                        // Successful login
                        SessionManager::createSession(
                            $user['id'],
                            $user['username'],
                            $user['full_name'],
                            $user['role']
                        );
                        
                        // Reset failed login attempts and update last login
                        $db->execute(
                            "UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL, last_login = NOW() WHERE id = ?",
                            [$user['id']]
                        );
                        
                        // Reset rate limit on successful login
                        SecurityManager::resetRateLimit('login', $clientIP);
                        
                        redirect('dashboard.php');
                    } else {
                        // Failed login - increment attempts
                        $failedAttempts = ($user['failed_login_attempts'] ?? 0) + 1;
                        $lockUntil = null;
                        
                        // Lock account after 5 failed attempts for 15 minutes
                        if ($failedAttempts >= 5) {
                            $lockUntil = date('Y-m-d H:i:s', time() + 900); // 15 minutes
                            $error = "Too many failed attempts. Account locked for 15 minutes.";
                        } else {
                            $error = "Invalid password";
                        }
                        
                        $db->execute(
                            "UPDATE users SET failed_login_attempts = ?, account_locked_until = ? WHERE id = ?",
                            [$failedAttempts, $lockUntil, $user['id']]
                        );
                        
                        SecurityManager::logSecurityEvent('LOGIN_FAILED', [
                            'username' => $username,
                            'ip' => $clientIP,
                            'attempts' => $failedAttempts
                        ]);
                    }
                } else {
                    // Don't reveal if username exists (security best practice)
                    $error = "Invalid username or password";
                    SecurityManager::logSecurityEvent('LOGIN_FAILED_INVALID_USER', [
                        'username' => $username,
                        'ip' => $clientIP
                    ]);
                }
            } catch (Exception $e) {
                $error = 'Server error. Please try again later.';
                error_log('Login error: ' . $e->getMessage());
                SecurityManager::logSecurityEvent('LOGIN_ERROR', [
                    'error' => $e->getMessage(),
                    'ip' => $clientIP
                ]);
            }
        }
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
            <?php echo SecurityManager::getCSRFField(); ?>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" required autocomplete="username">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required autocomplete="current-password">
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

