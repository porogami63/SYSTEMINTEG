<?php
require_once '../config.php';
require_once '../includes/JsonHelper.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isClinicAdmin()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$conn = getDBConnection();
$newStatus = isset($_POST['is_available']) ? intval($_POST['is_available']) : null;
if ($newStatus === null) {
    try {
        $json = JsonHelper::getJsonInput(true);
        if (is_array($json) && array_key_exists('is_available', $json)) {
            $newStatus = intval($json['is_available']);
        }
    } catch (RuntimeException $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
if ($newStatus === null || ($newStatus !== 0 && $newStatus !== 1)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid status']);
    exit;
}

// Find clinic for current user
$stmt = $conn->prepare("UPDATE clinics SET is_available = ? WHERE user_id = ?");
$stmt->bind_param("ii", $newStatus, $_SESSION['user_id']);
$ok = $stmt->execute();
$stmt->close();

// Audit log
try {
    $db = Database::getInstance();
    $clinic = $db->fetch("SELECT id FROM clinics WHERE user_id = ?", [$_SESSION['user_id']]);
    $clinicId = $clinic['id'] ?? null;
    AuditLogger::log($newStatus ? 'SET_AVAILABLE' : 'SET_UNAVAILABLE', 'clinic', $clinicId, ['is_available' => $newStatus]);
} catch (Exception $e) { /* ignore */ }
$conn->close();

echo json_encode(['ok' => $ok, 'is_available' => $newStatus]);


