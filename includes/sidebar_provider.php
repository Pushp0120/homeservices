<?php
// includes/sidebar_provider.php
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar sidebar-provider" id="sidebar">
  <div class="sidebar-brand">
    <span class="sidebar-brand-icon"><i class="bi bi-house-heart-fill"></i></span>
    <span class="sidebar-brand-text"><?= APP_NAME ?></span>
  </div>
  <div class="sidebar-user">
    <?php
    $sidebarPhoto = null;
    if (currentProviderId()) {
        $spStmt = getDB()->prepare("SELECT profile_photo FROM providers WHERE id=?");
        $spStmt->execute([currentProviderId()]);
        $sidebarPhoto = $spStmt->fetchColumn();
    }
    ?>
    <?php if ($sidebarPhoto): ?>
    <img src="<?= APP_URL ?>/uploads/providers/<?= htmlspecialchars($sidebarPhoto) ?>" alt="Photo"
         style="width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid rgba(255,255,255,.2)">
    <?php else: ?>
    <div class="avatar-circle avatar-provider"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
    <?php endif; ?>
    <div>
      <div class="fw-semibold text-white"><?= sanitize($_SESSION['name']) ?></div>
      <small class="text-white-50">Service Provider</small>
    </div>
  </div>
  <ul class="sidebar-nav">
    <li><a href="<?= APP_URL ?>/modules/provider/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
    <li><a href="<?= APP_URL ?>/modules/provider/bookings.php" class="<?= $current === 'bookings.php' ? 'active' : '' ?>"><i class="bi bi-calendar3"></i> Bookings</a></li>
    <li><a href="<?= APP_URL ?>/modules/provider/billing.php" class="<?= $current === 'billing.php' ? 'active' : '' ?>"><i class="bi bi-cash-coin"></i> Billing</a></li>
    <li><a href="<?= APP_URL ?>/modules/provider/reviews.php" class="<?= $current === 'reviews.php' ? 'active' : '' ?>"><i class="bi bi-star-half"></i> Reviews</a></li>
    <li><a href="<?= APP_URL ?>/modules/provider/profile.php" class="<?= $current === 'profile.php' ? 'active' : '' ?>"><i class="bi bi-person-badge"></i> Profile</a></li>
    <li class="mt-auto"><a href="<?= APP_URL ?>/logout.php" class="text-danger-light"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
  </ul>
</nav>
