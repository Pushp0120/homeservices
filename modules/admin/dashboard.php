<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
if (currentRole() !== 'admin') redirect(APP_URL . '/modules/user/dashboard.php');
$db = getDB();
$pageTitle = 'Admin Dashboard';

$totalUsers    = $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$totalProviders = $db->query("SELECT COUNT(*) FROM providers WHERE approval_status='approved'")->fetchColumn();
$pendingProviders = $db->query("SELECT COUNT(*) FROM providers WHERE approval_status='pending'")->fetchColumn();
$totalBookings = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalRevenue  = $db->query("SELECT COALESCE(SUM(grand_total),0) FROM invoices")->fetchColumn();
$completedBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status='completed'")->fetchColumn();

// Monthly revenue for chart (last 6 months)
$monthlyRevenue = $db->query("SELECT DATE_FORMAT(generated_at,'%b %Y') as month, SUM(grand_total) as revenue
    FROM invoices WHERE generated_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(generated_at), MONTH(generated_at) ORDER BY generated_at")->fetchAll();

// Top providers
$topProviders = $db->query("SELECT p.business_name, u.name, COUNT(b.id) as jobs,
    ROUND(AVG(r.rating),1) as avg_rating, COALESCE(SUM(inv.grand_total),0) as revenue
    FROM providers p JOIN users u ON p.user_id=u.id
    LEFT JOIN bookings b ON b.provider_id=p.id AND b.status='completed'
    LEFT JOIN reviews r ON r.provider_id=p.id
    LEFT JOIN invoices inv ON inv.booking_id=b.id
    WHERE p.approval_status='approved'
    GROUP BY p.id ORDER BY revenue DESC LIMIT 5")->fetchAll();

// Recent bookings
$recentBookings = $db->query("SELECT b.*, uc.name as cust_name, p.business_name
    FROM bookings b JOIN users uc ON b.customer_id=uc.id JOIN providers p ON b.provider_id=p.id
    ORDER BY b.created_at DESC LIMIT 6")->fetchAll();
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<div class="dashboard-wrapper">
<div class="overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle"><i class="bi bi-list fs-5"></i></button>
      <div class="topbar-title">Admin Dashboard</div>
    </div>
    <span class="text-muted small d-none d-sm-inline">Administrator</span>
  </div>
  <div class="page-content">
    <div class="page-hero mb-4" style="background:linear-gradient(135deg,#1e1b4b,#4338ca)">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
          <h4 class="mb-1 fw-700">System Overview 📊</h4>
          <p class="mb-0 opacity-75">Manage the entire HomeServe platform from here.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a href="<?= APP_URL ?>/modules/admin/providers.php?filter=pending" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);font-size:.8rem">
            <i class="bi bi-hourglass-split me-1"></i> Pending Providers <?php if($pendingProviders>0): ?><span class="badge" style="background:#fbbf24;color:#1f2937"><?= $pendingProviders ?></span><?php endif; ?>
          </a>
          <a href="<?= APP_URL ?>/modules/admin/bookings.php" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);font-size:.8rem">
            <i class="bi bi-calendar-check me-1"></i> All Bookings
          </a>
          <a href="<?= APP_URL ?>/modules/admin/reports.php" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);font-size:.8rem">
            <i class="bi bi-bar-chart me-1"></i> Reports
          </a>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <?php $sts = [['Total Customers','stat-bg-blue','people-fill',$totalUsers],['Approved Providers','stat-bg-green','person-badge-fill',$totalProviders],['Pending Approval','stat-bg-yellow','hourglass-split',$pendingProviders],['Total Bookings','stat-bg-purple','calendar-check-fill',$totalBookings],['Completed','stat-bg-cyan','check-all',$completedBookings],['Total Revenue','stat-bg-red','cash-stack',formatCurrency($totalRevenue)]]; ?>
      <?php foreach ($sts as [$label,$bg,$icon,$val]): ?>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
          <div class="stat-icon <?= $bg ?>"><i class="bi bi-<?= $icon ?>"></i></div>
          <div><div class="stat-value" style="font-size:1.3rem"><?= $val ?></div><div class="stat-label"><?= $label ?></div></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($pendingProviders > 0): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <div><strong><?= $pendingProviders ?></strong> provider(s) awaiting approval.
        <a href="<?= APP_URL ?>/modules/admin/providers.php?filter=pending" class="alert-link">Review now →</a>
      </div>
    </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
      <!-- Revenue Chart -->
      <div class="col-md-8">
        <div class="card h-100">
          <div class="card-header"><span class="card-title">Monthly Revenue (Last 6 Months)</span></div>
          <div class="card-body"><canvas id="revenueChart" height="100"></canvas></div>
        </div>
      </div>
      <!-- Top providers -->
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-header"><span class="card-title">Top Providers</span></div>
          <div class="card-body p-0">
            <?php foreach ($topProviders as $i => $p): ?>
            <div class="d-flex align-items-center gap-3 p-3 <?= $i < count($topProviders)-1 ? 'border-bottom' : '' ?>">
              <div class="fw-700 text-muted" style="width:20px"><?= $i+1 ?></div>
              <div class="avatar-circle" style="width:36px;height:36px;font-size:.9rem"><?= strtoupper(substr($p['business_name'],0,1)) ?></div>
              <div class="flex-fill">
                <div class="fw-600 small"><?= sanitize($p['business_name']) ?></div>
                <div class="text-muted" style="font-size:.75rem"><?= $p['jobs'] ?> jobs &bull; <?= $p['avg_rating'] ?? 0 ?>★</div>
              </div>
              <div class="text-primary fw-700 small"><?= formatCurrency($p['revenue']) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Bookings -->
    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <span class="card-title">Recent Bookings</span>
        <a href="<?= APP_URL ?>/modules/admin/bookings.php" class="btn btn-sm btn-outline-danger">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>#</th><th>Customer</th><th>Provider</th><th>Date</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($recentBookings as $b): ?>
          <tr>
            <td class="fw-600">#<?= $b['id'] ?></td>
            <td><?= sanitize($b['cust_name']) ?></td>
            <td><?= sanitize($b['business_name']) ?></td>
            <td><?= formatDate($b['scheduled_date']) ?></td>
            <td><?= statusBadge($b['status']) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($monthlyRevenue, 'month')) ?>,
    datasets: [{
      label: 'Revenue (₹)',
      data: <?= json_encode(array_column($monthlyRevenue, 'revenue')) ?>,
      backgroundColor: 'rgba(37,99,235,.8)',
      borderRadius: 8,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, grid: { color: '#f1f5f9' },
           ticks: { callback: v => '₹' + Number(v).toLocaleString('en-IN') }},
      x: { grid: { display: false }}
    }
  }
});
</script>
