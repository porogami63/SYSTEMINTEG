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

// Ensure upload directories exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
if (!file_exists(QR_DIR)) {
    mkdir(QR_DIR, 0777, true);
}

// Session Configuration
session_start();

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

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check user role
function isClinicAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'clinic_admin';
}

// Helper function to check if user is patient
function isPatient() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'patient';
}

// Redirect function
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
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
?>

