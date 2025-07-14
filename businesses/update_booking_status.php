<?php
// update_booking_status.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db.php';
session_start();

header('Content-Type: application/json');

$owner_id = $_SESSION['owner_id'] ?? null;
if (!$owner_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$booking_id = $_POST['booking_id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$booking_id || !$status) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing booking_id or status']);
    exit;
}

// Validate status
$allowed_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
if (!in_array($status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

// Update booking status
$update_stmt = $mysqli->prepare("UPDATE bookings SET status = ? WHERE id = ?");
if (!$update_stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $mysqli->error]);
    exit;
}
$update_stmt->bind_param("si", $status, $booking_id);
if ($update_stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $update_stmt->error]);
}
$update_stmt->close();
$mysqli->close();
exit;