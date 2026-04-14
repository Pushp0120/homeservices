<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
if (currentRole() !== 'admin') redirect(APP_URL . '/modules/user/dashboard.php');
$db = getDB();
$pageTitle = 'Reports & Analytics';

// ── Date range filter ─────────────────────────────────────
$range  = $_GET['range'] ?? '30';   // days
$start  = date('Y-m-d', strtotime("-{$range} days"));
$today  = date('Y-m-d');

// ── Summary stats ─────────────────────────────────────────
$totalRevenue    = $db->query("SELECT COALESCE(SUM(grand_total),0) FROM invoices")->fetchColumn();
$totalBookings   = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalCustomers  = $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$totalProviders  = $db->query("SELECT COUNT(*) FROM providers WHERE approval_status='approved'")->fetchColumn();
$avgRating       = $db->query("SELECT ROUND(AVG(rating),1) FROM reviews")->fetchColumn();
$completionRate  = $db->query("SELECT ROUND(SUM(status='completed')/COUNT(*)*100,1) FROM bookings")->fetchColumn();

// ── Monthly Revenue (last 6 months) ───────────────────────
$monthlyRevenue = $db->query("
    SELECT DATE_FORMAT(generated_at,'%b %Y') as month,
           SUM(grand_total) as revenue,
           COUNT(*) as invoice_count
    FROM invoices
    WHERE generated_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(generated_at), MONTH(generated_at)
    ORDER BY generated_at ASC
")->fetchAll();

// ── Bookings by Category ───────────────────────────────────
$catStats = $db->query("
    SELECT c.name, COUNT(b.id) as bookings,
           COALESCE(SUM(inv.grand_total),0) as revenue
    FROM categories c
    LEFT JOIN providers p ON p.category_id = c.id
    LEFT JOIN bookings b ON b.provider_id = p.id
    LEFT JOIN invoices inv ON inv.booking_id = b.id
    WHERE c.status = 'active'
    GROUP BY c.id, c.name
    ORDER BY bookings DESC
")->fetchAll();

// ── Bookings by Status ─────────────────────────────────────
$statusStats = $db->query("
    SELECT status, COUNT(*) as count
    FROM bookings
    GROUP BY status
    ORDER BY count DESC
")->fetchAll();

// ── Top Performing Providers ───────────────────────────────
$topProviders = $db->query("
    SELECT p.business_name, u.name as provider_name,
           c.name as category,
           COUNT(DISTINCT b.id) as total_bookings,
           SUM(CASE WHEN b.status='completed' THEN 1 ELSE 0 END) as completed,
           COALESCE(SUM(inv.grand_total),0) as revenue,
           ROUND(COALESCE(AVG(r.rating),0),1) as avg_rating
    FROM providers p
    JOIN users u ON p.user_id = u.id
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN bookings b ON b.provider_id = p.id
    LEFT JOIN invoices inv ON inv.booking_id = b.id AND b.status='completed'
    LEFT JOIN reviews r ON r.provider_id = p.id
    WHERE p.approval_status = 'approved'
    GROUP BY p.id
    ORDER BY revenue DESC, completed DESC
    LIMIT 8
")->fetchAll();

// ── Daily Bookings (last 14 days) ─────────────────────────
$dailyBookings = $db->query("
    SELECT DATE(created_at) as day,
           COUNT(*) as bookings
    FROM bookings
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
")->fetchAll();

// ── Recent Reviews ────────────────────────────────────────
$recentReviews = $db->query("
    SELECT r.rating, r.review_text, r.created_at,
           uc.name as customer_name,
           p.business_name
    FROM reviews r
    JOIN users uc ON r.customer_id = uc.id
    JOIN providers p ON r.provider_id = p.id
    ORDER BY r.created_at DESC
    LIMIT 5
")->fetchAll();
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<div class="dashboard-wrapper">
<div class="overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<div class="main-content">

  <!-- Topbar -->
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list fs-5"></i>
      </button>
      <div class="topbar-title">Reports & Analytics</div>
    </div>
    <div class="topbar-actions">
      <!-- Date range filter -->
      <select class="form-select form-select-sm" style="width:auto"
              onchange="location.href='?range='+this.value">
        <option value="7"  <?= $range=='7'  ?'selected':'' ?>>Last 7 days</option>
        <option value="30" <?= $range=='30' ?'selected':'' ?>>Last 30 days</option>
        <option value="90" <?= $range=='90' ?'selected':'' ?>>Last 90 days</option>
        <option value="365"<?= $range=='365'?'selected':'' ?>>Last 12 months</option>
      </select>
      <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print">
        <i class="bi bi-printer"></i> Print
      </button>
    </div>
  </div>

  <div class="page-content">

    <!-- Page Hero -->
    <div class="page-hero mb-4" style="background:linear-gradient(135deg,#7f1d1d,#dc2626)">
      <h4 class="mb-1 fw-700">📊 Reports & Analytics</h4>
      <p class="mb-0 opacity-75">Platform-wide performance overview and insights.</p>
    </div>

    <!-- ── Summary Stats ── -->
    <div class="row g-3 mb-4">
      <?php
      $summaryStats = [
        ['Total Revenue',      formatCurrency($totalRevenue),    'stat-bg-green',  'cash-stack'],
        ['Total Bookings',     $totalBookings,                   'stat-bg-blue',   'calendar-check-fill'],
        ['Completion Rate',    $completionRate . '%',            'stat-bg-cyan',   'check-all'],
        ['Total Customers',    $totalCustomers,                  'stat-bg-purple', 'people-fill'],
        ['Active Providers',   $totalProviders,                  'stat-bg-yellow', 'person-badge-fill'],
        ['Avg Rating',         ($avgRating ?: '—') . ' ★',      'stat-bg-red',    'star-fill'],
      ];
      foreach ($summaryStats as [$label, $val, $bg, $icon]):
      ?>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
          <div class="stat-icon <?= $bg ?>"><i class="bi bi-<?= $icon ?>"></i></div>
          <div>
            <div class="stat-value" style="font-size:1.25rem"><?= $val ?></div>
            <div class="stat-label"><?= $label ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Charts Row 1 ── -->
    <div class="row g-4 mb-4">

      <!-- Monthly Revenue Bar Chart -->
      <div class="col-lg-8">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="card-title">Monthly Revenue (Last 6 Months)</span>
            <span class="badge bg-success">
              <?= formatCurrency(array_sum(array_column($monthlyRevenue,'revenue'))) ?> total
            </span>
          </div>
          <div class="card-body">
            <?php if (empty($monthlyRevenue)): ?>
            <div class="empty-state"><i class="bi bi-bar-chart"></i><p>No revenue data yet.</p></div>
            <?php else: ?>
            <canvas id="revenueChart" height="110"></canvas>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Bookings by Status Doughnut -->
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header">
            <span class="card-title">Bookings by Status</span>
          </div>
          <div class="card-body d-flex align-items-center justify-content-center">
            <?php if (empty($statusStats)): ?>
            <div class="empty-state"><i class="bi bi-pie-chart"></i><p>No data yet.</p></div>
            <?php else: ?>
            <canvas id="statusChart" height="200"></canvas>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Charts Row 2 ── -->
    <div class="row g-4 mb-4">

      <!-- Daily Bookings Line Chart -->
      <div class="col-lg-7">
        <div class="card h-100">
          <div class="card-header">
            <span class="card-title">Daily Bookings (Last 14 Days)</span>
          </div>
          <div class="card-body">
            <?php if (empty($dailyBookings)): ?>
            <div class="empty-state"><i class="bi bi-graph-up"></i><p>No bookings in last 14 days.</p></div>
            <?php else: ?>
            <canvas id="dailyChart" height="130"></canvas>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Bookings by Category Doughnut -->
      <div class="col-lg-5">
        <div class="card h-100">
          <div class="card-header">
            <span class="card-title">Bookings by Category</span>
          </div>
          <div class="card-body d-flex align-items-center justify-content-center">
            <?php if (empty($catStats)): ?>
            <div class="empty-state"><i class="bi bi-pie-chart"></i><p>No data yet.</p></div>
            <?php else: ?>
            <canvas id="catChart" height="200"></canvas>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Top Providers Table ── -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="card-title">Top Performing Providers</span>
        <a href="<?= APP_URL ?>/modules/admin/providers.php" class="btn btn-sm btn-outline-danger">View All</a>
      </div>
      <?php if (empty($topProviders)): ?>
      <div class="card-body"><div class="empty-state"><i class="bi bi-people"></i><p>No providers yet.</p></div></div>
      <?php else: ?>
      <?php $maxRev = max(array_column($topProviders,'revenue')) ?: 1; ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Provider</th>
              <th>Category</th>
              <th>Bookings</th>
              <th>Completed</th>
              <th>Rating</th>
              <th>Revenue</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($topProviders as $i => $p): ?>
          <tr>
            <td>
              <?php if ($i === 0): ?>
                <span class="badge bg-warning text-dark">🥇 1</span>
              <?php elseif ($i === 1): ?>
                <span class="badge bg-secondary">🥈 2</span>
              <?php elseif ($i === 2): ?>
                <span class="badge" style="background:#cd7f32">🥉 3</span>
              <?php else: ?>
                <span class="text-muted fw-600"><?= $i+1 ?></span>
              <?php endif; ?>
            </td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="avatar-circle" style="width:32px;height:32px;font-size:.8rem">
                  <?= strtoupper(substr($p['business_name'],0,1)) ?>
                </div>
                <div>
                  <div class="fw-600 small"><?= sanitize($p['business_name']) ?></div>
                  <div class="text-muted" style="font-size:.72rem"><?= sanitize($p['provider_name']) ?></div>
                </div>
              </div>
            </td>
            <td><span class="badge bg-light text-dark border"><?= sanitize($p['category']) ?></span></td>
            <td><span class="fw-600"><?= $p['total_bookings'] ?></span></td>
            <td>
              <span class="text-success fw-600"><?= $p['completed'] ?></span>
              <?php if ($p['total_bookings'] > 0): ?>
              <small class="text-muted">(<?= round($p['completed']/$p['total_bookings']*100) ?>%)</small>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($p['avg_rating'] > 0): ?>
              <span class="text-warning">★</span>
              <span class="fw-600"><?= $p['avg_rating'] ?></span>
              <?php else: ?>
              <span class="text-muted small">No reviews</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-700 text-primary"><?= formatCurrency($p['revenue']) ?></div>
              <!-- Revenue bar -->
              <div style="height:4px;background:#f1f5f9;border-radius:4px;margin-top:3px;width:100px">
                <div style="height:100%;border-radius:4px;
                  background:linear-gradient(90deg,#2563eb,#7c3aed);
                  width:<?= round($p['revenue']/$maxRev*100) ?>%;
                  transition:width 1s ease"></div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── Category Revenue Table ── -->
    <div class="row g-4 mb-4">
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header"><span class="card-title">Revenue by Category</span></div>
          <div class="card-body p-0">
            <?php if (empty($catStats)): ?>
            <div class="p-4 text-center text-muted">No data yet.</div>
            <?php else:
              $maxCatRev = max(array_column($catStats,'revenue')) ?: 1;
              foreach ($catStats as $cat): ?>
            <div class="d-flex align-items-center gap-3 p-3 border-bottom">
              <div class="fw-600 small flex-fill"><?= sanitize($cat['name']) ?></div>
              <div class="text-muted small"><?= $cat['bookings'] ?> bookings</div>
              <div class="fw-700 text-primary small"><?= formatCurrency($cat['revenue']) ?></div>
            </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>

      <!-- Recent Reviews -->
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header"><span class="card-title">Recent Reviews</span></div>
          <div class="card-body p-0">
            <?php if (empty($recentReviews)): ?>
            <div class="p-4 text-center text-muted">No reviews yet.</div>
            <?php else: foreach ($recentReviews as $r): ?>
            <div class="p-3 border-bottom">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                  <span class="fw-600 small"><?= sanitize($r['customer_name']) ?></span>
                  <span class="text-muted small"> → <?= sanitize($r['business_name']) ?></span>
                </div>
                <div>
                  <?php for ($s=1;$s<=5;$s++): ?>
                  <i class="bi bi-star<?= $s<=$r['rating'] ? '-fill text-warning' : ' text-secondary opacity-25' ?>" style="font-size:.75rem"></i>
                  <?php endfor; ?>
                </div>
              </div>
              <?php if ($r['review_text']): ?>
              <p class="text-muted small mb-0 fst-italic">"<?= sanitize(substr($r['review_text'],0,80)) ?><?= strlen($r['review_text'])>80 ? '...' : '' ?>"</p>
              <?php endif; ?>
            </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /page-content -->
</div><!-- /main-content -->
</div><!-- /dashboard-wrapper -->

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>

<script>
const chartColors = ['#2563eb','#7c3aed','#059669','#d97706','#dc2626','#0891b2','#4f46e5','#9333ea'];

// ── Monthly Revenue Bar Chart ─────────────────────────────
<?php if (!empty($monthlyRevenue)): ?>
new Chart(document.getElementById('revenueChart').getContext('2d'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($monthlyRevenue, 'month')) ?>,
    datasets: [{
      label: 'Revenue (₹)',
      data: <?= json_encode(array_column($monthlyRevenue, 'revenue')) ?>,
      backgroundColor: 'rgba(37,99,235,.85)',
      borderRadius: 8,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true,
    animation: { duration: 1000, easing: 'easeOutQuart', delay: ctx => ctx.dataIndex * 120 },
    plugins: { legend: { display: false } },
    scales: {
      y: {
        beginAtZero: true,
        grid: { color: '#f1f5f9' },
        ticks: { callback: v => '₹' + Number(v).toLocaleString('en-IN') }
      },
      x: { grid: { display: false } }
    }
  }
});
<?php endif; ?>

// ── Bookings by Status Doughnut ───────────────────────────
<?php if (!empty($statusStats)): ?>
new Chart(document.getElementById('statusChart').getContext('2d'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_map(fn($s) => ucwords(str_replace('_',' ',$s['status'])), $statusStats)) ?>,
    datasets: [{
      data: <?= json_encode(array_column($statusStats, 'count')) ?>,
      backgroundColor: ['#f59e0b','#3b82f6','#8b5cf6','#10b981','#ef4444'],
      borderWidth: 3, borderColor: '#fff'
    }]
  },
  options: {
    responsive: true,
    animation: { animateRotate: true, duration: 1000 },
    plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 }}}
  }
});
<?php endif; ?>

// ── Daily Bookings Line Chart ─────────────────────────────
<?php if (!empty($dailyBookings)): ?>
new Chart(document.getElementById('dailyChart').getContext('2d'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_map(fn($d) => date('d M', strtotime($d['day'])), $dailyBookings)) ?>,
    datasets: [{
      label: 'Bookings',
      data: <?= json_encode(array_column($dailyBookings, 'bookings')) ?>,
      borderColor: '#2563eb',
      backgroundColor: 'rgba(37,99,235,.08)',
      borderWidth: 2.5,
      pointBackgroundColor: '#2563eb',
      pointRadius: 4,
      tension: 0.4,
      fill: true,
    }]
  },
  options: {
    responsive: true,
    animation: { duration: 1000, easing: 'easeOutQuart' },
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
      x: { grid: { display: false } }
    }
  }
});
<?php endif; ?>

// ── Bookings by Category Doughnut ─────────────────────────
<?php if (!empty($catStats)): ?>
new Chart(document.getElementById('catChart').getContext('2d'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($catStats, 'name')) ?>,
    datasets: [{
      data: <?= json_encode(array_column($catStats, 'bookings')) ?>,
      backgroundColor: chartColors,
      borderWidth: 3, borderColor: '#fff'
    }]
  },
  options: {
    responsive: true,
    animation: { animateRotate: true, duration: 1000, delay: ctx => ctx.dataIndex * 80 },
    plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10 }}}
  }
});
<?php endif; ?>
</script>