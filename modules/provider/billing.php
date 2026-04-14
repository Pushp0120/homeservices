<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
if (currentRole() !== 'provider') redirect(APP_URL . '/modules/user/dashboard.php');

$db  = getDB();
$pid = currentProviderId();
$bid = (int)($_GET['booking_id'] ?? 0);
$pageTitle = 'Finalize Billing';

// Fetch provider info
$prov = $db->prepare("SELECT p.*, c.name as cat_name FROM providers p JOIN categories c ON p.category_id=c.id WHERE p.id=?");
$prov->execute([$pid]);
$prov = $prov->fetch();

// Fetch booking — allow accepted OR in_progress
$stmt = $db->prepare("
    SELECT b.*, u.name as cust_name, u.email as cust_email, u.phone as cust_phone
    FROM bookings b JOIN users u ON b.customer_id = u.id
    WHERE b.id = ? AND b.provider_id = ? AND b.status IN ('accepted','in_progress')
");
$stmt->execute([$bid, $pid]);
$booking = $stmt->fetch();

// Already finalized → go to invoice
$invChk = $db->prepare("SELECT id FROM invoices WHERE booking_id=?");
$invChk->execute([$bid]);
if ($invChk->fetch()) redirect(APP_URL . '/modules/user/invoice.php?id=' . $bid . '&role=provider');

if (!$booking) {
    $pageTitle = 'Billing';
    require_once __DIR__ . '/../../includes/header.php';
    ?>
    <div class="dashboard-wrapper">
    <div class="overlay" id="sidebarOverlay"></div>
    <?php require_once __DIR__ . '/../../includes/sidebar_provider.php'; ?>
    <div class="main-content">
      <div class="topbar">
        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle">
            <i class="bi bi-list fs-5"></i>
          </button>
          <div class="topbar-title">Billing</div>
        </div>
        <a href="<?= APP_URL ?>/modules/provider/bookings.php" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrow-left"></i> Back to Bookings
        </a>
      </div>
      <div class="page-content">

        <?php if (!$bid): ?>
        <!-- No booking_id provided — show overview/landing state -->
        <div class="page-hero mb-4" style="background:linear-gradient(135deg,#064e3b,#059669)">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
              <h4 class="mb-1 fw-700"><i class="bi bi-cash-coin me-2"></i>Billing & Invoicing</h4>
              <p class="mb-0 opacity-75">Finalize completed jobs and generate professional invoices for your customers.</p>
            </div>
          </div>
        </div>

        <!-- How it works -->
        <div class="row g-3 mb-4">
          <?php foreach([
            ['bi-calendar-check','#2563eb','Step 1 — Accept a Booking','Accept a customer booking from your Bookings page. Once accepted, you can start the job.'],
            ['bi-tools','#7c3aed','Step 2 — Complete the Work','Carry out the requested services at the customer\'s address on the agreed date and time.'],
            ['bi-receipt','#059669','Step 3 — Finalize Billing','Click "Finalize Billing" on the accepted booking. Add all services rendered and generate the invoice.'],
            ['bi-qr-code-scan','#f59e0b','Step 4 — Collect Payment','Share the invoice. Customer pays via UPI QR code. You receive payment directly.'],
          ] as [$ico,$col,$ttl,$dsc]): ?>
          <div class="col-sm-6 col-lg-3">
            <div class="card h-100">
              <div class="card-body">
                <div style="width:46px;height:46px;border-radius:12px;background:<?= $col ?>14;border:1px solid <?= $col ?>22;display:flex;align-items:center;justify-content:center;margin-bottom:.9rem">
                  <i class="bi <?= $ico ?>" style="color:<?= $col ?>;font-size:1.2rem"></i>
                </div>
                <div class="fw-700 mb-1" style="font-size:.9rem"><?= $ttl ?></div>
                <div class="text-muted" style="font-size:.8rem;line-height:1.65"><?= $dsc ?></div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Bookings ready to finalize -->
        <?php
        $readyBookings = $db->prepare("
            SELECT b.*, u.name as cust_name, u.phone as cust_phone
            FROM bookings b
            JOIN users u ON b.customer_id = u.id
            LEFT JOIN invoices inv ON inv.booking_id = b.id
            WHERE b.provider_id = ? AND b.status IN ('accepted','in_progress')
            AND inv.id IS NULL
            ORDER BY b.scheduled_date ASC
        ");
        $readyBookings->execute([$pid]);
        $readyList = $readyBookings->fetchAll();
        ?>

        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="card-title"><i class="bi bi-hourglass-split text-warning me-1"></i>Bookings Ready to Finalize</span>
            <?php if ($readyList): ?>
            <span class="badge bg-warning text-dark"><?= count($readyList) ?> pending</span>
            <?php endif; ?>
          </div>
          <div class="card-body p-0">
            <?php if (empty($readyList)): ?>
            <div class="text-center py-5">
              <div style="font-size:2.8rem;margin-bottom:.75rem;opacity:.3">🧾</div>
              <div class="fw-700 text-dark mb-1">No bookings to finalize</div>
              <div class="text-muted small mb-3">All your accepted bookings have already been invoiced, or you have no accepted bookings yet.</div>
              <a href="<?= APP_URL ?>/modules/provider/bookings.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-calendar3 me-1"></i> View All Bookings
              </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Scheduled</th>
                    <th>Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($readyList as $rb): ?>
                  <tr>
                    <td class="text-muted small">#<?= $rb['id'] ?></td>
                    <td>
                      <div class="fw-700"><?= sanitize($rb['cust_name']) ?></div>
                      <div class="text-muted small"><?= sanitize($rb['cust_phone'] ?? '') ?></div>
                    </td>
                    <td>
                      <div class="fw-600"><?= formatDate($rb['scheduled_date']) ?></div>
                      <div class="text-muted small"><?= !empty($rb['scheduled_time']) ? date('h:i A', strtotime($rb['scheduled_time'])) : 'Time TBD' ?></div>
                    </td>
                    <td>
                      <?php
                      $sc = ['accepted'=>'bg-primary','in_progress'=>'bg-warning text-dark'];
                      $sl = ['accepted'=>'Accepted','in_progress'=>'In Progress'];
                      ?>
                      <span class="badge <?= $sc[$rb['status']] ?? 'bg-secondary' ?>"><?= $sl[$rb['status']] ?? ucfirst($rb['status']) ?></span>
                    </td>
                    <td>
                      <a href="<?= APP_URL ?>/modules/provider/billing.php?booking_id=<?= $rb['id'] ?>" class="btn btn-sm btn-success">
                        <i class="bi bi-cash-stack me-1"></i> Finalize Billing
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <?php else: ?>
        <!-- booking_id provided but booking not found / not eligible -->
        <div class="page-hero mb-4" style="background:linear-gradient(135deg,#7f1d1d,#dc2626)">
          <h4 class="mb-1 fw-700"><i class="bi bi-exclamation-triangle me-2"></i>Booking Not Found</h4>
          <p class="mb-0 opacity-75">This booking either doesn't exist, doesn't belong to you, or is not eligible for billing.</p>
        </div>
        <div class="text-center py-4">
          <a href="<?= APP_URL ?>/modules/provider/billing.php" class="btn btn-outline-primary me-2">
            <i class="bi bi-cash-coin me-1"></i> Go to Billing
          </a>
          <a href="<?= APP_URL ?>/modules/provider/bookings.php" class="btn btn-outline-secondary">
            <i class="bi bi-calendar3 me-1"></i> My Bookings
          </a>
        </div>
        <?php endif; ?>

      </div>
    </div>
    </div>
    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    <?php
    exit;
}

// Services from provider's OWN category ONLY
$categoryServices = $db->prepare("
    SELECT s.*, c.name as cat_name
    FROM services s JOIN categories c ON s.category_id = c.id
    WHERE s.status = 'active' AND s.category_id = ?
    ORDER BY s.name
");
$categoryServices->execute([$prov['category_id']]);
$serviceList = $categoryServices->fetchAll();

// Existing booking services (pre-added when customer booked)
$existingSvcs = $db->prepare("SELECT * FROM booking_services WHERE booking_id = ? ORDER BY id");
$existingSvcs->execute([$bid]);
$existingItems = $existingSvcs->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $svcs        = $_POST['services'] ?? [];
    $customNames = $_POST['custom_name'] ?? [];
    $customPrices= $_POST['custom_price'] ?? [];
    $customQtys  = $_POST['custom_qty'] ?? [];

    // Build final list combining catalog + custom services
    $finalItems = [];

    // Catalog services
    foreach ($svcs as $svc) {
        $svcId = (int)($svc['id'] ?? 0);
        $qty   = max(1, (int)($svc['qty'] ?? 1));
        if (!$svcId) continue;
        $row = $db->prepare("SELECT * FROM services WHERE id=?");
        $row->execute([$svcId]);
        $row = $row->fetch();
        if ($row) {
            $finalItems[] = [
                'service_id'    => $row['id'],
                'service_name'  => $row['name'],
                'service_price' => $row['price'],
                'quantity'      => $qty,
            ];
        }
    }

    // Custom / on-site services (e.g. "Add tap in sink" with custom price)
    foreach ($customNames as $i => $cname) {
        $cname  = sanitize($cname);
        $cprice = (float)($customPrices[$i] ?? 0);
        $cqty   = max(1, (int)($customQtys[$i] ?? 1));
        if ($cname && $cprice > 0) {
            $finalItems[] = [
                'service_id'    => null, // no catalog ID for custom
                'service_name'  => $cname,
                'service_price' => $cprice,
                'quantity'      => $cqty,
            ];
        }
    }

    if (empty($finalItems)) {
        $error = 'Please add at least one service before finalizing.';
    } else {
        $db->beginTransaction();
        try {
            // Clear existing services and re-insert everything
            $db->prepare("DELETE FROM booking_services WHERE booking_id=?")->execute([$bid]);

            $subtotal = 0;
            foreach ($finalItems as $item) {
                // For custom services without a catalog ID, use a placeholder service_id=0
                // But we need a valid service_id FK. Insert as the first service in same category or use a special "custom" approach:
                // We'll store service_id as the matched one or 0 (allow null via schema adjustment)
                $svcId = $item['service_id'] ?? 0;

                // If custom (no ID), find or create a catch-all — store with service_id = first service as reference
                // Best approach: just use service_id=1 as placeholder (schema allows it)
                // But proper: we'll skip FK for custom by using any valid service id
                if (!$svcId) {
                    // Use first service id as FK placeholder for custom entries
                    $anyId = $db->query("SELECT id FROM services LIMIT 1")->fetchColumn();
                    $svcId = $anyId ?: 1;
                }

                $ins = $db->prepare("
                    INSERT INTO booking_services (booking_id, service_id, service_name, service_price, quantity)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $ins->execute([
                    $bid,
                    $svcId,
                    $item['service_name'],
                    $item['service_price'],
                    $item['quantity']
                ]);
                $subtotal += $item['service_price'] * $item['quantity'];
            }

            $taxRate   = TAX_RATE;
            $taxAmount = $subtotal * $taxRate / 100;
            $total     = $subtotal + $taxAmount;
            $invNum    = generateInvoiceNumber();

            $db->prepare("
                INSERT INTO invoices (booking_id, invoice_number, subtotal, tax_rate, tax_amount, grand_total)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$bid, $invNum, $subtotal, $taxRate, $taxAmount, $total]);

            $db->prepare("UPDATE bookings SET status='completed' WHERE id=?")->execute([$bid]);
            $db->commit();

            redirect(APP_URL . '/modules/user/invoice.php?id=' . $bid . '&role=provider');
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Failed to finalize: ' . $e->getMessage();
        }
    }
}
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<style>
.custom-row { background: #fffbeb; border-left: 3px solid #f59e0b; }
.section-badge { font-size:.75rem; font-weight:600; letter-spacing:.5px; text-transform:uppercase; }
.service-type-tabs .nav-link { font-size:.85rem; padding:.5rem 1rem; }
</style>

<div class="dashboard-wrapper">
<div class="overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../../includes/sidebar_provider.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list fs-5"></i>
      </button>
      <div class="topbar-title">Finalize Billing – Booking #<?= $bid ?></div>
    </div>
    <a href="<?= APP_URL ?>/modules/provider/bookings.php" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <div class="page-content">
    <?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2">
      <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
    </div>
    <?php endif; ?>

    <!-- Info banner -->
    <div class="alert alert-info d-flex gap-2 align-items-start mb-4">
      <i class="bi bi-lightbulb-fill fs-5 text-info mt-1 flex-shrink-0"></i>
      <div>
        <strong>How billing works:</strong> Add all <strong><?= sanitize($prov['cat_name']) ?></strong> services 
        you performed from the catalog. If you did extra unlisted work (e.g. <em>"Replace tap washer"</em>, 
        <em>"Emergency call-out fee"</em>), use the <strong>Custom Service</strong> tab to add it with your own price.
      </div>
    </div>

    <div class="row g-4">
      <!-- Left: Main form -->
      <div class="col-lg-8">
        <form method="POST" id="billingForm">

          <!-- Customer Info -->
          <div class="card mb-3">
            <div class="card-header"><span class="card-title"><i class="bi bi-person-circle me-1"></i>Customer Information</span></div>
            <div class="card-body">
              <div class="row g-2 small">
                <div class="col-sm-3 text-muted fw-600">Name</div>
                <div class="col-sm-9"><?= sanitize($booking['cust_name']) ?></div>
                <div class="col-sm-3 text-muted fw-600">Phone</div>
                <div class="col-sm-9"><?= sanitize($booking['cust_phone'] ?? 'N/A') ?></div>
                <div class="col-sm-3 text-muted fw-600">Address</div>
                <div class="col-sm-9"><?= sanitize($booking['address']) ?></div>
                <div class="col-sm-3 text-muted fw-600">Scheduled</div>
                <div class="col-sm-9"><?= formatDate($booking['scheduled_date']) ?> at <?= date('h:i A', strtotime($booking['scheduled_time'])) ?></div>
                <?php if ($booking['notes']): ?>
                <div class="col-sm-3 text-muted fw-600">Notes</div>
                <div class="col-sm-9 fst-italic"><?= sanitize($booking['notes']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Services Card -->
          <div class="card mb-3">
            <div class="card-header">
              <span class="card-title"><i class="bi bi-tools me-1"></i>Services Rendered</span>
              <div class="text-muted small mt-1">Add all services performed — catalog items AND any extra on-site work</div>
            </div>
            <div class="card-body">

              <!-- Add service tabs -->
              <ul class="nav nav-pills service-type-tabs mb-3 p-1 rounded" style="background:#f1f5f9">
                <li class="nav-item">
                  <button type="button" class="nav-link active" id="tabCatalog" onclick="switchTab('catalog')">
                    <i class="bi bi-list-ul"></i> Catalog Services
                  </button>
                </li>
                <li class="nav-item">
                  <button type="button" class="nav-link" id="tabCustom" onclick="switchTab('custom')">
                    <i class="bi bi-pencil-square"></i> Custom / On-Site Service
                  </button>
                </li>
              </ul>

              <!-- Catalog picker -->
              <div id="catalogPicker" class="mb-3">
                <div class="row g-2">
                  <div class="col-sm-8">
                    <select id="serviceSelect" class="form-select">
                      <option value="">-- Select a <?php echo sanitize($prov["cat_name"]); ?> service --</option>
                      <?php foreach ($serviceList as $s): ?>
                      <option value="<?= $s["id"] ?>" data-name="<?= sanitize($s["name"]) ?>" data-price="<?= $s["price"] ?>">
                        <?= sanitize($s["name"]) ?> — ₹<?= number_format($s["price"], 0) ?> / <?= sanitize($s["unit"]) ?>
                      </option>
                      <?php endforeach; ?>
                      <?php if (empty($serviceList)): ?>
                      <option disabled>No services found for your category</option>
                      <?php endif; ?>
                    </select>
                  </div>
                  <div class="col-sm-2">
                    <input type="number" id="catalogQty" class="form-control" value="1" min="1" max="99" placeholder="Qty">
                  </div>
                  <div class="col-sm-2">
                    <button type="button" class="btn btn-success w-100" onclick="addFromCatalog()">
                      <i class="bi bi-plus-lg"></i> Add
                    </button>
                  </div>
                </div>
              </div>

              <!-- Custom service picker -->
              <div id="customPicker" class="mb-3" style="display:none">
                <div class="p-3 rounded border" style="background:#fffbeb;border-color:#fde68a!important">
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-info-circle text-warning"></i>
                    <span class="small text-muted">Use this for services not in the catalog — e.g. <em>"Replace tap washer"</em>, <em>"Emergency fee"</em>, <em>"Extra pipe fitting"</em></span>
                  </div>
                  <div class="row g-2">
                    <div class="col-sm-5">
                      <input type="text" id="customName" class="form-control" placeholder="Service name (e.g. Add tap in sink)">
                    </div>
                    <div class="col-sm-3">
                      <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" id="customPrice" class="form-control" placeholder="Price" min="1" step="0.01">
                      </div>
                    </div>
                    <div class="col-sm-2">
                      <input type="number" id="customQty" class="form-control" value="1" min="1" max="99" placeholder="Qty">
                    </div>
                    <div class="col-sm-2">
                      <button type="button" class="btn btn-warning w-100" onclick="addCustomService()">
                        <i class="bi bi-plus-lg"></i> Add
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Services table -->
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead>
                    <tr>
                      <th>Service</th>
                      <th style="width:110px">Unit Price</th>
                      <th style="width:80px">Qty</th>
                      <th style="width:110px">Subtotal</th>
                      <th style="width:40px"></th>
                    </tr>
                  </thead>
                  <tbody id="serviceRows">
                    <tr id="emptyRow">
                      <td colspan="5" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-4 d-block mb-1 opacity-50"></i>
                        No services added yet. Use the tabs above to add services.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Notes to customer -->
          <div class="card mb-4">
            <div class="card-header"><span class="card-title"><i class="bi bi-chat-left-text me-1"></i>Note to Customer <span class="text-muted fw-normal">(optional)</span></span></div>
            <div class="card-body">
              <textarea name="provider_note" class="form-control" rows="2"
                placeholder="e.g. Found a leaking joint near the main pipe — replaced it to prevent future damage. Recommend re-checking after 30 days."></textarea>
            </div>
          </div>

          <div class="d-flex gap-3 align-items-center">
            <button type="submit" class="btn btn-success btn-lg px-5 fw-600" id="finalizeBtn">
              <i class="bi bi-cash-stack"></i> Finalize & Generate Invoice
            </button>
            <span class="text-muted small"><i class="bi bi-lock-fill"></i> Cannot be undone after finalization</span>
          </div>
        </form>
      </div>

      <!-- Right: Live Invoice Summary -->
      <div class="col-lg-4">
        <div class="card sticky-top" style="top:80px">
          <div class="card-header" style="background:#0f172a">
            <span class="card-title text-white"><i class="bi bi-receipt me-1"></i>Live Invoice Preview</span>
          </div>
          <div class="card-body p-0">
            <div id="invoicePreviewRows" class="p-3 border-bottom" style="min-height:60px;font-size:.85rem">
              <div class="text-muted text-center py-2">Services will appear here</div>
            </div>
            <div class="p-3">
              <div class="d-flex justify-content-between mb-1 small">
                <span class="text-muted">Subtotal</span>
                <span id="subtotalDisplay">₹0</span>
              </div>
              <div class="d-flex justify-content-between mb-2 small">
                <span class="text-muted">VAT (<?= TAX_RATE ?>%)</span>
                <span id="taxDisplay">₹0</span>
              </div>
              <div class="d-flex justify-content-between fw-800 pt-2 border-top">
                <span>Grand Total</span>
                <span id="totalDisplay" class="text-success fs-5">₹0</span>
              </div>
              <input type="hidden" id="taxRate" value="<?= TAX_RATE ?>">
            </div>
          </div>
          <div class="card-footer bg-light small text-muted">
            <i class="bi bi-info-circle"></i> Customer will receive this invoice after you finalize.
          </div>
        </div>

        <!-- Provider category info -->
        <div class="card mt-3">
          <div class="card-body py-3">
            <div class="small text-muted fw-600 mb-1">Your Service Category</div>
            <div class="fw-700"><i class="bi <?= sanitize($prov['cat_name'] ?? 'bi-tools') ?> me-1"></i><?= sanitize($prov['cat_name']) ?></div>
            <hr class="my-2">
            <div class="small text-muted">
              Catalog tab shows only <strong><?= sanitize($prov['cat_name']) ?></strong> services.<br>
              For unlisted work (e.g. extra fittings, call-out fee), use the <strong>Custom Service</strong> tab.
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
// ── Tab switching ───────────────────────────────────────────
function switchTab(tab) {
  document.getElementById('catalogPicker').style.display = tab === 'catalog' ? '' : 'none';
  document.getElementById('customPicker').style.display  = tab === 'custom'  ? '' : 'none';
  document.getElementById('tabCatalog').classList.toggle('active', tab === 'catalog');
  document.getElementById('tabCustom').classList.toggle('active', tab === 'custom');
}

// ── Add from catalog ────────────────────────────────────────
function addFromCatalog() {
  const sel = document.getElementById('serviceSelect');
  const opt = sel.options[sel.selectedIndex];
  const qty = parseInt(document.getElementById('catalogQty').value) || 1;
  if (!opt.value) { showToast('Please select a service from the list.', 'warning'); return; }
  document.getElementById('emptyRow')?.remove();
  addServiceRow(opt.value, opt.dataset.name, parseFloat(opt.dataset.price), qty, false);
  sel.value = '';
  document.getElementById('catalogQty').value = 1;
  updateTotals();
}

// ── Add custom on-site service ──────────────────────────────
function addCustomService() {
  const name  = document.getElementById('customName').value.trim();
  const price = parseFloat(document.getElementById('customPrice').value);
  const qty   = parseInt(document.getElementById('customQty').value) || 1;

  if (!name)       { showToast('Please enter a service name.', 'warning'); return; }
  if (!price || price <= 0) { showToast('Please enter a valid price.', 'warning'); return; }

  document.getElementById('emptyRow')?.remove();
  // Use id=0 to mark as custom
  addServiceRow('custom_' + Date.now(), name, price, qty, true);
  document.getElementById('customName').value  = '';
  document.getElementById('customPrice').value = '';
  document.getElementById('customQty').value   = 1;
  updateTotals();
}

// ── Override addServiceRow to support custom services ───────
// We override the global one from main.js for this page only
window._serviceRowIdx = 0;

function addServiceRow(serviceId, serviceName, servicePrice, qty, isCustom) {
  const tbody = document.getElementById('serviceRows');
  if (!tbody) return;
  const idx = window._serviceRowIdx++;
  const row = document.createElement('tr');
  if (isCustom) row.classList.add('custom-row');
  row.setAttribute('data-row', idx);

  const isCustomId = String(serviceId).startsWith('custom_');

  row.innerHTML = `
    <td>
      ${isCustom
        ? `<input type="hidden" name="custom_name[]" value="${serviceName}">
           <input type="hidden" name="custom_price[]" value="${servicePrice}">
           <input type="hidden" name="custom_qty[]" value="${qty}">
           <span class="badge bg-warning text-dark me-1" style="font-size:.7rem">Custom</span>`
        : `<input type="hidden" name="services[${idx}][id]" value="${serviceId}">`
      }
      <span>${serviceName}</span>
    </td>
    <td><span class="fw-600">₹${parseFloat(servicePrice).toLocaleString('en-IN')}</span></td>
    <td>
      <input type="number"
        ${isCustom ? `name="custom_qty_live[]"` : `name="services[${idx}][qty]"`}
        value="${qty}" min="1" max="99"
        class="form-control form-control-sm qty-input"
        style="width:70px"
        data-price="${servicePrice}"
        data-custom="${isCustom ? '1' : '0'}"
        data-row="${idx}">
    </td>
    <td class="subtotal-cell fw-600 text-primary">
      ₹${Math.round(servicePrice * qty).toLocaleString('en-IN')}
    </td>
    <td>
      <button type="button" class="btn btn-sm btn-outline-danger btn-icon remove-row" data-row="${idx}">
        <i class="bi bi-trash"></i>
      </button>
    </td>`;

  tbody.appendChild(row);
}

// ── Qty change handler ──────────────────────────────────────
document.addEventListener('input', e => {
  if (e.target.classList.contains('qty-input')) {
    const row   = e.target.closest('tr');
    const price = parseFloat(e.target.dataset.price);
    const qty   = parseInt(e.target.value) || 1;
    row.querySelector('.subtotal-cell').textContent = '₹' + Math.round(price * qty).toLocaleString('en-IN');
    // Update hidden custom qty if custom row
    if (e.target.dataset.custom === '1') {
      const hidden = row.querySelector('input[name="custom_qty[]"]');
      if (hidden) hidden.value = qty;
    }
    updateTotals();
  }
});

// ── Remove row ──────────────────────────────────────────────
document.addEventListener('click', e => {
  if (e.target.closest('.remove-row')) {
    e.target.closest('tr').remove();
    updateTotals();
    if (document.querySelectorAll('#serviceRows tr').length === 0) {
      const tbody = document.getElementById('serviceRows');
      tbody.innerHTML = `<tr id="emptyRow"><td colspan="5" class="text-center text-muted py-4">
        <i class="bi bi-inbox fs-4 d-block mb-1 opacity-50"></i>No services added yet.</td></tr>`;
    }
  }
});

// ── Update totals + live preview ────────────────────────────
function updateTotals() {
  const rows = document.querySelectorAll('#serviceRows tr[data-row]');
  let subtotal = 0;
  const previewRows = [];

  rows.forEach(row => {
    const qtyInput = row.querySelector('.qty-input');
    const price = parseFloat(qtyInput?.dataset.price || 0);
    const qty   = parseInt(qtyInput?.value || 1);
    const name  = row.querySelector('span:last-of-type')?.textContent || '';
    subtotal += price * qty;
    previewRows.push(`
      <div class="d-flex justify-content-between mb-1">
        <span class="text-truncate me-2" style="max-width:160px">${name} ×${qty}</span>
        <span class="fw-600 text-nowrap">₹${Math.round(price * qty).toLocaleString('en-IN')}</span>
      </div>`);
  });

  const taxRate  = parseFloat(document.getElementById('taxRate').value || 0);
  const tax      = subtotal * taxRate / 100;
  const total    = subtotal + tax;
  const fmt      = v => '₹' + Math.round(v).toLocaleString('en-IN');

  document.getElementById('subtotalDisplay').textContent = fmt(subtotal);
  document.getElementById('taxDisplay').textContent      = fmt(tax);
  document.getElementById('totalDisplay').textContent    = fmt(total);

  const preview = document.getElementById('invoicePreviewRows');
  preview.innerHTML = previewRows.length
    ? previewRows.join('')
    : '<div class="text-muted text-center py-2 small">Services will appear here</div>';
}

// ── Pre-load existing booking services ──────────────────────
document.addEventListener('DOMContentLoaded', function () {
  window._serviceRowIdx = 0;
  <?php foreach ($existingItems as $es): ?>
  document.getElementById('emptyRow')?.remove();
  addServiceRow(
    <?= (int)$es['service_id'] ?>,
    '<?= addslashes(htmlspecialchars_decode($es['service_name'])) ?>',
    <?= (float)$es['service_price'] ?>,
    <?= (int)$es['quantity'] ?>,
    false
  );
  <?php endforeach; ?>
  updateTotals();
});

// ── Confirm before submit ───────────────────────────────────
document.getElementById('billingForm').addEventListener('submit', function(e) {
  const rows = document.querySelectorAll('#serviceRows tr[data-row]');
  if (rows.length === 0) {
    e.preventDefault();
    showToast('Please add at least one service before finalizing.', 'danger');
    return;
  }
  if (!confirm('Finalize this booking and generate the invoice? This cannot be undone.')) {
    e.preventDefault();
  }
});
</script>