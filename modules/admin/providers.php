<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
if (currentRole() !== 'admin') redirect(APP_URL . '/modules/admin/dashboard.php');
$db = getDB();
$pageTitle = 'Manage Providers';

if (isset($_GET['action'], $_GET['id'])) {
    $pid = (int)$_GET['id'];
    $act = $_GET['action'];
    if ($act === 'approve')  $db->prepare("UPDATE providers SET approval_status='approved' WHERE id=?")->execute([$pid]);
    if ($act === 'suspend')  $db->prepare("UPDATE providers SET approval_status='suspended' WHERE id=?")->execute([$pid]);
    if ($act === 'pending')  $db->prepare("UPDATE providers SET approval_status='pending' WHERE id=?")->execute([$pid]);
    redirect(APP_URL . '/modules/admin/providers.php');
}

$filter = $_GET['filter'] ?? 'all';
$where  = $filter !== 'all' ? "WHERE p.approval_status='$filter'" : '';
$providers = $db->query("SELECT p.*, u.name, u.email, u.phone, u.status as user_status, c.name as cat_name,
    ROUND(AVG(r.rating),1) as avg_rating, COUNT(DISTINCT r.id) as review_count,
    COUNT(DISTINCT b.id) as total_bookings,
    SUM(CASE WHEN b.status='completed' THEN 1 ELSE 0 END) as completed_jobs
    FROM providers p JOIN users u ON p.user_id=u.id JOIN categories c ON p.category_id=c.id
    LEFT JOIN reviews r ON r.provider_id=p.id LEFT JOIN bookings b ON b.provider_id=p.id
    $where GROUP BY p.id ORDER BY p.created_at DESC")->fetchAll();
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<div class="dashboard-wrapper">
<div class="overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle"><i class="bi bi-list fs-5"></i></button>
      <div class="topbar-title">Manage Providers</div>
    </div>
  </div>
  <div class="page-content">
    <!-- Filter tabs -->
    <div class="card mb-3"><div class="card-body py-2">
      <div class="d-flex gap-2 flex-wrap">
        <?php foreach (['all','pending','approved','suspended'] as $s): ?>
        <a href="?filter=<?= $s ?>" class="btn btn-sm <?= $filter===$s ? 'btn-danger' : 'btn-outline-secondary' ?>">
          <?= ucfirst($s) ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div></div>

    <div class="card table-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="card-title">Providers <span class="badge bg-danger"><?= count($providers) ?></span></span>
        <div class="search-bar"><i class="bi bi-search"></i><input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Search..." style="width:200px;padding-left:2.2rem"></div>
      </div>
      <div class="table-responsive" data-searchable>
        <table class="table">
          <thead><tr><th>#</th><th>Business</th><th>Category</th><th>Rating</th><th>Jobs</th><th>Base Price</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if (empty($providers)): ?>
          <tr><td colspan="8" class="text-center py-4 text-muted">No providers found.</td></tr>
          <?php else: foreach ($providers as $p): ?>
          <tr>
            <td><?= $p['id'] ?></td>
            <td>
              <div class="fw-600"><?= sanitize($p['business_name']) ?></div>
              <div class="text-muted small"><?= sanitize($p['name']) ?> &bull; <?= sanitize($p['email']) ?></div>
            </td>
            <td><?= sanitize($p['cat_name']) ?></td>
            <td>
              <span class="text-warning"><i class="bi bi-star-fill"></i></span>
              <?= $p['avg_rating'] ?? '0.0' ?> (<?= $p['review_count'] ?>)
            </td>
            <td><?= $p['completed_jobs'] ?> / <?= $p['total_bookings'] ?></td>
            <td><?= formatCurrency($p['base_price']) ?></td>
            <td><?= statusBadge($p['approval_status']) ?></td>
            <td>
              <?php if ($p['approval_status']==='pending'): ?>
              <a href="?action=approve&id=<?= $p['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this provider?')">Approve</a>
              <a href="?action=suspend&id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject?')">Reject</a>
              <?php elseif ($p['approval_status']==='approved'): ?>
              <a href="?action=suspend&id=<?= $p['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirm('Suspend this provider?')">Suspend</a>
              <?php else: ?>
              <a href="?action=approve&id=<?= $p['id'] ?>" class="btn btn-sm btn-success">Restore</a>
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
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
