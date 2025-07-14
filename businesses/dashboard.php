<?php
session_start();
if (!isset($_SESSION['owner_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BookItBro Business Dashboard</title>
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
  <div class="dashboard-container">
    <aside class="sidebar">
      <div class="logo"><i class="fa-solid fa-web-awesome"></i></div>
      <nav>
        <a href="#" class="active" data-tab="dashboard" title="Dashboard">
          <i class="fa-solid fa-gauge"></i><span>Dashboard</span>
        </a>
        <a href="#" data-tab="bookings" title="Bookings">
          <i class="fa-solid fa-calendar-check"></i><span>Bookings</span>
        </a>
        <a href="#" data-tab="staff" class="sidebar-link">
          <i class="fa-solid fa-user-group"></i><span>Staff</span>
        </a>
        <a href="#" data-tab="profile" title="Profile">
          <i class="fa-solid fa-id-card"></i><span>Profile</span>
        </a>
        <!-- <a href="#" title="Hours">
          <i class="fa-solid fa-clock"></i><span>Hours</span>
        </a>
        <a href="#" title="Reviews">
          <i class="fa-solid fa-star"></i><span>Reviews</span>
        </a> -->
      </nav>
      <button class="logout" title="Logout" onclick="window.location.href='logout.php';">
        <i class="fa-solid fa-right-from-bracket"></i>
      </button>
    </aside>

    <div class="main-content">
      <div class="topbar">
        <div class="business-title"><span id="businessName">BookItBro</span></div>
        <div class="quick-actions">
  <button id="addBookingBtn"><i class="fa-solid fa-plus"></i> Add Booking</button>
</div>
      </div>
<!-- Booking Modal -->
<div id="bookingModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width: 500px; padding: 1.5rem; border-radius: 12px; background: white; box-shadow: 0 2px 12px rgba(74, 78, 105, 0.1);">
    <span class="close" id="closeBookingModal" style="float:right; cursor:pointer; font-size: 1.5rem;">&times;</span>
    <h2>Add Booking</h2>
    <form id="bookingForm">
      <h5>Name of the  Customer : <strong>Walk-In</strong></h5>
      <label>Employee:
        <select name="employee_id" required>
          <option value="">Select Employee</option>
          <!-- JS will fill employees here -->
        </select>
      </label><br><br>
      <label>Service:
        <input type="text" name="service" required placeholder="Service name" />
      </label><br><br>
      <label>Booking Date:
        <input type="date" name="booking_date" required />
      </label><br><br>
      <label>Booking Time:
        <input type="time" name="booking_time" required />
      </label><br><br>
      <label>Amount:
        <input type="number" name="amount" min="0" required />
      </label><br><br>
      <button type="submit">Add Booking</button>
    </form>
  </div>
</div>

      <!-- Dashboard Tab -->
      <div class="dashboard-widgets tab-content" data-tab="dashboard">
        <div class="widget widget-bookings">
          <div class="widget-title"><i class="fa-solid fa-calendar-check"></i> Today's Bookings</div>
          <div class="widget-value">0</div>
          <ul class="widget-list"></ul>
          <div class="widget-icon"><i class="fa-solid fa-calendar-check"></i></div>
        </div>
        <div class="widget widget-revenue">
          <div class="widget-title"><i class="fa-solid fa-indian-rupee-sign"></i> Revenue (Today)</div>
          <div class="widget-value">₹0</div>
          <div class="widget-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        </div>
        <div class="widget widget-next">
          <div class="widget-title"><i class="fa-solid fa-clock"></i> Next Appointment</div>
          <div class="widget-list">
            <li>No upcoming appointments</li>
          </div>
          <div class="widget-icon"><i class="fa-solid fa-clock"></i></div>
        </div>
        <div class="widget widget-reviews">
          <div class="widget-title"><i class="fa-solid fa-star"></i> Recent Reviews</div>
          <ul class="widget-list"></ul>
          <div class="widget-icon"><i class="fa-solid fa-star"></i></div>
        </div>
      </div>

      <!-- Bookings Tab -->
      <div class="bookings-tab tab-content" data-tab="bookings" style="display:none;">
        <div class="bookings-header">
          <h2><i class="fa-solid fa-calendar-check"></i> All Bookings</h2>
          <div class="filters">
            <select id="filterStatus">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
            <input type="date" id="filterDate" />
          </div>
        </div>

        <table class="bookings-table">
          <thead>
            <tr>
              <th>Customer</th>
              <th>Service</th>
              <th>Employee</th>
              <th>Date</th>
              <th>Time</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="allBookingsBody">
            <!-- Bookings will be loaded here -->
          </tbody>
        </table>
      </div>

      <!-- Staff Tab -->
      <div class="staff-tab tab-content" data-tab="staff" style="display:none;">
        <h2>Staff Management</h2>
        <button id="addStaffBtn" style="margin:0.5rem;">Add Staff</button>
        <table class="staff-table" id="staffTable" cellpadding="8" cellspacing="0" style="width:100%;">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Mobile</th>
              <th>Position</th>
              <th>Available</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>

        <!-- Staff Modal -->
        <div id="staffModal" style="display:none; position:fixed; top:20%; left:50%; transform:translateX(-50%); background:#fff; padding:20px; border:1px solid #ccc; z-index:1000; width:auto;">
          <form id="staffForm">
            <input type="hidden" id="staffId" />
            <label>Name:<br><input type="text" id="staffName" required /></label><br><br>
            <label>Email:<br><input type="email" id="staffEmail" required /></label><br><br>
            <label>Mobile:<br><input type="text" id="staffMobile" /></label><br><br>
            <label>Position:<br><input type="text" id="staffPosition" /></label><br><br>
            <button type="submit">Add</button>
            <button type="button" id="cancelStaffBtn">Cancel</button>
          </form>
        </div>
      </div>

<!-- Profile Tab -->
<div class="profile-tab tab-content" data-tab="profile" style="display:none; padding: 2rem; background: var(--widget-bg); border-radius: 12px; margin: 1rem 2rem; box-shadow: 0 2px 12px rgba(74, 78, 105, 0.07); overflow-y: auto; max-height: 80vh;">
  <h2>Profile</h2>
  <div class="profile-tabs">
    <button class="profile-tab-btn active" data-profile-tab="business-details">Business Details</button>
    <button class="profile-tab-btn" data-profile-tab="owner-details">Owner Details</button>
    <button class="profile-tab-btn" data-profile-tab="moderators">Moderators</button>
  </div>

  <div class="profile-tab-content active" id="business-details" style="margin-top: 1rem;">
    <form id="businessForm">
      <label>Business Name:<br><input type="text" name="business_name" required></label><br><br>
      <label>Address:<br><input type="text" name="address" required></label><br><br>
      <label>City:<br><input type="text" name="city" required></label><br><br>
      <label>State:<br><input type="text" name="state" required></label><br><br>
      <label>Mobile:<br><input type="text" name="mobile" required></label><br><br>
      <label>Working Hours:<div id="workingHoursContainer"><!-- JS will generate day rows here --></div></label><br><br>
      <label>Slot Duration (minutes):<br><input type="number" name="slot_duration" min="5" max="120" required></label><br><br>
      <label>Category:<br><input type="text" name="category" required></label><br><br>
      <button type="submit">Save Business Details</button>
    </form>
  </div>

  <div class="profile-tab-content" id="owner-details" style="display:none; margin-top: 1rem;">
    <form id="ownerForm">
      <label>Name:<br><input type="text" name="owner_name" required></label><br><br>
      <label>Email:<br><input type="email" name="owner_email" required></label><br><br>
      <label>Mobile:<br><input type="text" name="owner_mobile" required></label><br><br>
      <label>Change Password:<br><input type="password" name="owner_password" placeholder="Leave blank to keep current"></label><br><br>
      <button type="submit">Save Owner Details</button>
    </form>
  </div>

  <div class="profile-tab-content" id="moderators" style="display:none; margin-top: 1rem;">
    <button id="addModeratorBtn" style="margin-bottom: 1rem;">Add Moderator</button>
    <table id="moderatorsTable" style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th>Name</th><th>Email</th><th>Mobile</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <!-- Moderators list will be loaded here -->
      </tbody>
    </table>

    <!-- Add Moderator Modal -->
    <div id="moderatorModal" style="display:none; position:fixed; top:20%; left:50%; transform:translateX(-50%); background:#fff; padding:20px; border:1px solid #ccc; z-index:1000; width:auto;">
      <form id="moderatorForm">
        <label>Name:<br><input type="text" name="mod_name" required></label><br><br>
        <label>Email:<br><input type="email" name="mod_email" required></label><br><br>
        <label>Mobile:<br><input type="text" name="mod_mobile"></label><br><br>
        <label>Password:<br><input type="password" name="mod_password" required></label><br><br>
        <button type="submit">Add Moderator</button>
        <button type="button" id="cancelModeratorBtn">Cancel</button>
      </form>
    </div>
  </div>
</div>

  </div>
</div>
    </div> <!-- end main-content -->
  </div> <!-- end dashboard-container -->

  <script src="assets/js/dashboard.js"></script>
</body>
</html>
