<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
if (currentRole() !== 'admin') redirect(APP_URL . '/modules/admin/dashboard.php');
$db = getDB();
$pageTitle = 'All Bookings';

$filter  = $_GET['status'] ?? 'all';
$search  = sanitize($_GET['q'] ?? '');
$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$where = "WHERE 1=1";
if ($filter !== 'all') $where .= " AND b.status='" . addslashes($filter) . "'";
if ($search)           $where .= " AND (uc.name LIKE '%" . addslashes($search) . "%' OR p.business_name LIKE '%" . addslashes($search) . "%')";

$total = $db->query("SELECT COUNT(*) FROM bookings b
    JOIN users uc ON b.customer_id=uc.id
    JOIN providers p ON b.provider_id=p.id
    $where")->fetchColumn();
$totalPages = ceil($total / $perPage);

$bookings = $db->query("SELECT b.*, uc.name as cust_name, p.business_name, c.name as category,
    inv.grand_total, inv.invoice_number
    FROM bookings b JOIN users uc ON b.customer_id=uc.id
    JOIN providers p ON b.provider_id=p.id JOIN categories c ON p.category_id=c.id
    LEFT JOIN invoices inv ON inv.booking_id=b.id
    $where ORDER BY b.created_at DESC
    LIMIT $perPage OFFSET $offset")->fetchAll();
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<div class="dashboard-wrapper">
<div class="overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle"><i class="bi bi-list fs-5"></i></button>
      <div class="topbar-title">All Bookings</div>
    </div>
  </div>
  <div class="page-content">
    <div class="card mb-3"><div class="card-body py-2">
      <div class="d-flex gap-2 flex-wrap">
        <?php foreach (['all','pending','accepted','in_progress','completed','cancelled'] as $s): ?>
        <a href="?status=<?= $s ?>" class="btn btn-sm <?= $filter===$s ? 'btn-danger' : 'btn-outline-secondary' ?>"><?= ucwords(str_replace('_',' ',$s)) ?></a>
        <?php endforeach; ?>
      </div>
    </div></div>

    <div class="card table-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="card-title">Bookings <span class="badge bg-danger"><?= $total ?></span></span>
        <form method="GET" class="d-flex gap-2 align-items-center">
          <input type="hidden" name="status" value="<?= sanitize($filter) ?>">
          <div class="search-bar"><i class="bi bi-search"></i>
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Search customer/provider..." style="width:220px;padding-left:2.2rem" value="<?= $search ?>">
          </div>
          <button class="btn btn-sm btn-primary">Go</button>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>#</th><th>Customer</th><th>Provider</th><th>Category</th><th>Date</th><th>Status</th><th>Invoice</th></tr></thead>
          <tbody>
          <?php if (empty($bookings)): ?>
          <tr><td colspan="7" class="text-center py-4 text-muted">No bookings.</td></tr>
          <?php else: foreach ($bookings as $b): ?>
          <tr>
            <td class="fw-600">#<?= $b['id'] ?></td>
            <td><?= sanitize($b['cust_name']) ?></td>
            <td><?= sanitize($b['business_name']) ?></td>
            <td><?= sanitize($b['category']) ?></td>
            <td><?= formatDate($b['scheduled_date']) ?></td>
            <td><?= statusBadge($b['status']) ?></td>
            <td>
              <?php if ($b['invoice_number']): ?>
              <div class="small fw-600"><?= $b['invoice_number'] ?></div>
              <div class="text-success fw-700 small"><?= formatCurrency($b['grand_total']) ?></div>
              <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
            </td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <?php if ($totalPages > 1): ?>
      <div class="card-body py-2 border-top d-flex justify-content-between align-items-center">
        <span class="text-muted small">Showing <?= $offset+1 ?>–<?= min($offset+$perPage, $total) ?> of <?= $total ?></span>
        <nav><ul class="pagination pagination-sm mb-0 gap-1">
          <?php if ($page > 1): ?><li class="page-item"><a class="page-link rounded" href="?status=<?= $filter ?>&q=<?= urlencode($search) ?>&page=<?= $page-1 ?>"><i class="bi bi-chevron-left"></i></a></li><?php endif; ?>
          <?php for ($i=1;$i<=$totalPages;$i++): ?>
          <li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link rounded" href="?status=<?= $filter ?>&q=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a></li>
          <?php endfor; ?>
          <?php if ($page < $totalPages): ?><li class="page-item"><a class="page-link rounded" href="?status=<?= $filter ?>&q=<?= urlencode($search) ?>&page=<?= $page+1 ?>"><i class="bi bi-chevron-right"></i></a></li><?php endif; ?>
        </ul></nav>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
