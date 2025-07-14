<?php
// encryption.php

/**
 * Encrypt sensitive data
 */
 
 require 'db.php';
 
function encryptData($data) {
    if (empty($data)) return $data;
    
    $key = ENCRYPTION_KEY;
    $method = ENCRYPTION_METHOD;
    
    // Generate a random IV
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
    
    // Encrypt the data
    $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
    
    // Combine IV and encrypted data, then base64 encode
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt sensitive data
 */
function decryptData($encryptedData) {
    if (empty($encryptedData)) return $encryptedData;
    
    $key = ENCRYPTION_KEY;
    $method = ENCRYPTION_METHOD;
    
    // Decode the base64 data
    $data = base64_decode($encryptedData);
    
    // Extract IV length
    $ivLength = openssl_cipher_iv_length($method);
    
    // Extract IV and encrypted data
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    
    // Decrypt the data
    return openssl_decrypt($encrypted, $method, $key, 0, $iv);
}

/**
 * Hash sensitive data (one-way, for searching)
 */
 
function hashData($data) {
    return hash('sha256', $data);
}

/**
 * Generate secure random token
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}
?>