<?php
header('Content-Type: application/json');
require '../db.php';
session_start();

$owner_id = $_SESSION['owner_id'] ?? null;
if (!$owner_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$mod_id = $_POST['id'] ?? 0;
if (!$mod_id) {
    echo json_encode(['error' => 'Invalid moderator ID']);
    exit;
}

// Verify moderator belongs to same business
$stmt = $mysqli->prepare("SELECT business_id FROM owners WHERE id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$stmt->bind_result($business_id);
$stmt->fetch();
$stmt->close();

$stmt = $mysqli->prepare("SELECT id FROM owners WHERE id = ? AND business_id = ?");
$stmt->bind_param("ii", $mod_id, $business_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo json_encode(['error' => 'Moderator not found or unauthorized']);
    exit;
}
$stmt->close();

// Delete moderator
$stmt = $mysqli->prepare("DELETE FROM owners WHERE id = ?");
$stmt->bind_param("i", $mod_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to remove moderator']);
}
$stmt->close();