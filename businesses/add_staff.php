<?php
header('Content-Type: application/json');
require '../db.php';
require '../encryption.php';

session_start(); // Make sure session is started

$business_id = $_SESSION['business_id'] ?? null; // Fixed typo 'bussiness_id' to 'business_id'

$name_plain = $_POST['name'] ?? '';
$email_plain = $_POST['email'] ?? '';
$mobile_plain = $_POST['mobile'] ?? '';
$position = $_POST['position'] ?? '';

error_log("Received staff data:");
error_log("business_id: " . var_export($business_id, true));
error_log("name_plain: " . var_export($name_plain, true));
error_log("email_plain: " . var_export($email_plain, true));
error_log("mobile_plain: " . var_export($mobile_plain, true));
error_log("position: " . var_export($position, true));

if (!$name_plain || !$email_plain) {
    error_log("Validation failed: Name and email are required");
    echo json_encode(['success' => false, 'error' => 'Name and email are required']);
    exit;
}

$name = encryptData($name_plain);
$email = encryptData($email_plain);
$mobile = encryptData($mobile_plain);

error_log("Encrypted data:");
error_log("name: " . var_export($name, true));
error_log("email: " . var_export($email, true));
error_log("mobile: " . var_export($mobile, true));

$stmt = $mysqli->prepare("INSERT INTO employees (business_id, name, email, mobile, position, available, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
if (!$stmt) {
    error_log("Prepare failed: " . $mysqli->error);
    echo json_encode(['success' => false, 'error' => 'Database prepare failed']);
    exit;
}
$stmt->bind_param("issss", $business_id, $name, $email, $mobile, $position);
$success = $stmt->execute();

if (!$success) {
    error_log("Execute failed: " . $stmt->error);
}

echo json_encode(['success' => $success]);