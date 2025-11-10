<?php
/**
 * MediArchive - Configuration File
 * Digital Medical Certificate & Verification System
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '115320');
define('DB_NAME', 'mediarchive');

// Site Configuration
define('SITE_NAME', 'MediArchive');
define('SITE_URL', 'http://localhost/SYSTEMINTEG/');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('QR_DIR', __DIR__ . '/qrcodes/');
define('TEMP_DIR', __DIR__ . '/temp/');

// Ensure upload directories exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
if (!file_exists(QR_DIR)) {
    mkdir(QR_DIR, 0777, true);
}
if (!file_exists(TEMP_DIR)) {
    mkdir(TEMP_DIR, 0777, true);
}

// Load OOP utilities first (includes security classes)
if (file_exists(__DIR__ . '/includes/bootstrap.php')) {
    require_once __DIR__ . '/includes/bootstrap.php';
}

// Session Configuration - Secure session handling (must be before any output)
SessionManager::startSecureSession();

// Security Headers - Set before any output (but after session start)
if (!headers_sent()) {
    SecurityManager::setSecurityHeaders();
}

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Manila');

// Database Connection Function
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

// Helper function to check if user is logged in (with session validation)
function isLoggedIn() {
    return SessionManager::isAuthenticated();
}

// Helper function to check user role
function isClinicAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'clinic_admin';
}

// Helper function to check if user is patient
function isPatient() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'patient';
}

// Helper function to check if user is web admin
function isWebAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'web_admin';
}

// Redirect function
function redirect($url) {
    // If URL is relative and doesn't start with /, prepend views/ directory
    if (!preg_match('#^(https?://|/)#', $url)) {
        // Check if we're in views directory by checking if the current script is in views/
        $script_dir = dirname($_SERVER['SCRIPT_NAME']);
        if (strpos($script_dir, '/views') !== false || strpos($script_dir, '\\views') !== false) {
            // Already in views directory, use as-is
        } else {
            // Not in views directory, prepend views/
            $url = 'views/' . $url;
        }
    }
    header("Location: " . $url);
    exit();
}

// Sanitize input (enhanced with security validation)
function sanitizeInput($data, $type = 'string', $options = []) {
    if (is_array($data)) {
        return array_map(function($item) use ($type, $options) {
            return sanitizeInput($item, $type, $options);
        }, $data);
    }
    
    // Use InputValidator for comprehensive validation
    $result = InputValidator::validate($data, $type, $options);
    if ($result['valid']) {
        return $result['value'];
    }
    
    // If validation fails, log and return sanitized version
    SecurityManager::logSecurityEvent('INPUT_VALIDATION_FAILED', [
        'input' => substr($data, 0, 100),
        'type' => $type,
        'error' => $result['error'] ?? 'Validation failed'
    ]);
    
    // Fallback to basic sanitization
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Generate unique certificate ID
function generateCertID() {
    return 'MED-' . date('Ymd') . '-' . strtoupper(uniqid());
}

// Notification helper
function notifyUser(mysqli $conn, int $userId, string $title, string $message, string $link = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $title, $message, $link);
    $stmt->execute();
    $stmt->close();
}

// Note: Security classes are loaded at the top of this file
