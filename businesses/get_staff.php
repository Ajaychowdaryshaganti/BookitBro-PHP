<?php
header('Content-Type: application/json');
require '../db.php';
require '../encryption.php';
session_start();

$business_id = $_SESSION['business_id'] ?? 1;

$stmt = $mysqli->prepare("SELECT id, name, email, mobile, position, available FROM employees WHERE business_id = ?");
$stmt->bind_param("i", $business_id);
$stmt->execute();
$result = $stmt->get_result();

$staff = [];
while ($row = $result->fetch_assoc()) {
    $row['name'] = decryptData($row['name']);
    $row['email'] = decryptData($row['email']);
    $row['mobile'] = decryptData($row['mobile']);
    $staff[] = $row;
}

echo json_encode($staff);