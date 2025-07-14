<?php
// notifications.php
require_once 'vendor/autoload.php'; // If using Composer
require_once 'encryption.php';


date_default_timezone_set('Australia/Melbourne'); // For now
// Later, change to 'Asia/Kolkata' for India


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Fast2SMS Configuration
define('FAST2SMS_API_KEY', 'SFD5aIdZNEp2YeUKkiJsn7XGgq3m8bj69tyWvxCLOHAQVP4lTc8A6LEKRcaqZjf52tmxeMbQu1sdVl9Y'); // Replace with your actual API key

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com'); // or your SMTP server
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'unprofessionalbachelors@gmail.com');
define('SMTP_PASSWORD', 'vvnf mhhp humd fqom'); // Use app password for Gmail
define('FROM_EMAIL', 'noreply@bookitbro.com');
define('FROM_NAME', 'BookItBro');

function sendSMS($mobile, $message) {
    $apiKey = "SFD5aIdZNEp2YeUKkiJsn7XGgq3m8bj69tyWvxCLOHAQVP4lTc8A6LEKRcaqZjf52tmxeMbQu1sdVl9Y-----"; // Replace with your Fast2SMS API key

    $fields = array(
        "message" => $message,
        "language" => "english",
        "route" => "q",
        "numbers" => $mobile
    );

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://www.fast2sms.com/dev/bulkV2",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($fields),
        CURLOPT_HTTPHEADER => array(
            "authorization: $apiKey",
            "accept: */*",
            "cache-control: no-cache",
            "content-type: application/json"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        return [
            'success' => false,
            'error' => $err
        ];
    } else {
        $result = json_decode($response, true);
        if (isset($result['return']) && $result['return'] === true) {
            return [
                'success' => true,
                'response' => $result
            ];
        } else {
            return [
                'success' => false,
                'error' => $result['message'][0] ?? 'Unknown error'
            ];
        }
    }
}

/**
 * Send Email using PHPMailer
 */
function sendEmail($to, $subject, $body, $isHTML = true) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

/**
 * Generate and send verification code
 */
function sendVerificationCode($email, $mobile, $name) {
    global $mysqli;
    
    // Generate 6-digit code
    $code = sprintf("%06d", mt_rand(100000, 999999));
    
    // Encrypt the code
    $codeEncrypted = encryptData($code);
    
    // Create hashes for searching
    $emailHash = hashData($email);
    $mobileHash = hashData($mobile);
    
    // Set expiry (10 minutes)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Store in database
    $stmt = $mysqli->prepare("INSERT INTO verification_codes (email_hash, mobile_hash, code, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $emailHash, $mobileHash, $codeEncrypted, $expiresAt);
    
    if (!$stmt->execute()) {
        $stmt->close();
        return ['success' => false, 'error' => 'Database error'];
    }
    $stmt->close();
    
    // Send SMS
    $smsMessage = "Your BookItBro verification code is: $code. Valid for 10 minutes.";
    $smsResult = sendSMS($mobile, $smsMessage);
    
    // Send Email
    $emailSubject = "BookItBro - Verification Code";
    $emailBody = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #181B4D;'>Welcome to BookItBro!</h2>
            <p>Hi $name,</p>
            <p>Thank you for signing up with BookItBro. Please use the verification code below to complete your registration:</p>
            <div style='background: #f5f5f5; padding: 20px; text-align: center; margin: 20px 0;'>
                <h1 style='color: #FFA23B; font-size: 32px; margin: 0;'>$code</h1>
            </div>
            <p>This code will expire in 10 minutes.</p>
            <p>If you didn't request this code, please ignore this email.</p>
            <hr style='border: 1px solid #eee; margin: 20px 0;'>
            <p style='color: #666; font-size: 12px;'>BookItBro - Just Book It!</p>
        </div>
    </body>
    </html>";
    
    $emailResult = sendEmail($email, $emailSubject, $emailBody);
    
    // Return results
    return [
        'success' => true,
        'sms_sent' => $smsResult['success'],
        'email_sent' => $emailResult['success'],
        'sms_error' => $smsResult['error'] ?? null,
        'email_error' => $emailResult['error'] ?? null
    ];
}

/**
 * Verify the code
 */
function verifyCode($email, $mobile, $inputCode) {
    global $mysqli;
    
    $emailHash = hashData($email);
    $mobileHash = hashData($mobile);
    
    error_log("=== verifyCode Debug ===");
    error_log("Email: " . $email);
    error_log("Mobile: " . $mobile);
    error_log("Input Code: " . $inputCode);
    error_log("Email Hash: " . $emailHash);
    error_log("Mobile Hash: " . $mobileHash);
    
    // Get the latest non-verified code
    $stmt = $mysqli->prepare("SELECT * FROM verification_codes WHERE email_hash = ? AND mobile_hash = ? AND expires_at > NOW() AND verified = 0 ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("ss", $emailHash, $mobileHash);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row) {
        error_log("Found code in database (ID: " . $row['id'] . ")");
        error_log("Encrypted code from DB: " . $row['code']);
        
        $storedCode = decryptData($row['code']);
        
        error_log("Decrypted stored code: " . $storedCode);
        error_log("User input code: " . $inputCode);
        error_log("Codes match: " . ($storedCode === $inputCode ? 'YES' : 'NO'));
        
        if ($storedCode === $inputCode) {
            error_log("SUCCESS: Codes match! Marking as verified.");
            // Mark as verified
            $updateStmt = $mysqli->prepare("UPDATE verification_codes SET verified = 1 WHERE id = ?");
            $updateStmt->bind_param("i", $row['id']);
            $updateStmt->execute();
            $updateStmt->close();
            return true;
        } else {
            error_log("FAILURE: Codes do not match!");
        }
    } else {
        error_log("No valid code found in database (expired, already verified, or doesn't exist)");
    }
    
    error_log("=== End verifyCode Debug ===");
    return false;
}
?>