<?php
// session.php
session_start();

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user with decrypted data
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return getUserById($_SESSION['user_id']);
}

// Logout function
function logout() {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>