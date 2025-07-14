<?php
header('Content-Type: application/json');
require '../db.php';
require '../encryption.php';
session_start();

$owner_id = $_SESSION['owner_id'] ?? null;
if (!$owner_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = $_POST;

// Encrypt fields
$name = ($data['owner_name']);
$email = ($data['owner_email']);
$mobile = ($data['owner_mobile']);

if (!empty($data['owner_password'])) {
    $password_hash = password_hash($data['owner_password'], PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("UPDATE owners SET name=?, email=?, mobile=?, password=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $email, $mobile, $password_hash, $owner_id);
} else {
    $stmt = $mysqli->prepare("UPDATE owners SET name=?, email=?, mobile=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $email, $mobile, $owner_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to update owner']);
}
$stmt->close();