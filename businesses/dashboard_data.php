<?php
require_once '../db.php';
require_once '../encryption.php';
session_start();

header('Content-Type: application/json');

$business_id= $_SESSION['business_id'] ?? null;
if (!$business_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Get business info
$business = $mysqli->query("SELECT id, name FROM businesses WHERE id = $business_id LIMIT 1")->fetch_assoc();

// Today's date
$today = date('Y-m-d');

// Today's bookings
$bookings = [];
$res = $mysqli->query("
    SELECT 
        TIME_FORMAT(b.booking_time, '%H:%i') as time, 
        u.name as customer_name_encrypted
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    WHERE b.business_id = $business_id 
      AND b.booking_date = '$today'
    ORDER BY b.booking_time ASC 
    LIMIT 5
");
while ($row = $res->fetch_assoc()) {
    $row['customer_name'] = decryptData($row['customer_name_encrypted']);
    unset($row['customer_name_encrypted']);
    $bookings[] = $row;
}

// Revenue today
$revenue = $mysqli->query("
    SELECT SUM(amount) as total 
    FROM bookings 
    WHERE business_id = $business_id 
      AND booking_date = '$today' 
      AND status = 'completed'
")->fetch_assoc()['total'] ?? 0;

// Next appointment (after now)
$now = date('H:i:s');
$next_result = $mysqli->query("
    SELECT 
        TIME_FORMAT(b.booking_time, '%H:%i') as time, 
        u.name as customer_name_encrypted, 
        b.service, 
        b.status
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    WHERE b.business_id = $business_id 
      AND b.booking_date = '$today'
      AND b.booking_time > '$now'
    ORDER BY b.booking_time ASC 
    LIMIT 1
");
$next = null;
if ($next_result && $next_row = $next_result->fetch_assoc()) {
    $next_row['customer_name'] = decryptData($next_row['customer_name_encrypted']);
    unset($next_row['customer_name_encrypted']);
    $next = $next_row;
}

// Initialize filters
$dateFilter = '';
$statusFilter = '';

// Date filter
if (isset($_GET['date']) && $_GET['date'] != '') {
  $date = $mysqli->real_escape_string($_GET['date']);
  $dateFilter = " AND b.booking_date = '$date'";
}

// Status filter
if (isset($_GET['status']) && $_GET['status'] != '') {
  $status = $mysqli->real_escape_string($_GET['status']);
  $statusFilter = " AND b.status = '$status'";
} else {
  // If no filter is set, exclude completed
  $statusFilter = " AND b.status != 'completed'";
}


// Combined query with both filters
$res = $mysqli->query("
    SELECT 
        b.id,
        b.booking_date,
        TIME_FORMAT(b.booking_time, '%H:%i') as time, 
        u.name as customer_name_encrypted,
        b.service,
        b.status,
        e.name AS employee_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    LEFT JOIN employees e ON b.employee_id = e.id
    WHERE b.business_id = $business_id $dateFilter $statusFilter
    ORDER BY b.booking_date ASC, b.booking_time ASC
    LIMIT 50
");

while ($row = $res->fetch_assoc()) {
    $row['customer_name'] = decryptData($row['customer_name_encrypted']);
    unset($row['customer_name_encrypted']);
    // Decrypt employee name if it's not null
if (!empty($row['employee_name'])) {
    $row['employee_name'] = decryptData($row['employee_name']);
}
    $all_bookings[] = $row;
}

// Reviews (empty for now)
$reviews = [];

echo json_encode([
    'business_name' => $business['name'] ?? '',
    'bookings' => $bookings,
    'revenue' => $revenue,
    'next' => $next,
    'reviews' => $reviews,
    'all_bookings' => $all_bookings
]);
?>