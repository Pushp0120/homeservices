<?php
// includes/sidebar_admin.php
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar sidebar-admin" id="sidebar">
  <div class="sidebar-brand">
    <span class="sidebar-brand-icon"><i class="bi bi-house-heart-fill"></i></span>
    <span class="sidebar-brand-text"><?= APP_NAME ?></span>
  </div>
  <div class="sidebar-user">
    <div class="avatar-circle avatar-admin"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
    <div>
      <div class="fw-semibold text-white"><?= sanitize($_SESSION['name']) ?></div>
      <small class="text-white-50">Administrator</small>
    </div>
  </div>
  <ul class="sidebar-nav">
    <li><a href="<?= APP_URL ?>/modules/admin/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
    <li><a href="<?= APP_URL ?>/modules/admin/users.php" class="<?= $current === 'users.php' ? 'active' : '' ?>"><i class="bi bi-people-fill"></i> Users</a></li>
    <li><a href="<?= APP_URL ?>/modules/admin/providers.php" class="<?= $current === 'providers.php' ? 'active' : '' ?>"><i class="bi bi-person-badge-fill"></i> Providers</a></li>
    <li><a href="<?= APP_URL ?>/modules/admin/categories.php" class="<?= $current === 'categories.php' ? 'active' : '' ?>"><i class="bi bi-tags-fill"></i> Categories</a></li>
    <li><a href="<?= APP_URL ?>/modules/admin/services.php" class="<?= $current === 'services.php' ? 'active' : '' ?>"><i class="bi bi-tools"></i> Services</a></li>
    <li><a href="<?= APP_URL ?>/modules/admin/bookings.php" class="<?= $current === 'bookings.php' ? 'active' : '' ?>"><i class="bi bi-calendar-check-fill"></i> Bookings</a></li>
    <li><a href="<?= APP_URL ?>/modules/admin/payments.php" class="<?= $current === 'payments.php' ? 'active' : '' ?>"><i class="bi bi-cash-coin"></i> Payments</a></li>
    <li><a href="<?= APP_URL ?>/modules/admin/reports.php" class="<?= $current === 'reports.php' ? 'active' : '' ?>"><i class="bi bi-bar-chart-fill"></i> Reports</a></li>
    <li class="mt-auto"><a href="<?= APP_URL ?>/logout.php" class="text-danger-light"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
  </ul>
</nav>