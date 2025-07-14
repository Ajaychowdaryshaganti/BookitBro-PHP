<?php
session_start();
require_once '../db.php';
require_once '../functions.php'; // clean()
require_once '../encryption.php';

$error = '';
$success = '';
$showForm = true;

$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (!$token) {
    die("Invalid password reset link.");
}

$token_hash = hash('sha256', $token);

// Check token validity
$stmt = $mysqli->prepare("SELECT owner_id, expires_at, used FROM password_resets WHERE token_hash = ?");
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$stmt->bind_result($owner_id, $expires_at, $used);
if (!$stmt->fetch()) {
    $stmt->close();
    die("Invalid or expired password reset link.");
}
$stmt->close();

if ($used) {
    die("This password reset link has already been used.");
}

if (strtotime($expires_at) < time()) {
    die("This password reset link has expired.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($password) || empty($password_confirm)) {
        $error = "Please fill in all password fields.";
    } elseif ($password !== $password_confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        // Hash new password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Update password in owners table
        $stmt = $mysqli->prepare("UPDATE owners SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $password_hash, $owner_id);
        $stmt->execute();
        $stmt->close();

        // Mark token as used
        $stmt = $mysqli->prepare("UPDATE password_resets SET used = 1 WHERE token_hash = ?");
        $stmt->bind_param("s", $token_hash);
        $stmt->execute();
        $stmt->close();

        $success = "Password reset successful! You can now <a href='login.php'>login</a>.";
        $showForm = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reset Password</title>
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
    input[type="password"] {
      width: 100%;
      padding: 0.5rem 0.75rem;
      margin-bottom: 1.25rem;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 1rem;
      box-sizing: border-box;
      transition: border-color 0.3s;
    }
    input[type="password"]:focus {
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
    <h2>Reset Password</h2>

    <?php if ($error): ?>
      <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="message success"><?= $success ?></div>
    <?php endif; ?>

    <?php if ($showForm): ?>
      <form method="POST" novalidate>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>" />
        <label for="password">New Password</label>
        <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" />

        <label for="password_confirm">Confirm Password</label>
        <input type="password" id="password_confirm" name="password_confirm" required minlength="8" autocomplete="new-password" />

        <button type="submit">Reset Password</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>