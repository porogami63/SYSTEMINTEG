<?php
require_once '../config.php';

// Use SessionManager for secure logout
SessionManager::destroySession();

// Check for redirect parameter, default to home page
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '../index.php';

// Sanitize redirect to prevent open redirect vulnerability
if (strpos($redirect, 'http') === 0) {
    // External URL - redirect to login instead
    redirect('login.php');
} else {
    // Internal redirect
    redirect($redirect);
}
?>

