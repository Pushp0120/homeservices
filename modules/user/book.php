<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
if (currentRole() !== 'customer' && currentRole() !== 'provider') redirect(APP_URL . '/modules/user/dashboard.php');

$db = getDB();
$uid = currentUserId();
$providerId = (int)($_GET['provider_id'] ?? 0);

if (!$providerId) redirect(APP_URL . '/modules/user/browse.php');

$stmt = $db->prepare("SELECT p.*, u.name as provider_name, u.email, u.phone,
    c.name as category_name, c.id as cat_id
    FROM providers p JOIN users u ON p.user_id=u.id JOIN categories c ON p.category_id=c.id
    WHERE p.id=? AND p.approval_status='approved'");
$stmt->execute([$providerId]);
$provider = $stmt->fetch();
if (!$provider) { echo '<p>Provider not found.</p>'; exit; }

$services = $db->prepare("SELECT * FROM services WHERE category_id=? AND status='active' ORDER BY name");
$services->execute([$provider['cat_id']]);
$serviceList = $services->fetchAll();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date    = $_POST['scheduled_date'] ?? '';
    $time    = $_POST['scheduled_time'] ?? '';
    $address = sanitize($_POST['address'] ?? '');
    $notes   = sanitize($_POST['notes'] ?? '');
    $svcs    = $_POST['services'] ?? [];

    if (!$date || !$time || !$address || empty($svcs)) {
        $error = 'Please fill in all required fields and select at least one service.';
    } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
        $error = 'Please select a future date.';
    
    } else {
        verifyCsrf();
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO bookings (customer_id,provider_id,scheduled_date,scheduled_time,address,notes) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$uid, $providerId, $date, $time, $address, $notes]);
            $bookingId = $db->lastInsertId();
            $estimatedTotal = 0;
            foreach ($svcs as $svcId => $dummy) {
                $svcRow = $db->prepare("SELECT * FROM services WHERE id=?");
                $svcRow->execute([(int)$svcId]);
                $svcData = $svcRow->fetch();
                if ($svcData) {
                    $ins = $db->prepare("INSERT INTO booking_services (booking_id,service_id,service_name,service_price,quantity) VALUES (?,?,?,?,?)");
                    $ins->execute([$bookingId, $svcData['id'], $svcData['name'], $svcData['price'], 1]);
                    $estimatedTotal += $svcData['price'];
                }
            }
            $db->commit();
            $redirectUrl = APP_URL . '/modules/user/booking_detail.php?id=' . $bookingId . '&success=1';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success'        => true,
                    'redirect'       => $redirectUrl,
                    'bookingId'      => $bookingId,
                    'providerName'   => $provider['business_name'],
                    'estimatedTotal' => $estimatedTotal,
                    'taxRate'        => TAX_RATE,
                    'date'           => $date,
                    'time'           => $time,
                ]);
                exit;
            }
            redirect($redirectUrl);
        } catch (Exception $e) {
            $db->rollback();
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Booking failed. Please try again.']);
                exit;
            }
            $error = 'Booking failed. Please try again.';
        }
    }
}
$pageTitle = 'Book Service';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<style>
.datetime-group { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.datetime-card {
  border: 2px solid #e2e8f0; border-radius: 14px; padding: 1rem 1.25rem;
  cursor: pointer; transition: all .2s ease; background: #fff; position: relative;
}
.datetime-card:hover { border-color: var(--primary); background: #eff6ff; }
.datetime-card.filled { border-color: var(--primary); background: #eff6ff; }
.datetime-card .dc-label {
  font-size: .7rem; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
  color: #94a3b8; margin-bottom: .25rem; display: flex; align-items: center; gap: .4rem;
}
.datetime-card .dc-label.filled-label { color: var(--primary); }
.datetime-card .dc-value { font-size: 1.1rem; font-weight: 700; color: #0f172a; min-height: 1.5rem; }
.datetime-card .dc-placeholder { font-size: .95rem; color: #cbd5e1; font-weight: 400; }
/* FIX: pointer-events:none so all clicks reach the card div, not the hidden input */
.datetime-card input[type=date],
.datetime-card input[type=time] {
  position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
  pointer-events: none;
}
.time-slots { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .5rem; }
.time-slot {
  padding: .4rem .9rem; border-radius: 8px; border: 1.5px solid #e2e8f0;
  font-size: .8rem; font-weight: 600; cursor: pointer; transition: all .2s ease;
  background: #fff; color: #475569;
}
.time-slot:hover { border-color: var(--primary); color: var(--primary); background: #eff6ff; }
.time-slot.active { border-color: var(--primary); background: var(--primary); color: #fff; }
.address-wrap { position: relative; }
.address-wrap textarea { padding-right: 120px; }
.btn-locate {
  position: absolute; right: .5rem; top: .5rem;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  color: #fff; border: none; border-radius: 8px;
  padding: .4rem .85rem; font-size: .78rem; font-weight: 600;
  cursor: pointer; transition: all .2s ease; display: flex; align-items: center; gap: .35rem;
  white-space: nowrap;
}
.btn-locate:hover { opacity: .9; transform: translateY(-1px); }
.btn-locate.loading i { animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.service-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
.service-check-item {
  border: 2px solid #e2e8f0; border-radius: 12px; padding: .75rem 1rem;
  cursor: pointer; transition: all .2s ease; user-select: none;
  display: flex; align-items: center; gap: .75rem;
}
.service-check-item:hover { border-color: var(--primary); background: #eff6ff; }
.service-check-item.selected { border-color: var(--primary); background: #eff6ff; }
.service-check-item.selected .svc-check-icon { background: var(--primary); color: #fff; }
.svc-check-icon {
  width: 32px; height: 32px; border-radius: 8px; border: 2px solid #e2e8f0;
  display: flex; align-items: center; justify-content: center;
  font-size: .9rem; flex-shrink: 0; transition: all .2s ease; color: var(--primary);
}
.svc-name { font-size: .85rem; font-weight: 600; color: #1e293b; }
.svc-price { font-size: .78rem; color: #64748b; }
.service-check-item.selected .svc-name { color: var(--primary); }
.svc-summary { background: #f8fafc; border-radius: 10px; padding: 1rem; min-height: 60px; }
.svc-tag {
  display: inline-flex; align-items: center; gap: .4rem;
  background: var(--primary); color: #fff;
  border-radius: 20px; padding: .25rem .75rem; font-size: .78rem; font-weight: 600; margin: .2rem;
}
.svc-tag button { background: none; border: none; color: rgba(255,255,255,.7); font-size: .85rem; cursor: pointer; padding: 0; line-height: 1; }
.svc-tag button:hover { color: #fff; }
.action-buttons {
  display: flex; gap: 1rem; align-items: center;
  padding: 1.5rem; background: #fff;
  border-radius: 14px; border: 1px solid #f1f5f9;
  box-shadow: 0 4px 16px rgba(0,0,0,.06); margin-top: .5rem;
}
.btn-confirm {
  flex: 1; padding: .875rem 1.5rem;
  background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
  color: #fff; border: none; border-radius: 12px;
  font-size: 1rem; font-weight: 700; cursor: pointer;
  transition: all .25s ease; display: flex; align-items: center; justify-content: center; gap: .6rem;
  box-shadow: 0 4px 20px rgba(37,99,235,.35);
}
.btn-confirm:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(37,99,235,.45); }
.btn-confirm:active { transform: scale(.97); }
.btn-confirm i { font-size: 1.1rem; }
.btn-cancel-book {
  padding: .875rem 1.5rem; background: #fff; color: #64748b;
  border: 2px solid #e2e8f0; border-radius: 12px;
  font-size: .9rem; font-weight: 600; text-decoration: none;
  transition: all .2s ease; display: flex; align-items: center; gap: .5rem; white-space: nowrap;
}
.btn-cancel-book:hover { border-color: #dc2626; color: #dc2626; background: #fef2f2; }

/* ── BOOKING CONFIRM POPUP ───────────────────────────────────── */
#bookingSuccessOverlay {
  display: none;
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(8, 14, 29, 0.82);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  align-items: center; justify-content: center;
}
#bookingSuccessOverlay.show { display: flex; animation: overlayIn .3s ease; }
@keyframes overlayIn {
  from { opacity: 0; } to { opacity: 1; }
}
.bsc-card {
  background: #fff;
  border-radius: 28px;
  padding: 2.75rem 2.5rem 2.25rem;
  max-width: 420px; width: 90%;
  text-align: center;
  box-shadow: 0 40px 90px rgba(0,0,0,.45);
  animation: cardPop .4s cubic-bezier(.34,1.56,.64,1);
  position: relative; overflow: hidden;
}
@keyframes cardPop {
  from { opacity: 0; transform: scale(.75) translateY(30px); }
  to   { opacity: 1; transform: scale(1)  translateY(0); }
}
/* top gradient bar */
.bsc-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; height: 5px;
  background: linear-gradient(90deg, #2563eb, #7c3aed, #06b6d4);
}
/* Animated check ring */
.bsc-ring {
  width: 88px; height: 88px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2563eb, #7c3aed);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 1.5rem;
  box-shadow: 0 10px 36px rgba(37,99,235,.35);
  animation: ringPop .5s .1s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes ringPop {
  from { opacity: 0; transform: scale(.4) rotate(-180deg); }
  to   { opacity: 1; transform: scale(1)  rotate(0deg); }
}
.bsc-ring .bsc-check {
  font-size: 2.4rem; color: #fff;
  animation: checkBounce .4s .45s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes checkBounce {
  from { opacity: 0; transform: scale(.3); }
  to   { opacity: 1; transform: scale(1); }
}
.bsc-title {
  font-family: 'Sora', 'Inter', sans-serif;
  font-size: 1.45rem; font-weight: 800;
  color: #0f172a; letter-spacing: -.5px;
  margin-bottom: .3rem;
}
.bsc-sub {
  font-size: .875rem; color: #64748b;
  line-height: 1.6; margin-bottom: 1.5rem;
}
.bsc-detail-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: .6rem .9rem;
  background: #f8fafc;
  border-radius: 10px;
  margin-bottom: .5rem;
  font-size: .83rem;
}
.bsc-detail-row .bdc-key { color: #64748b; display: flex; align-items: center; gap: .4rem; }
.bsc-detail-row .bdc-val { font-weight: 700; color: #0f172a; }
.bsc-total {
  display: flex; justify-content: space-between; align-items: center;
  padding: .75rem .9rem;
  background: linear-gradient(135deg, rgba(37,99,235,.07), rgba(124,58,237,.07));
  border: 1.5px solid rgba(37,99,235,.15);
  border-radius: 10px;
  margin: .75rem 0 1.5rem;
}
.bsc-total .bdc-key { color: #2563eb; font-weight: 700; font-size: .88rem; }
.bsc-total .bdc-val { font-size: 1.1rem; font-weight: 800; color: #2563eb; }
/* Progress bar auto-redirect */
.bsc-progress-wrap {
  background: #f1f5f9; border-radius: 50px; height: 4px;
  margin-bottom: 1.25rem; overflow: hidden;
}
.bsc-progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #2563eb, #7c3aed);
  border-radius: 50px;
  width: 100%;
  transition: width 2.4s linear;
}
.bsc-progress-bar.shrink { width: 0%; }
.bsc-redirect-note {
  font-size: .75rem; color: #94a3b8;
  margin-bottom: 1rem;
}
.bsc-btn {
  width: 100%;
  padding: .8rem 1rem;
  background: linear-gradient(135deg, #2563eb, #7c3aed);
  color: #fff; border: none; border-radius: 12px;
  font-size: .95rem; font-weight: 700;
  cursor: pointer; transition: all .2s;
  display: flex; align-items: center; justify-content: center; gap: .5rem;
}
.bsc-btn:hover { opacity: .92; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,.35); }
/* Confetti particles */
.bsc-confetti {
  position: absolute; top: 0; left: 0; right: 0; bottom: 0;
  pointer-events: none; overflow: hidden; border-radius: 28px;
}
.conf-dot {
  position: absolute;
  width: 7px; height: 7px; border-radius: 2px;
  animation: confFall 1.8s ease-out forwards;
  opacity: 0;
}
@keyframes confFall {
  0%   { opacity: 1; transform: translateY(-20px) rotate(0deg); }
  80%  { opacity: 1; }
  100% { opacity: 0; transform: translateY(280px) rotate(720deg); }
}
</style>

<?php require_once __DIR__ . '/../../includes/topnav_user.php'; ?>
<div class="user-main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <div class="topbar-title">Book Service</div>
    </div>
    <a href="<?= APP_URL ?>/modules/user/browse.php" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>
  <div class="page-content">
    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= $error ?></div>
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-md-4 order-md-2">
        <div class="card mb-3">
          <div class="card-body text-center">
            <div class="provider-avatar mx-auto mb-2" style="width:64px;height:64px;font-size:1.8rem">
              <?= strtoupper(substr($provider['business_name'],0,1)) ?>
            </div>
            <h6 class="fw-700"><?= sanitize($provider['business_name']) ?></h6>
            <div class="text-muted small mb-1"><?= sanitize($provider['category_name']) ?></div>
            <div class="text-muted small"><i class="bi bi-person"></i> <?= sanitize($provider['provider_name']) ?></div>
            <div class="text-muted small"><i class="bi bi-telephone"></i> <?= sanitize($provider['phone'] ?? 'N/A') ?></div>
            <div class="text-muted small"><i class="bi bi-briefcase"></i> <?= $provider['experience_years'] ?> years exp</div>
            <div class="mt-2 fw-700 text-primary">From &#8377;<?= number_format($provider['base_price'], 0) ?></div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-title">Booking Summary</span></div>
          <div class="card-body">
            <div id="summaryItems" class="mb-2 text-muted small">No services selected yet.</div>
            <hr class="my-2">
            <div class="d-flex justify-content-between mb-1"><span class="text-muted small">Subtotal</span><span id="subtotalDisplay">&#8377;0</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted small">Tax (<?= TAX_RATE ?>%)</span><span id="taxDisplay">&#8377;0</span></div>
            <div class="d-flex justify-content-between fw-700 pt-2 border-top"><span>Estimated Total</span><span id="totalDisplay" class="text-primary">&#8377;0</span></div>
            <input type="hidden" id="taxRate" value="<?= TAX_RATE ?>">
            <p class="text-muted small mt-2 mb-0">Final total calculated by provider after service.</p>
          </div>
        </div>
      </div>

      <div class="col-md-8 order-md-1">
        <form method="POST" id="bookingForm">
          <?= csrfField() ?>

          <div class="card mb-3">
            <div class="card-header"><span class="card-title"><i class="bi bi-calendar3"></i> When do you need the service?</span></div>
            <div class="card-body">
              <div class="datetime-group mb-3">
                <div class="datetime-card" id="dateCard">
                  <div class="dc-label" id="dateLabelEl"><i class="bi bi-calendar3"></i> Date</div>
                  <div class="dc-value" id="dateDisplay"><span class="dc-placeholder">Pick a date</span></div>
                  <input type="date" name="scheduled_date" id="dateInput" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="datetime-card" id="timeCard">
                  <div class="dc-label" id="timeLabelEl"><i class="bi bi-clock"></i> Time</div>
                  <div class="dc-value" id="timeDisplay"><span class="dc-placeholder">Pick a slot</span></div>
                  <input type="hidden" name="scheduled_time" id="timeInput" required>
                </div>
              </div>
              <label class="form-label fw-600" style="font-size:.85rem"><i class="bi bi-clock-history text-primary"></i> Available Time Slots</label>
              <div class="time-slots" id="timeSlots">
                <?php
                $slots = ['08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00'];
                $labels= ['8 AM','9 AM','10 AM','11 AM','12 PM','1 PM','2 PM','3 PM','4 PM','5 PM','6 PM'];
                foreach ($slots as $i => $slot): ?>
                <div class="time-slot" onclick="selectTime('<?= $slot ?>','<?= $labels[$i] ?>')"><?= $labels[$i] ?></div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="card mb-3">
            <div class="card-header"><span class="card-title"><i class="bi bi-geo-alt"></i> Service Address</span></div>
            <div class="card-body">
              <div class="address-wrap">
                <textarea name="address" id="addressField" class="form-control" rows="3"
                  placeholder="Enter your full address or click 'Use My Location'" required></textarea>
                <button type="button" class="btn-locate" id="locateBtn" onclick="getLocation()">
                  <i class="bi bi-geo-alt-fill"></i> My Location
                </button>
              </div>
              <div id="locationStatus" class="mt-2 small" style="display:none"></div>
            </div>
          </div>

          <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span class="card-title"><i class="bi bi-list-check"></i> Select Services</span>
              <span class="badge bg-primary" id="svcCount">0 selected</span>
            </div>
            <div class="card-body">
              <?php if (empty($serviceList)): ?>
              <p class="text-muted">No services listed for this category.</p>
              <?php else: ?>
              <div class="service-grid" id="serviceGrid">
                <?php foreach ($serviceList as $s): ?>
                <div class="service-check-item" data-id="<?= $s['id'] ?>" data-price="<?= $s['price'] ?>" data-name="<?= sanitize($s['name']) ?>">
                  <div class="svc-check-icon"><i class="bi bi-check-lg"></i></div>
                  <div>
                    <div class="svc-name"><?= sanitize($s['name']) ?></div>
                    <div class="svc-price">&#8377;<?= number_format($s['price'],0) ?> / <?= sanitize($s['unit']) ?></div>
                  </div>
                  <input type="checkbox" name="services[<?= $s['id'] ?>]" value="1" style="display:none" class="svc-checkbox">
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="card mb-3">
            <div class="card-header"><span class="card-title"><i class="bi bi-chat-left-text"></i> Additional Notes</span></div>
            <div class="card-body">
              <textarea name="notes" class="form-control" rows="2" placeholder="Any special instructions or details about the job..."></textarea>
            </div>
          </div>


          <div class="action-buttons">
            <button type="submit" class="btn-confirm">
              <i class="bi bi-calendar-check-fill"></i>
              Confirm Booking
            </button>
            <a href="<?= APP_URL ?>/modules/user/browse.php" class="btn-cancel-book">
              <i class="bi bi-x-circle"></i> Cancel
            </a>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>

<!-- ── BOOKING SUCCESS OVERLAY ─────────────────────────────── -->
<div id="bookingSuccessOverlay" role="dialog" aria-modal="true" aria-labelledby="bscTitle">
  <div class="bsc-card" id="bscCard">
    <!-- confetti container -->
    <div class="bsc-confetti" id="bscConfetti"></div>

    <div class="bsc-ring">
      <i class="bi bi-check-lg bsc-check"></i>
    </div>

    <div class="bsc-title" id="bscTitle">Booking Confirmed!</div>
    <p class="bsc-sub" id="bscSub">Your booking has been placed successfully.<br>The provider will confirm shortly.</p>

    <div class="bsc-detail-row">
      <span class="bdc-key"><i class="bi bi-shop"></i> Provider</span>
      <span class="bdc-val" id="bscProvider">—</span>
    </div>
    <div class="bsc-detail-row">
      <span class="bdc-key"><i class="bi bi-calendar-event"></i> Date</span>
      <span class="bdc-val" id="bscDate">—</span>
    </div>
    <div class="bsc-detail-row">
      <span class="bdc-key"><i class="bi bi-clock"></i> Time</span>
      <span class="bdc-val" id="bscTime">—</span>
    </div>
    <div class="bsc-total">
      <span class="bdc-key"><i class="bi bi-receipt"></i> Est. Total</span>
      <span class="bdc-val" id="bscTotal">—</span>
    </div>

    <div class="bsc-progress-wrap">
      <div class="bsc-progress-bar" id="bscProgressBar"></div>
    </div>
    <p class="bsc-redirect-note" id="bscNote">Redirecting to your booking details…</p>

    <button class="bsc-btn" id="bscGoBtn" onclick="goToBooking()">
      <i class="bi bi-arrow-right-circle-fill"></i> View My Booking
    </button>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script>
// DATE DISPLAY
document.getElementById('dateInput').addEventListener('change', function() {
  const v = this.value;
  if (!v) return;
  const d = new Date(v + 'T00:00:00');
  document.getElementById('dateDisplay').innerHTML =
    '<span style="font-size:.75rem;display:block;color:#2563eb;font-weight:700">' +
    d.toLocaleDateString('en-IN',{weekday:'long'}) + '</span>' +
    d.toLocaleDateString('en-IN',{day:'numeric',month:'long',year:'numeric'});
  document.getElementById('dateCard').classList.add('filled');
  document.getElementById('dateLabelEl').classList.add('filled-label');
});

// DATE CARD CLICK — FIX: simplified, no e.target check needed (input has pointer-events:none)
document.getElementById('dateCard').addEventListener('click', function() {
  const inp = document.getElementById('dateInput');
  try {
    if (inp.showPicker) inp.showPicker();
  } catch(e) {
    inp.click();
  }
});

// TIME SLOT
function selectTime(value, label) {
  document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('active'));
  event.target.classList.add('active');
  document.getElementById('timeInput').value = value;
  document.getElementById('timeDisplay').innerHTML =
    '<span style="font-size:.75rem;display:block;color:#2563eb;font-weight:700">Selected</span>' + label;
  document.getElementById('timeCard').classList.add('filled');
  document.getElementById('timeLabelEl').classList.add('filled-label');
}

// AUTO LOCATION
function getLocation() {
  const btn = document.getElementById('locateBtn');
  const status = document.getElementById('locationStatus');
  if (!navigator.geolocation) {
    status.style.display = 'block';
    status.innerHTML = '<i class="bi bi-exclamation-triangle text-warning"></i> Geolocation not supported.';
    return;
  }
  btn.classList.add('loading');
  btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Locating...';
  status.style.display = 'block';
  status.innerHTML = '<i class="bi bi-hourglass-split text-primary"></i> Getting your location...';
  navigator.geolocation.getCurrentPosition(
    async (pos) => {
      const { latitude, longitude } = pos.coords;
      try {
        const r = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&addressdetails=1`);
        const data = await r.json();
        const addr = data.display_name || `Lat: ${latitude.toFixed(5)}, Lon: ${longitude.toFixed(5)}`;
        document.getElementById('addressField').value = addr;
        status.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> Location detected successfully!';
        btn.innerHTML = '<i class="bi bi-geo-alt-fill"></i> My Location';
        btn.classList.remove('loading');
      } catch(e) {
        document.getElementById('addressField').value = `${latitude.toFixed(5)}, ${longitude.toFixed(5)}`;
        status.innerHTML = '<i class="bi bi-check-circle text-success"></i> Coordinates set. Please verify the address.';
        btn.innerHTML = '<i class="bi bi-geo-alt-fill"></i> My Location';
        btn.classList.remove('loading');
      }
    },
    (err) => {
      btn.innerHTML = '<i class="bi bi-geo-alt-fill"></i> My Location';
      btn.classList.remove('loading');
      status.innerHTML = '<i class="bi bi-x-circle text-danger"></i> Location access denied. Please enter manually.';
    },
    { timeout: 10000 }
  );
}

// SERVICE CHECKBOXES
const selectedServices = {};

document.querySelectorAll('.svc-checkbox').forEach(cb => {
  cb.addEventListener('change', function() {
    const item  = this.closest('.service-check-item');
    const id    = item.dataset.id;
    const price = parseFloat(item.dataset.price);
    const name  = item.dataset.name;
    if (this.checked) {
      selectedServices[id] = { price, name };
      item.classList.add('selected');
    } else {
      delete selectedServices[id];
      item.classList.remove('selected');
    }
    updateBookingSummary();
  });
});

document.querySelectorAll('.service-check-item').forEach(item => {
  item.addEventListener('click', function(e) {
    if (e.target === this.querySelector('.svc-checkbox')) return;
    const cb = this.querySelector('.svc-checkbox');
    cb.checked = !cb.checked;
    cb.dispatchEvent(new Event('change'));
  });
});

function updateBookingSummary() {
  const count = Object.keys(selectedServices).length;
  document.getElementById('svcCount').textContent = count + ' selected';
  let subtotal = 0;
  let summaryHtml = '';
  Object.entries(selectedServices).forEach(([id, s]) => {
    subtotal += s.price;
    summaryHtml += `<div class="d-flex justify-content-between mb-1 small"><span>${s.name}</span><span class="fw-600">&#8377;${Math.round(s.price).toLocaleString('en-IN')}</span></div>`;
  });
  document.getElementById('summaryItems').innerHTML = summaryHtml || '<span class="text-muted small">No services selected yet.</span>';
  const tax   = subtotal * <?= TAX_RATE ?> / 100;
  const total = subtotal + tax;
  const fmt = v => '&#8377;' + Math.round(v).toLocaleString('en-IN');
  document.getElementById('subtotalDisplay').innerHTML = fmt(subtotal);
  document.getElementById('taxDisplay').innerHTML      = fmt(tax);
  document.getElementById('totalDisplay').innerHTML    = fmt(total);
}


document.getElementById('bookingForm').addEventListener('submit', function(e) {
    e.preventDefault();

    if (Object.keys(selectedServices).length === 0) {
        showFormError('Please select at least one service.');
        return;
    }

    const btn = document.querySelector('.btn-confirm');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split" style="animation:spin .7s linear infinite"></i> Placing Booking…';

    const formData = new FormData(this);

    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showBookingSuccess(data);
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-calendar-check-fill"></i> Confirm Booking';
            showFormError(data.error || 'Booking failed. Please try again.');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-calendar-check-fill"></i> Confirm Booking';
        showFormError('Network error. Please check your connection and try again.');
    });
});

function showFormError(msg) {
    let el = document.getElementById('inlineError');
    if (!el) {
        el = document.createElement('div');
        el.id = 'inlineError';
        el.className = 'alert alert-danger d-flex align-items-center gap-2 mt-2';
        document.querySelector('.action-buttons').after(el);
    }
    el.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>' + msg;
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

let _redirectUrl = '';
function showBookingSuccess(data) {
    _redirectUrl = data.redirect;

    // Fill popup details
    document.getElementById('bscProvider').textContent = data.providerName || '—';

    // Format date
    if (data.date) {
        const d = new Date(data.date + 'T00:00:00');
        document.getElementById('bscDate').textContent =
            d.toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' });
    }

    // Format time
    if (data.time) {
        const [h, m] = data.time.split(':');
        const hr = parseInt(h);
        document.getElementById('bscTime').textContent =
            (hr % 12 || 12) + ':' + m + ' ' + (hr >= 12 ? 'PM' : 'AM');
    }

    // Total with tax
    const tax   = data.estimatedTotal * data.taxRate / 100;
    const total = data.estimatedTotal + tax;
    document.getElementById('bscTotal').textContent =
        total > 0 ? '\u20B9' + Math.round(total).toLocaleString('en-IN') : 'To be confirmed';

    // Show overlay
    const overlay = document.getElementById('bookingSuccessOverlay');
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';

    // Launch confetti
    launchConfetti();

    // Start progress bar shrink (triggers after a tiny repaint delay)
    const bar = document.getElementById('bscProgressBar');
    requestAnimationFrame(() => requestAnimationFrame(() => bar.classList.add('shrink')));

    // Auto-redirect after 2.6s
    setTimeout(goToBooking, 2600);
}

function goToBooking() {
    if (_redirectUrl) window.location.href = _redirectUrl;
}

function launchConfetti() {
    const colors = ['#2563eb','#7c3aed','#06b6d4','#10b981','#f59e0b','#f43f5e'];
    const container = document.getElementById('bscConfetti');
    container.innerHTML = '';
    for (let i = 0; i < 28; i++) {
        const dot = document.createElement('div');
        dot.className = 'conf-dot';
        dot.style.cssText = [
            'left:'   + Math.random() * 100 + '%',
            'top:'    + (Math.random() * 30 - 30) + 'px',
            'background:' + colors[Math.floor(Math.random() * colors.length)],
            'animation-delay:' + (Math.random() * .6) + 's',
            'animation-duration:' + (1.2 + Math.random() * .8) + 's',
            'transform:rotate(' + Math.random() * 360 + 'deg)',
            'width:' + (5 + Math.random() * 6) + 'px',
            'height:' + (5 + Math.random() * 6) + 'px',
        ].join(';');
        container.appendChild(dot);
    }
}
</script>