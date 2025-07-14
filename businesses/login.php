<?php
session_start();
require_once '../functions.php';
require_once '../notifications.php';

$error = '';
$success = '';
$step = $_GET['step'] ?? 1;

// Check for success message from signup
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = "Account created successfully! Please login.";
}

// Step 1: Initial login form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 1) {
    $emailOrMobile = clean($_POST['login_email']);
    $password = $_POST['login_password'];

    // Validation
    if (empty($emailOrMobile) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Check if owner exists and password is correct
        $owner = loginOwner($emailOrMobile, $password);
        
        if ($owner) {
            // Store owner data in session temporarily for 2FA
            $_SESSION['business_login_data'] = [
                'owner_id' => $owner['id'],
                'owner_name' => $owner['name'],
                'owner_email' => $owner['email'],
                'owner_mobile' => $owner['mobile'],
				'business_id' => $owner['business_id']
            ];
            
           // Send verification code for 2FA
            $result = sendVerificationCode($owner['email'], $owner['mobile'], $owner['name']);
            
            if ($result['success']) {
                $_SESSION['otp_sent_time'] = time();
                header("Location: login.php?step=2");
                exit;
            } else {
                $error = "Failed to send verification code. Please try again.";
            }
        } else {
            $error = "Invalid email/mobile or password.";
        } 
    }
}

// Step 2: Verify OTP and complete login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
    $inputCode = clean($_POST['verification_code']);
    
    if (empty($_SESSION['business_login_data'])) {
        header("Location: login.php?step=1");
        exit;
    }
    
    $loginData = $_SESSION['business_login_data'];
    
    if (empty($inputCode)) {
        $error = "Please enter the verification code.";
    } elseif (verifyCode($loginData['owner_email'], $loginData['owner_mobile'], $inputCode)) {
        // Code verified, complete login
        $_SESSION['owner_id'] = $loginData['owner_id'];
        $_SESSION['owner_name'] = $loginData['owner_name'];
        $_SESSION['login_time'] = time();
		$_SESSION['business_id']=$loginData['business_id'];
        
        // Clear login data
        unset($_SESSION['business_login_data']);
        unset($_SESSION['otp_sent_time']);
        
        // Redirect to dashboard
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid or expired verification code.";
    }
}

// Resend OTP
if (isset($_GET['resend']) && $_GET['resend'] == 1 && $step == 2) {
    if (!empty($_SESSION['business_login_data'])) {
        $lastSent = $_SESSION['otp_sent_time'] ?? 0;
        if (time() - $lastSent < 30) {
            $error = "Please wait " . (30 - (time() - $lastSent)) . " seconds before requesting a new code.";
        } else {
            $loginData = $_SESSION['business_login_data'];
            $result = sendVerificationCode($loginData['owner_email'], $loginData['owner_mobile'], $loginData['owner_name']);
            if ($result['success']) {
                $_SESSION['otp_sent_time'] = time();
                $success = "Verification code resent successfully.";
            } else {
                $error = "Failed to resend verification code.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BookItBro - Business Login</title>
  <link rel="stylesheet" href="../styles.css">
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap" rel="stylesheet">
</head>
<body>
  <nav class="navbar">
    <div class="nav-brand">
      <img src="../img/clock.png" alt="BookItBro Logo" class="nav-logo">
      <span class="nav-app-title">BookItBro Business</span>
    </div>
    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
      <span class="hamburger"></span>
      <span class="hamburger"></span>
      <span class="hamburger"></span>
    </button>
    <ul class="nav-links" id="navLinks">
      <li><a href="../index.html">Home</a></li>
      <li><a href="signup.php">Business Signup</a></li>
      <li><a href="../login.php">Customer Login</a></li>
    </ul>
  </nav>

  <main class="main-content">
    <?php if ($step == 1): ?>
      <h2 class="form-title">Business Login</h2>
      
      <?php if ($error): ?>
        <div class="form-message error"><?= $error ?></div>
      <?php endif; ?>
      
      <?php if ($success): ?>
        <div class="form-message success"><?= $success ?></div>
      <?php endif; ?>

      <form class="form-box" method="POST" autocomplete="off">
  <label for="login_email">Email or Mobile</label>
  <input type="text" id="login_email" name="login_email" value="<?= $_POST['login_email'] ?? '' ?>" required>

  <label for="login_password">Password</label>
  <input type="password" id="login_password" name="login_password" required>

  <button type="submit" class="form-btn">Login</button>

  <p class="form-link">
    <a href="request_reset.php">Forgot Password?</a>
  </p>

  <p class="form-link">Don't have a business account? <a href="signup.php">Signup</a></p>
  <p class="form-link">Are you a customer? <a href="../login.php">Customer Login</a></p>
</form>
      
    <?php elseif ($step == 2): ?>
      <h2 class="form-title">Verify Login</h2>
      <p class="verification-info">
        For security, we've sent a 6-digit verification code to:<br>
        <strong><?= isset($_SESSION['business_login_data']['owner_email']) ? maskEmail($_SESSION['business_login_data']['owner_email']) : '' ?></strong><br>
        <strong><?= isset($_SESSION['business_login_data']['owner_mobile']) ? maskMobile($_SESSION['business_login_data']['owner_mobile']) : '' ?></strong>
      </p>
      
      <?php if ($error): ?>
        <div class="form-message error"><?= $error ?></div>
      <?php endif; ?>
      
      <?php if ($success): ?>
        <div class="form-message success"><?= $success ?></div>
      <?php endif; ?>

      <form class="form-box" method="POST" autocomplete="off">
        <label for="verification_code">Enter 6-Digit Code</label>
        <input type="text" id="verification_code" name="verification_code" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required>

        <button type="submit" class="form-btn">Verify & Login</button>
        
        <div class="resend-section">
          <p class="form-link">
            Didn't receive the code? <a href="login.php?step=2&resend=1" id="resendLink">Resend Code</a>
          </p>
          <p class="form-link">
            <a href="login.php?step=1">← Back to Login</a>
          </p>
        </div>
      </form>
    <?php endif; ?>
  </main>

  <footer class="footer">
    <p>&copy; 2024 BookItBro. All rights reserved.</p>
  </footer>
  
  <script>
    document.getElementById('navToggle').addEventListener('click', function() {
      document.getElementById('navLinks').classList.toggle('nav-active');
      this.classList.toggle('open');
    });
    
    <?php if ($step == 2): ?>
    document.getElementById('verification_code').focus();
    <?php endif; ?>
  </script>
</body>
</html>