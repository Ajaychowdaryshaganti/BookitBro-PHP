<?php
header('Content-Type: application/json');
require '../db.php';
require '../encryption.php';
session_start();

$owner_id = $_SESSION['owner_id'] ?? null;
$business_id=$_SESSION['business_id'] ?? null;
if (!$owner_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Fetch business info
$stmt = $mysqli->prepare("SELECT * FROM businesses WHERE id = ?");
$stmt->bind_param("i", $business_id);
$stmt->execute();
$business = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Decrypt sensitive business fields if encrypted
if ($business) {
    $business['name'] = ($business['name']);
    $business['address'] = ($business['address']);
    $business['city'] = ($business['city']);
    $business['state'] = ($business['state']);
    $business['mobile'] = ($business['mobile']);
    $business['working_hours'] = ($business['working_hours']);
    $business['category'] = ($business['category']);
}

// Fetch owner info
$stmt = $mysqli->prepare("SELECT id, name, email, mobile FROM owners WHERE id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$owner = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Decrypt owner fields
if ($owner) {
    $owner['name'] = ($owner['name']);
    $owner['email'] = ($owner['email']);
    $owner['mobile'] = ($owner['mobile']);
}

// Fetch moderators (owners with same business but different owner_id)
$moderators = [];
if ($business) {
    $business_id = $business['id'];
    $stmt = $mysqli->prepare("SELECT id, name, email, mobile FROM owners WHERE business_id = ? AND id != ?");
    $stmt->bind_param("ii", $business_id, $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['name'] = ($row['name']);
        $row['email'] = ($row['email']);
        $row['mobile'] = ($row['mobile']);
        $moderators[] = $row;
    }
    $stmt->close();
}

echo json_encode([
    'business' => $business ?: null,
    'owner' => $owner ?: null,
    'moderators' => $moderators
]);