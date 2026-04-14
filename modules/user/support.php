<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
refreshProviderSession();
if (currentRole() === 'admin')    redirect(APP_URL . '/modules/admin/dashboard.php');
if (currentRole() === 'provider') redirect(APP_URL . '/modules/provider/dashboard.php');

$db  = getDB();
$uid = currentUserId();
$pageTitle = 'Support';

$success = '';
$errors  = [];

// ── Handle new ticket submission ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_ticket') {
    verifyCsrf();

    $subject  = trim($_POST['subject']  ?? '');
    $category = trim($_POST['category'] ?? '');
    $priority = trim($_POST['priority'] ?? 'medium');
    $message  = trim($_POST['message']  ?? '');

    $validCategories = ['booking_issue','payment_issue','account_issue','provider_complaint','general_inquiry','other'];
    $validPriorities = ['low','medium','high'];

    if (!$subject)                                    $errors[] = 'Subject is required.';
    if (strlen($subject) > 200)                       $errors[] = 'Subject must be under 200 characters.';
    if (!in_array($category, $validCategories))       $errors[] = 'Please select a valid category.';
    if (!in_array($priority, $validPriorities))       $errors[] = 'Please select a valid priority.';
    if (!$message)                                    $errors[] = 'Message is required.';
    if (strlen($message) < 20)                        $errors[] = 'Message must be at least 20 characters.';

    if (empty($errors)) {
        $ticketNumber = 'TKT-' . strtoupper(substr(uniqid(), -6));
        $stmt = $db->prepare(
            "INSERT INTO support_tickets (user_id, ticket_number, subject, category, priority, message)
             VALUES (?,?,?,?,?,?)"
        );
        $stmt->execute([$uid, $ticketNumber, $subject, $category, $priority, $message]);
        $success = "Your ticket <strong>$ticketNumber</strong> has been submitted! Our support team will respond within 24 hours.";
    }
}

// ── Fetch this user's tickets ──────────────────────────────
$ticketStmt = $db->prepare(
    "SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC"
);
$ticketStmt->execute([$uid]);
$tickets = $ticketStmt->fetchAll();

// ── Ticket stats ───────────────────────────────────────────
$statsStmt = $db->prepare(
    "SELECT
       COUNT(*) as total,
       SUM(status='open') as open_count,
       SUM(status='in_progress') as inprog_count,
       SUM(status='resolved') as resolved_count
     FROM support_tickets WHERE user_id=?"
);
$statsStmt->execute([$uid]);
$tstat = $statsStmt->fetch();
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/topnav_user.php'; ?>

<style>
/* ── Support Page ── */
.sup-wrap { padding: 0 1.25rem 3rem; }

