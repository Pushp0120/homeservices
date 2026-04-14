<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
if (currentRole() !== 'provider') redirect(APP_URL . '/modules/user/dashboard.php');
$db  = getDB();
$pid = currentProviderId();
$pageTitle = 'Manage Bookings';

$action = $_GET['action'] ?? '';
$bid    = (int)($_GET['id'] ?? 0);
if ($action && $bid) {
    $check = $db->prepare("SELECT id,status FROM bookings WHERE id=? AND provider_id=?");
    $check->execute([$bid,$pid]); $bk = $check->fetch();
    if ($bk) {
        if ($action==='accept' && $bk['status']==='pending')
            $db->prepare("UPDATE bookings SET status='accepted' WHERE id=?")->execute([$bid]);
        if ($action==='reject' && $bk['status']==='pending')
            $db->prepare("UPDATE bookings SET status='cancelled',cancellation_reason='Rejected by provider' WHERE id=?")->execute([$bid]);
        if ($action==='inprogress' && $bk['status']==='accepted')
            $db->prepare("UPDATE bookings SET status='in_progress' WHERE id=?")->execute([$bid]);
    }
    redirect(APP_URL . '/modules/provider/bookings.php');
}

$filter = $_GET['status'] ?? 'all';
$stmt = $db->prepare("SELECT b.*, u.name as cust_name, u.email as cust_email, u.phone as cust_phone,
    c.name as category_name,
    (SELECT COUNT(*) FROM booking_services bs WHERE bs.booking_id=b.id) as svc_count,
    (SELECT SUM(bs.subtotal) FROM booking_services bs WHERE bs.booking_id=b.id) as svc_total
    FROM bookings b
    JOIN users u ON b.customer_id=u.id
    JOIN providers pr ON b.provider_id=pr.id
    JOIN categories c ON pr.category_id=c.id
    WHERE b.provider_id=? ORDER BY b.created_at DESC");
$stmt->execute([$pid]);
$all = $stmt->fetchAll();
if ($filter !== 'all') $all = array_filter($all, fn($b) => $b['status'] === $filter);
$counts = array_count_values(array_column($stmt->fetchAll() ?: [], 'status'));
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<style>
.booking-card {
  background:#fff; border-radius:16px; border:1px solid #f1f5f9;
  box-shadow:0 2px 8px rgba(0,0,0,.06); overflow:hidden;
  transition:all .2s ease;
}
.booking-card:hover { box-shadow:0 8px 24px rgba(0,0,0,.1); transform:translateY(-2px); }
.booking-card-header {
  padding:1rem 1.25rem; border-bottom:1px solid #f8fafc;
  display:flex; align-items:center; justify-content:space-between;
  background:#fafbfc;
}
.booking-card-body { padding:1.25rem; }
.booking-card-footer {
  padding:.875rem 1.25rem; border-top:1px solid #f8fafc;
  display:flex; align-items:center; justify-content:space-between;
  background:#fafbfc; flex-wrap:wrap; gap:.5rem;
}
.cust-avatar {
  width:44px; height:44px; border-radius:50%;
  background:linear-gradient(135deg,#2563eb,#7c3aed);
  color:#fff; font-weight:700; font-size:1.1rem;
  display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.info-row { display:flex; align-items:center; gap:.5rem; font-size:.85rem; color:#475569; margin-bottom:.4rem; }
.info-row i { width:16px; color:#94a3b8; flex-shrink:0; }
.info-row strong { color:#1e293b; }
.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:.5rem 1.5rem; margin-top:.75rem; }
.detail-item-label { font-size:.7rem; text-transform:uppercase; letter-spacing:1px; color:#94a3b8; font-weight:600; }
.detail-item-value { font-size:.875rem; font-weight:600; color:#1e293b; }
.booking-number { font-family:monospace; font-size:.9rem; font-weight:700; color:#2563eb; }
.notes-box { background:#f8fafc; border-radius:8px; padding:.75rem; margin-top:.75rem; font-size:.82rem; color:#64748b; border-left:3px solid #e2e8f0; }
</style>
<div class="dashboard-wrapper">
<div class="overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../../includes/sidebar_provider.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle"><i class="bi bi-list fs-5"></i></button>
      <div class="topbar-title">Bookings</div>
    </div>
    <span class="badge bg-primary"><?=count($all)?> shown</span>
  </div>
  <div class="page-content">

    <!-- Filter tabs -->
    <div class="card mb-4">
      <div class="card-body py-2">
        <div class="d-flex gap-2 flex-wrap align-items-center">
          <?php foreach(['all','pending','accepted','in_progress','completed','cancelled'] as $s): ?>
          <a href="?status=<?=$s?>" class="btn btn-sm <?=$filter===$s?'btn-success':'btn-outline-secondary'?>">
            <?=ucwords(str_replace('_',' ',$s))?>
          </a>
          <?php endforeach; ?>
          <div class="ms-auto">
            <input type="text" id="cardSearch" class="form-control form-control-sm" placeholder="Search customer..." style="width:200px" oninput="filterCards(this.value)">
          </div>
        </div>
      </div>
    </div>

    <?php if(empty($all)): ?>
    <div class="empty-state"><i class="bi bi-calendar-x"></i><p>No bookings found.</p></div>
    <?php else: ?>
    <div class="row g-3" id="bookingCards">
    <?php foreach($all as $b):
      $svcRows = $db->prepare("SELECT * FROM booking_services WHERE booking_id=?");
      $svcRows->execute([$b['id']]); $services = $svcRows->fetchAll();
      $inv = $db->prepare("SELECT id FROM invoices WHERE booking_id=?"); $inv->execute([$b['id']]); $hasInv = $inv->fetch();
    ?>
    <div class="col-lg-6" data-name="<?=strtolower(sanitize($b['cust_name']))?>">
      <div class="booking-card">

        <!-- Card Header -->
        <div class="booking-card-header">
          <div class="d-flex align-items-center gap-2">
            <span class="booking-number">#<?=$b['id']?></span>
            <?=statusBadge($b['status'])?>
          </div>
          <div class="text-muted small"><i class="bi bi-clock"></i> <?=timeAgo($b['created_at'])?></div>
        </div>

        <!-- Card Body -->
        <div class="booking-card-body">
          <!-- Customer Info -->
          <div class="d-flex align-items-center gap-3 mb-3 pb-3" style="border-bottom:1px solid #f1f5f9">
            <div class="cust-avatar"><?=strtoupper(substr($b['cust_name'],0,1))?></div>
            <div class="flex-fill">
              <div class="fw-700 fs-6"><?=sanitize($b['cust_name'])?></div>
              <div class="d-flex gap-3 flex-wrap mt-1">
                <div class="info-row mb-0"><i class="bi bi-envelope"></i><span><?=sanitize($b['cust_email'])?></span></div>
                <div class="info-row mb-0"><i class="bi bi-telephone"></i><span><?=sanitize($b['cust_phone']??'N/A')?></span></div>
              </div>
            </div>
          </div>

          <!-- Booking Details Grid -->
          <div class="detail-grid">
            <div>
              <div class="detail-item-label">Category</div>
              <div class="detail-item-value"><i class="bi bi-tag text-primary"></i> <?=sanitize($b['category_name'])?></div>
            </div>
            <div>
              <div class="detail-item-label">Scheduled Date</div>
              <div class="detail-item-value"><i class="bi bi-calendar3 text-success"></i> <?=formatDate($b['scheduled_date'])?></div>
            </div>
            <div>
              <div class="detail-item-label">Time</div>
              <div class="detail-item-value"><i class="bi bi-clock text-warning"></i> <?=date('h:i A',strtotime($b['scheduled_time']))?></div>
            </div>
            <div>
              <div class="detail-item-label">Services</div>
              <div class="detail-item-value"><i class="bi bi-list-check text-primary"></i> <?=count($services)?> service<?=count($services)!=1?'s':''?></div>
            </div>
          </div>

          <!-- Address -->
          <div class="info-row mt-3"><i class="bi bi-geo-alt-fill text-danger"></i><span><?=sanitize($b['address'])?></span></div>

          <!-- Services list -->
          <?php if(!empty($services)): ?>
          <div style="margin-top:.75rem">
            <div class="detail-item-label mb-2">Services Requested</div>
            <?php foreach($services as $svc): ?>
            <div class="d-flex justify-content-between align-items-center py-1" style="border-bottom:1px dashed #f1f5f9;font-size:.82rem">
              <span class="text-muted"><?=sanitize($svc['service_name'])?></span>
              <span class="fw-600"><?=formatCurrency($svc['subtotal'])?></span>
            </div>
            <?php endforeach; ?>
            <?php if($b['svc_total']): ?>
            <div class="d-flex justify-content-between mt-1 fw-700" style="font-size:.85rem">
              <span>Estimated Total</span>
              <span class="text-primary"><?=formatCurrency($b['svc_total'])?></span>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Notes -->
          <?php if(!empty($b['notes'])): ?>
          <div class="notes-box"><i class="bi bi-chat-left-text"></i> <?=sanitize($b['notes'])?></div>
          <?php endif; ?>
        </div>

        <!-- Card Footer — Actions -->
        <div class="booking-card-footer">
          <div class="text-muted small"><i class="bi bi-hash"></i> Booking <?=$b['id']?></div>
          <div class="d-flex gap-2 flex-wrap">
            <?php if($b['status']==='pending'): ?>
            <a href="?action=accept&id=<?=$b['id']?>" class="btn btn-sm btn-success" onclick="return confirm('Accept this booking?')"><i class="bi bi-check-circle"></i> Accept</a>
            <a href="?action=reject&id=<?=$b['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this booking?')"><i class="bi bi-x-circle"></i> Reject</a>
            <?php elseif($b['status']==='accepted'): ?>
            <a href="?action=inprogress&id=<?=$b['id']?>" class="btn btn-sm btn-info text-white" onclick="return confirm('Start service?')"><i class="bi bi-play-circle"></i> Start</a>
            <a href="<?=APP_URL?>/modules/provider/billing.php?booking_id=<?=$b['id']?>" class="btn btn-sm btn-primary"><i class="bi bi-cash-coin"></i> Finalize</a>
            <?php elseif($b['status']==='in_progress'): ?>
            <a href="<?=APP_URL?>/modules/provider/billing.php?booking_id=<?=$b['id']?>" class="btn btn-sm btn-primary"><i class="bi bi-cash-coin"></i> Finalize Billing</a>
            <?php elseif($b['status']==='completed'): ?>
            <?php if($hasInv): ?>
            <a href="<?=APP_URL?>/modules/user/invoice.php?id=<?=$b['id']?>" class="btn btn-sm btn-outline-success"><i class="bi bi-receipt"></i> View Invoice</a>
            <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script>
function filterCards(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#bookingCards [data-name]').forEach(card => {
    card.style.display = card.dataset.name.includes(q) ? '' : 'none';
  });
}
// timeAgo helper if not in PHP
</script>