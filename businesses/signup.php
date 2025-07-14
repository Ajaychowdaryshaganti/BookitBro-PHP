<?php
session_start();
require_once '../functions.php';
require_once '../notifications.php';

$error = '';
$success = '';
$step = $_GET['step'] ?? 1;

// Get categories from database
$categories = getCategories();

// Step 1: Initial signup form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 1) {
    $ownerName = clean($_POST['owner_name']);
    $ownerEmail = clean($_POST['owner_email']);
    $ownerMobile = clean($_POST['owner_mobile']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    $businessName = clean($_POST['business_name']);
    $businessAddress = clean($_POST['business_address']);
    $businessCity = clean($_POST['business_city']);
    $businessState = clean($_POST['business_state']);
    $businessMobile = clean($_POST['business_mobile']);
    $slotDuration = (int)$_POST['slot_duration'];
    $categoryId = (int)$_POST['category_id'];
    
    // Process working hours for each day
    $workingHours = [];
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    
    foreach ($days as $day) {
        if (isset($_POST[$day . '_open']) && $_POST[$day . '_open'] === 'on') {
            $openTime = $_POST[$day . '_open_time'] ?? '';
            $closeTime = $_POST[$day . '_close_time'] ?? '';
            if (!empty($openTime) && !empty($closeTime)) {
                $workingHours[$day] = [
                    'open' => $openTime,
                    'close' => $closeTime
                ];
            }
        }
    }

    // Validation
    if (empty($ownerName) || empty($ownerEmail) || empty($ownerMobile) || empty($password) || 
        empty($businessName) || empty($businessAddress)) {
        $error = "All required fields must be filled.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (!filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!preg_match('/^[0-9]{10}$/', $ownerMobile)) {
        $error = "Mobile number must be 10 digits.";
    } elseif (empty($workingHours)) {
        $error = "Please select at least one working day with opening hours.";
    } else {
        // Check if owner already exists
        if (checkOwnerExists($ownerEmail, $ownerMobile)) {
            $error = "An account with this email or mobile already exists.";
        } else {
            // Store data in session for verification
            $_SESSION['business_signup_data'] = [
                'owner_name' => $ownerName,
                'owner_email' => $ownerEmail,
                'owner_mobile' => $ownerMobile,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'business_name' => $businessName,
                'business_address' => $businessAddress,
                'business_city' => $businessCity,
                'business_state' => $businessState,
                'business_mobile' => $businessMobile,
                'working_hours' => json_encode($workingHours),
                'slot_duration' => $slotDuration,
                'category_id' => $categoryId
            ];
            
            // Send verification code
            $result = sendVerificationCode($ownerEmail, $ownerMobile, $ownerName);
            
            if ($result['success']) {
                $_SESSION['otp_sent_time'] = time();
                header("Location: signup.php?step=2");
                exit;
            } else {
                $error = "Failed to send verification code. Please try again.";
            }
        }
    }
}

// Step 2: Verify OTP and complete signup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
    $inputCode = clean($_POST['verification_code']);
    
    if (empty($_SESSION['business_signup_data'])) {
        header("Location: signup.php?step=1");
        exit;
    }
    
    $signupData = $_SESSION['business_signup_data'];
    
    if (empty($inputCode)) {
        $error = "Please enter the verification code.";
    } elseif (verifyCode($signupData['owner_email'], $signupData['owner_mobile'], $inputCode)) {
        // Code verified, create owner and business
        $result = createOwnerAndBusiness($signupData);
        
        if ($result['success']) {
            // Clear signup data
            unset($_SESSION['business_signup_data']);
            unset($_SESSION['otp_sent_time']);
            
            $success = "Business account created successfully! You can now login.";
            header("Location: login.php?success=1");
            exit;
        } else {
            $error = $result['error'];
        }
    } else {
        $error = "Invalid or expired verification code.";
    }
}

