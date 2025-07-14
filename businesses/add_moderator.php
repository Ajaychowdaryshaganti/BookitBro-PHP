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

// Get business_id of current owner
$stmt = $mysqli->prepare("SELECT business_id FROM owners WHERE id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$stmt->bind_result($business_id);
$stmt->fetch();
$stmt->close();

if (!$business_id) {
    echo json_encode(['error' => 'Business not found']);
    exit;
}

// Check if email already exists
$mod_email_enc = $data['mod_email'];
$stmt = $mysqli->prepare("SELECT id FROM owners WHERE email = ?");
$stmt->bind_param("s", $mod_email_enc);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['error' => 'Email already exists']);
    exit;
}
$stmt->close();

// Encrypt fields
$mod_name_enc = $data['mod_name'];
$mod_mobile_enc = $data['mod_mobile'];
$password_hash = password_hash($data['mod_password'], PASSWORD_DEFAULT);

$stmt = $mysqli->prepare("INSERT INTO owners (business_id, name, email, mobile, password) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $business_id, $mod_name_enc, $mod_email_enc, $mod_mobile_enc, $password_hash);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to add moderator']);
}
$stmt->close();