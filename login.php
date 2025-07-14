<?php
session_start();
require_once 'functions.php';
require_once 'notifications.php';

$error = '';
$success = '';
$step = $_GET['step'] ?? 1;

// Step 1: Initial login form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 1) {
    $emailOrMobile = clean($_POST['login-email']);
    $password = $_POST['login-password'];

    // Validation
    if (empty($emailOrMobile) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Check if user exists and password is correct
        $user = loginUser($emailOrMobile, $password);
        
        if ($user) {
            // Store user data in session temporarily for 2FA
            $_SESSION['login_data'] = [
                'user_id' => $user['id'],
                'user_name' => $user['name'],
                'user_email' => $user['email'],
                'user_mobile' => $user['mobile'],
                'user_role' => $user['role']
            ];
            
            // Send verification code for 2FA
            $result = sendVerificationCode($user['email'], $user['mobile'], $user['name']);
            
            if ($result['success']) {
                $_SESSION['otp_sent_time'] = time();
                header("Location: login.php?step=2");
                exit;
            } else {
                $error = "Failed to send verification code. Please try again.";
                if (!$result['sms_sent']) {
                    $error .= " SMS Error: " . ($result['sms_error'] ?? 'Unknown');
                }
                if (!$result['email_sent']) {
                    $error .= " Email Error: " . ($result['email_error'] ?? 'Unknown');
                }
            }
        } else {
            $error = "Invalid email/mobile or password.";
        }
    }
}

// Step 2: Verify OTP and complete login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
    $inputCode = clean($_POST['verification-code']);
    
    if (empty($_SESSION['login_data'])) {
        header("Location: login.php?step=1");
        exit;
    }
    
    $loginData = $_SESSION['login_data'];
    
    if (empty($inputCode)) {
        $error = "Please enter the verification code.";
    } elseif (verifyCode($loginData['user_email'], $loginData['user_mobile'], $inputCode)) {
        // Code verified, complete login
        $_SESSION['user_id'] = $loginData['user_id'];
        $_SESSION['user_name'] = $loginData['user_name'];
        $_SESSION['user_role'] = $loginData['user_role'];
        $_SESSION['login_time'] = time();
        
        // Clear login data
        unset($_SESSION['login_data']);
        unset($_SESSION['otp_sent_time']);
        
        // Redirect to homepage
        header("Location: home.php?login=1");
        exit;
    } else {
        $error = "Invalid or expired verification code.";
    }
}

// Resend OTP
if (isset($_GET['resend']) && $_GET['resend'] == 1 && $step == 2) {
    if (!empty($_SESSION['login_data'])) {
        // Check if 30 seconds have passed since last OTP
        $lastSent = $_SESSION['otp_sent_time'] ?? 0;
        if (time() - $lastSent < 30) {
            $error = "Please wait " . (30 - (time() - $lastSent)) . " seconds before requesting a new code.";
        } else {
            $loginData = $_SESSION['login_data'];
            $result = sendVerificationCode($loginData['user_email'], $loginData['user_mobile'], $loginData['user_name']);
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
  <title>BookItBro - Login</title>
  <link rel="stylesheet" href="styles.css">
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap" rel="stylesheet">
</head>
<body>
  <nav class="navbar">
    <div class="nav-brand">
      <img src="img/clock.png" alt="BookItBro Logo" class="nav-logo">
      <span class="nav-app-title">BookItBro</span>
    </div>
    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
      <span class="hamburger"></span>
      <span class="hamburger"></span>
      <span class="hamburger"></span>
    </button>
    <ul class="nav-links" id="navLinks">
      <li><a href="index.html">Home</a></li>
      <li><a href="login.php">Login</a></li>
      <li><a href="signup.php">Signup</a></li>
      <li><a href="contact.php">Contact</a></li>
    </ul>
  </nav>

  <main class="main-content">
    <?php if ($step == 1): ?>
      <h2 class="form-title">Login</h2>
      
      <?php if ($error): ?>
        <div class="form-message error"><?= $error ?></div>
      <?php endif; ?>

      <form class="form-box" method="POST" autocomplete="off">
        <label for="login-email">Email or Mobile</label>
        <input type="text" id="login-email" name="login-email" value="<?= isset($_POST['login-email']) ? clean($_POST['login-email']) : '' ?>" required>

        <label for="login-password">Password</label>
        <input type="password" id="login-password" name="login-password" required>

        <button type="submit" class="form-btn">Login</button>
        <p class="form-link">Don't have an account? <a href="signup.php">Signup</a></p>
      </form>
      
    <?php elseif ($step == 2): ?>
      <h2 class="form-title">Verify Login</h2>
      <p class="verification-info">
        For security, we've sent a 6-digit verification code to:<br>
        <strong><?= isset($_SESSION['login_data']['user_email']) ? maskEmail($_SESSION['login_data']['user_email']) : '' ?></strong><br>
        <strong><?= isset($_SESSION['login_data']['user_mobile']) ? maskMobile($_SESSION['login_data']['user_mobile']) : '' ?></strong>
      </p>
      
      <?php if ($error): ?>
        <div class="form-message error"><?= $error ?></div>
      <?php endif; ?>
      
      <?php if ($success): ?>
        <div class="form-message success"><?= $success ?></div>
      <?php endif; ?>

      <form class="form-box" method="POST" autocomplete="off">
        <label for="verification-code">Enter 6-Digit Code</label>
        <input type="text" id="verification-code" name="verification-code" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required>

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
    // Auto-focus on verification code input
    document.getElementById('verification-code').focus();
    
    // Auto-submit when 6 digits are entered (optional - uncomment if desired)
    /*
    document.getElementById('verification-code').addEventListener('input', function() {
      if (this.value.length === 6) {
        this.form.submit();
      }
    });
    */
    <?php endif; ?>
  </script>
</body>
</html>