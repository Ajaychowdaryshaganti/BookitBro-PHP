<?php
// db.php
$host = 'localhost';
$username = 'root';
$password = 'PepsiMax';
$database = 'bookitbro';
error_reporting(E_ALL & ~E_WARNING);
date_default_timezone_set('Australia/Melbourne');

// Encryption key - KEEP THIS SECRET AND SECURE!
// In production, store this in environment variables or a secure config file
define('ENCRYPTION_KEY', 'hQmPGNr-#?mN#DFd%z7jtha3#X42drYz'); // Must be 32 characters
define('ENCRYPTION_METHOD', 'AES-256-CBC');


$domainName = "http://bookitbro.duckdns.org/";


$mysqli = new mysqli($host, $username, $password, $database);

$timezone = 'Australia/Melbourne'; // Replace with your desired timezone
$query = "SET time_zone = '$timezone'";
// Check connection
if ($mysqli->connect_error) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

// Set charset
$mysqli->set_charset('utf8mb4');

// Add this line:
$conn = $mysqli;
?>