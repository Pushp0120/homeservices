<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

// If already an approved provider, send to provider dashboard
if (currentRole() === 'provider') redirect(APP_URL . '/modules/provider/dashboard.php');
// If not even a provider applicant, send to customer dashboard
$db     = getDB();
$stmt   = $db->prepare("SELECT p.*, c.name as category_name FROM providers p JOIN categories c ON p.category_id=c.id WHERE p.user_id=?");
$stmt->execute([currentUserId()]);
$prov = $stmt->fetch();
if (!$prov) redirect(APP_URL . '/modules/user/dashboard.php');

// If they somehow got approved, refresh the session and redirect
if ($prov['approval_status'] === 'approved') {
    refreshProviderSession();
    redirect(APP_URL . '/modules/provider/dashboard.php');
}

$status    = $prov['approval_status']; // 'pending' or 'suspended'
$userName  = explode(' ', $_SESSION['name'])[0];
$bizName   = htmlspecialchars($prov['business_name']);
$catName   = htmlspecialchars($prov['category_name']);
$createdAt = date('d M Y, h:i A', strtotime($prov['created_at'] ?? 'now'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Application <?= $status === 'suspended' ? 'Suspended' : 'Pending Review' ?> | HomeServe</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; }
body {
  font-family: 'Inter', sans-serif;
  background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #1a1a3e 100%);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  overflow-x: hidden;
}

/* Floating orbs background */
body::before, body::after {
  content: '';
  position: fixed;
  border-radius: 50%;
  filter: blur(80px);
  opacity: .25;
  pointer-events: none;
  z-index: 0;
  animation: float 8s ease-in-out infinite;
}
body::before {
  width: 500px; height: 500px;
  background: #2563eb;
  top: -100px; left: -150px;
}
body::after {
  width: 400px; height: 400px;
  background: #7c3aed;
  bottom: -100px; right: -100px;
  animation-delay: 3s;
}
@keyframes float {
  0%, 100% { transform: translateY(0) scale(1); }
  50%       { transform: translateY(-30px) scale(1.04); }
}

.card-wrap {
  position: relative; z-index: 1;
  width: 100%; max-width: 620px;
  background: rgba(255,255,255,.97);
  border-radius: 28px;
  box-shadow: 0 32px 80px rgba(0,0,0,.4);
  overflow: hidden;
}

/* Header band */
.card-header-band {
  background: linear-gradient(135deg, #1e3a5f, #2563eb 60%, #7c3aed);
  padding: 2.25rem 2rem 1.75rem;
  text-align: center;
  color: #fff;
  position: relative;
  overflow: hidden;
}
.card-header-band::after {
  content: '';
  position: absolute;
  bottom: -1px; left: 0; right: 0;
  height: 28px;
  background: rgba(255,255,255,.97);
  border-radius: 28px 28px 0 0;
}
.logo-wrap { margin-bottom: 1rem; }
.logo-wrap img { height: 40px; }
.brand-name { font-size: 1.4rem; font-weight: 800; letter-spacing: -.5px; }
.brand-name span { color: #93c5fd; }

/* Pulse icon */
.pulse-icon {
  width: 80px; height: 80px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 1.25rem;
  font-size: 2.2rem;
  position: relative;
}
.pulse-icon.pending  { background: rgba(234,179,8,.15); color: #ca8a04; }
.pulse-icon.suspended{ background: rgba(239,68,68,.15);  color: #dc2626; }
.pulse-icon::before {
  content: '';
  position: absolute; inset: -8px;
  border-radius: 50%;
  border: 3px solid currentColor;
  opacity: .25;
  animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { transform: scale(1); opacity: .25; }
  50%       { transform: scale(1.15); opacity: .08; }
}

.card-body-area { padding: 1.75rem 2rem 2rem; }

.headline { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin-bottom: .4rem; }
.sub      { color: #64748b; font-size: .92rem; line-height: 1.7; }

/* Info strip */
.info-strip {
  display: flex; gap: .75rem; flex-wrap: wrap;
  background: #f8fafc; border-radius: 12px;
  padding: .875rem 1rem; margin: 1.25rem 0;
  border: 1px solid #e2e8f0;
}
.info-chip {
  display: flex; align-items: center; gap: .4rem;
  font-size: .8rem; font-weight: 600; color: #475569;
}
.info-chip i { color: #2563eb; }

/* Steps */
.steps { list-style: none; padding: 0; margin: 1.25rem 0; }
.step-item {
  display: flex; align-items: flex-start; gap: .875rem;
  padding: .75rem 0;
  border-bottom: 1px solid #f1f5f9;
}
.step-item:last-child { border-bottom: none; }
.step-num {
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: .78rem; font-weight: 800; flex-shrink: 0; margin-top: .1rem;
}
.step-num.done    { background: #dcfce7; color: #166534; }
.step-num.active  { background: #dbeafe; color: #1d4ed8; animation: stepPulse 1.5s ease-in-out infinite; }
.step-num.waiting { background: #f1f5f9; color: #94a3b8; }
@keyframes stepPulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(37,99,235,.3); }
  50%       { box-shadow: 0 0 0 8px rgba(37,99,235,0); }
}
.step-info .step-title  { font-size: .875rem; font-weight: 700; color: #1e293b; }
.step-info .step-desc   { font-size: .78rem;  color: #64748b; margin-top: .15rem; }

/* Timer bar */
.timer-bar-wrap {
  background: #f0fdf4; border: 1px solid #bbf7d0;
  border-radius: 12px; padding: 1rem 1.25rem; margin: 1rem 0;
}
.timer-label { font-size: .78rem; font-weight: 700; color: #166534; margin-bottom: .5rem; display: flex; align-items: center; gap: .4rem; }
.timer-bar { height: 6px; background: #dcfce7; border-radius: 3px; overflow: hidden; }
.timer-fill {
  height: 100%; width: 60%;
  background: linear-gradient(90deg, #10b981, #059669);
  border-radius: 3px;
  animation: shimmer 2s ease-in-out infinite;
}
@keyframes shimmer {
  0%   { opacity: 1; }
  50%  { opacity: .6; }
  100% { opacity: 1; }
}
.timer-text { font-size: .75rem; color: #4ade80; margin-top: .4rem; font-weight: 600; }

/* Action buttons */
.action-row { display: flex; gap: .75rem; flex-wrap: wrap; }
.btn-customer {
  flex: 1; min-width: 140px;
  display: flex; align-items: center; justify-content: center; gap: .45rem;
  padding: .75rem 1.25rem; border-radius: 12px;
  background: linear-gradient(135deg, #2563eb, #7c3aed);
  color: #fff; font-weight: 700; font-size: .9rem;
  text-decoration: none; border: none;
  transition: all .2s ease;
  box-shadow: 0 4px 16px rgba(37,99,235,.3);
}
.btn-customer:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(37,99,235,.4); color: #fff; }
.btn-logout {
  display: flex; align-items: center; justify-content: center; gap: .45rem;
  padding: .75rem 1.25rem; border-radius: 12px;
  background: #fff; color: #64748b; font-weight: 600; font-size: .9rem;
  text-decoration: none; border: 2px solid #e2e8f0;
  transition: all .2s ease;
}
.btn-logout:hover { border-color: #dc2626; color: #dc2626; background: #fef2f2; }

/* Suspended state overrides */
.suspended-note {
  background: #fef2f2; border: 1.5px solid #fca5a5;
  border-radius: 12px; padding: 1rem 1.25rem; margin: 1rem 0;
  font-size: .85rem; color: #991b1b;
}

/* Footer note */
.footer-note {
  margin-top: 1.5rem; padding-top: 1.25rem;
  border-top: 1px solid #f1f5f9;
  text-align: center; font-size: .78rem; color: #94a3b8;
}
.footer-note a { color: #2563eb; font-weight: 600; text-decoration: none; }
</style>
</head>
<body>

<div class="card-wrap">

  <!-- Header -->
  <div class="card-header-band">
    <div class="logo-wrap">
      <img src="<?= APP_URL ?>/assets/images/logo.svg" alt="HomeServe" onerror="this.style.display='none'">
    </div>
  </div>

  <!-- Body -->
  <div class="card-body-area">

    <!-- Icon -->
    <div class="pulse-icon <?= $status ?>">
      <?php if ($status === 'suspended'): ?>
        <i class="bi bi-slash-circle-fill"></i>
      <?php else: ?>
        <i class="bi bi-hourglass-split"></i>
      <?php endif; ?>
    </div>

    <?php if ($status === 'suspended'): ?>
      <!-- SUSPENDED STATE -->
      <div class="headline text-center">Account Suspended</div>
      <p class="sub text-center mb-0">Hi <?= $userName ?>, your provider account has been suspended by the admin.</p>

      <div class="suspended-note mt-3">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        If you believe this is a mistake, please contact support at
        <a href="mailto:support@homeserve.in" style="color:#dc2626;font-weight:700">support@homeserve.in</a>.
      </div>

    <?php else: ?>
      <!-- PENDING STATE -->
      <div class="headline text-center">🎉 Application Submitted!</div>
      <p class="sub text-center">
        Thank you, <strong><?= $userName ?></strong>! Your provider application for
        <strong><?= $bizName ?></strong> has been received and is currently under review.
      </p>

      <!-- Business info strip -->
      <div class="info-strip">
        <div class="info-chip"><i class="bi bi-building"></i> <?= $bizName ?></div>
        <div class="info-chip"><i class="bi bi-grid"></i> <?= $catName ?></div>
        <div class="info-chip"><i class="bi bi-calendar3"></i> Submitted <?= $createdAt ?></div>
      </div>

      <!-- Progress steps -->
      <ul class="steps">
        <li class="step-item">
          <div class="step-num done"><i class="bi bi-check-lg"></i></div>
          <div class="step-info">
            <div class="step-title">Registration Complete</div>
            <div class="step-desc">Your account and provider profile have been created successfully.</div>
          </div>
        </li>
        <li class="step-item">
          <div class="step-num active"><i class="bi bi-search"></i></div>
          <div class="step-info">
            <div class="step-title">Admin Review — In Progress</div>
            <div class="step-desc">Our team is verifying your details. This usually takes up to <strong>24 hours</strong>.</div>
          </div>
        </li>
        <li class="step-item">
          <div class="step-num waiting">3</div>
          <div class="step-info">
            <div class="step-title">Provider Dashboard Unlocked</div>
            <div class="step-desc">Once approved, you'll be automatically redirected to your provider panel — no need to log out.</div>
          </div>
        </li>
      </ul>

      <!-- Estimated timer bar -->
      <div class="timer-bar-wrap">
        <div class="timer-label"><i class="bi bi-clock-fill"></i> Estimated Review Time</div>
        <div class="timer-bar"><div class="timer-fill"></div></div>
        <div class="timer-text">⏱ Usually approved within 24 hours of submission</div>
      </div>

    <?php endif; ?>

    <!-- Action buttons -->
    <div class="action-row mt-3">
      <a href="<?= APP_URL ?>/modules/user/dashboard.php" class="btn-customer">
        <i class="bi bi-house-fill"></i>
        Browse as Customer
      </a>
      <a href="<?= APP_URL ?>/logout.php" class="btn-logout">
        <i class="bi bi-box-arrow-left"></i>
        Logout
      </a>
    </div>

    <!-- Footer note -->
    <div class="footer-note">
      Need help? Email us at
      <a href="mailto:support@homeserve.in">support@homeserve.in</a>
      &nbsp;·&nbsp;
      <a href="<?= APP_URL ?>/modules/user/dashboard.php">Go to Customer Dashboard</a>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-refresh every 30s to detect approval without needing to log out
setTimeout(() => location.reload(), 30000);
</script>
</body>
</html>
