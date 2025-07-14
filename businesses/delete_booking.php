<?php
require_once '../db.php';
session_start();

header('Content-Type: application/json');

$business_id = $_SESSION['business_id'] ?? null;
if (!$business_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$booking_id = $_POST['booking_id'] ?? null;

if (!$booking_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing booking_id']);
    exit;
}

// Check if booking belongs to this business
$business_id_1 = $mysqli->query("SELECT business_id FROM bookings WHERE id = $booking_id")->fetch_assoc()['business_id'] ?? null;


if ($business_id != $business_id_1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$stmt = $mysqli->prepare("UPDATE bookings SET status = 'deleted' WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$mysqli->close();
?>