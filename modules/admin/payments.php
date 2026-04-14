<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$db = getDB();

// Handle verify / reject action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $payId  = (int)($_POST['payment_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($payId && in_array($action, ['verify', 'reject'])) {
        if ($action === 'verify') {
            // Mark payment as verified
            $db->prepare("UPDATE payments SET status='verified', verified_at=NOW() WHERE id=?")
               ->execute([$payId]);
            // Also mark invoice as paid
            $db->prepare("UPDATE invoices SET payment_status='paid' WHERE booking_id=(SELECT booking_id FROM payments WHERE id=?)")
               ->execute([$payId]);
            $msg = 'Payment verified and invoice marked as Paid!';
        } else {
            $db->prepare("UPDATE payments SET status='rejected', verified_at=NOW() WHERE id=?")
               ->execute([$payId]);
            $msg = 'Payment rejected.';
        }
        redirect(APP_URL . '/modules/admin/payments.php?msg=' . urlencode($msg));
    }
}

// Fetch all payments with booking/user info
$payments = $db->query("
    SELECT p.*, 
        b.id as booking_id,
        uc.name as customer_name, uc.phone as customer_phone,
        inv.invoice_number, inv.grand_total, inv.payment_status
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN users uc ON b.customer_id = uc.id
    JOIN invoices inv ON inv.booking_id = b.id
    ORDER BY p.created_at DESC
")->fetchAll();

$pageTitle = 'Payment Verification';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<div class="dashboard-wrapper">
<div class="overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle"><i class="bi bi-list fs-5"></i></button>
      <div class="topbar-title"><i class="bi bi-cash-coin me-2"></i>Payment Verification</div>
    </div>
  </div>
  <div class="page-content">

    <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill"></i> <?= sanitize($_GET['msg']) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <?php
      $pending  = array_filter($payments, fn($p) => $p['status'] === 'pending');
      $verified = array_filter($payments, fn($p) => $p['status'] === 'verified');
      $rejected = array_filter($payments, fn($p) => $p['status'] === 'rejected');
    ?>
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon stat-bg-yellow"><i class="bi bi-hourglass-split"></i></div>
          <div>
            <div class="stat-value"><?= count($pending) ?></div>
            <div class="stat-label">Pending Verification</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon stat-bg-green"><i class="bi bi-shield-check"></i></div>
          <div>
            <div class="stat-value"><?= count($verified) ?></div>
            <div class="stat-label">Verified Payments</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon stat-bg-red"><i class="bi bi-x-circle"></i></div>
          <div>
            <div class="stat-value"><?= count($rejected) ?></div>
            <div class="stat-label">Rejected</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">All Payment Submissions</span></div>
      <div class="card-body p-0">
        <?php if (empty($payments)): ?>
        <div class="empty-state">
          <i class="bi bi-inbox"></i>
          <p class="mt-2">No payments submitted yet.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead style="background:#f8fafc">
              <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Invoice</th>
                <th>Amount</th>
                <th>Transaction ID</th>
                <th>Submitted At</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($payments as $p): ?>
              <tr>
                <td class="text-muted small"><?= $p['id'] ?></td>
                <td>
                  <div class="fw-600 small"><?= sanitize($p['customer_name']) ?></div>
                  <div class="text-muted" style="font-size:.75rem"><?= sanitize($p['customer_phone'] ?? '—') ?></div>
                </td>
                <td>
                  <a href="<?= APP_URL ?>/modules/user/invoice.php?id=<?= $p['booking_id'] ?>" target="_blank" class="small fw-600 text-primary">
                    <?= sanitize($p['invoice_number']) ?>
                  </a>
                </td>
                <td class="fw-700"><?= formatCurrency($p['grand_total']) ?></td>
                <td>
                  <span style="font-family:monospace;font-weight:700;font-size:.85rem;background:#f1f5f9;padding:.2rem .5rem;border-radius:6px">
                    <?= sanitize($p['transaction_id']) ?>
                  </span>
                </td>
                <td class="text-muted small"><?= formatDateTime($p['created_at']) ?></td>
                <td>
                  <?php if ($p['status'] === 'pending'): ?>
                  <span class="badge" style="background:#fef3c7;color:#d97706;padding:.3rem .7rem">⏳ Pending</span>
                  <?php elseif ($p['status'] === 'verified'): ?>
                  <span class="badge" style="background:#dcfce7;color:#16a34a;padding:.3rem .7rem">✅ Verified</span>
                  <?php else: ?>
                  <span class="badge" style="background:#fee2e2;color:#dc2626;padding:.3rem .7rem">❌ Rejected</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($p['status'] === 'pending'): ?>
                  <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="action" value="verify">
                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Mark this payment as verified?')">
                      <i class="bi bi-check-lg"></i> Verify
                    </button>
                  </form>
                  <form method="POST" style="display:inline;margin-left:.3rem">
                    <?= csrfField() ?>
                    <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this payment?')">
                      <i class="bi bi-x-lg"></i> Reject
                    </button>
                  </form>
                  <?php else: ?>
                  <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>