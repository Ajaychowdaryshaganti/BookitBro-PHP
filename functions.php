<?php
// functions.php
require_once 'db.php';
require_once 'encryption.php';

// Sanitize input
function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Check if email or mobile exists (using hashed values for search)
function userExists($email, $mobile) {
    global $mysqli;
    $emailHash = hashData($email);
    $mobileHash = hashData($mobile);
    
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email_hash = ? OR mobile_hash = ?");
    $stmt->bind_param("ss", $emailHash, $mobileHash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

// Register user with encryption
function registerUser($name, $email, $mobile, $password) {
    global $mysqli;
    
    // Hash password (for authentication)
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Encrypt sensitive data
    $nameEncrypted = encryptData($name);
    $emailEncrypted = encryptData($email);
    $mobileEncrypted = encryptData($mobile);
    
    // Create hashes for searching
    $emailHash = hashData($email);
    $mobileHash = hashData($mobile);
    
    $stmt = $mysqli->prepare("INSERT INTO users (name, email, mobile, email_hash, mobile_hash, password, role) VALUES (?, ?, ?, ?, ?, ?, 'customer')");
    $stmt->bind_param("ssssss", $nameEncrypted, $emailEncrypted, $mobileEncrypted, $emailHash, $mobileHash, $passwordHash);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Login user with encryption
function loginUser($emailOrMobile, $password) {
    global $mysqli;
    
    // Create hash for searching
    $searchHash = hashData($emailOrMobile);
    
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE email_hash = ? OR mobile_hash = ?");
    $stmt->bind_param("ss", $searchHash, $searchHash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user && password_verify($password, $user['password'])) {
        // Decrypt sensitive data for session
        $user['name'] = decryptData($user['name']);
        $user['email'] = decryptData($user['email']);
        $user['mobile'] = decryptData($user['mobile']);
        return $user;
    }
    return false;
}

// Save contact message with encryption
function saveContactMessage($name, $contact, $message) {
    global $mysqli;
    
    // Encrypt sensitive data
    $nameEncrypted = encryptData($name);
    $contactEncrypted = encryptData($contact);
    $messageEncrypted = encryptData($message);
    
    $stmt = $mysqli->prepare("INSERT INTO contact_messages (name, contact, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nameEncrypted, $contactEncrypted, $messageEncrypted);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Get user by ID with decryption
function getUserById($userId) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user) {
        // Decrypt sensitive data
        $user['name'] = decryptData($user['name']);
        $user['email'] = decryptData($user['email']);
        $user['mobile'] = decryptData($user['mobile']);
    }
    
    return $user;
}

// Validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate mobile format (basic Indian mobile validation)
function isValidMobile($mobile) {
    return preg_match('/^[6-9]\d{9}$/', $mobile);
}

// Generate and store 2FA code with encryption
function generate2FACode($userId) {
    global $mysqli;
    
    $code = sprintf("%06d", mt_rand(100000, 999999));
    $codeEncrypted = encryptData($code);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    $stmt = $mysqli->prepare("INSERT INTO user_2fa (user_id, code, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $codeEncrypted, $expiresAt);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result ? $code : false;
}

// Verify 2FA code
function verify2FACode($userId, $inputCode) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT * FROM user_2fa WHERE user_id = ? AND expires_at > NOW() AND used = 0 ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row) {
        $storedCode = decryptData($row['code']);
        if ($storedCode === $inputCode) {
            // Mark code as used
            $updateStmt = $mysqli->prepare("UPDATE user_2fa SET used = 1 WHERE id = ?");
            $updateStmt->bind_param("i", $row['id']);
            $updateStmt->execute();
            $updateStmt->close();
            return true;
        }
    }
    
    return false;
}
// Add to your functions.php
function logSecurityEvent($event, $userId = null, $details = '') {
    global $mysqli;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt = $mysqli->prepare("INSERT INTO security_logs (event, user_id, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisss", $event, $userId, $ip, $userAgent, $details);
    $stmt->execute();
    $stmt->close();
}
// Add these functions to your existing functions.php

/**
 * Mask email for privacy
 */
function maskEmail($email) {
    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1];
    
    $maskedName = substr($name, 0, 2) . str_repeat('*', strlen($name) - 2);
    return $maskedName . '@' . $domain;
}

/**
 * Mask mobile for privacy
 */
function maskMobile($mobile) {
    return substr($mobile, 0, 2) . str_repeat('*', 6) . substr($mobile, -2);
}

/**
 * Check if user is logged in
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

/* to pull categories from db */
function getCategories() {
    global $conn;
    $categories = [];
    
    $query = "SELECT id, name FROM categories ORDER BY name ASC";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

function checkOwnerExists($email, $mobile) {
    global $conn;
    $email = clean($email);
    $mobile = clean($mobile);

    $query = "SELECT id FROM owners WHERE email = '$email' OR mobile = '$mobile'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return true; // Owner exists
    } else {
        return false; // Owner does not exist
    }
}

function createOwnerAndBusiness($data) {
    global $conn;

    $ownerName = clean($data['owner_name']);
    $ownerEmail = clean($data['owner_email']);
    $ownerMobile = clean($data['owner_mobile']);
    $passwordHash = $data['password'];
    $businessName = clean($data['business_name']);
    $businessAddress = clean($data['business_address']);
    $businessCity = clean($data['business_city']);
    $businessState = clean($data['business_state']);
    $businessMobile = clean($data['business_mobile']);
    $workingHours = $data['working_hours'];
    $slotDuration = (int)$data['slot_duration'];
    $categoryId = (int)$data['category_id'];

    mysqli_begin_transaction($conn);

    try {
        // Insert owner
$stmt = $conn->prepare("INSERT INTO owners (name, email, mobile, password) VALUES (?, ?, ?, ?)");
if (!$stmt) throw new Exception("Prepare failed (owners): " . $conn->error);
$stmt->bind_param("ssss", $ownerName, $ownerEmail, $ownerMobile, $passwordHash);
if (!$stmt->execute()) throw new Exception("Execute failed (owners): " . $stmt->error);
$stmt->close();

// Insert business
$stmt = $conn->prepare("INSERT INTO businesses (name, address, city, state, mobile, working_hours, slot_duration, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) throw new Exception("Prepare failed (businesses): " . $conn->error);
$stmt->bind_param("ssssssii", $businessName, $businessAddress, $businessCity, $businessState, $businessMobile, $workingHours, $slotDuration, $categoryId);
if (!$stmt->execute()) throw new Exception("Execute failed (businesses): " . $stmt->error);
$businessId = $stmt->insert_id;
$stmt->close();

// Update owner with business_id
$updateStmt = $conn->prepare("UPDATE owners SET business_id = ? WHERE id = ?");
if (!$updateStmt) throw new Exception("Prepare failed (update owners): " . $conn->error);
$updateStmt->bind_param("ii", $businessId, $ownerId);
if (!$updateStmt->execute()) throw new Exception("Execute failed (update owners): " . $updateStmt->error);
$updateStmt->close();

        mysqli_commit($conn);
        return ['success' => true];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("BUSINESS SIGNUP ERROR: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function loginOwner($emailOrMobile, $password) {
    global $conn;
    $emailOrMobile = clean($emailOrMobile);

    // Try to find owner by email or mobile
    $stmt = $conn->prepare("SELECT * FROM owners WHERE email = ? OR mobile = ?");
    $stmt->bind_param("ss", $emailOrMobile, $emailOrMobile);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        // Verify password
        if (password_verify($password, $row['password'])) {
            return $row; // Return owner data
        }
    }
    return false; // Login failed
}

?>