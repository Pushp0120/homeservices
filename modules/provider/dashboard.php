<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
if (currentRole() !== 'provider') redirect(APP_URL . '/modules/user/dashboard.php');
$db  = getDB();
$pid = currentProviderId();
$pageTitle = 'Provider Dashboard';

$stats = $db->prepare("SELECT
  COUNT(*) as total,
  SUM(status='pending') as pending,
  SUM(status='accepted') as accepted,
  SUM(status='completed') as completed
  FROM bookings WHERE provider_id=?");
$stats->execute([$pid]);
$stat = $stats->fetch();

$earnings = $db->prepare("SELECT COALESCE(SUM(inv.grand_total),0) as total_earnings
    FROM invoices inv JOIN bookings b ON inv.booking_id=b.id
    WHERE b.provider_id=? AND b.status='completed'");
$earnings->execute([$pid]);
$earn = $earnings->fetch();

$recent = $db->prepare("SELECT b.*, u.name as cust_name, u.phone as cust_phone
    FROM bookings b JOIN users u ON b.customer_id=u.id
    WHERE b.provider_id=? ORDER BY b.created_at DESC LIMIT 5");
$recent->execute([$pid]);
$recentBookings = $recent->fetchAll();

$avgRating = $db->prepare("SELECT ROUND(AVG(rating),1) as avg FROM reviews WHERE provider_id=?");
$avgRating->execute([$pid]);
$avgR = $avgRating->fetch();

// Monthly earnings chart (last 6 months)
$monthlyEarnings = $db->prepare("
    SELECT DATE_FORMAT(inv.generated_at,'%b') as month,
           COALESCE(SUM(inv.grand_total),0) as earnings,
           COUNT(b.id) as jobs
    FROM bookings b
    JOIN invoices inv ON inv.booking_id = b.id
    WHERE b.provider_id=? AND b.status='completed'
      AND inv.generated_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(inv.generated_at), MONTH(inv.generated_at)
    ORDER BY inv.generated_at ASC
");
$monthlyEarnings->execute([$pid]);
$monthlyData = $monthlyEarnings->fetchAll();
$chartLabels   = json_encode(array_column($monthlyData, 'month'));
$chartEarnings = json_encode(array_column($monthlyData, 'earnings'));
$chartJobs     = json_encode(array_column($monthlyData, 'jobs'));
// Today's bookings
$todayBookings = $db->prepare("SELECT b.*, u.name as cust_name, u.phone as cust_phone
    FROM bookings b JOIN users u ON b.customer_id=u.id
    WHERE b.provider_id=? AND b.status='accepted' AND b.scheduled_date=CURDATE()
    ORDER BY b.scheduled_time ASC");
$todayBookings->execute([$pid]);
$todayJobs = $todayBookings->fetchAll();
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<div class="dashboard-wrapper">
<div class="overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../../includes/sidebar_provider.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle"><i class="bi bi-list fs-5"></i></button>
      <div class="topbar-title">Provider Dashboard</div>
    </div>
    <span class="text-muted small d-none d-sm-inline">Welcome, <?= sanitize($_SESSION['name']) ?></span>
  </div>
  <div class="page-content">
    <div class="page-hero mb-4" style="background:linear-gradient(135deg,#064e3b,#059669)">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
          <h4 class="mb-1 fw-700">Provider Dashboard 🔧</h4>
          <p class="mb-0 opacity-75">Manage your bookings and track your earnings.</p>
        </div>
        <div style="background:rgba(255,255,255,.12);border-radius:14px;padding:.75rem 1.2rem;text-align:center">
          <div style="font-size:.7rem;opacity:.7;text-transform:uppercase;letter-spacing:.5px">Today's Jobs</div>
          <div style="font-size:1.4rem;font-weight:800"><?= count($todayJobs) ?></div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <?php $sts = [['Total Bookings','stat-bg-blue','calendar3',$stat['total']],['Pending','stat-bg-yellow','clock',$stat['pending']],['Completed','stat-bg-green','check-circle',$stat['completed']],['Avg Rating','stat-bg-purple','star-fill',$avgR['avg'] ?? 0],['Total Earnings','stat-bg-cyan','currency-dollar',formatCurrency($earn['total_earnings'])]]; ?>
      <?php foreach ($sts as [$label,$bg,$icon,$val]): ?>
      <div class="col-6 col-lg-<?= count($sts) > 4 ? '2' : '3' ?>" style="col-sm-6">
        <div class="stat-card">
          <div class="stat-icon <?= $bg ?>"><i class="bi bi-<?= $icon ?>"></i></div>
          <div><div class="stat-value" style="font-size:1.4rem"><?= $val ?></div><div class="stat-label"><?= $label ?></div></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pending bookings alert -->
    <?php if ($stat['pending'] > 0): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2">
      <i class="bi bi-exclamation-triangle-fill fs-5"></i>
      <div>You have <strong><?= $stat['pending'] ?></strong> pending booking(s) awaiting your response.
        <a href="<?= APP_URL ?>/modules/provider/bookings.php?status=pending" class="alert-link">View now →</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Charts + Quick Actions Row -->
    <div class="row g-4 mb-4">
      <!-- Monthly Earnings Chart -->
      <div class="col-lg-8">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="card-title"><i class="bi bi-bar-chart-fill text-success me-1"></i>Monthly Earnings</span>
            <span class="badge bg-success">Last 6 Months</span>
          </div>
          <div class="card-body">
            <?php if (empty($monthlyData)): ?>
            <div class="empty-state py-4">
              <i class="bi bi-bar-chart opacity-25" style="font-size:2.5rem"></i>
              <p class="mt-2 text-muted">Complete bookings to see earnings here.</p>
            </div>
            <?php else: ?>
            <canvas id="earningsChart" height="110"></canvas>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header"><span class="card-title">⚡ Quick Actions</span></div>
          <div class="card-body p-3">
            <a href="<?= APP_URL ?>/modules/provider/bookings.php?status=pending" class="d-flex align-items-center gap-3 p-3 rounded-3 mb-2 text-decoration-none" style="background:#fffbeb;border:1px solid #fde68a">
              <div style="width:42px;height:42px;background:#fef9c3;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem">⏳</div>
              <div><div class="fw-700 text-dark" style="font-size:.9rem">Pending Requests</div><div class="text-muted" style="font-size:.78rem"><?= $stat['pending'] ?> awaiting response</div></div>
              <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </a>
            <a href="<?= APP_URL ?>/modules/provider/bookings.php?status=accepted" class="d-flex align-items-center gap-3 p-3 rounded-3 mb-2 text-decoration-none" style="background:#eff6ff;border:1px solid #bfdbfe">
              <div style="width:42px;height:42px;background:#dbeafe;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem">📋</div>
              <div><div class="fw-700 text-dark" style="font-size:.9rem">Active Jobs</div><div class="text-muted" style="font-size:.78rem"><?= $stat['accepted'] ?> accepted</div></div>
              <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </a>
            <a href="<?= APP_URL ?>/modules/provider/reviews.php" class="d-flex align-items-center gap-3 p-3 rounded-3 mb-2 text-decoration-none" style="background:#faf5ff;border:1px solid #e9d5ff">
              <div style="width:42px;height:42px;background:#ede9fe;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem">⭐</div>
              <div><div class="fw-700 text-dark" style="font-size:.9rem">My Reviews</div><div class="text-muted" style="font-size:.78rem">Rating: <?= $avgR['avg'] ?? 'No ratings' ?>/5</div></div>
              <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </a>
            <a href="<?= APP_URL ?>/modules/provider/profile.php" class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none" style="background:#f0fdf4;border:1px solid #bbf7d0">
              <div style="width:42px;height:42px;background:#dcfce7;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem">👤</div>
              <div><div class="fw-700 text-dark" style="font-size:.9rem">Edit Profile</div><div class="text-muted" style="font-size:.78rem">Update your info & services</div></div>
              <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Today's Schedule -->
    <?php if (!empty($todayJobs)): ?>
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="card-title"><i class="bi bi-calendar-day text-success me-1"></i>Today's Schedule</span>
        <span class="badge bg-success"><?= count($todayJobs) ?> job<?= count($todayJobs)>1?'s':'' ?> today</span>
      </div>
      <div class="card-body p-0">
        <?php foreach($todayJobs as $i=>$tj): ?>
        <div class="d-flex align-items-center gap-3 p-3 <?= $i<count($todayJobs)-1?'border-bottom':'' ?>">
          <div style="width:44px;height:44px;background:#d1fae5;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0">
            <i class="bi bi-person-check-fill text-success"></i>
          </div>
          <div class="flex-fill min-width-0">
            <div class="fw-700 text-truncate"><?= sanitize($tj['cust_name']) ?></div>
            <div class="text-muted small"><?= !empty($tj['scheduled_time']) ? htmlspecialchars($tj['scheduled_time']) : 'Time TBD' ?> &bull; <?= sanitize($tj['service_type'] ?? 'Service') ?></div>
          </div>
          <?php if(!empty($tj['cust_phone'])): ?>
          <a href="tel:<?= htmlspecialchars($tj['cust_phone']) ?>" class="btn btn-sm btn-outline-success">
            <i class="bi bi-telephone"></i>
          </a>
          <?php endif; ?>
          <a href="<?= APP_URL ?>/modules/provider/billing.php?booking_id=<?= $tj['id'] ?>" class="btn btn-sm btn-primary">Finalize</a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Recent Bookings -->
    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <span class="card-title">Recent Bookings</span>
        <a href="<?= APP_URL ?>/modules/provider/bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>#</th><th>Customer</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
          <?php if (empty($recentBookings)): ?>
          <tr><td colspan="5" class="text-center py-4 text-muted">No bookings yet.</td></tr>
          <?php else: foreach ($recentBookings as $b): ?>
          <tr>
            <td class="fw-600">#<?= $b['id'] ?></td>
            <td><?= sanitize($b['cust_name']) ?></td>
            <td><?= formatDate($b['scheduled_date']) ?></td>
            <td><?= statusBadge($b['status']) ?></td>
            <td>
              <?php if ($b['status']==='pending'): ?>
              <a href="<?= APP_URL ?>/modules/provider/bookings.php?action=accept&id=<?= $b['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Accept this booking?')">Accept</a>
              <a href="<?= APP_URL ?>/modules/provider/bookings.php?action=reject&id=<?= $b['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this booking?')">Reject</a>
              <?php elseif ($b['status']==='accepted'): ?>
              <a href="<?= APP_URL ?>/modules/provider/billing.php?booking_id=<?= $b['id'] ?>" class="btn btn-sm btn-primary">Finalize</a>
              <?php else: ?>
              <span class="text-muted small"><?= ucfirst($b['status']) ?></span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php if (!empty($monthlyData)): ?>
<script>
(function() {
  const ctx = document.getElementById('earningsChart');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= $chartLabels ?>,
      datasets: [
        {
          label: 'Earnings (₹)',
          data: <?= $chartEarnings ?>,
          backgroundColor: 'rgba(5,150,105,0.15)',
          borderColor: '#059669',
          borderWidth: 2,
          borderRadius: 8,
          yAxisID: 'y',
        },
        {
          label: 'Jobs Completed',
          data: <?= $chartJobs ?>,
          type: 'line',
          borderColor: '#2563eb',
          backgroundColor: 'rgba(37,99,235,0.08)',
          borderWidth: 2,
          pointRadius: 5,
          pointBackgroundColor: '#2563eb',
          tension: 0.4,
          fill: true,
          yAxisID: 'y1',
        }
      ]
    },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top', labels: { font: { family: 'Inter', size: 12 } } },
        tooltip: {
          callbacks: {
            label: (ctx) => ctx.datasetIndex === 0
              ? ' ₹' + Number(ctx.raw).toLocaleString('en-IN')
              : ' ' + ctx.raw + ' jobs'
          }
        }
      },
      scales: {
        y:  { position: 'left',  beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { callback: v => '₹' + v.toLocaleString('en-IN') } },
        y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { stepSize: 1 } },
        x:  { grid: { display: false } }
      }
    }
  });
})();
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