// Resend OTP
if (isset($_GET['resend']) && $_GET['resend'] == 1 && $step == 2) {
    if (!empty($_SESSION['business_signup_data'])) {
        $lastSent = $_SESSION['otp_sent_time'] ?? 0;
        if (time() - $lastSent < 30) {
            $error = "Please wait " . (30 - (time() - $lastSent)) . " seconds before requesting a new code.";
        } else {
            $signupData = $_SESSION['business_signup_data'];
            $result = sendVerificationCode($signupData['owner_email'], $signupData['owner_mobile'], $signupData['owner_name']);
            if ($result['success']) {
                $_SESSION['otp_sent_time'] = time();
                $success = "Verification code resent successfully.";
            } else {
                $error = "Failed to resend verification code.";
            }
        }
    }
}

// Indian states array
$indianStates = [
    'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat', 
    'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 
    'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 
    'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 
    'Uttarakhand', 'West Bengal', 'Andaman and Nicobar Islands', 'Chandigarh', 
    'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Jammu and Kashmir', 'Ladakh', 
    'Lakshadweep', 'Puducherry'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BookItBro - Business Signup</title>
  <link rel="stylesheet" href="../styles.css">
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap" rel="stylesheet">
  <style>
    .working-hours-section {
      margin: 1rem 0;
      padding: 1rem;
      border: 1px solid #ddd;
      border-radius: 5px;
      background-color: #f9f9f9;
    }
    
    .day-row {
      display: flex;
      align-items: center;
      margin-bottom: 0.5rem;
      gap: 10px;
    }
    
    .day-checkbox {
      width: auto;
      margin-right: 10px;
    }
    
    .day-label {
      min-width: 100px;
      font-weight: 500;
    }
    
    .time-input {
      width: 120px;
      padding: 0.5rem;
      border: 1px solid #ddd;
      border-radius: 3px;
    }
    
    .time-separator {
      margin: 0 5px;
    }
    
    @media (max-width: 768px) {
      .day-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
      }
      
      .time-inputs {
        display: flex;
        align-items: center;
        gap: 5px;
      }
    }
  </style>
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
      <li><a href="login.php">Business Login</a></li>
      <li><a href="../login.php">Customer Login</a></li>
    </ul>
  </nav>

  <main class="main-content">
    <?php if ($step == 1): ?>
      <h2 class="form-title">Business Signup</h2>
      
      <?php if ($error): ?>
        <div class="form-message error"><?= $error ?></div>
      <?php endif; ?>

      <form class="form-box" method="POST" autocomplete="off">
        <h3>Owner Information</h3>
        <label for="owner_name">Owner Name *</label>
        <input type="text" id="owner_name" name="owner_name" value="<?= $_POST['owner_name'] ?? '' ?>" required>

        <label for="owner_email">Owner Email *</label>
        <input type="email" id="owner_email" name="owner_email" value="<?= $_POST['owner_email'] ?? '' ?>" required>

        <label for="owner_mobile">Owner Mobile *</label>
        <input type="tel" id="owner_mobile" name="owner_mobile" value="<?= $_POST['owner_mobile'] ?? '' ?>" pattern="[0-9]{10}" required>

        <label for="password">Password *</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Confirm Password *</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <h3>Business Information</h3>
        <label for="business_name">Business Name *</label>
        <input type="text" id="business_name" name="business_name" value="<?= $_POST['business_name'] ?? '' ?>" required>

        <label for="business_address">Business Address *</label>
        <textarea id="business_address" name="business_address" required><?= $_POST['business_address'] ?? '' ?></textarea>

        <label for="business_city">City</label>
        <input type="text" id="business_city" name="business_city" value="<?= $_POST['business_city'] ?? '' ?>">

        <label for="business_state">State</label>
        <select id="business_state" name="business_state">
          <option value="">Select State</option>
          <?php foreach ($indianStates as $state): ?>
            <option value="<?= $state ?>" <?= ($_POST['business_state'] ?? '') == $state ? 'selected' : '' ?>><?= $state ?></option>
          <?php endforeach; ?>
        </select>

        <label for="business_mobile">Business Mobile</label>
        <input type="tel" id="business_mobile" name="business_mobile" value="<?= $_POST['business_mobile'] ?? '' ?>" pattern="[0-9]{10}">

        <div class="working-hours-section">
          <h3>Working Hours *</h3>
          <p style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;">Select the days your business is open and set the opening hours for each day.</p>
          
          <?php 
          $days = [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday', 
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday'
          ];
          
          foreach ($days as $dayKey => $dayName): 
          ?>
            <div class="day-row">
              <input type="checkbox" id="<?= $dayKey ?>_open" name="<?= $dayKey ?>_open" class="day-checkbox" 
                     <?= isset($_POST[$dayKey . '_open']) ? 'checked' : '' ?>
                     onchange="toggleTimeInputs('<?= $dayKey ?>')">
              <label for="<?= $dayKey ?>_open" class="day-label"><?= $dayName ?></label>
              <div class="time-inputs">
                <input type="time" id="<?= $dayKey ?>_open_time" name="<?= $dayKey ?>_open_time" 
                       class="time-input" value="<?= $_POST[$dayKey . '_open_time'] ?? '09:00' ?>"
                       <?= !isset($_POST[$dayKey . '_open']) ? 'disabled' : '' ?>>
                <span class="time-separator">to</span>
                <input type="time" id="<?= $dayKey ?>_close_time" name="<?= $dayKey ?>_close_time" 
                       class="time-input" value="<?= $_POST[$dayKey . '_close_time'] ?? '18:00' ?>"
                       <?= !isset($_POST[$dayKey . '_open']) ? 'disabled' : '' ?>>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <label for="slot_duration">Slot Duration (minutes)</label>
        <select id="slot_duration" name="slot_duration">
          <option value="15" <?= ($_POST['slot_duration'] ?? 30) == 15 ? 'selected' : '' ?>>15 minutes</option>
          <option value="30" <?= ($_POST['slot_duration'] ?? 30) == 30 ? 'selected' : '' ?>>30 minutes</option>
          <option value="45" <?= ($_POST['slot_duration'] ?? 30) == 45 ? 'selected' : '' ?>>45 minutes</option>
          <option value="60" <?= ($_POST['slot_duration'] ?? 30) == 60 ? 'selected' : '' ?>>60 minutes</option>
        </select>

        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
          <option value="">Select Category</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= $category['id'] ?>" <?= ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($category['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <button type="submit" class="form-btn">Create Business Account</button>
        <p class="form-link">Already have an account? <a href="login.php">Login</a></p>
      </form>
      
    <?php elseif ($step == 2): ?>
      <h2 class="form-title">Verify Your Account</h2>
      <p class="verification-info">
        We've sent a 6-digit verification code to:<br>
        <strong><?= isset($_SESSION['business_signup_data']['owner_email']) ? maskEmail($_SESSION['business_signup_data']['owner_email']) : '' ?></strong><br>
        <strong><?= isset($_SESSION['business_signup_data']['owner_mobile']) ? maskMobile($_SESSION['business_signup_data']['owner_mobile']) : '' ?></strong>
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

        <button type="submit" class="form-btn">Verify & Create Account</button>
        
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
    
    function toggleTimeInputs(day) {
      const checkbox = document.getElementById(day + '_open');
      const openTime = document.getElementById(day + '_open_time');
      const closeTime = document.getElementById(day + '_close_time');
      
      if (checkbox.checked) {
        openTime.disabled = false;
        closeTime.disabled = false;
      } else {
        openTime.disabled = true;
        closeTime.disabled = true;
      }
    }
    
    // Initialize time inputs on page load
    document.addEventListener('DOMContentLoaded', function() {
      const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
      days.forEach(function(day) {
        toggleTimeInputs(day);
      });
    });
    
    <?php if ($step == 2): ?>
    document.getElementById('verification_code').focus();
    <?php endif; ?>
  </script>
</body>
</html>