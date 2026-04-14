<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
$db  = getDB();
$uid = currentUserId();
$pageTitle = 'My Bookings';

// Handle cancellation
if ($_POST['action'] ?? '' === 'cancel') {
    $bid = (int)($_POST['booking_id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM bookings WHERE id=? AND customer_id=?");
    $stmt->execute([$bid, $uid]);
    $bk = $stmt->fetch();
    if ($bk && in_array($bk['status'], ['pending','accepted'])) {
        $db->prepare("UPDATE bookings SET status='cancelled', cancellation_reason=? WHERE id=?")->execute([sanitize($_POST['reason'] ?? 'Customer cancelled'), $bid]);
    }
    redirect(APP_URL . '/modules/user/bookings.php?msg=cancelled');
}

$filter = $_GET['status'] ?? 'all';
$where  = $filter !== 'all' ? "AND b.status='" . $db->quote($filter) . "'" : '';
$stmt = $db->prepare("SELECT b.*, p.business_name, c.name as category, u.name as pname
    FROM bookings b JOIN providers p ON b.provider_id=p.id
    JOIN categories c ON p.category_id=c.id JOIN users u ON p.user_id=u.id
    WHERE b.customer_id=? ORDER BY b.created_at DESC");
$stmt->execute([$uid]);
$bookings = $stmt->fetchAll();
if ($filter !== 'all') $bookings = array_filter($bookings, fn($b) => $b['status'] === $filter);
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>


<?php require_once __DIR__ . '/../../includes/topnav_user.php'; ?>
<div class="user-main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      
      <div class="topbar-title">My Bookings</div>
    </div>
    <a href="<?= APP_URL ?>/modules/user/browse.php" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> New Booking</a>
  </div>
  <div class="page-content">
    <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-info">Booking cancelled successfully.</div>
    <?php endif; ?>

    <!-- Status filter tabs -->
    <div class="card mb-4">
      <div class="card-body py-2">
        <div class="d-flex gap-2 flex-wrap">
          <?php foreach (['all','pending','accepted','in_progress','completed','cancelled'] as $s): ?>
          <a href="?status=<?= $s ?>" class="btn btn-sm <?= $filter===$s ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <?= ucwords(str_replace('_',' ',$s)) ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Search -->
    <div class="card mb-3">
      <div class="card-body py-2">
        <div class="search-bar">
          <i class="bi bi-search"></i>
          <input type="text" class="form-control" id="tableSearch" placeholder="Search bookings...">
        </div>
      </div>
    </div>

    <div class="card table-card">
      <div class="table-responsive" data-searchable>
        <table class="table">
          <thead><tr><th>Booking #</th><th>Provider</th><th>Category</th><th>Date</th><th>Status</th><th>Invoice</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if (empty($bookings)): ?>
          <tr><td colspan="7" class="text-center py-4 text-muted">No bookings found.</td></tr>
          <?php else: foreach ($bookings as $b): ?>
          <tr>
            <td><span class="fw-600">#<?= $b['id'] ?></span></td>
            <td><?= sanitize($b['business_name']) ?></td>
            <td><?= sanitize($b['category']) ?></td>
            <td><?= formatDate($b['scheduled_date']) ?> <?= date('h:i A', strtotime($b['scheduled_time'])) ?></td>
            <td><?= statusBadge($b['status']) ?></td>
            <td>
              <?php $inv = $db->prepare("SELECT id FROM invoices WHERE booking_id=?"); $inv->execute([$b['id']]); $invRow = $inv->fetch(); ?>
              <?php if ($invRow): ?>
              <a href="<?= APP_URL ?>/modules/user/invoice.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-receipt"></i> View</a>
              <?php else: ?><span class="text-muted small">Pending</span><?php endif; ?>
            </td>
            <td>
              <a href="<?= APP_URL ?>/modules/user/booking_detail.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary">Detail</a>
              <?php if (in_array($b['status'],['pending','accepted'])): ?>
              <button class="btn btn-sm btn-outline-danger" onclick="cancelBooking(<?= $b['id'] ?>)">Cancel</button>
              <?php endif; ?>
              <?php if ($b['status']==='completed'): ?>
              <?php $rv = $db->prepare("SELECT id FROM reviews WHERE booking_id=?"); $rv->execute([$b['id']]); $rvRow = $rv->fetch(); ?>
              <?php if (!$rvRow): ?>
              <a href="<?= APP_URL ?>/modules/user/review.php?booking_id=<?= $b['id'] ?>" 
                 class="btn btn-sm btn-warning fw-600">
                <i class="bi bi-star-fill"></i> Rate
              </a>
              <?php else: ?>
              <span class="badge bg-success"><i class="bi bi-check-circle"></i> Reviewed</span>
              <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>



<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-danger text-white"><h5 class="modal-title">Cancel Booking</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <form method="POST">
      <input type="hidden" name="action" value="cancel">
      <input type="hidden" name="booking_id" id="cancelBookingId">
      <div class="modal-body">
        <label class="form-label">Reason for cancellation</label>
        <textarea name="reason" class="form-control" rows="3" placeholder="Optional reason..."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-danger">Confirm Cancel</button>
      </div>
    </form>
  </div></div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script>
function cancelBooking(id) {
  document.getElementById('cancelBookingId').value = id;
  new bootstrap.Modal(document.getElementById('cancelModal')).show();
}
</script>
