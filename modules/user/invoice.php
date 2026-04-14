<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
$db  = getDB();
$uid = currentUserId();
$bid = (int)($_GET['id'] ?? 0);
$role = currentRole();

// Build access condition based on role:
// - Customer: must be the booking owner
// - Provider: must be the assigned provider of the booking
// - Admin: can view any invoice
if ($role === 'admin') {
    $stmt = $db->prepare("SELECT b.*, inv.*,
        uc.name as cust_name, uc.email as cust_email, uc.phone as cust_phone,
        up.name as prov_name, up.email as prov_email, up.phone as prov_phone,
        p.business_name, p.id as provider_id, c.name as category
        FROM bookings b
        JOIN invoices inv ON inv.booking_id = b.id
        JOIN users uc ON b.customer_id = uc.id
        JOIN providers p ON b.provider_id = p.id
        JOIN users up ON p.user_id = up.id
        JOIN categories c ON p.category_id = c.id
        WHERE b.id = ?");
    $stmt->execute([$bid]);

} elseif ($role === 'provider') {
    $pid = currentProviderId();
    $stmt = $db->prepare("SELECT b.*, inv.*,
        uc.name as cust_name, uc.email as cust_email, uc.phone as cust_phone,
        up.name as prov_name, up.email as prov_email, up.phone as prov_phone,
        p.business_name, p.id as provider_id, c.name as category
        FROM bookings b
        JOIN invoices inv ON inv.booking_id = b.id
        JOIN users uc ON b.customer_id = uc.id
        JOIN providers p ON b.provider_id = p.id
        JOIN users up ON p.user_id = up.id
        JOIN categories c ON p.category_id = c.id
        WHERE b.id = ? AND b.provider_id = ?");
    $stmt->execute([$bid, $pid]);

} else {
    // Customer
    $stmt = $db->prepare("SELECT b.*, inv.*,
        uc.name as cust_name, uc.email as cust_email, uc.phone as cust_phone,
        up.name as prov_name, up.email as prov_email, up.phone as prov_phone,
        p.business_name, p.id as provider_id, c.name as category
        FROM bookings b
        JOIN invoices inv ON inv.booking_id = b.id
        JOIN users uc ON b.customer_id = uc.id
        JOIN providers p ON b.provider_id = p.id
        JOIN users up ON p.user_id = up.id
        JOIN categories c ON p.category_id = c.id
        WHERE b.id = ? AND b.customer_id = ?");
    $stmt->execute([$bid, $uid]);
}

$data = $stmt->fetch();

if (!$data) {
    ?>
    <!DOCTYPE html><html><head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
    <div class="d-flex align-items-center justify-content-center" style="min-height:100vh">
      <div class="text-center p-5">
        <div style="font-size:4rem">📄</div>
        <h4 class="mt-3">Invoice Not Found</h4>
        <p class="text-muted">This invoice doesn't exist or you don't have permission to view it.</p>
        <?php if ($role === 'provider'): ?>
        <a href="<?= APP_URL ?>/modules/provider/bookings.php" class="btn btn-primary">← Back to Bookings</a>
        <?php elseif ($role === 'admin'): ?>
        <a href="<?= APP_URL ?>/modules/admin/bookings.php" class="btn btn-primary">← Back to Bookings</a>
        <?php else: ?>
        <a href="<?= APP_URL ?>/modules/user/bookings.php" class="btn btn-primary">← Back to Bookings</a>
        <?php endif; ?>
      </div>
    </div>
    </body></html>
    <?php
    exit;
}

$services = $db->prepare("SELECT * FROM booking_services WHERE booking_id = ? ORDER BY id");
$services->execute([$bid]);
$items = $services->fetchAll();

// Determine back link based on role
$backLink = match($role) {
    'provider' => APP_URL . '/modules/provider/bookings.php',
    'admin'    => APP_URL . '/modules/admin/bookings.php',
    default    => APP_URL . '/modules/user/bookings.php',
};

$pageTitle = 'Invoice ' . $data['invoice_number'];

// Determine which sidebar to show
$sidebarFile = match($role) {
    'provider' => __DIR__ . '/../../includes/sidebar_provider.php',
    'admin'    => __DIR__ . '/../../includes/sidebar_admin.php',
    default    => __DIR__ . '/../../includes/sidebar_user.php',
};
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>


<?php require_once $sidebarFile; ?>

<div class="user-main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary sidebar-toggle no-print" id="sidebarToggle">
        <i class="bi bi-list fs-5"></i>
      </button>
      <div class="topbar-title">Invoice</div>
    </div>
    <div class="no-print d-flex gap-2">
      <a href="<?= $backLink ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <button onclick="window.print()" class="btn btn-sm btn-primary">
        <i class="bi bi-printer"></i> Print
      </button>
    </div>
  </div>

  <div class="page-content">
    <div class="invoice-box">

      <!-- Invoice Header -->
      <div class="invoice-header">
        <div class="row align-items-center">
          <div class="col">
            <div class="invoice-logo"><i class="bi bi-house-heart-fill"></i> HomeServe</div>
            <div class="text-muted small">Professional Home Services</div>
          </div>
          <div class="col text-end">
            <div class="invoice-number"><?= sanitize($data['invoice_number']) ?></div>
            <div class="text-muted small">Date: <?= formatDate($data['generated_at']) ?></div>
            <div class="mt-1"><?= statusBadge($data['payment_status']) ?></div>
          </div>
        </div>
      </div>

      <!-- Bill To / Provider -->
      <div class="row mb-4">
        <div class="col-sm-6 mb-3">
          <div class="fw-700 text-uppercase small text-muted mb-2 border-bottom pb-1">Bill To (Customer)</div>
          <div class="fw-600"><?= sanitize($data['cust_name']) ?></div>
          <div class="text-muted small"><?= sanitize($data['cust_email']) ?></div>
          <div class="text-muted small"><?= sanitize($data['cust_phone'] ?? '—') ?></div>
        </div>
        <div class="col-sm-6 mb-3">
          <div class="fw-700 text-uppercase small text-muted mb-2 border-bottom pb-1">Service Provider</div>
          <div class="fw-600"><?= sanitize($data['business_name']) ?></div>
          <div class="text-muted small"><?= sanitize($data['prov_name']) ?></div>
          <div class="text-muted small"><?= sanitize($data['prov_email']) ?></div>
          <div class="text-muted small"><?= sanitize($data['prov_phone'] ?? '—') ?></div>
        </div>
      </div>

      <!-- Booking Details -->
      <div class="p-3 rounded mb-4" style="background:#f8fafc;font-size:.875rem">
        <div class="row g-2">
          <div class="col-sm-4">
            <span class="text-muted">Booking #:</span>
            <strong class="ms-1"><?= $bid ?></strong>
          </div>
          <div class="col-sm-4">
            <span class="text-muted">Service Date:</span>
            <strong class="ms-1"><?= formatDate($data['scheduled_date']) ?></strong>
          </div>
          <div class="col-sm-4">
            <span class="text-muted">Category:</span>
            <strong class="ms-1"><?= sanitize($data['category']) ?></strong>
          </div>
        </div>
      </div>

      <!-- Services Table -->
      <table class="table invoice-table mb-4">
        <thead>
          <tr>
            <th>#</th>
            <th>Service Description</th>
            <th class="text-end">Unit Price</th>
            <th class="text-center">Qty</th>
            <th class="text-end">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $i => $item): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= sanitize($item['service_name']) ?></td>
            <td class="text-end"><?= formatCurrency($item['service_price']) ?></td>
            <td class="text-center"><?= $item['quantity'] ?></td>
            <td class="text-end fw-600"><?= formatCurrency($item['subtotal']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" class="text-end fw-600">Subtotal</td>
            <td class="text-end"><?= formatCurrency($data['subtotal']) ?></td>
          </tr>
          <tr>
            <td colspan="4" class="text-end fw-600">VAT (<?= $data['tax_rate'] ?>%)</td>
            <td class="text-end"><?= formatCurrency($data['tax_amount']) ?></td>
          </tr>
          <tr class="invoice-total-row">
            <td colspan="4" class="text-end fw-700">GRAND TOTAL</td>
            <td class="text-end invoice-grand"><?= formatCurrency($data['grand_total']) ?></td>
          </tr>
        </tfoot>
      </table>

      <div class="text-muted small text-center pt-3 border-top">
        Thank you for choosing HomeServe. For queries, contact support@homeserve.com
      </div>

      <?php if ($data['payment_status'] === 'unpaid' && $role === 'customer'): ?>
      <!-- ── QR PAYMENT SECTION ─────────────────────────── -->
      <div class="qr-payment-section no-print" id="qrPaymentSection">
        <div class="qr-pay-header">
          <i class="bi bi-qr-code-scan"></i>
          <span>Pay via UPI / QR Code</span>
          <span class="badge bg-success ms-2" style="font-size:.7rem">FREE · Instant</span>
        </div>
        <div class="qr-pay-body">
          <div class="qr-code-wrap">
            <?php
              // Build UPI deep link — works with PhonePe, GPay, Paytm, any UPI app
              $amount     = number_format((float)$data['grand_total'], 2, '.', '');
              $upiId      = 'zeelp9557@okhdfcbank'; // ← Change to your actual UPI ID
              $payeeName  = 'HomeServe';
              $tn         = 'Invoice ' . $data['invoice_number'];

              // QR should contain raw UPI string (NOT url-encoded) for apps to scan correctly
              $upiString  = "upi://pay?pa={$upiId}&pn=" . urlencode($payeeName) . "&am={$amount}&cu=INR&tn=" . urlencode($tn);
              $qrUrl      = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" . urlencode($upiString) . "&ecc=M&margin=8";

              // App-specific deep links
              $upiLink    = $upiString; // generic
              $gpayLink   = "gpay://upi/pay?pa={$upiId}&pn=" . urlencode($payeeName) . "&am={$amount}&cu=INR&tn=" . urlencode($tn);
              $phonepeLink= "phonepe://pay?pa={$upiId}&pn=" . urlencode($payeeName) . "&am={$amount}&cu=INR&tn=" . urlencode($tn);
              $paytmLink  = "paytmmp://pay?pa={$upiId}&pn=" . urlencode($payeeName) . "&am={$amount}&cu=INR&tn=" . urlencode($tn);
            ?>
            <img src="<?= $qrUrl ?>" alt="UPI QR Code" class="qr-img" id="qrCodeImg">
            <div class="qr-scan-label"><i class="bi bi-phone"></i> Scan with any UPI App</div>
          </div>
          <div class="qr-info-col">
            <div class="qr-amount-display">
              <div class="small text-muted mb-1">Amount to Pay</div>
              <div class="qr-amount"><?= formatCurrency($data['grand_total']) ?></div>
            </div>
            <div class="upi-id-box">
              <div class="small text-muted mb-1"><i class="bi bi-link-45deg"></i> UPI ID</div>
              <div class="upi-id-val" id="upiIdText"><?= htmlspecialchars($upiId) ?></div>
              <button onclick="copyUPI()" class="btn-copy-upi" title="Copy UPI ID">
                <i class="bi bi-clipboard" id="copyIcon"></i> Copy
              </button>
            </div>
            <div class="upi-apps-row">
              <div class="small text-muted mb-2">Pay using:</div>
              <div class="d-flex gap-2 flex-wrap">
                <a href="<?= $gpayLink ?>" class="upi-app-btn gpay">
                  <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/f2/Google_Pay_Logo.svg/512px-Google_Pay_Logo.svg.png" alt="GPay" height="22"> GPay
                </a>
                <a href="<?= $phonepeLink ?>" class="upi-app-btn phonepe">
                  <img src="https://cdn.worldvectorlogo.com/logos/phonepe-1.svg" alt="PhonePe" height="22"> PhonePe
                </a>
                <a href="<?= $paytmLink ?>" class="upi-app-btn paytm">
                  <img src="https://cdn.worldvectorlogo.com/logos/paytm.svg" alt="Paytm" height="22"> Paytm
                </a>
              </div>
            </div>
            <div class="mt-3">
              <a href="<?= $upiLink ?>" class="btn-pay-upi">
                <i class="bi bi-phone"></i> Pay ₹<?= number_format((float)$data['grand_total'], 0) ?> Now
              </a>
            </div>
            <div class="small text-muted mt-2">
              <i class="bi bi-shield-check text-success"></i> Secure UPI payment · No extra charges
            </div>
            <div class="small mt-2 p-2 rounded" style="background:#f0f9ff;border:1px solid #bae6fd;color:#0369a1">
              <i class="bi bi-info-circle-fill"></i>
              <strong>Open on your mobile</strong> — Scan the QR code with another phone, or tap the GPay / PhonePe button above.
            </div>
          </div>
        </div>

        <!-- ── TRANSACTION ID SUBMISSION ── -->
        <div class="txn-submit-section" id="txnSection">
          <div class="txn-header">
            <i class="bi bi-patch-check-fill"></i>
            Payment Done? Enter Transaction ID
          </div>
          <div class="txn-body">
            <p class="txn-desc">
              After paying via UPI, enter the <strong>Transaction ID / UTR Number</strong> from your GPay / PhonePe / Paytm payment receipt.
            </p>
            <div class="txn-input-wrap">
              <div class="txn-input-group">
                <span class="txn-prefix"><i class="bi bi-hash"></i></span>
                <input type="text" id="txnIdInput" class="txn-input"
                  placeholder="e.g. 123456789012 or T2501071234567"
                  maxlength="50"
                  oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')">
                <button class="txn-submit-btn" id="txnSubmitBtn" onclick="submitTxnId()">
                  <i class="bi bi-send-check-fill"></i> Submit
                </button>
              </div>
              <div id="txnStatus" class="txn-status"></div>
            </div>
            <div class="txn-how">
              <div class="txn-how-title"><i class="bi bi-question-circle"></i> Where to find your Transaction ID?</div>
              <div class="txn-how-grid">
                <div class="txn-how-item">
                  <i class="bi bi-google" style="color:#4285f4"></i>
                  <span><strong>GPay:</strong> Payment history → Transaction details → UPI Transaction ID</span>
                </div>
                <div class="txn-how-item">
                  <i class="bi bi-phone-fill" style="color:#5f259f"></i>
                  <span><strong>PhonePe:</strong> History → Transaction → Transaction ID</span>
                </div>
                <div class="txn-how-item">
                  <i class="bi bi-wallet2" style="color:#00b9f1"></i>
                  <span><strong>Paytm:</strong> Passbook → Order details → UTR Number</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Success state after submission -->
        <div class="txn-submitted-banner" id="txnSubmittedBanner" style="display:none">
          <i class="bi bi-clock-history text-warning" style="font-size:1.5rem"></i>
          <div>
            <div class="fw-700">Payment Details Submitted!</div>
            <div class="small text-muted">Our team will verify and mark your invoice as Paid shortly. Please wait a moment.</div>
          </div>
        </div>

      </div>
      <?php elseif ($role === 'provider'): ?>
      <!-- Provider sees a success message after finalize instead of QR -->
      <div class="paid-stamp no-print" style="background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8">
        <i class="bi bi-check-circle-fill" style="color:#2563eb"></i>
        Invoice generated successfully! The customer has been notified.
      </div>
      <?php else: ?>
      <div class="paid-stamp no-print">
        <i class="bi bi-check-circle-fill text-success"></i> Payment Received — Thank You!
      </div>
      <?php endif; ?>

      <!-- QR on Print -->
      <?php if ($data['payment_status'] === 'unpaid'): ?>
      <div class="print-only" style="display:none;text-align:center;margin-top:1rem;padding-top:1rem;border-top:1px dashed #e2e8f0">
        <p style="font-size:.85rem;color:#64748b;margin-bottom:.5rem"><strong>Pay via UPI — Scan QR Code</strong></p>
        <img src="<?= $qrUrl ?>" alt="UPI QR" style="width:130px;height:130px">
        <p style="font-size:.75rem;color:#94a3b8;margin-top:.25rem">UPI ID: <?= htmlspecialchars($upiId) ?> | Amount: ₹<?= $amount ?></p>
      </div>
      <?php endif; ?>

    </div>
  </div>



<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<style>
/* ── TRANSACTION ID SECTION ─────────────────────────── */
.txn-submit-section {
  margin-top: 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
  background: #fff;
}
.txn-header {
  background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
  color: #fff;
  padding: .85rem 1.25rem;
  font-weight: 700;
  font-size: .95rem;
  display: flex;
  align-items: center;
  gap: .5rem;
}
.txn-body { padding: 1.25rem; }
.txn-desc {
  font-size: .85rem;
  color: #475569;
  margin-bottom: 1rem;
  line-height: 1.6;
}
.txn-input-wrap { margin-bottom: 1rem; }
.txn-input-group {
  display: flex;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  transition: all .2s;
}
.txn-input-group:focus-within {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}
.txn-prefix {
  background: #f8fafc;
  padding: .75rem 1rem;
  color: #2563eb;
  font-size: 1rem;
  border-right: 1.5px solid #e2e8f0;
  display: flex;
  align-items: center;
}
.txn-input {
  flex: 1;
  border: none;
  outline: none;
  padding: .75rem 1rem;
  font-family: 'Courier New', monospace;
  font-size: .95rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: .5px;
  background: #fff;
}
.txn-input::placeholder { color: #cbd5e1; font-weight: 400; font-family: inherit; letter-spacing: 0; }
.txn-submit-btn {
  background: linear-gradient(135deg, #16a34a, #15803d);
  color: #fff;
  border: none;
  padding: .75rem 1.25rem;
  font-family: inherit;
  font-weight: 700;
  font-size: .85rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: .4rem;
  white-space: nowrap;
  transition: all .2s;
}
.txn-submit-btn:hover { filter: brightness(1.1); }
.txn-submit-btn:disabled { background: #94a3b8; cursor: not-allowed; }
.txn-status {
  margin-top: .5rem;
  font-size: .82rem;
  min-height: 20px;
  padding: 0 .25rem;
}
.txn-status.success { color: #16a34a; }
.txn-status.error   { color: #dc2626; }

/* How to find TXN ID */
.txn-how {
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: .85rem 1rem;
}
.txn-how-title {
  font-size: .78rem;
  font-weight: 700;
  color: #475569;
  margin-bottom: .6rem;
  display: flex;
  align-items: center;
  gap: .4rem;
}
.txn-how-grid { display: flex; flex-direction: column; gap: .4rem; }
.txn-how-item {
  display: flex;
  align-items: flex-start;
  gap: .5rem;
  font-size: .78rem;
  color: #64748b;
  line-height: 1.5;
}
.txn-how-item i { margin-top: 2px; flex-shrink: 0; font-size: .9rem; }

/* Submitted banner */
.txn-submitted-banner {
  margin-top: 1rem;
  background: #fffbeb;
  border: 2px solid #fde68a;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}
</style>
<script>
function submitTxnId() {
  const txnId = document.getElementById('txnIdInput').value.trim();
  const statusEl = document.getElementById('txnStatus');
  const btn = document.getElementById('txnSubmitBtn');

  if (txnId.length < 6) {
    statusEl.className = 'txn-status error';
    statusEl.innerHTML = '<i class="bi bi-x-circle"></i> Please enter a valid Transaction ID (min 6 characters).';
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Submitting...';
  statusEl.className = 'txn-status';
  statusEl.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving payment details...';

  const formData = new FormData();
  formData.append('booking_id', '<?= $bid ?>');
  formData.append('transaction_id', txnId);
  formData.append('amount', '<?= $data['grand_total'] ?>');

  fetch('<?= APP_URL ?>/api/submit_payment.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        document.getElementById('txnSection').style.display = 'none';
        document.getElementById('txnSubmittedBanner').style.display = 'flex';
      } else {
        statusEl.className = 'txn-status error';
        statusEl.innerHTML = '<i class="bi bi-x-circle"></i> ' + data.message;
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-check-fill"></i> Submit';
      }
    })
    .catch(() => {
      statusEl.className = 'txn-status error';
      statusEl.innerHTML = '<i class="bi bi-x-circle"></i> Network error. Please try again.';
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-send-check-fill"></i> Submit';
    });
}

function copyUPI() {
  const upiId = document.getElementById('upiIdText').textContent;
  navigator.clipboard.writeText(upiId).then(() => {
    const icon = document.getElementById('copyIcon');
    const btn  = icon.closest('button');
    icon.className = 'bi bi-clipboard-check';
    btn.innerHTML  = '<i class="bi bi-clipboard-check" id="copyIcon"></i> Copied!';
    btn.style.color = '#16a34a';
    setTimeout(() => {
      btn.innerHTML = '<i class="bi bi-clipboard" id="copyIcon"></i> Copy';
      btn.style.color = '';
    }, 2000);
  }).catch(() => {
    alert('UPI ID: ' + upiId + '\n\nPlease copy manually.');
  });
}
</script>

<style>
/* ── QR PAYMENT SECTION ─────────────────────────────── */
.qr-payment-section {
  margin-top: 1.5rem;
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,.07);
}
.qr-pay-header {
  background: linear-gradient(135deg, #1d4ed8 0%, #7c3aed 100%);
  color: #fff;
  padding: .85rem 1.25rem;
  font-weight: 700;
  font-size: 1rem;
  display: flex;
  align-items: center;
  gap: .5rem;
}
.qr-pay-body {
  display: flex;
  gap: 2rem;
  padding: 1.5rem;
  background: #f8fafc;
  flex-wrap: wrap;
}
.qr-code-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .5rem;
}
.qr-img {
  width: 180px;
  height: 180px;
  border-radius: 12px;
  border: 3px solid #e2e8f0;
  background: #fff;
  padding: 6px;
}
.qr-scan-label {
  font-size: .75rem;
  color: #64748b;
  font-weight: 600;
  text-align: center;
}
.qr-info-col {
  flex: 1;
  min-width: 220px;
}
.qr-amount-display {
  background: #fff;
  border: 2px solid #dbeafe;
  border-radius: 10px;
  padding: .75rem 1rem;
  margin-bottom: .75rem;
}
.qr-amount {
  font-size: 1.6rem;
  font-weight: 800;
  color: #1d4ed8;
}
.upi-id-box {
  background: #fff;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: .6rem 1rem;
  margin-bottom: .75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: .4rem;
}
.upi-id-val {
  font-weight: 700;
  color: #0f172a;
  font-family: monospace;
  font-size: .95rem;
}
.btn-copy-upi {
  background: #f1f5f9;
  border: 1.5px solid #e2e8f0;
  border-radius: 6px;
  padding: .2rem .7rem;
  font-size: .78rem;
  cursor: pointer;
  font-weight: 600;
  color: #475569;
  transition: all .2s;
}
.btn-copy-upi:hover { background: #e2e8f0; }
.upi-app-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .35rem .75rem;
  border-radius: 8px;
  font-size: .78rem;
  font-weight: 600;
  text-decoration: none;
  border: 1.5px solid #e2e8f0;
  background: #fff;
  color: #1e293b;
  transition: all .2s;
}
.upi-app-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1); color: #1e293b; }
.btn-pay-upi {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  padding: .7rem 1.5rem;
  background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
  color: #fff;
  border-radius: 10px;
  font-weight: 700;
  font-size: .95rem;
  text-decoration: none;
  transition: all .2s;
  box-shadow: 0 4px 14px rgba(22,163,74,.35);
}
.btn-pay-upi:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(22,163,74,.45); color: #fff; }
.qr-note {
  background: #fef9c3;
  padding: .65rem 1.25rem;
  font-size: .8rem;
  color: #854d0e;
  border-top: 1px solid #fde68a;
}
.paid-stamp {
  text-align: center;
  padding: 1rem;
  font-size: 1rem;
  font-weight: 700;
  color: #16a34a;
  background: #f0fdf4;
  border-radius: 10px;
  margin-top: 1rem;
  border: 1.5px solid #bbf7d0;
}
@media print {
  .print-only { display: block !important; }
}
</style>
<script>
function copyUPI() {
  const upiId = document.getElementById('upiIdText').textContent;
  navigator.clipboard.writeText(upiId).then(() => {
    const icon = document.getElementById('copyIcon');
    const btn  = icon.closest('button');
    icon.className = 'bi bi-clipboard-check';
    btn.innerHTML  = '<i class="bi bi-clipboard-check" id="copyIcon"></i> Copied!';
    btn.style.color = '#16a34a';
    setTimeout(() => {
      btn.innerHTML = '<i class="bi bi-clipboard" id="copyIcon"></i> Copy';
      btn.style.color = '';
    }, 2000);
  }).catch(() => {
    alert('UPI ID: ' + upiId + '\n\nPlease copy manually.');
  });
}
</script>