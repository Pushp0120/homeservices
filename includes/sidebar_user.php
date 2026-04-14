<?php
// includes/sidebar_user.php
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <span class="sidebar-brand-icon"><i class="bi bi-house-heart-fill"></i></span>
    <span class="sidebar-brand-text"><?= APP_NAME ?></span>
  </div>
  <div class="sidebar-user">
    <div class="avatar-circle"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
    <div>
      <div class="fw-semibold text-white"><?= sanitize($_SESSION['name']) ?></div>
      <small class="text-white-50">Customer</small>
    </div>
  </div>
  <ul class="sidebar-nav">
    <li><a href="<?= APP_URL ?>/modules/user/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
    <li><a href="<?= APP_URL ?>/modules/user/browse.php" class="<?= $current === 'browse.php' ? 'active' : '' ?>"><i class="bi bi-grid-3x3-gap"></i> Browse Services</a></li>
    <li><a href="<?= APP_URL ?>/modules/user/bookings.php" class="<?= $current === 'bookings.php' ? 'active' : '' ?>"><i class="bi bi-calendar-check"></i> My Bookings</a></li>
    <li><a href="<?= APP_URL ?>/modules/user/invoices.php" class="<?= $current === 'invoices.php' ? 'active' : '' ?>"><i class="bi bi-receipt"></i> Invoices</a></li>
    <li><a href="<?= APP_URL ?>/modules/user/profile.php" class="<?= $current === 'profile.php' ? 'active' : '' ?>"><i class="bi bi-person-circle"></i> Profile</a></li>
    <li class="mt-auto"><a href="<?= APP_URL ?>/logout.php" class="text-danger-light"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
  </ul>
</nav>
