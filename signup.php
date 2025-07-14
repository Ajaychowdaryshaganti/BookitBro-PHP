<?php
session_start();
require_once 'functions.php';
require_once 'notifications.php';

$error = '';
$success = '';
$step = $_GET['step'] ?? 1;

// Step 1: Initial signup form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 1) {
    $name = clean($_POST['signup-name']);
    $email = clean($_POST['signup-email']);
    $mobile = clean($_POST['signup-mobile']);
    $password = $_POST['signup-password'];

    // Validation
    if (empty($name) || empty($email) || empty($mobile) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!isValidEmail($email)) {
        $error = "Please enter a valid email address.";
    } elseif (!isValidMobile($mobile)) {
        $error = "Please enter a valid 10-digit mobile number.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (userExists($email, $mobile)) {
        $error = "Email or mobile already registered.";
    } else {
        // Store data in session temporarily
        $_SESSION['signup_data'] = [
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
            'password' => $password
        ];
        
        // Send verification code
        $result = sendVerificationCode($email, $mobile, $name);
        
        if ($result['success']) {
            $_SESSION['otp_sent_time'] = time();
            header("Location: signup.php?step=2");
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
    }
}

// Step 2: Verify OTP and complete registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
    $inputCode = clean($_POST['verification-code']);
    
    if (empty($_SESSION['signup_data'])) {
        header("Location: signup.php?step=1");
        exit;
    }
    
    $signupData = $_SESSION['signup_data'];
    
    if (empty($inputCode)) {
        $error = "Please enter the verification code.";
    } elseif (verifyCode($signupData['email'], $signupData['mobile'], $inputCode)) {
        // Code verified, create user account
        if (registerUser($signupData['name'], $signupData['email'], $signupData['mobile'], $signupData['password'])) {
            // Get the newly created user
            $user = loginUser($signupData['email'], $signupData['password']);
            if ($user) {
                // Set session for auto-login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                // Clear signup data
                unset($_SESSION['signup_data']);
                unset($_SESSION['otp_sent_time']);
                
                // Redirect to homepage
                header("Location: home.php?welcome=1");
                exit;
            }
        } else {
            $error = "Registration failed. Please try again.";
        }
    } else {
        $error = "Invalid or expired verification code.";
    }
}

// Resend OTP
if (isset($_GET['resend']) && $_GET['resend'] == 1 && $step == 2) {
    if (!empty($_SESSION['signup_data'])) {
        // Check if 30 seconds have passed since last OTP
        $lastSent = $_SESSION['otp_sent_time'] ?? 0;
        if (time() - $lastSent < 30) {
            $error = "Please wait " . (30 - (time() - $lastSent)) . " seconds before requesting a new code.";
        } else {
            $signupData = $_SESSION['signup_data'];
            $result = sendVerificationCode($signupData['email'], $signupData['mobile'], $signupData['name']);
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
  <title>BookItBro - Signup</title>
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
      <h2 class="form-title">Create Account</h2>
      
      <?php if ($error): ?>
        <div class="form-message error"><?= $error ?></div>
      <?php endif; ?>

      <form class="form-box" method="POST" autocomplete="off">
        <label for="signup-name">Full Name</label>
        <input type="text" id="signup-name" name="signup-name" value="<?= isset($_POST['signup-name']) ? clean($_POST['signup-name']) : '' ?>" required>

        <label for="signup-email">Email Address</label>
        <input type="email" id="signup-email" name="signup-email" value="<?= isset($_POST['signup-email']) ? clean($_POST['signup-email']) : '' ?>" required>

        <label for="signup-mobile">Mobile Number</label>
        <input type="text" id="signup-mobile" name="signup-mobile" value="<?= isset($_POST['signup-mobile']) ? clean($_POST['signup-mobile']) : '' ?>" maxlength="10" placeholder="10-digit mobile number" required>

        <label for="signup-password">Password</label>
        <input type="password" id="signup-password" name="signup-password" placeholder="Minimum 6 characters" required>

        <button type="submit" class="form-btn">Send Verification Code</button>
        <p class="form-link">Already have an account? <a href="login.php">Login</a></p>
      </form>
      
    <?php elseif ($step == 2): ?>
      <h2 class="form-title">Verify Your Account</h2>
      <p class="verification-info">
        We've sent a 6-digit verification code to:<br>
        <strong><?= isset($_SESSION['signup_data']['email']) ? maskEmail($_SESSION['signup_data']['email']) : '' ?></strong><br>
        <strong><?= isset($_SESSION['signup_data']['mobile']) ? maskMobile($_SESSION['signup_data']['mobile']) : '' ?></strong>
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

        <button type="submit" class="form-btn">Verify & Complete Signup</button>
        
        <div class="resend-section">
          <p class="form-link">
            Didn't receive the code? <a href="signup.php?step=2&resend=1" id="resendLink">Resend Code</a>
          </p>
          <p class="form-link">
            <a href="signup.php?step=1">← Back to Signup</a>
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
    
   /* // Auto-submit when 6 digits are entered
    document.getElementById('verification-code').addEventListener('input', function() {
      if (this.value.length === 6) {
        this.form.submit();
      }
    });*/
    <?php endif; ?>
  </script>
</body>
</html>