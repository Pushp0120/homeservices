<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
refreshProviderSession();
if (currentRole() === 'admin')    redirect(APP_URL . '/modules/admin/dashboard.php');
if (currentRole() === 'provider') redirect(APP_URL . '/modules/provider/dashboard.php');
$providerStatus = getPendingProviderStatus();

$db  = getDB();
$uid = currentUserId();
$pageTitle = 'My Dashboard';

$stats = $db->prepare("SELECT
  COUNT(*) as total,
  SUM(status='pending') as pending,
  SUM(status='completed') as completed,
  SUM(status='cancelled') as cancelled
  FROM bookings WHERE customer_id=?");
$stats->execute([$uid]);
$stat = $stats->fetch();

$recent = $db->prepare("SELECT b.*, u.name as provider_name, p.business_name, c.name as category
  FROM bookings b
  JOIN providers p ON b.provider_id=p.id
  JOIN users u ON p.user_id=u.id
  JOIN categories c ON p.category_id=c.id
  WHERE b.customer_id=? ORDER BY b.created_at DESC LIMIT 5");
$recent->execute([$uid]);
$recentBookings = $recent->fetchAll();

// Next upcoming booking
$nextBooking = $db->prepare("SELECT b.*, p.business_name, c.name as category
  FROM bookings b
  JOIN providers p ON b.provider_id=p.id
  JOIN categories c ON p.category_id=c.id
  WHERE b.customer_id=? AND b.status='pending' AND b.scheduled_date >= CURDATE()
  ORDER BY b.scheduled_date ASC LIMIT 1");
$nextBooking->execute([$uid]);
$upcoming = $nextBooking->fetch();

// Time-based greeting
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$greetEmoji = $hour < 12 ? '☀️' : ($hour < 17 ? '👋' : '🌙');

// Total spend
try {
    $spendStmt = $db->prepare("SELECT COALESCE(SUM(inv.grand_total),0) as total_spend FROM invoices inv JOIN bookings b ON inv.booking_id=b.id WHERE b.customer_id=?");
    $spendStmt->execute([$uid]);
    $totalSpend = $spendStmt->fetchColumn();
} catch(Exception $e) { $totalSpend = 0; }
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/topnav_user.php'; ?>

<style>
/* ── Dashboard Redesign ── */
.dash-wrap { padding: 0 1.25rem 3rem; }

/* Hero Banner */
.dash-hero {
  border-radius: 20px;
  padding: 2.5rem 2.25rem;
  margin-bottom: 2rem;
  position: relative;
  overflow: hidden;
  background: linear-gradient(130deg, var(--primary) 0%, var(--primary-dark, #1a6a5e) 100%);
  color: #fff;
}
.dash-hero::before {
  content: '';
  position: absolute;
  right: -60px; top: -60px;
  width: 280px; height: 280px;
  border-radius: 50%;
  background: rgba(255,255,255,0.07);
}
.dash-hero::after {
  content: '';
  position: absolute;
  right: 80px; bottom: -80px;
  width: 200px; height: 200px;
  border-radius: 50%;
  background: rgba(255,255,255,0.05);
}
.dash-hero h3 { font-size: 1.75rem; font-weight: 800; margin-bottom: .3rem; }
.dash-hero p  { opacity: .85; margin-bottom: 0; font-size: .95rem; }
.hero-search {
  display: flex;
  gap: .5rem;
  margin-top: 1.5rem;
  max-width: 500px;
}
.hero-search input {
  flex: 1;
  border: none;
  border-radius: 10px;
  padding: .65rem 1rem;
  font-size: .92rem;
  outline: none;
  background: rgba(255,255,255,0.18);
  color: #fff;
  backdrop-filter: blur(4px);
}
.hero-search input::placeholder { color: rgba(255,255,255,0.65); }
.hero-search button {
  border: none;
  border-radius: 10px;
  padding: .65rem 1.4rem;
  background: #fff;
  color: var(--primary);
  font-weight: 700;
  font-size: .9rem;
  cursor: pointer;
  white-space: nowrap;
  transition: opacity .15s;
}
.hero-search button:hover { opacity: .88; }

/* Alert banners */
.dash-alert {
  display: flex;
  align-items: flex-start;
  gap: .9rem;
  border-radius: 14px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.5rem;
}
.dash-alert.pending  { background: #fffbeb; border: 1.5px solid #fcd34d; }
.dash-alert.suspended{ background: #fef2f2; border: 1.5px solid #fca5a5; }
.dash-alert .alert-icon { font-size: 1.4rem; line-height: 1; margin-top: 2px; }
.dash-alert .alert-title { font-weight: 700; margin-bottom: .2rem; font-size: .95rem; }
.dash-alert.pending   .alert-title  { color: #92400e; }
.dash-alert.suspended .alert-title  { color: #991b1b; }
.dash-alert.pending   .alert-body   { color: #78350f; font-size: .84rem; }
.dash-alert.suspended .alert-body   { color: #7f1d1d; font-size: .84rem; }

/* Two-column layout below hero */
.dash-grid { display: grid; grid-template-columns: 1fr 300px; gap: 1.5rem; margin-bottom: 1.75rem; }
@media(max-width:860px){ .dash-grid{ grid-template-columns:1fr; } }

/* Stat cards */
.stat-row { display: grid; grid-template-columns: repeat(4,1fr); gap: .85rem; margin-bottom: 1.75rem; }
@media(max-width:700px){ .stat-row{ grid-template-columns:repeat(2,1fr); } }
.stat-card-v2 {
  background: #fff;
  border-radius: 14px;
  padding: 1.1rem 1.1rem;
  display: flex;
  align-items: center;
  gap: .85rem;
  box-shadow: 0 1px 6px rgba(0,0,0,.06);
  border: 1px solid #f0f0f0;
  transition: transform .15s, box-shadow .15s;
}
.stat-card-v2:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,.09); }
.stat-icon-v2 {
  width: 44px; height: 44px;
  border-radius: 11px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.15rem;
  flex-shrink: 0;
}
.si-blue   { background: #eff6ff; color: #3b82f6; }
.si-yellow { background: #fffbeb; color: #d97706; }
.si-green  { background: #f0fdf4; color: #16a34a; }
.si-red    { background: #fef2f2; color: #dc2626; }
.stat-val  { font-size: 1.45rem; font-weight: 800; line-height: 1; }
.stat-lbl  { font-size: .76rem; color: #6b7280; margin-top: 2px; font-weight: 500; }

/* Upcoming booking card */
.upcoming-card {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #eee;
  box-shadow: 0 1px 6px rgba(0,0,0,.05);
  overflow: hidden;
}
.upcoming-card .uc-head {
  padding: .85rem 1.1rem;
  font-weight: 700;
  font-size: .93rem;
  border-bottom: 1px solid #f3f3f3;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.upcoming-card .uc-body { padding: 1.1rem; }
.uc-badge {
  font-size: .7rem;
  font-weight: 700;
  letter-spacing: .05em;
  text-transform: uppercase;
  background: #d1fae5;
  color: #065f46;
  border-radius: 6px;
  padding: 2px 8px;
}
.uc-title  { font-size: 1.05rem; font-weight: 700; margin: .4rem 0 .2rem; }
.uc-meta   { font-size: .83rem; color: #6b7280; display: flex; align-items: center; gap: .35rem; margin-bottom: 1rem; }
.uc-actions{ display: flex; gap: .5rem; flex-wrap: wrap; }
.uc-empty  { text-align: center; padding: 2rem 1rem; color: #9ca3af; font-size: .88rem; }
.uc-empty i{ display: block; font-size: 2rem; margin-bottom: .5rem; }

/* Quick actions panel */
.qa-panel {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #eee;
  box-shadow: 0 1px 6px rgba(0,0,0,.05);
  padding: 1rem;
}
.qa-panel .qa-head { font-weight: 700; font-size: .93rem; margin-bottom: .85rem; }
.qa-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
.qa-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: .4rem;
  background: #f9fafb;
  border: 1px solid #ebebeb;
  border-radius: 12px;
  padding: .9rem .5rem;
  text-decoration: none;
  color: #374151;
  font-size: .8rem;
  font-weight: 600;
  transition: background .15s, border-color .15s;
  cursor: pointer;
}
.qa-btn i { font-size: 1.25rem; color: var(--primary); }
.qa-btn:hover { background: var(--primary-light, #e6f7f5); border-color: var(--primary); color: var(--primary); }

/* Recent bookings table card */
.bookings-card {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #eee;
  box-shadow: 0 1px 6px rgba(0,0,0,.05);
  overflow: hidden;
}
.bookings-card .bc-head {
  padding: .9rem 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #f3f3f3;
}
.bookings-card .bc-title { font-weight: 700; font-size: .95rem; }
.table-v2 { width: 100%; border-collapse: collapse; font-size: .88rem; }
.table-v2 thead th {
  padding: .65rem 1.25rem;
  text-align: left;
  font-size: .75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: #9ca3af;
  background: #fafafa;
  border-bottom: 1px solid #f0f0f0;
}
.table-v2 tbody td {
  padding: .8rem 1.25rem;
  border-bottom: 1px solid #f7f7f7;
  vertical-align: middle;
  color: #374151;
}
.table-v2 tbody tr:last-child td { border-bottom: none; }
.table-v2 tbody tr:hover td { background: #fafafa; }
.booking-id { font-weight: 700; color: #111; }
.empty-row td { text-align: center; padding: 2.5rem !important; color: #9ca3af; }
</style>

<div class="user-main-content">
  <div class="page-content dash-wrap">

    <!-- ── HERO ── -->
    <div class="dash-hero" style="background:linear-gradient(130deg,#1e40af 0%,#2563eb 60%,#7c3aed 100%)">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
          <div style="font-size:.82rem;font-weight:600;opacity:.75;margin-bottom:.25rem;letter-spacing:.5px"><?= $greeting ?> <?= $greetEmoji ?></div>
          <h3>Welcome back, <?= sanitize(explode(' ', $_SESSION['name'])[0]) ?>!</h3>
          <p>Find and book trusted service professionals in seconds.</p>
        </div>
        <div class="d-none d-md-flex align-items-center gap-2" style="text-align:right">
          <div style="background:rgba(255,255,255,.12);border-radius:14px;padding:.85rem 1.2rem">
            <div style="font-size:.72rem;opacity:.7;margin-bottom:.2rem">Total Spent</div>
            <div style="font-size:1.3rem;font-weight:800">₹<?= number_format($totalSpend, 0) ?></div>
          </div>
        </div>
      </div>
      <form class="hero-search" action="<?= APP_URL ?>/modules/user/browse.php" method="GET">
        <input type="text" name="q" placeholder="Search services (cleaning, plumbing…)">
        <button type="submit"><i class="bi bi-search me-1"></i> Search</button>
      </form>
    </div>

    <!-- ── ALERTS ── -->
    <?php if ($providerStatus === 'pending'): ?>
    <div class="dash-alert pending">
      <span class="alert-icon">⏳</span>
      <div>
        <div class="alert-title">Provider Application Under Review</div>
        <div class="alert-body">Your provider profile is pending admin approval. You can browse and book services as a customer in the meantime. You'll be redirected automatically once approved.</div>
      </div>
    </div>
    <?php elseif ($providerStatus === 'suspended'): ?>
    <div class="dash-alert suspended">
      <span class="alert-icon">⚠️</span>
      <div>
        <div class="alert-title">Provider Account Suspended</div>
        <div class="alert-body">Your provider profile has been suspended by the admin. Please contact support for more information.</div>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── STAT CARDS ── -->
    <div class="stat-row">
      <div class="stat-card-v2">
        <div class="stat-icon-v2 si-blue"><i class="bi bi-calendar3"></i></div>
        <div><div class="stat-val"><?= $stat['total'] ?></div><div class="stat-lbl">Total Bookings</div></div>
      </div>
      <div class="stat-card-v2">
        <div class="stat-icon-v2 si-yellow"><i class="bi bi-clock"></i></div>
        <div><div class="stat-val"><?= $stat['pending'] ?></div><div class="stat-lbl">Pending</div></div>
      </div>
      <div class="stat-card-v2">
        <div class="stat-icon-v2 si-green"><i class="bi bi-check-circle"></i></div>
        <div><div class="stat-val"><?= $stat['completed'] ?></div><div class="stat-lbl">Completed</div></div>
      </div>
      <div class="stat-card-v2">
        <div class="stat-icon-v2 si-red"><i class="bi bi-x-circle"></i></div>
        <div><div class="stat-val"><?= $stat['cancelled'] ?></div><div class="stat-lbl">Cancelled</div></div>
      </div>
    </div>

    <!-- ── UPCOMING + QUICK ACTIONS ── -->
    <div class="dash-grid">

      <!-- Next Booking -->
      <div class="upcoming-card">
        <div class="uc-head">
          <span>Your Next Booking</span>
          <a href="<?= APP_URL ?>/modules/user/bookings.php" style="font-size:.8rem;color:var(--primary);font-weight:600;text-decoration:none">View all →</a>
        </div>
        <div class="uc-body">
          <?php if ($upcoming): ?>
            <span class="uc-badge">Upcoming</span>
            <div class="uc-title"><?= sanitize($upcoming['business_name']) ?></div>
            <div class="uc-meta">
              <i class="bi bi-calendar-event"></i>
              <?= formatDate($upcoming['scheduled_date']) ?>
              &nbsp;·&nbsp;
              <i class="bi bi-tag"></i>
              <?= sanitize($upcoming['category']) ?>
            </div>
            <?php if (!empty($upcoming['scheduled_time'])): ?>
            <div class="uc-meta" style="margin-top:-.5rem">
              <i class="bi bi-clock"></i> <?= htmlspecialchars($upcoming['scheduled_time']) ?>
            </div>
            <?php endif; ?>
            <div class="uc-actions">
              <a href="<?= APP_URL ?>/modules/user/booking_detail.php?id=<?= $upcoming['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
              <a href="<?= APP_URL ?>/modules/user/bookings.php" class="btn btn-sm btn-outline-secondary">All Bookings</a>
            </div>
          <?php else: ?>
            <div class="uc-empty">
              <i class="bi bi-calendar-x"></i>
              No upcoming bookings.<br>
              <a href="<?= APP_URL ?>/modules/user/browse.php" style="color:var(--primary);font-weight:600">Book a service →</a>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="qa-panel">
        <div class="qa-head">Quick Actions</div>
        <div class="qa-grid">
          <a class="qa-btn" href="<?= APP_URL ?>/modules/user/browse.php">
            <i class="bi bi-search"></i>Browse
          </a>
          <a class="qa-btn" href="<?= APP_URL ?>/modules/user/bookings.php">
            <i class="bi bi-journal-bookmark"></i>Bookings
          </a>
          <a class="qa-btn" href="<?= APP_URL ?>/modules/user/invoices.php">
            <i class="bi bi-receipt"></i>Invoices
          </a>
          <a class="qa-btn" href="<?= APP_URL ?>/modules/user/profile.php">
            <i class="bi bi-person-circle"></i>Profile
          </a>
          <a class="qa-btn" href="<?= APP_URL ?>/modules/user/support.php" style="grid-column:span 2">
            <i class="bi bi-headset"></i>Get Support
          </a>
        </div>
      </div>

    </div><!-- /.dash-grid -->

    <!-- ── RECENT BOOKINGS TABLE ── -->
    <div class="bookings-card">
      <div class="bc-head">
        <span class="bc-title">Recent Bookings</span>
        <a href="<?= APP_URL ?>/modules/user/bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table-v2">
          <thead>
            <tr>
              <th>#</th>
              <th>Provider</th>
              <th>Category</th>
              <th>Date</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentBookings)): ?>
            <tr class="empty-row">
              <td colspan="6">
                <i class="bi bi-calendar3" style="font-size:1.6rem;color:#d1d5db;display:block;margin-bottom:.5rem"></i>
                No bookings yet. <a href="<?= APP_URL ?>/modules/user/browse.php" style="color:var(--primary);font-weight:600">Book a service</a>
              </td>
            </tr>
            <?php else: foreach ($recentBookings as $b): ?>
            <tr>
              <td><span class="booking-id">#<?= $b['id'] ?></span></td>
              <td><?= sanitize($b['business_name']) ?></td>
              <td><?= sanitize($b['category']) ?></td>
              <td><?= formatDate($b['scheduled_date']) ?></td>
              <td><?= statusBadge($b['status']) ?></td>
              <td style="display:flex;gap:.4rem;flex-wrap:wrap">
                <a href="<?= APP_URL ?>/modules/user/booking_detail.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                <?php if ($b['status'] === 'completed'): ?>
                <?php $chk = $db->prepare("SELECT id FROM reviews WHERE booking_id=?"); $chk->execute([$b['id']]); ?>
                <?php if (!$chk->fetch()): ?>
                <a href="<?= APP_URL ?>/modules/user/review.php?booking_id=<?= $b['id'] ?>" class="btn btn-sm btn-warning">Rate</a>
                <?php endif; ?>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- /.dash-wrap -->
</div><!-- /.user-main-content -->

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>