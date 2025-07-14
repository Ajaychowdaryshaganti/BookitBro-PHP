<?php
session_start();
require '../db.php';
require '../functions.php'; // for clean()
require '../notifications.php'; // for sendEmail() or sendSMS()

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrMobile = clean($_POST['email_or_mobile'] ?? '');

    if (empty($emailOrMobile)) {
        $error = "Please enter your registered email or mobile.";
    } else {
       
        $stmt = $mysqli->prepare("SELECT id, name, email, mobile FROM owners");
        $stmt->execute();
        $result = $stmt->get_result();

        $owner = null;
        while ($row = $result->fetch_assoc()) {
            if (($row['email']) === $emailOrMobile || ($row['mobile']) === $emailOrMobile) {
                $owner = $row;
                break;
            }
        }
        $stmt->close();

        if (!$owner) {
            $error = "No account found with that email or mobile.";
        } else {
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            error_log(print_r($token, true));
            $token_hash = hash('sha256', $token);
            $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

            // Insert token into password_resets
            $stmt = $mysqli->prepare("INSERT INTO password_resets (owner_id, token_hash, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $owner['id'], $token_hash, $expires_at);
            $stmt->execute();
            $stmt->close();

            // Send reset link via email (or SMS)
            require '../db.php'; // or config.php where $domainName is defined

            $resetLink = $domainName . "/businesses/reset_password.php?token=$token";

            $subject = "BookItBro Password Reset Request";
            $message = "Hi " . ($owner['name']) . ",\n\n";
            $message .= "We received a request to reset your password. Click the link below to reset it:\n";
            $message .= "$resetLink\n\n";
            $message .= "This link will expire in 1 hour.\n\n";
            $message .= "If you did not request this, please ignore this email.";

            $emailSent = sendEmail(($owner['email']), $subject, $message);

            if ($emailSent) {
                $success = "Password reset link sent to your email.";
            } else {
                $error = "Failed to send reset email. Please try again later.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Forgot Password</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f8;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .container {
      background: white;
      padding: 2rem 3rem;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      max-width: 400px;
      width: 100%;
      box-sizing: border-box;
    }
    h2 {
      margin-bottom: 1.5rem;
      color: #333;
      text-align: center;
    }
    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: #555;
    }
    input[type="text"] {
      width: 100%;
      padding: 0.5rem 0.75rem;
      margin-bottom: 1.25rem;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 1rem;
      box-sizing: border-box;
      transition: border-color 0.3s;
    }
    input[type="text"]:focus {
      border-color: #007bff;
      outline: none;
    }
    button {
      width: 100%;
      padding: 0.6rem 0;
      background-color: #007bff;
      border: none;
      border-radius: 6px;
      color: white;
      font-size: 1.1rem;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    button:hover {
      background-color: #0056b3;
    }
    .message {
      margin-bottom: 1rem;
      padding: 0.75rem 1rem;
      border-radius: 6px;
      font-weight: 600;
      text-align: center;
    }
    .error {
      background-color: #f8d7da;
      color: #842029;
      border: 1px solid #f5c2c7;
    }
    .success {
      background-color: #d1e7dd;
      color: #0f5132;
      border: 1px solid #badbcc;
    }
    a {
      color: #007bff;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Forgot Password</h2>

    <?php if ($error): ?>
      <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <label for="email_or_mobile">Email or Mobile:</label>
      <input type="text" id="email_or_mobile" name="email_or_mobile" required autocomplete="email" />
      <button type="submit">Send Reset Link</button>
    </form>
  </div>
</body>
</html>