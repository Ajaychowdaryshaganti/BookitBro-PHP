<?php
header('Content-Type: application/json');
require '../db.php';

$business_id = $_SESSION['business_id'] ?? 1;
$id = intval($_POST['id']);

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid staff ID']);
    exit;
}

$stmt = $mysqli->prepare("DELETE from employees WHERE id = ? AND business_id = ?");
$stmt->bind_param("ii", $id, $business_id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);