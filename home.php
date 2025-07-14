<?php
session_start();
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get current user data
$user = getUserById($_SESSION['user_id']);
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$welcomeMessage = isset($_GET['welcome']) ? true : false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BookItBro - Home</title>
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
      <li><a href="home.php">Home</a></li>
      <li><a href="my-bookings.php">My Bookings</a></li>
      <li><a href="profile.php">Profile</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>

  <?php if ($welcomeMessage): ?>
  <div class="welcome-banner">
    <div class="welcome-content">
      <h2>Welcome to BookItBro, <?= htmlspecialchars($user['name']) ?>! 🎉</h2>
      <p>Your account has been successfully created and verified.</p>
    </div>
  </div>
  <?php endif; ?>

  <header class="header">
    <h1 class="app-title">Find & Book Services</h1>
    <p class="tagline">JUST BOOK IT..!</p>
  </header>

  <main class="main-content">
    <div class="user-greeting">
      <h3>Hello, <?= htmlspecialchars($user['name']) ?>!</h3>
      <p>What service are you looking for today?</p>
    </div>

    <form class="search-form" action="search.php" method="GET" autocomplete="off">
      <input type="text" name="q" placeholder="Search for saloons, services, or location" class="search-input">
      <button type="submit" class="search-btn">Search</button>
    </form>
    
    <button class="location-btn" onclick="searchNearby()">Find Services Near Me</button>

    <div class="service-categories">
      <h3>Popular Categories</h3>
      <div class="category-grid">
        <a href="search.php?category=saloon" class="category-card">
          <div class="category-icon">✂️</div>
          <h4>Saloons</h4>
          <p>Hair cut, styling & grooming</p>
        </a>
        <a href="search.php?category=spa" class="category-card">
          <div class="category-icon">🧖‍♀️</div>
          <h4>Spa & Wellness</h4>
          <p>Massage, facial & relaxation</p>
        </a>
        <a href="search.php?category=mechanic" class="category-card">
          <div class="category-icon">🔧</div>
          <h4>Auto Services</h4>
          <p>Car repair & maintenance</p>
        </a>
        <a href="search.php?category=beauty" class="category-card">
          <div class="category-icon">💄</div>
          <h4>Beauty Services</h4>
          <p>Makeup, nails & skincare</p>
        </a>
      </div>
    </div>

    <div class="recent-bookings">
      <h3>Your Recent Activity</h3>
      <div class="activity-card">
        <p>No recent bookings yet.</p>
        <a href="search.php" class="activity-link">Start exploring services →</a>
      </div>
    </div>
  </main>

  <footer class="footer">
    <p>&copy; 2024 BookItBro. All rights reserved.</p>
  </footer>

  <script>
    document.getElementById('navToggle').addEventListener('click', function() {
      document.getElementById('navLinks').classList.toggle('nav-active');
      this.classList.toggle('open');
    });

    function searchNearby() {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            window.location.href = `search.php?lat=${lat}&lng=${lng}&nearby=1`;
          },
          function(error) {
            alert("Unable to retrieve your location. Please search manually.");
          }
        );
      } else {
        alert("Geolocation is not supported by this browser.");
      }
    }

    // Auto-hide welcome banner after 5 seconds
    <?php if ($welcomeMessage): ?>
    setTimeout(function() {
      const banner = document.querySelector('.welcome-banner');
      if (banner) {
        banner.style.opacity = '0';
        setTimeout(() => banner.remove(), 500);
      }
    }, 5000);
    <?php endif; ?>
  </script>
</body>
</html>