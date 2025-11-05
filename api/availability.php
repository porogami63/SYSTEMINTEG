<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isClinicAdmin()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$conn = getDBConnection();
$newStatus = isset($_POST['is_available']) ? intval($_POST['is_available']) : null;
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
$conn->close();

echo json_encode(['ok' => $ok, 'is_available' => $newStatus]);


