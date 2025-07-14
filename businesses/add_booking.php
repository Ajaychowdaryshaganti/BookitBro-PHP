<?php
header('Content-Type: application/json');
require '../db.php';
session_start();

$business_id = $_SESSION['business_id'] ?? null;
if (!$business_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$employee_id = $_POST['employee_id'] ?? null;
$service = $_POST['service'] ?? '';
$booking_date = $_POST['booking_date'] ?? '';
$booking_time = $_POST['booking_time'] ?? '';
$user_id=$_POST['user_id'] ?? '';
$amount = intval($_POST['amount'] ?? 0);

if (!$employee_id || !$service || !$booking_date || !$booking_time || $amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Missing or invalid fields']);
    exit;
}

// user_id is NULL as per your instruction
$status = 'confirmed';

$stmt = $mysqli->prepare("INSERT INTO bookings (user_id, business_id, employee_id, service, booking_date, booking_time, status, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiissssi", $user_id,$business_id, $employee_id, $service, $booking_date, $booking_time, $status, $amount);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
$stmt->close();