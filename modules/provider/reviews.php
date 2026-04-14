<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
if (currentRole() !== 'provider') redirect(APP_URL . '/modules/user/dashboard.php');
$db  = getDB();
$pid = currentProviderId();
$pageTitle = 'My Reviews';

$reviews = $db->prepare("SELECT r.*, u.name as cust_name, b.scheduled_date
    FROM reviews r JOIN users u ON r.customer_id=u.id JOIN bookings b ON r.booking_id=b.id
    WHERE r.provider_id=? ORDER BY r.created_at DESC");
$reviews->execute([$pid]);
$reviewList = $reviews->fetchAll();

$avg = $db->prepare("SELECT ROUND(AVG(rating),1) as avg, COUNT(*) as total FROM reviews WHERE provider_id=?");
$avg->execute([$pid]);
$avgData = $avg->fetch();

function providerStarBar($rating, $reviewList) {
    $html = '';
    for ($i=5;$i>=1;$i--) {
        $count = count(array_filter($reviewList, fn($r) => $r['rating']==$i));
        $pct   = $reviewList ? round($count/count($reviewList)*100) : 0;
        $html .= "<div class=\"d-flex align-items-center gap-2 mb-1\">
            <span class=\"text-muted small\" style=\"width:20px\">$i</span>
            <i class=\"bi bi-star-fill text-warning\"></i>
            <div class=\"progress flex-fill\" style=\"height:8px\"><div class=\"progress-bar bg-warning\" style=\"width:{$pct}%\"></div></div>
            <span class=\"text-muted small\" style=\"width:30px\">$count</span>
        </div>";
    }
    return $html;
}
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<div class="dashboard-wrapper">
<div class="overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../../includes/sidebar_provider.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle"><i class="bi bi-list fs-5"></i></button>
      <div class="topbar-title">My Reviews</div>
    </div>
  </div>
  <div class="page-content">
    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <div class="card text-center p-4">
          <div style="font-size:3rem;font-weight:800;color:#0f172a"><?= $avgData['avg'] ?? '0.0' ?></div>
          <div class="rating-stars justify-content-center d-flex gap-1 my-2">
            <?php for($i=1;$i<=5;$i++): ?>
            <i class="bi bi-star-fill <?= $i<=round($avgData['avg'] ?? 0) ? 'text-warning' : 'text-muted opacity-25' ?>"></i>
            <?php endfor; ?>
          </div>
          <div class="text-muted small"><?= $avgData['total'] ?> reviews</div>
        </div>
      </div>
      <div class="col-md-8">
        <div class="card p-4">
          <div class="fw-700 mb-3">Rating Breakdown</div>
          <?= providerStarBar(0, $reviewList) ?>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">All Reviews</span></div>
      <div class="card-body">
        <?php if (empty($reviewList)): ?>
        <div class="empty-state"><i class="bi bi-star"></i><p>No reviews yet.</p></div>
        <?php else: foreach ($reviewList as $r): ?>
        <div class="p-3 mb-3 rounded border" style="background:#f8fafc">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="fw-600"><?= sanitize($r['cust_name']) ?></div>
              <div class="text-muted small"><?= formatDate($r['created_at']) ?> &bull; Service: <?= formatDate($r['scheduled_date']) ?></div>
            </div>
            <div class="d-flex align-items-center gap-1">
              <?php for($i=1;$i<=5;$i++): ?>
              <i class="bi bi-star-fill <?= $i<=$r['rating'] ? 'text-warning' : 'text-muted opacity-25' ?>"></i>
              <?php endfor; ?>
              <span class="fw-700 ms-1"><?= $r['rating'] ?></span>
            </div>
          </div>
          <?php if ($r['review_text']): ?>
          <p class="mt-2 mb-0"><?= sanitize($r['review_text']) ?></p>
          <?php endif; ?>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
