
<?php
header('Content-Type: application/json');
require '../db.php';
require '../encryption.php';
session_start();

$business_id = $_SESSION['business_id'] ?? null;
if (!$business_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = $_POST;

// Encrypt fields before saving
$name = ($data['business_name']);
$address = ($data['address']);
$city = ($data['city']);
$state = ($data['state']);
$mobile = ($data['mobile']);
$working_hours = ($data['working_hours']);
$slot_duration = intval($data['slot_duration']);
$category = ($data['category']);

$stmt = $mysqli->prepare("UPDATE businesses SET name=?, address=?, city=?, state=?, mobile=?, working_hours=?, slot_duration=?, category=? WHERE id=?");
$stmt->bind_param("ssssssisi", $name, $address, $city, $state, $mobile, $working_hours, $slot_duration, $category, $business_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to update business']);
}
$stmt->close();