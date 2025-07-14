<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Tab Test</title>
<style>
  .tab-content { display: none; }
  .tab-content.active { display: block; }
  nav a.active { font-weight: bold; }
</style>
</head>
<body>
<nav>
  <a href="#" data-tab="dashboard" class="active">Dashboard</a>
  <a href="#" data-tab="bookings">Bookings</a>
</nav>

<div class="tab-content active" data-tab="dashboard">
  <h2>Dashboard Content</h2>
  <p>Welcome to dashboard.</p>
</div>
<div class="tab-content" data-tab="bookings">
  <h2>Bookings Content</h2>
  <p>Here are all bookings.</p>
</div>

<script>
document.querySelectorAll('nav a').forEach(tab => {
  tab.addEventListener('click', e => {
    e.preventDefault();
    document.querySelectorAll('nav a').forEach(a => a.classList.remove('active'));
    tab.classList.add('active');
    document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
    const tabName = tab.getAttribute('data-tab');
    const content = document.querySelector('.tab-content[data-tab="' + tabName + '"]');
    if (content) content.classList.add('active');
  });
});
</script>
</body>
</html>