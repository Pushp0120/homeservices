<?php
// includes/topnav_user.php — Top navigation bar for customer pages
$current = basename($_SERVER['PHP_SELF']);
$navLinks = [
  ['dashboard.php',  'bi-speedometer2',     'Dashboard'],
  ['browse.php',     'bi-grid-3x3-gap',     'Browse'],
  ['bookings.php',   'bi-calendar-check',   'My Bookings'],
  ['invoices.php',   'bi-receipt',          'Invoices'],
  ['support.php',    'bi-headset',          'Support'],
];
?>
<style>
/* ── USER TOPNAV ─────────────────────────────────────────── */
.user-topnav {
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  position: sticky; top: 0; z-index: 1000;
  box-shadow: 0 1px 8px rgba(0,0,0,.06);
}
.user-topnav-inner {
  display: flex; align-items: center;
  padding: 0 1.5rem; height: 64px; gap: 0;
  max-width: 100%;
}
.topnav-brand {
  display: flex; align-items: center; gap: .6rem;
  text-decoration: none; font-size: 1.1rem; font-weight: 800;
  color: #0f172a; letter-spacing: -.5px; flex-shrink: 0;
  margin-right: 2rem;
}
.topnav-brand i { color: #2563eb; font-size: 1.3rem; }

.topnav-links {
  display: flex; align-items: center; gap: .25rem;
  flex: 1; list-style: none; margin: 0; padding: 0;
}
.topnav-links a {
  display: flex; align-items: center; gap: .45rem;
  padding: .5rem .9rem; border-radius: 10px;
  text-decoration: none; font-size: .875rem; font-weight: 500;
  color: #64748b; transition: all .18s ease; white-space: nowrap;
}
.topnav-links a:hover { background: #f1f5f9; color: #1e293b; }
.topnav-links a.active {
  background: #eff6ff; color: #2563eb; font-weight: 700;
}
.topnav-links a.active i { color: #2563eb; }
.topnav-links a i { font-size: 1rem; }

.topnav-right {
  display: flex; align-items: center; gap: .75rem; flex-shrink: 0; margin-left: auto;
}
.topnav-new-booking {
  display: flex; align-items: center; gap: .4rem;
  background: #2563eb; color: #fff;
  padding: .45rem 1rem; border-radius: 10px;
  font-size: .8rem; font-weight: 700; text-decoration: none;
  transition: all .2s ease; white-space: nowrap;
}
.topnav-new-booking:hover { background: #1d4ed8; color: #fff; transform: translateY(-1px); }

/* Profile dropdown */
.topnav-profile { position: relative; }
.topnav-avatar {
  width: 38px; height: 38px; border-radius: 50%;
  background: linear-gradient(135deg, #2563eb, #7c3aed);
  color: #fff; font-weight: 700; font-size: .95rem;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: transform .2s ease; flex-shrink: 0;
  border: 2px solid #e2e8f0;
}
.topnav-avatar:hover { transform: scale(1.08); }

.topnav-dropdown {
  position: absolute; right: 0; top: calc(100% + .75rem);
  background: #fff; border: 1px solid #e2e8f0;
  border-radius: 16px; box-shadow: 0 16px 48px rgba(0,0,0,.15);
  width: 260px; overflow: hidden;
  display: none; animation: dropIn .25s cubic-bezier(.34,1.56,.64,1);
  z-index: 2000;
}
@keyframes dropIn {
  from { opacity: 0; transform: translateY(-10px) scale(.96); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}
.topnav-dropdown.show { display: block; }
.topnav-dropdown-header {
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, #eff6ff, #f5f3ff);
  border-bottom: 1px solid #e2e8f0;
}
.topnav-dropdown-name { font-weight: 700; color: #0f172a; font-size: .95rem; }
.topnav-dropdown-email { font-size: .78rem; color: #64748b; }
.topnav-dropdown-items { padding: .5rem; }
.topnav-dropdown-item {
  display: flex; align-items: center; gap: .75rem;
  padding: .6rem .875rem; border-radius: 10px;
  text-decoration: none; color: #374151; font-size: .875rem;
  transition: background .15s; cursor: pointer;
}
.topnav-dropdown-item:hover { background: #f8fafc; color: #1e293b; }
.topnav-dropdown-item i { width: 18px; text-align: center; color: #64748b; }
.topnav-dropdown-item.danger { color: #dc2626; }
.topnav-dropdown-item.danger:hover { background: #fef2f2; }
.topnav-dropdown-item.danger i { color: #dc2626; }
.topnav-dropdown-divider { height: 1px; background: #f1f5f9; margin: .25rem .5rem; }

/* Mobile hamburger */
.topnav-hamburger {
  display: none; background: none; border: none;
  font-size: 1.4rem; color: #374151; cursor: pointer; padding: .25rem;
}
.topnav-mobile-menu {
  display: none; background: #fff;
  border-top: 1px solid #f1f5f9; padding: .75rem 1rem;
}
.topnav-mobile-menu.show { display: block; }
.topnav-mobile-menu a {
  display: flex; align-items: center; gap: .75rem;
  padding: .65rem .875rem; border-radius: 10px;
  text-decoration: none; font-size: .9rem; color: #374151;
  transition: background .15s;
}
.topnav-mobile-menu a:hover, .topnav-mobile-menu a.active {
  background: #eff6ff; color: #2563eb;
}

@media (max-width: 768px) {
  .topnav-links { display: none; }
  .topnav-hamburger { display: block; }
  .topnav-new-booking span { display: none; }
  .topnav-new-booking { padding: .45rem .6rem; }
}
</style>

<div class="user-topnav">
  <div class="user-topnav-inner">
    <!-- Brand -->
   <a href="<?= APP_URL ?>/modules/user/dashboard.php" class="topnav-brand">
  <img src="<?= APP_URL ?>/assets/images/logo.svg" alt="HomeServe" height="38">
</a>

    <!-- Nav Links -->
    <ul class="topnav-links">
      <?php foreach ($navLinks as [$page, $icon, $label]): ?>
      <li>
        <a href="<?= APP_URL ?>/modules/user/<?= $page ?>" class="<?= $current === $page ? 'active' : '' ?>">
          <i class="bi <?= $icon ?>"></i> <?= $label ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>

    <!-- Right side -->
    <div class="topnav-right">
      <?php
      // Show pending bookings notification badge
      $pendingCount = 0;
      if (isLoggedIn()) {
          $pendingStmt = getDB()->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id=? AND status='pending'");
          $pendingStmt->execute([currentUserId()]);
          $pendingCount = (int)$pendingStmt->fetchColumn();
      }
      ?>
      <!-- Notification bell -->
      <a href="<?= APP_URL ?>/modules/user/bookings.php?status=pending" class="position-relative text-muted" style="text-decoration:none;font-size:1.2rem" title="Pending Bookings">
        <i class="bi bi-bell"></i>
        <?php if ($pendingCount > 0): ?>
        <span style="position:absolute;top:-4px;right:-4px;background:#dc2626;color:#fff;width:16px;height:16px;border-radius:50%;font-size:.6rem;font-weight:800;display:flex;align-items:center;justify-content:center"><?= $pendingCount ?></span>
        <?php endif; ?>
      </a>

      <a href="<?= APP_URL ?>/modules/user/browse.php" class="topnav-new-booking">
        <i class="bi bi-plus-circle-fill"></i> <span>New Booking</span>
      </a>

      <!-- Profile Avatar + Dropdown -->
      <div class="topnav-profile">
        <div class="topnav-avatar" id="topnavAvatarBtn" onclick="toggleProfileDropdown()">
          <?= strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="topnav-dropdown" id="topnavDropdown">
          <div class="topnav-dropdown-header">
            <div class="topnav-dropdown-name"><?= sanitize($_SESSION['name'] ?? '') ?></div>
            <div class="topnav-dropdown-email"><?= sanitize($_SESSION['email'] ?? '') ?></div>
            <div style="margin-top:.4rem"><span class="badge bg-primary" style="font-size:.7rem">Customer</span></div>
          </div>
          <div class="topnav-dropdown-items">
            <a href="<?= APP_URL ?>/modules/user/profile.php" class="topnav-dropdown-item">
              <i class="bi bi-person-circle"></i> My Profile
            </a>
            <a href="<?= APP_URL ?>/modules/user/profile.php?tab=settings" class="topnav-dropdown-item">
              <i class="bi bi-gear"></i> Account Settings
            </a>
            <a href="<?= APP_URL ?>/modules/user/bookings.php" class="topnav-dropdown-item">
              <i class="bi bi-calendar-check"></i> My Bookings
            </a>
            <a href="<?= APP_URL ?>/modules/user/invoices.php" class="topnav-dropdown-item">
              <i class="bi bi-receipt"></i> Invoices
            </a>
            <div class="topnav-dropdown-divider"></div>
            <a href="<?= APP_URL ?>/logout.php" class="topnav-dropdown-item danger">
              <i class="bi bi-box-arrow-left"></i> Logout
            </a>
          </div>
        </div>
      </div>

      <!-- Mobile hamburger -->
      <button class="topnav-hamburger" id="mobileMenuBtn" onclick="toggleMobileMenu()">
        <i class="bi bi-list"></i>
      </button>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div class="topnav-mobile-menu" id="mobileMenu">
    <?php foreach ($navLinks as [$page, $icon, $label]): ?>
    <a href="<?= APP_URL ?>/modules/user/<?= $page ?>" class="<?= $current === $page ? 'active' : '' ?>">
      <i class="bi <?= $icon ?>"></i> <?= $label ?>
    </a>
    <?php endforeach; ?>
    <a href="<?= APP_URL ?>/modules/user/profile.php">
      <i class="bi bi-gear"></i> Settings
    </a>
    <a href="<?= APP_URL ?>/logout.php" style="color:#dc2626">
      <i class="bi bi-box-arrow-left"></i> Logout
    </a>
  </div>
</div>

<script>
function toggleProfileDropdown() {
  document.getElementById('topnavDropdown').classList.toggle('show');
}
function toggleMobileMenu() {
  const m = document.getElementById('mobileMenu');
  m.classList.toggle('show');
  document.getElementById('mobileMenuBtn').querySelector('i').className =
    m.classList.contains('show') ? 'bi bi-x-lg' : 'bi bi-list';
}
document.addEventListener('click', function(e) {
  if (!e.target.closest('.topnav-profile')) {
    document.getElementById('topnavDropdown')?.classList.remove('show');
  }
});
// Close mobile menu when a link is tapped
document.querySelectorAll('.topnav-mobile-menu a').forEach(function(link) {
  link.addEventListener('click', function() {
    document.getElementById('mobileMenu').classList.remove('show');
    document.getElementById('mobileMenuBtn').querySelector('i').className = 'bi bi-list';
  });
});
</script>
