<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
$db = getDB(); $uid = currentUserId();
$pageTitle = 'My Invoices';
$invoices = $db->prepare("SELECT inv.*, b.scheduled_date, b.status as booking_status, p.business_name
    FROM invoices inv JOIN bookings b ON inv.booking_id=b.id JOIN providers p ON b.provider_id=p.id
    WHERE b.customer_id=? ORDER BY inv.generated_at DESC");
$invoices->execute([$uid]);
$invList = $invoices->fetchAll();
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>


<?php require_once __DIR__ . '/../../includes/topnav_user.php'; ?>
<div class="user-main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      
      <div class="topbar-title">My Invoices</div>
    </div>
  </div>
  <div class="page-content">
    <div class="card table-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="card-title">All Invoices</span>
        <div class="search-bar"><i class="bi bi-search"></i><input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Search..." style="width:200px;padding-left:2.2rem"></div>
      </div>
      <div class="table-responsive" data-searchable>
        <table class="table">
          <thead><tr><th>Invoice #</th><th>Provider</th><th>Service Date</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
          <?php if (empty($invList)): ?>
          <tr><td colspan="6" class="text-center py-4 text-muted">No invoices found.</td></tr>
          <?php else: foreach ($invList as $i): ?>
          <tr>
            <td><span class="fw-600"><?= $i['invoice_number'] ?></span></td>
            <td><?= sanitize($i['business_name']) ?></td>
            <td><?= formatDate($i['scheduled_date']) ?></td>
            <td class="fw-700"><?= formatCurrency($i['grand_total']) ?></td>
            <td><?= statusBadge($i['payment_status']) ?></td>
            <td><a href="<?= APP_URL ?>/modules/user/invoice.php?id=<?= $i['booking_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a></td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
