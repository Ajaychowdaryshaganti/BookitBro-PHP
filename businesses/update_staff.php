<?php
header('Content-Type: application/json');
require 'db.php';
require 'encryption.php';

$business_id = $_SESSION['business_id'] ?? 1;

$id = intval($_POST['id'] ?? 0);
$name_plain = $_POST['name'] ?? '';
$email_plain = $_POST['email'] ?? '';
$mobile_plain = $_POST['mobile'] ?? '';
$position = $_POST['position'] ?? '';

if (!$id || !$name_plain || !$email_plain) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$name = encryptData($name_plain);
$email = encryptData($email_plain);
$mobile = encryptData($mobile_plain);

$stmt = $mysqli->prepare("UPDATE employees SET name = ?, email = ?, mobile = ?, position = ?, updated_at = NOW() WHERE id = ? AND business_id = ?");
$stmt->bind_param("ssssii", $name, $email, $mobile, $position, $id, $business_id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);