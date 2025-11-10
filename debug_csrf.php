<?php
/**
 * CSRF Token Debug Script
 * Use this to debug CSRF token issues
 */
require_once 'config.php';

echo "<h2>CSRF Token Debug Information</h2>";
echo "<pre>";

echo "Session Status: " . session_status() . " (2 = PHP_SESSION_ACTIVE)\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";

echo "\n--- Session Data ---\n";
if (isset($_SESSION)) {
    foreach ($_SESSION as $key => $value) {
        if ($key === 'csrf_token') {
            echo "$key: " . substr($value, 0, 20) . "... (length: " . strlen($value) . ")\n";
        } else {
            echo "$key: " . (is_string($value) ? substr($value, 0, 50) : var_export($value, true)) . "\n";
        }
    }
} else {
    echo "No session data\n";
}

echo "\n--- CSRF Token Generation Test ---\n";
$token1 = SecurityManager::generateCSRFToken();
echo "Generated Token 1: " . substr($token1, 0, 20) . "...\n";

$token2 = SecurityManager::generateCSRFToken();
echo "Generated Token 2: " . substr($token2, 0, 20) . "...\n";
echo "Tokens Match: " . ($token1 === $token2 ? "YES" : "NO") . "\n";

echo "\n--- CSRF Token Validation Test ---\n";
$testToken = $token1;
$isValid = SecurityManager::validateCSRFToken($testToken);
echo "Validation Result: " . ($isValid ? "VALID" : "INVALID") . "\n";

$invalidToken = "invalid_token";
$isValidInvalid = SecurityManager::validateCSRFToken($invalidToken);
echo "Invalid Token Test: " . ($isValidInvalid ? "VALID (ERROR!)" : "INVALID (CORRECT)") . "\n";

echo "\n--- Session Cookie Info ---\n";
$cookieParams = session_get_cookie_params();
echo "Cookie Lifetime: " . $cookieParams['lifetime'] . "\n";
echo "Cookie Path: " . $cookieParams['path'] . "\n";
echo "Cookie Domain: " . $cookieParams['domain'] . "\n";
echo "Cookie Secure: " . ($cookieParams['secure'] ? "YES" : "NO") . "\n";
echo "Cookie HttpOnly: " . ($cookieParams['httponly'] ? "YES" : "NO") . "\n";

echo "\n--- Request Info ---\n";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'NOT SET') . "\n";
echo "POST csrf_token: " . (isset($_POST['csrf_token']) ? substr($_POST['csrf_token'], 0, 20) . "..." : "NOT SET") . "\n";
echo "GET csrf_token: " . (isset($_GET['csrf_token']) ? substr($_GET['csrf_token'], 0, 20) . "..." : "NOT SET") . "\n";

echo "\n--- CSRF Field Output ---\n";
echo SecurityManager::getCSRFField();

echo "</pre>";

echo "<h3>Test Form</h3>";
echo "<form method='POST'>";
echo SecurityManager::getCSRFField();
echo "<input type='text' name='test_input' placeholder='Test input'>";
echo "<button type='submit'>Test Submit</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Form Submission Result</h3>";
    echo "<pre>";
    echo "POST Data: " . print_r($_POST, true) . "\n";
    try {
        SecurityManager::verifyCSRFToken();
        echo "CSRF Token: VALID\n";
    } catch (Exception $e) {
        echo "CSRF Token: INVALID - " . $e->getMessage() . "\n";
    }
    echo "</pre>";
}
?>