/* Hero */
.sup-hero {
  border-radius: 20px;
  padding: 2.5rem 2.25rem;
  margin-bottom: 2rem;
  position: relative; overflow: hidden;
  background: linear-gradient(130deg, #6d28d9 0%, #4f46e5 100%);
  color: #fff;
}
.sup-hero::before {
  content:''; position:absolute; right:-60px; top:-60px;
  width:280px; height:280px; border-radius:50%;
  background:rgba(255,255,255,.07);
}
.sup-hero::after {
  content:''; position:absolute; right:80px; bottom:-80px;
  width:200px; height:200px; border-radius:50%;
  background:rgba(255,255,255,.05);
}
.sup-hero h3 { font-size:1.75rem; font-weight:800; margin-bottom:.3rem; }
.sup-hero p  { opacity:.85; margin-bottom:0; font-size:.95rem; }

/* Stat cards */
.sup-stat-row { display:grid; grid-template-columns:repeat(4,1fr); gap:.85rem; margin-bottom:1.75rem; }
@media(max-width:700px){ .sup-stat-row{ grid-template-columns:repeat(2,1fr); } }
.sup-stat-card {
  background:#fff; border-radius:14px; padding:1.1rem;
  display:flex; align-items:center; gap:.85rem;
  box-shadow:0 1px 6px rgba(0,0,0,.06); border:1px solid #f0f0f0;
  transition:transform .15s, box-shadow .15s;
}
.sup-stat-card:hover { transform:translateY(-2px); box-shadow:0 4px 14px rgba(0,0,0,.09); }
.sup-stat-icon {
  width:44px; height:44px; border-radius:11px;
  display:flex; align-items:center; justify-content:center;
  font-size:1.15rem; flex-shrink:0;
}
.si-purple { background:#f5f3ff; color:#7c3aed; }
.si-blue   { background:#eff6ff; color:#3b82f6; }
.si-orange { background:#fff7ed; color:#ea580c; }
.si-green  { background:#f0fdf4; color:#16a34a; }
.sup-stat-val { font-size:1.45rem; font-weight:800; line-height:1; }
.sup-stat-lbl { font-size:.76rem; color:#6b7280; margin-top:2px; font-weight:500; }

/* Two-column grid */
.sup-grid { display:grid; grid-template-columns:1fr 380px; gap:1.5rem; }
@media(max-width:900px){ .sup-grid{ grid-template-columns:1fr; } }

/* Panel shared styles */
.sup-panel {
  background:#fff; border-radius:16px; border:1px solid #eee;
  box-shadow:0 1px 6px rgba(0,0,0,.05); overflow:hidden;
}
.sup-panel-head {
  padding:.9rem 1.25rem; font-weight:700; font-size:.95rem;
  border-bottom:1px solid #f3f3f3; display:flex;
  justify-content:space-between; align-items:center;
}

/* New ticket form */
.ticket-form { padding:1.25rem; }
.ticket-form .form-label { font-size:.83rem; font-weight:600; color:#374151; margin-bottom:.3rem; }
.ticket-form .form-control, .ticket-form .form-select {
  border-radius:10px; border:1.5px solid #e5e7eb; font-size:.88rem;
  padding:.55rem .9rem; transition:border-color .15s, box-shadow .15s;
}
.ticket-form .form-control:focus, .ticket-form .form-select:focus {
  border-color:#6d28d9; box-shadow:0 0 0 3px rgba(109,40,217,.1); outline:none;
}
.ticket-form textarea { resize:vertical; min-height:130px; }
.priority-sel { display:flex; gap:.5rem; }
.priority-sel input[type=radio] { display:none; }
.priority-sel label {
  flex:1; text-align:center; padding:.45rem .6rem; border-radius:9px;
  border:1.5px solid #e5e7eb; font-size:.78rem; font-weight:600;
  cursor:pointer; transition:all .15s;
}
.priority-sel input[type=radio]:checked + label.low    { background:#dcfce7; border-color:#16a34a; color:#15803d; }
.priority-sel input[type=radio]:checked + label.medium { background:#fef9c3; border-color:#ca8a04; color:#92400e; }
.priority-sel input[type=radio]:checked + label.high   { background:#fee2e2; border-color:#dc2626; color:#b91c1c; }
.priority-sel label:hover { border-color:#6d28d9; }

/* Alert banner */
.sup-alert-success {
  display:flex; align-items:flex-start; gap:.75rem;
  background:#f0fdf4; border:1.5px solid #86efac;
  border-radius:12px; padding:.9rem 1.1rem; margin-bottom:1.25rem;
  font-size:.88rem; color:#166534;
}
.sup-alert-error {
  display:flex; align-items:flex-start; gap:.75rem;
  background:#fef2f2; border:1.5px solid #fca5a5;
  border-radius:12px; padding:.9rem 1.1rem; margin-bottom:1.25rem;
  font-size:.88rem; color:#991b1b;
}

/* Ticket history */
.ticket-list { padding:0; }
.ticket-item {
  display:flex; align-items:flex-start; gap:.9rem;
  padding:1rem 1.25rem; border-bottom:1px solid #f7f7f7;
  transition:background .12s; cursor:default;
}
.ticket-item:last-child { border-bottom:none; }
.ticket-item:hover { background:#fafafa; }
.ticket-item-icon {
  width:38px; height:38px; border-radius:10px;
  display:flex; align-items:center; justify-content:center;
  font-size:1rem; flex-shrink:0;
}
.ti-open      { background:#eff6ff; color:#3b82f6; }
.ti-progress  { background:#fff7ed; color:#ea580c; }
.ti-resolved  { background:#f0fdf4; color:#16a34a; }
.ti-closed    { background:#f9fafb; color:#9ca3af; }
.ticket-number{ font-size:.72rem; color:#9ca3af; font-weight:600; letter-spacing:.04em; }
.ticket-subject{ font-size:.9rem; font-weight:700; color:#111; margin:.1rem 0 .15rem; }
.ticket-meta  { font-size:.76rem; color:#6b7280; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
.ticket-empty { text-align:center; padding:2.5rem 1rem; color:#9ca3af; }
.ticket-empty i{ display:block; font-size:2rem; margin-bottom:.5rem; }

/* Status badge overrides */
.tbadge { font-size:.68rem; font-weight:700; letter-spacing:.04em; padding:2px 8px; border-radius:5px; text-transform:uppercase; }
.tbadge-open      { background:#dbeafe; color:#1e40af; }
.tbadge-progress  { background:#ffedd5; color:#9a3412; }
.tbadge-resolved  { background:#dcfce7; color:#15803d; }
.tbadge-closed    { background:#f3f4f6; color:#6b7280; }

/* FAQ panel */
.sup-faq { padding:1rem 1.25rem; }
.faq-item { border-bottom:1px solid #f3f3f3; }
.faq-item:last-child { border-bottom:none; }
.faq-q {
  padding:.75rem 0; font-weight:600; font-size:.87rem; color:#1f2937;
  cursor:pointer; display:flex; justify-content:space-between; align-items:center;
  user-select:none; gap:.5rem;
}
.faq-q i.chevron { font-size:.8rem; color:#9ca3af; transition:transform .2s; flex-shrink:0; }
.faq-item.open .faq-q i.chevron { transform:rotate(180deg); }
.faq-a {
  font-size:.83rem; color:#6b7280; line-height:1.6;
  max-height:0; overflow:hidden; transition:max-height .3s ease, padding .2s;
}
.faq-item.open .faq-a { max-height:300px; padding-bottom:.75rem; }

/* Contact info card */
.contact-card {
  background:linear-gradient(135deg, #6d28d9, #4f46e5);
  border-radius:14px; padding:1.25rem; color:#fff; margin-top:1.25rem;
}
.contact-card h6 { font-weight:800; margin-bottom:.75rem; font-size:.95rem; }
.contact-row {
  display:flex; align-items:center; gap:.6rem;
  font-size:.83rem; margin-bottom:.55rem; opacity:.9;
}
.contact-row i { font-size:1rem; opacity:.8; }
.contact-row:last-child { margin-bottom:0; }
</style>

<div class="user-main-content">
  <div class="page-content sup-wrap">

    <!-- ── HERO ── -->
    <div class="sup-hero">
      <h3><i class="bi bi-headset me-2"></i>Support Center</h3>
      <p>Need help? Submit a ticket and our team will get back to you within 24 hours.</p>
    </div>

    <!-- ── STATS ── -->
    <div class="sup-stat-row">
      <div class="sup-stat-card">
        <div class="sup-stat-icon si-purple"><i class="bi bi-ticket-perforated"></i></div>
        <div><div class="sup-stat-val"><?= (int)$tstat['total'] ?></div><div class="sup-stat-lbl">Total Tickets</div></div>
      </div>
      <div class="sup-stat-card">
        <div class="sup-stat-icon si-blue"><i class="bi bi-envelope-open"></i></div>
        <div><div class="sup-stat-val"><?= (int)$tstat['open_count'] ?></div><div class="sup-stat-lbl">Open</div></div>
      </div>
      <div class="sup-stat-card">
        <div class="sup-stat-icon si-orange"><i class="bi bi-arrow-repeat"></i></div>
        <div><div class="sup-stat-val"><?= (int)$tstat['inprog_count'] ?></div><div class="sup-stat-lbl">In Progress</div></div>
      </div>
      <div class="sup-stat-card">
        <div class="sup-stat-icon si-green"><i class="bi bi-check-circle"></i></div>
        <div><div class="sup-stat-val"><?= (int)$tstat['resolved_count'] ?></div><div class="sup-stat-lbl">Resolved</div></div>
      </div>
    </div>

    <!-- ── MAIN GRID ── -->
    <div class="sup-grid">

      <!-- LEFT: New Ticket Form + History -->
      <div>

        <!-- New Ticket -->
        <div class="sup-panel mb-4">
          <div class="sup-panel-head">
            <span><i class="bi bi-plus-circle me-2 text-purple" style="color:#6d28d9"></i>Submit a New Ticket</span>
          </div>
          <div class="ticket-form">

            <?php if ($success): ?>
            <div class="sup-alert-success">
              <i class="bi bi-check-circle-fill mt-1"></i>
              <div><?= $success ?></div>
            </div>
            <?php endif; ?>

            <?php if ($errors): ?>
            <div class="sup-alert-error">
              <i class="bi bi-exclamation-triangle-fill mt-1"></i>
              <div>
                <?php foreach ($errors as $e): ?>
                  <div><?= sanitize($e) ?></div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <form method="POST" novalidate>
              <?= csrfField() ?>
              <input type="hidden" name="action" value="submit_ticket">

              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Subject <span class="text-danger">*</span></label>
                  <input type="text" name="subject" class="form-control"
                         placeholder="Briefly describe your issue"
                         value="<?= sanitize($_POST['subject'] ?? '') ?>" maxlength="200">
                </div>

                <div class="col-sm-6">
                  <label class="form-label">Category <span class="text-danger">*</span></label>
                  <select name="category" class="form-select">
                    <option value="">— Select category —</option>
                    <?php
                    $cats = [
                      'booking_issue'      => '📅 Booking Issue',
                      'payment_issue'      => '💳 Payment Issue',
                      'account_issue'      => '👤 Account Issue',
                      'provider_complaint' => '⚠️ Provider Complaint',
                      'general_inquiry'    => '💬 General Inquiry',
                      'other'              => '🔧 Other',
                    ];
                    foreach ($cats as $val => $label):
                      $sel = (($_POST['category'] ?? '') === $val) ? 'selected' : '';
                    ?>
                    <option value="<?= $val ?>" <?= $sel ?>><?= $label ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-sm-6">
                  <label class="form-label">Priority <span class="text-danger">*</span></label>
                  <div class="priority-sel">
                    <?php $curPrio = $_POST['priority'] ?? 'medium'; ?>
                    <input type="radio" id="prio_low"    name="priority" value="low"    <?= $curPrio==='low'    ? 'checked' : '' ?>>
                    <label for="prio_low"    class="low">🟢 Low</label>
                    <input type="radio" id="prio_medium" name="priority" value="medium" <?= $curPrio==='medium' ? 'checked' : '' ?>>
                    <label for="prio_medium" class="medium">🟡 Medium</label>
                    <input type="radio" id="prio_high"   name="priority" value="high"   <?= $curPrio==='high'   ? 'checked' : '' ?>>
                    <label for="prio_high"   class="high">🔴 High</label>
                  </div>
                </div>

                <div class="col-12">
                  <label class="form-label">Message <span class="text-danger">*</span></label>
                  <textarea name="message" class="form-control"
                            placeholder="Describe your issue in detail (at least 20 characters)…"><?= sanitize($_POST['message'] ?? '') ?></textarea>
                </div>

                <div class="col-12">
                  <button type="submit" class="btn btn-primary w-100" style="background:#6d28d9;border-color:#6d28d9;font-weight:700;border-radius:10px;padding:.65rem">
                    <i class="bi bi-send me-2"></i>Submit Ticket
                  </button>
                </div>
              </div>
            </form>

          </div>
        </div>

        <!-- Ticket History -->
        <div class="sup-panel">
          <div class="sup-panel-head">
            <span><i class="bi bi-clock-history me-2" style="color:#6d28d9"></i>My Tickets</span>
            <span class="badge" style="background:#f5f3ff;color:#6d28d9;font-weight:700"><?= count($tickets) ?></span>
          </div>
          <div class="ticket-list">
            <?php if (empty($tickets)): ?>
            <div class="ticket-empty">
              <i class="bi bi-ticket-perforated"></i>
              No support tickets yet.<br>
              <span style="font-size:.82rem">Use the form above to get help.</span>
            </div>
            <?php else: foreach ($tickets as $t):
              $iconMap  = ['open'=>'ti-open','in_progress'=>'ti-progress','resolved'=>'ti-resolved','closed'=>'ti-closed'];
              $iconI    = ['open'=>'bi-envelope-open','in_progress'=>'bi-arrow-repeat','resolved'=>'bi-check-circle','closed'=>'bi-archive'];
              $badgeMap = ['open'=>'tbadge-open','in_progress'=>'tbadge-progress','resolved'=>'tbadge-resolved','closed'=>'tbadge-closed'];
              $badgeLbl = ['open'=>'Open','in_progress'=>'In Progress','resolved'=>'Resolved','closed'=>'Closed'];
              $catLbl   = [
                'booking_issue'=>'Booking Issue','payment_issue'=>'Payment Issue',
                'account_issue'=>'Account Issue','provider_complaint'=>'Provider Complaint',
                'general_inquiry'=>'General Inquiry','other'=>'Other'
              ];
              $prioColors = ['low'=>'#15803d','medium'=>'#92400e','high'=>'#b91c1c'];
              $prioEmoji  = ['low'=>'🟢','medium'=>'🟡','high'=>'🔴'];
            ?>
            <div class="ticket-item">
              <div class="ticket-item-icon <?= $iconMap[$t['status']] ?? 'ti-closed' ?>">
                <i class="bi <?= $iconI[$t['status']] ?? 'bi-ticket' ?>"></i>
              </div>
              <div style="flex:1;min-width:0;">
                <div class="ticket-number"><?= sanitize($t['ticket_number']) ?></div>
                <div class="ticket-subject"><?= sanitize($t['subject']) ?></div>
                <div class="ticket-meta">
                  <span class="tbadge <?= $badgeMap[$t['status']] ?? 'tbadge-closed' ?>"><?= $badgeLbl[$t['status']] ?? 'Closed' ?></span>
                  <span><?= $catLbl[$t['category']] ?? $t['category'] ?></span>
                  <span style="color:<?= $prioColors[$t['priority']] ?? '#6b7280' ?>"><?= $prioEmoji[$t['priority']] ?? '' ?> <?= ucfirst($t['priority']) ?></span>
                  <span>· <?= formatDateTime($t['created_at']) ?></span>
                </div>
                <?php if ($t['admin_reply']): ?>
                <div style="margin-top:.5rem;background:#f0fdf4;border-radius:8px;padding:.6rem .85rem;font-size:.81rem;color:#166534;border-left:3px solid #16a34a;">
                  <strong><i class="bi bi-reply-fill me-1"></i>Support Reply:</strong><br>
                  <?= nl2br(sanitize($t['admin_reply'])) ?>
                  <div style="font-size:.72rem;color:#9ca3af;margin-top:.25rem"><?= formatDateTime($t['replied_at']) ?></div>
                </div>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; endif; ?>
          </div>
        </div>

      </div><!-- /left -->

      <!-- RIGHT: FAQ + Contact -->
      <div>

        <!-- FAQ -->
        <div class="sup-panel mb-4">
          <div class="sup-panel-head">
            <span><i class="bi bi-question-circle me-2" style="color:#6d28d9"></i>Frequently Asked Questions</span>
          </div>
          <div class="sup-faq">
            <?php
            $faqs = [
              ['How do I cancel a booking?',
               "Go to <strong>My Bookings</strong>, find the booking you want to cancel, and click the <strong>Cancel</strong> button. Cancellation is available for bookings that are still <em>Pending</em> or <em>Accepted</em>."],
              ['When will I be charged for a service?',
               "Payment details and invoices are generated once your booking is marked <em>Completed</em> by the provider. You can view all invoices under the <strong>Invoices</strong> tab."],
              ['How do I leave a review for a provider?',
               "After a booking is marked Completed, a <strong>Rate</strong> button will appear on your bookings list and dashboard. Click it to leave a star rating and review."],
              ['My provider didn\'t show up — what should I do?',
               "Please raise a support ticket using the form on this page and select <em>Provider Complaint</em> as the category. Our team will investigate and take action within 24-48 hours."],
              ['How do I update my profile or password?',
               "Click your avatar in the top-right corner, then choose <strong>My Profile</strong> or <strong>Account Settings</strong> to update your name, phone, email, and password."],
              ['How long does it take for my provider application to be approved?',
               "Provider applications are typically reviewed within 1-2 business days. You'll be automatically redirected to the provider dashboard once approved."],
              ['Can I book multiple services in one booking?',
               "Yes! When browsing a provider, you can select multiple individual services to add to a single booking before confirming."],
            ];
            foreach ($faqs as $i => $faq): ?>
            <div class="faq-item" id="faq-<?= $i ?>">
              <div class="faq-q" onclick="toggleFaq(<?= $i ?>)">
                <span><?= $faq[0] ?></span>
                <i class="bi bi-chevron-down chevron"></i>
              </div>
              <div class="faq-a"><?= $faq[1] ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Contact Info -->
        <div class="contact-card">
          <h6><i class="bi bi-telephone-fill me-2"></i>Other Ways to Reach Us</h6>
          <div class="contact-row"><i class="bi bi-envelope-fill"></i> support@homeserve.com</div>
          <div class="contact-row"><i class="bi bi-telephone-fill"></i> +91 98765 43210</div>
          <div class="contact-row"><i class="bi bi-clock-fill"></i> Mon – Sat, 9 AM – 7 PM IST</div>
          <div class="contact-row"><i class="bi bi-whatsapp"></i> WhatsApp: +91 91234 56789</div>
        </div>

      </div><!-- /right -->

    </div><!-- /.sup-grid -->

  </div><!-- /.sup-wrap -->
</div><!-- /.user-main-content -->

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script>
function toggleFaq(i) {
  const item = document.getElementById('faq-' + i);
  item.classList.toggle('open');
}
</script>
