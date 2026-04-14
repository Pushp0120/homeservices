<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
$db  = getDB();
$uid = currentUserId();
$bid = (int)($_GET['id'] ?? 0);
$role = currentRole();

// ═══════════════════════════════════════════════════════════════
// STEP 1 — Fetch BOOKING (without invoice requirement)
//          LEFT JOIN so we get booking even if invoice doesn't exist
// ═══════════════════════════════════════════════════════════════
if ($role === 'admin') {
    $stmt = $db->prepare("SELECT b.*,
        uc.name as cust_name, uc.email as cust_email, uc.phone as cust_phone,
        up.name as prov_name, up.email as prov_email, up.phone as prov_phone,
        p.business_name, p.id as provider_id, c.name as category, c.icon as cat_icon, c.color as cat_color
        FROM bookings b
        JOIN users uc ON b.customer_id = uc.id
        JOIN providers p ON b.provider_id = p.id
        JOIN users up ON p.user_id = up.id
        JOIN categories c ON p.category_id = c.id
        WHERE b.id = ?");
    $stmt->execute([$bid]);

} elseif ($role === 'provider') {
    $pid = currentProviderId();
    $stmt = $db->prepare("SELECT b.*,
        uc.name as cust_name, uc.email as cust_email, uc.phone as cust_phone,
        up.name as prov_name, up.email as prov_email, up.phone as prov_phone,
        p.business_name, p.id as provider_id, c.name as category, c.icon as cat_icon, c.color as cat_color
        FROM bookings b
        JOIN users uc ON b.customer_id = uc.id
        JOIN providers p ON b.provider_id = p.id
        JOIN users up ON p.user_id = up.id
        JOIN categories c ON p.category_id = c.id
        WHERE b.id = ? AND b.provider_id = ?");
    $stmt->execute([$bid, $pid]);

} else {
    $stmt = $db->prepare("SELECT b.*,
        uc.name as cust_name, uc.email as cust_email, uc.phone as cust_phone,
        up.name as prov_name, up.email as prov_email, up.phone as prov_phone,
        p.business_name, p.id as provider_id, c.name as category, c.icon as cat_icon, c.color as cat_color
        FROM bookings b
        JOIN users uc ON b.customer_id = uc.id
        JOIN providers p ON b.provider_id = p.id
        JOIN users up ON p.user_id = up.id
        JOIN categories c ON p.category_id = c.id
        WHERE b.id = ? AND b.customer_id = ?");
    $stmt->execute([$bid, $uid]);
}

$booking = $stmt->fetch();

// ═══════════════════════════════════════════════════════════════
// STEP 2 — Booking not found / no permission → real 404
// ═══════════════════════════════════════════════════════════════
if (!$booking) {
    $backLink = match($role) {
        'provider' => APP_URL . '/modules/provider/bookings.php',
        'admin'    => APP_URL . '/modules/admin/bookings.php',
        default    => APP_URL . '/modules/user/bookings.php',
    };
    ?>
    <!DOCTYPE html><html lang="en"><head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Booking Not Found | HomeServe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
    body{background:#f8fafc;font-family:'Segoe UI',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh}
    .err-card{background:#fff;border-radius:20px;padding:3rem 2.5rem;text-align:center;max-width:420px;box-shadow:0 8px 32px rgba(0,0,0,.08);border:1px solid #f1f5f9}
    .err-icon{width:80px;height:80px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:2rem;color:#ef4444}
    </style>
    </head><body>
    <div class="err-card">
      <div class="err-icon"><i class="bi bi-slash-circle"></i></div>
      <h4 class="fw-800 mb-2" style="color:#0f172a">Booking Not Found</h4>
      <p class="text-muted mb-4" style="font-size:.9rem">This booking doesn't exist or you don't have permission to view it.</p>
      <a href="<?= $backLink ?>" class="btn btn-primary px-4">← Back to Bookings</a>
    </div>
    </body></html>
    <?php
    exit;
}

// ═══════════════════════════════════════════════════════════════
// STEP 3 — Check if invoice exists
// ═══════════════════════════════════════════════════════════════
$invStmt = $db->prepare("SELECT * FROM invoices WHERE booking_id = ?");
$invStmt->execute([$bid]);
$invoice = $invStmt->fetch();

// ═══════════════════════════════════════════════════════════════
// STEP 4 — If NO invoice → Show beautiful Booking Status Page
// ═══════════════════════════════════════════════════════════════
if (!$invoice) {

    // Fetch booked services
    $svcStmt = $db->prepare("SELECT * FROM booking_services WHERE booking_id = ? ORDER BY id");
    $svcStmt->execute([$bid]);
    $bookedServices = $svcStmt->fetchAll();

    $backLink = match($role) {
        'provider' => APP_URL . '/modules/provider/bookings.php',
        'admin'    => APP_URL . '/modules/admin/bookings.php',
        default    => APP_URL . '/modules/user/bookings.php',
    };

    $sidebarFile = match($role) {
        'provider' => __DIR__ . '/../../includes/sidebar_provider.php',
        'admin'    => __DIR__ . '/../../includes/sidebar_admin.php',
        default    => __DIR__ . '/../../includes/sidebar_user.php',
    };

    $pageTitle = 'Booking #' . $bid . ' — Status';

    // Status config
    $statusConfig = [
        'pending'     => ['label' => 'Pending',     'color' => '#f59e0b', 'bg' => '#fffbeb', 'border' => '#fde68a', 'icon' => 'hourglass-split',      'step' => 1],
        'accepted'    => ['label' => 'Accepted',    'color' => '#2563eb', 'bg' => '#eff6ff', 'border' => '#bfdbfe', 'icon' => 'check-circle-fill',    'step' => 2],
        'in_progress' => ['label' => 'In Progress', 'color' => '#7c3aed', 'bg' => '#faf5ff', 'border' => '#e9d5ff', 'icon' => 'tools',                'step' => 3],
        'completed'   => ['label' => 'Completed',   'color' => '#059669', 'bg' => '#f0fdf4', 'border' => '#bbf7d0', 'icon' => 'check2-all',           'step' => 4],
        'cancelled'   => ['label' => 'Cancelled',   'color' => '#ef4444', 'bg' => '#fef2f2', 'border' => '#fca5a5', 'icon' => 'x-circle-fill',        'step' => 0],
    ];
    $sc = $statusConfig[$booking['status']] ?? $statusConfig['pending'];
    $currentStep = $sc['step'];

    $steps = [
        1 => ['icon' => 'calendar-check', 'title' => 'Booking Placed',    'desc' => 'Your booking request was submitted'],
        2 => ['icon' => 'person-check',   'title' => 'Provider Accepted',  'desc' => 'Provider confirmed your booking'],
        3 => ['icon' => 'tools',          'title' => 'Service In Progress','desc' => 'Provider is working on your job'],
        4 => ['icon' => 'receipt',        'title' => 'Invoice Ready',       'desc' => 'Provider will finalize and send invoice'],
    ];

    // Estimated services total (before invoice)
    $estimatedTotal = array_sum(array_map(fn($s) => $s['subtotal'] ?? ($s['service_price'] * $s['quantity']), $bookedServices));

    require_once __DIR__ . '/../../includes/header.php';
    ?>

    <?php if ($role === 'provider'): ?>
    <div class="dashboard-wrapper">
    <div class="overlay" id="sidebarOverlay"></div>
    <?php require_once $sidebarFile; ?>
    <div class="main-content">
    <?php else: ?>
    <?php require_once __DIR__ . '/../../includes/topnav_user.php'; ?>
    <div class="user-main-content">
    <?php endif; ?>

    <style>
    /* ── Booking Status Page Styles ─────────────────── */
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    .bs-page { font-family: 'Plus Jakarta Sans', sans-serif; }

    /* Hero banner */
    .bs-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #312e81 100%);
        border-radius: 20px;
        padding: 2rem 2.5rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .bs-hero::before {
        content: '';
        position: absolute;
        width: 300px; height: 300px;
        border-radius: 50%;
        background: rgba(124,58,237,.25);
        top: -100px; right: -60px;
        pointer-events: none;
    }
    .bs-hero::after {
        content: '';
        position: absolute;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: rgba(37,99,235,.2);
        bottom: -80px; left: 40px;
        pointer-events: none;
    }
    .bs-hero-content { position: relative; z-index: 1; }
    .bs-booking-num {
        font-size: .78rem; font-weight: 700; letter-spacing: 2px;
        text-transform: uppercase; color: #93c5fd; margin-bottom: .4rem;
    }
    .bs-hero-title { font-size: 1.6rem; font-weight: 800; margin-bottom: .3rem; }
    .bs-hero-sub   { font-size: .88rem; color: rgba(255,255,255,.65); }

    /* Status pill */
    .bs-status-pill {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .45rem 1.1rem; border-radius: 50px;
        font-size: .82rem; font-weight: 700;
        border: 2px solid;
    }
    .bs-status-pill .pulse-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: currentColor;
        animation: bsPulse 1.5s ease-in-out infinite;
    }
    @keyframes bsPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: .4; transform: scale(.7); }
    }

    /* Progress tracker */
    .bs-progress-wrap {
        background: #fff; border-radius: 16px;
        border: 1.5px solid #f1f5f9;
        padding: 1.75rem;
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
        margin-bottom: 1.5rem;
    }
    .bs-steps {
        display: flex; align-items: flex-start;
        position: relative; gap: 0;
    }
    .bs-step {
        flex: 1; display: flex; flex-direction: column;
        align-items: center; text-align: center;
        position: relative;
    }
    /* Connector line */
    .bs-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 22px; left: 50%; width: 100%;
        height: 3px;
        background: #e2e8f0;
        z-index: 0;
        transition: background .4s;
    }
    .bs-step.done:not(:last-child)::after,
    .bs-step.active:not(:last-child)::after {
        background: linear-gradient(90deg, #2563eb, #e2e8f0);
    }
    .bs-step.done:not(:last-child)::after { background: #2563eb; }

    .bs-step-icon {
        width: 44px; height: 44px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; position: relative; z-index: 1;
        border: 3px solid #e2e8f0;
        background: #f8fafc; color: #94a3b8;
        transition: all .3s;
    }
    .bs-step.done  .bs-step-icon { background: #2563eb; border-color: #2563eb; color: #fff; }
    .bs-step.active .bs-step-icon {
        background: #fff; border-color: #2563eb; color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37,99,235,.15);
        animation: stepGlow 2s ease-in-out infinite;
    }
    .bs-step.cancelled .bs-step-icon { background: #ef4444; border-color: #ef4444; color: #fff; }

    @keyframes stepGlow {
        0%, 100% { box-shadow: 0 0 0 4px rgba(37,99,235,.15); }
        50%       { box-shadow: 0 0 0 8px rgba(37,99,235,.05); }
    }

    .bs-step-label {
        margin-top: .6rem; font-size: .72rem; font-weight: 700;
        color: #94a3b8; line-height: 1.3;
    }
    .bs-step.done   .bs-step-label { color: #2563eb; }
    .bs-step.active .bs-step-label { color: #0f172a; }

    /* Info cards */
    .bs-card {
        background: #fff; border-radius: 16px;
        border: 1.5px solid #f1f5f9;
        box-shadow: 0 2px 12px rgba(0,0,0,.05);
        overflow: hidden;
        margin-bottom: 1.25rem;
    }
    .bs-card-header {
        padding: .9rem 1.25rem;
        border-bottom: 1.5px solid #f1f5f9;
        font-weight: 700; font-size: .85rem;
        text-transform: uppercase; letter-spacing: .5px;
        color: #64748b;
        display: flex; align-items: center; gap: .5rem;
    }
    .bs-card-header i { font-size: 1rem; color: #2563eb; }
    .bs-card-body { padding: 1.25rem; }

    /* Info rows */
    .bs-info-row {
        display: flex; align-items: center;
        padding: .6rem 0; border-bottom: 1px solid #f8fafc;
        gap: .75rem;
    }
    .bs-info-row:last-child { border-bottom: none; }
    .bs-info-icon {
        width: 32px; height: 32px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: .9rem; flex-shrink: 0;
        background: #eff6ff; color: #2563eb;
    }
    .bs-info-label { font-size: .73rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
    .bs-info-value { font-size: .88rem; font-weight: 700; color: #0f172a; }

    /* Services mini table */
    .bs-svc-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: .65rem 0; border-bottom: 1px dashed #f1f5f9;
        font-size: .85rem;
    }
    .bs-svc-row:last-child { border-bottom: none; }
    .bs-svc-name { color: #334155; font-weight: 500; }
    .bs-svc-price { font-weight: 700; color: #2563eb; }

    /* Estimated total */
    .bs-est-total {
        background: linear-gradient(135deg, #eff6ff, #f5f3ff);
        border: 2px solid #bfdbfe; border-radius: 12px;
        padding: 1rem 1.25rem; margin-top: .75rem;
        display: flex; align-items: center; justify-content: space-between;
    }
    .bs-est-label { font-size: .78rem; font-weight: 700; color: #3730a3; }
    .bs-est-value { font-size: 1.4rem; font-weight: 800; color: #1d4ed8; }

    /* What's next banner */
    .bs-next-banner {
        border-radius: 14px; padding: 1.1rem 1.25rem;
        display: flex; align-items: flex-start; gap: .875rem;
        margin-bottom: 1.25rem;
    }
    .bs-next-icon {
        width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
    }
    .bs-next-title { font-size: .88rem; font-weight: 700; margin-bottom: .2rem; }
    .bs-next-desc  { font-size: .8rem;  line-height: 1.6; }

    /* Action buttons */
    .bs-action-btn {
        display: flex; align-items: center; gap: .75rem;
        padding: .9rem 1.1rem; border-radius: 12px;
        text-decoration: none; border: 1.5px solid #e2e8f0;
        background: #fff; color: #334155;
        font-weight: 600; font-size: .85rem;
        transition: all .2s; margin-bottom: .6rem;
    }
    .bs-action-btn:hover {
        border-color: #2563eb; color: #2563eb;
        background: #eff6ff; transform: translateX(4px);
    }
    .bs-action-btn .bs-ab-icon {
        width: 36px; height: 36px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem; flex-shrink: 0;
        background: #f1f5f9; transition: all .2s;
    }
    .bs-action-btn:hover .bs-ab-icon { background: #dbeafe; }
    .bs-action-btn .bs-ab-arrow { margin-left: auto; color: #cbd5e1; font-size: .8rem; transition: all .2s; }
    .bs-action-btn:hover .bs-ab-arrow { color: #2563eb; transform: translateX(3px); }

    /* Cancelled state */
    .bs-cancelled-banner {
        background: #fef2f2; border: 2px solid #fca5a5;
        border-radius: 16px; padding: 1.5rem;
        text-align: center; margin-bottom: 1.5rem;
    }

    /* Responsive */
    @media (max-width: 576px) {
        .bs-hero { padding: 1.5rem; }
        .bs-hero-title { font-size: 1.3rem; }
        .bs-step-label { font-size: .65rem; }
        .bs-step-icon { width: 36px; height: 36px; font-size: .9rem; }
        .bs-steps .bs-step:not(:last-child)::after { top: 18px; }
    }
    </style>

    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
        <?php if ($role === 'provider'): ?>
            <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
        <?php endif; ?>
            <div class="topbar-title">Booking Details</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $backLink ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <?php if (in_array($role, ['provider']) && in_array($booking['status'], ['accepted', 'in_progress'])): ?>
            <a href="<?= APP_URL ?>/modules/provider/billing.php?booking_id=<?= $bid ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-cash-coin"></i> Finalize Billing
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="page-content bs-page">

        <!-- ── CANCELLED STATE ─────────────────────────────── -->
        <?php if ($booking['status'] === 'cancelled'): ?>
        <div class="bs-cancelled-banner">
            <div style="font-size:2.5rem;margin-bottom:.5rem">❌</div>
            <h5 class="fw-800 mb-1" style="color:#991b1b">Booking Cancelled</h5>
            <p class="text-muted small mb-2">This booking has been cancelled.</p>
            <?php if (!empty($booking['cancellation_reason'])): ?>
            <div class="d-inline-block px-3 py-1 rounded-pill text-muted small" style="background:#fff;border:1px solid #fca5a5">
                <i class="bi bi-chat-left-text me-1"></i>
                Reason: <?= sanitize($booking['cancellation_reason']) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ── HERO BANNER ──────────────────────────────────── -->
        <div class="bs-hero">
            <div class="bs-hero-content">
                <div class="row align-items-center g-3">
                    <div class="col">
                        <div class="bs-booking-num">📋 Booking #<?= $bid ?></div>
                        <div class="bs-hero-title"><?= sanitize($booking['business_name']) ?></div>
                        <div class="bs-hero-sub d-flex align-items-center gap-2 flex-wrap mt-1">
                            <span><i class="bi <?= sanitize($booking['cat_icon'] ?? 'bi-grid') ?>"></i>
                            <?= sanitize($booking['category']) ?></span>
                            <span style="color:rgba(255,255,255,.3)">•</span>
                            <span><i class="bi bi-calendar3"></i>
                            <?= formatDate($booking['scheduled_date']) ?></span>
                            <span style="color:rgba(255,255,255,.3)">•</span>
                            <span><i class="bi bi-clock"></i>
                            <?= date('h:i A', strtotime($booking['scheduled_time'])) ?></span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="bs-status-pill" style="color:<?= $sc['color'] ?>;background:<?= $sc['bg'] ?>;border-color:<?= $sc['border'] ?>">
                            <?php if ($booking['status'] !== 'cancelled'): ?>
                            <span class="pulse-dot"></span>
                            <?php endif; ?>
                            <i class="bi bi-<?= $sc['icon'] ?>"></i>
                            <?= $sc['label'] ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($booking['status'] !== 'cancelled'): ?>
        <!-- ── PROGRESS TRACKER ─────────────────────────────── -->
        <div class="bs-progress-wrap">
            <div class="fw-700 mb-3" style="font-size:.82rem;text-transform:uppercase;letter-spacing:1px;color:#64748b">
                <i class="bi bi-map text-primary me-1"></i> Service Progress
            </div>
            <div class="bs-steps">
                <?php foreach ($steps as $stepNum => $step):
                    if ($currentStep === 0) $cls = ''; // cancelled — no highlighting
                    elseif ($stepNum < $currentStep) $cls = 'done';
                    elseif ($stepNum === $currentStep) $cls = 'active';
                    else $cls = '';
                ?>
                <div class="bs-step <?= $cls ?>">
                    <div class="bs-step-icon">
                        <?php if ($stepNum < $currentStep): ?>
                        <i class="bi bi-check-lg"></i>
                        <?php else: ?>
                        <i class="bi bi-<?= $step['icon'] ?>"></i>
                        <?php endif; ?>
                    </div>
                    <div class="bs-step-label"><?= $step['title'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Current status message -->
            <div class="mt-3 pt-3" style="border-top:1px solid #f1f5f9">
                <?php
                $msgs = [
                    'pending'     => ['🕐', '#854d0e', '#fffbeb', '#fde68a', 'Waiting for Provider', 'Your booking request has been sent. The provider will confirm it shortly. You\'ll be notified once they accept.'],
                    'accepted'    => ['✅', '#1e40af', '#eff6ff', '#bfdbfe', 'Booking Confirmed!', 'Great news! The provider has accepted your booking. Get ready — service is scheduled for ' . formatDate($booking['scheduled_date']) . '.'],
                    'in_progress' => ['🔧', '#5b21b6', '#faf5ff', '#e9d5ff', 'Service In Progress', 'The provider is currently working on your job. Invoice will be generated once the work is complete.'],
                    'completed'   => ['🎉', '#065f46', '#f0fdf4', '#bbf7d0', 'Service Completed!', 'Your service has been completed. The provider is finalizing your invoice — it will appear here shortly.'],
                ];
                $msg = $msgs[$booking['status']] ?? $msgs['pending'];
                [$emoji, $txtColor, $bgColor, $bdColor, $msgTitle, $msgDesc] = $msg;
                ?>
                <div class="d-flex align-items-start gap-3 p-3 rounded-3" style="background:<?= $bgColor ?>;border:1.5px solid <?= $bdColor ?>">
                    <span style="font-size:1.5rem;line-height:1"><?= $emoji ?></span>
                    <div>
                        <div class="fw-700 mb-1" style="color:<?= $txtColor ?>;font-size:.88rem"><?= $msgTitle ?></div>
                        <div style="color:<?= $txtColor ?>;opacity:.8;font-size:.8rem;line-height:1.6"><?= $msgDesc ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-3">

            <!-- LEFT COLUMN -->
            <div class="col-lg-7">

                <!-- Provider Info -->
                <div class="bs-card">
                    <div class="bs-card-header">
                        <i class="bi bi-person-badge"></i> Service Provider
                    </div>
                    <div class="bs-card-body">
                        <div class="d-flex align-items-center gap-3 mb-3 pb-3" style="border-bottom:1px solid #f1f5f9">
                            <div style="width:52px;height:52px;border-radius:14px;background:<?= sanitize($booking['cat_color'] ?? 'linear-gradient(135deg,#2563eb,#7c3aed)') ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;font-weight:800;flex-shrink:0">
                                <?= strtoupper(substr($booking['business_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-700" style="font-size:1rem"><?= sanitize($booking['business_name']) ?></div>
                                <div class="text-muted small"><?= sanitize($booking['prov_name']) ?></div>
                            </div>
                        </div>
                        <div class="bs-info-row">
                            <div class="bs-info-icon"><i class="bi bi-envelope"></i></div>
                            <div><div class="bs-info-label">Email</div><div class="bs-info-value"><?= sanitize($booking['prov_email']) ?></div></div>
                        </div>
                        <div class="bs-info-row">
                            <div class="bs-info-icon"><i class="bi bi-telephone"></i></div>
                            <div><div class="bs-info-label">Phone</div><div class="bs-info-value"><?= sanitize($booking['prov_phone'] ?? '—') ?></div></div>
                        </div>
                        <div class="bs-info-row">
                            <div class="bs-info-icon" style="background:#f0fdf4;color:#059669"><i class="bi bi-grid"></i></div>
                            <div><div class="bs-info-label">Category</div><div class="bs-info-value"><?= sanitize($booking['category']) ?></div></div>
                        </div>
                    </div>
                </div>

                <!-- Booking Info -->
                <div class="bs-card">
                    <div class="bs-card-header">
                        <i class="bi bi-calendar3"></i> Booking Details
                    </div>
                    <div class="bs-card-body">
                        <div class="bs-info-row">
                            <div class="bs-info-icon"><i class="bi bi-hash"></i></div>
                            <div><div class="bs-info-label">Booking ID</div><div class="bs-info-value">#<?= $bid ?></div></div>
                        </div>
                        <div class="bs-info-row">
                            <div class="bs-info-icon" style="background:#fef9c3;color:#ca8a04"><i class="bi bi-calendar-event"></i></div>
                            <div><div class="bs-info-label">Scheduled Date</div><div class="bs-info-value"><?= formatDate($booking['scheduled_date']) ?></div></div>
                        </div>
                        <div class="bs-info-row">
                            <div class="bs-info-icon" style="background:#faf5ff;color:#7c3aed"><i class="bi bi-clock"></i></div>
                            <div><div class="bs-info-label">Scheduled Time</div><div class="bs-info-value"><?= date('h:i A', strtotime($booking['scheduled_time'])) ?></div></div>
                        </div>
                        <div class="bs-info-row">
                            <div class="bs-info-icon" style="background:#fef2f2;color:#ef4444"><i class="bi bi-geo-alt-fill"></i></div>
                            <div><div class="bs-info-label">Service Address</div><div class="bs-info-value"><?= sanitize($booking['address']) ?></div></div>
                        </div>
                        <div class="bs-info-row">
                            <div class="bs-info-icon" style="background:#f0fdf4;color:#059669"><i class="bi bi-calendar-plus"></i></div>
                            <div><div class="bs-info-label">Booking Placed On</div><div class="bs-info-value"><?= formatDate($booking['created_at']) ?></div></div>
                        </div>
                        <?php if (!empty($booking['notes'])): ?>
                        <div class="mt-3 p-3 rounded-3" style="background:#f8fafc;border-left:3px solid #2563eb;font-size:.85rem;color:#475569">
                            <div class="fw-700 mb-1" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b"><i class="bi bi-chat-left-text me-1"></i>Notes</div>
                            <?= sanitize($booking['notes']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN -->
            <div class="col-lg-5">

                <!-- Services Requested -->
                <div class="bs-card">
                    <div class="bs-card-header">
                        <i class="bi bi-list-check"></i> Services Requested
                    </div>
                    <div class="bs-card-body">
                        <?php if (!empty($bookedServices)): ?>
                            <?php foreach ($bookedServices as $svc): ?>
                            <div class="bs-svc-row">
                                <div>
                                    <div class="bs-svc-name"><?= sanitize($svc['service_name']) ?></div>
                                    <div class="text-muted" style="font-size:.75rem">Qty: <?= $svc['quantity'] ?> × <?= formatCurrency($svc['service_price']) ?></div>
                                </div>
                                <div class="bs-svc-price"><?= formatCurrency($svc['subtotal'] ?? $svc['service_price'] * $svc['quantity']) ?></div>
                            </div>
                            <?php endforeach; ?>
                            <div class="bs-est-total">
                                <div>
                                    <div class="bs-est-label"><i class="bi bi-calculator me-1"></i>Estimated Total</div>
                                    <div style="font-size:.72rem;color:#6366f1;margin-top:.15rem">* Final amount may vary after invoice</div>
                                </div>
                                <div class="bs-est-value"><?= formatCurrency($estimatedTotal) ?></div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3 text-muted small">
                                <i class="bi bi-inbox d-block mb-1" style="font-size:1.5rem;opacity:.3"></i>
                                No services listed yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Invoice Status -->
                <?php if ($booking['status'] !== 'cancelled'): ?>
                <div class="bs-card">
                    <div class="bs-card-header">
                        <i class="bi bi-receipt"></i> Invoice Status
                    </div>
                    <div class="bs-card-body">
                        <?php
                        $invoiceMsgs = [
                            'pending'     => ['⏳', '#854d0e', '#fffbeb', '#fde68a', 'Waiting for provider to accept', 'Invoice will be generated after the provider accepts and completes the job.'],
                            'accepted'    => ['📋', '#1e40af', '#eff6ff', '#bfdbfe', 'Service not started yet', 'Invoice will be generated once the provider completes your service.'],
                            'in_progress' => ['🔧', '#5b21b6', '#faf5ff', '#e9d5ff', 'Service in progress...', 'Your invoice is being prepared. Once the provider finalizes the job, it will appear here automatically.'],
                            'completed'   => ['⚡', '#065f46', '#f0fdf4', '#bbf7d0', 'Invoice generating soon!', 'Service is complete! The provider is finalizing your invoice. Refresh in a few minutes.'],
                        ];
                        [$iEmoji, $iTxtColor, $iBgColor, $iBdColor, $iTitle, $iDesc] = $invoiceMsgs[$booking['status']] ?? $invoiceMsgs['pending'];
                        ?>
                        <div class="text-center py-2">
                            <div style="font-size:2.5rem;margin-bottom:.5rem"><?= $iEmoji ?></div>
                            <div class="fw-700 mb-1" style="color:<?= $iTxtColor ?>;font-size:.9rem"><?= $iTitle ?></div>
                            <p style="color:#64748b;font-size:.8rem;line-height:1.6;margin-bottom:1rem"><?= $iDesc ?></p>
                            <button onclick="location.reload()" class="btn btn-sm" style="background:#f1f5f9;border:1.5px solid #e2e8f0;color:#475569;font-weight:600;border-radius:8px;font-size:.8rem">
                                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Page
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Customer Info (for provider/admin view) -->
                <?php if ($role !== 'customer'): ?>
                <div class="bs-card">
                    <div class="bs-card-header">
                        <i class="bi bi-person"></i> Customer Info
                    </div>
                    <div class="bs-card-body">
                        <div class="bs-info-row">
                            <div class="bs-info-icon"><i class="bi bi-person"></i></div>
                            <div><div class="bs-info-label">Name</div><div class="bs-info-value"><?= sanitize($booking['cust_name']) ?></div></div>
                        </div>
                        <div class="bs-info-row">
                            <div class="bs-info-icon"><i class="bi bi-envelope"></i></div>
                            <div><div class="bs-info-label">Email</div><div class="bs-info-value"><?= sanitize($booking['cust_email']) ?></div></div>
                        </div>
                        <div class="bs-info-row">
                            <div class="bs-info-icon"><i class="bi bi-telephone"></i></div>
                            <div><div class="bs-info-label">Phone</div><div class="bs-info-value"><?= sanitize($booking['cust_phone'] ?? '—') ?></div></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div>
                    <div class="fw-700 mb-2" style="font-size:.78rem;text-transform:uppercase;letter-spacing:1px;color:#94a3b8">
                        Quick Actions
                    </div>
                    <a href="<?= $backLink ?>" class="bs-action-btn">
                        <div class="bs-ab-icon"><i class="bi bi-arrow-left"></i></div>
                        <div><div style="font-size:.82rem">Back to Bookings</div></div>
                        <i class="bi bi-chevron-right bs-ab-arrow"></i>
                    </a>
                    <?php if ($role === 'customer' && in_array($booking['status'], ['pending', 'accepted'])): ?>
                    <a href="<?= APP_URL ?>/modules/user/browse.php" class="bs-action-btn">
                        <div class="bs-ab-icon" style="background:#f0fdf4"><i class="bi bi-plus-circle" style="color:#059669"></i></div>
                        <div><div style="font-size:.82rem">Book Another Service</div></div>
                        <i class="bi bi-chevron-right bs-ab-arrow"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($role === 'provider' && in_array($booking['status'], ['accepted', 'in_progress'])): ?>
                    <a href="<?= APP_URL ?>/modules/provider/billing.php?booking_id=<?= $bid ?>" class="bs-action-btn" style="border-color:#2563eb;background:#eff6ff">
                        <div class="bs-ab-icon" style="background:#dbeafe"><i class="bi bi-cash-coin" style="color:#2563eb"></i></div>
                        <div>
                            <div style="font-size:.82rem;color:#1d4ed8;font-weight:700">Finalize Billing</div>
                            <div style="font-size:.72rem;color:#64748b">Generate invoice for this booking</div>
                        </div>
                        <i class="bi bi-chevron-right bs-ab-arrow"></i>
                    </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </div><!-- end page-content -->

    <?php if ($role === 'provider'): ?>
    </div></div>
    <?php else: ?>
    </div>
    <?php endif; ?>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    <?php
    exit; // Don't fall through to invoice rendering
}

// ═══════════════════════════════════════════════════════════════
// STEP 5 — Invoice EXISTS → Render the full invoice page (original)
// ═══════════════════════════════════════════════════════════════
$data = array_merge($booking, (array)$invoice); // merge booking + invoice data

$services = $db->prepare("SELECT * FROM booking_services WHERE booking_id = ? ORDER BY id");
$services->execute([$bid]);
$items = $services->fetchAll();

$backLink = match($role) {
    'provider' => APP_URL . '/modules/provider/bookings.php',
    'admin'    => APP_URL . '/modules/admin/bookings.php',
    default    => APP_URL . '/modules/user/bookings.php',
};

$pageTitle = 'Invoice ' . $data['invoice_number'];

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
              $amount     = number_format((float)$data['grand_total'], 2, '.', '');
              $upiId      = 'zeelp9557@okhdfcbank';
              $payeeName  = 'HomeServe';
              $tn         = 'Invoice ' . $data['invoice_number'];
              $upiString  = "upi://pay?pa={$upiId}&pn=" . urlencode($payeeName) . "&am={$amount}&cu=INR&tn=" . urlencode($tn);
              $qrUrl      = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" . urlencode($upiString) . "&ecc=M&margin=8";
              $upiLink    = $upiString;
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
          </div>
        </div>
        <div class="qr-note">
          <i class="bi bi-info-circle-fill"></i>
          After paying, enter your Transaction ID / UTR below to confirm your payment.
        </div>
      </div>

      <!-- TRANSACTION ID SUBMISSION -->
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
              <div class="txn-how-item"><i class="bi bi-google" style="color:#4285f4"></i><span><strong>GPay:</strong> Payment history → Transaction details → UPI Transaction ID</span></div>
              <div class="txn-how-item"><i class="bi bi-phone-fill" style="color:#5f259f"></i><span><strong>PhonePe:</strong> History → Transaction → Transaction ID</span></div>
              <div class="txn-how-item"><i class="bi bi-wallet2" style="color:#00b9f1"></i><span><strong>Paytm:</strong> Passbook → Order details → UTR Number</span></div>
            </div>
          </div>
        </div>
      </div>
      <div class="txn-submitted-banner" id="txnSubmittedBanner" style="display:none">
        <i class="bi bi-check-circle-fill text-success" style="font-size:1.5rem"></i>
        <div>
          <div class="fw-700 text-success">Payment Submitted! ✅</div>
          <div class="small text-muted">Your transaction ID has been recorded. Thank you!</div>
        </div>
      </div>

      <?php elseif ($role === 'provider'): ?>
      <div class="paid-stamp no-print" style="background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8">
        <i class="bi bi-check-circle-fill" style="color:#2563eb"></i>
        Invoice generated successfully! The customer has been notified.
      </div>
      <?php else: ?>
      <div class="paid-stamp no-print">
        <i class="bi bi-check-circle-fill text-success"></i> Payment Received — Thank You!
      </div>
      <?php endif; ?>

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
.txn-submit-section { margin-top:1rem;border:2px solid #e2e8f0;border-radius:14px;overflow:hidden;background:#fff; }
.txn-header { background:linear-gradient(135deg,#0f172a,#1e3a8a);color:#fff;padding:.85rem 1.25rem;font-weight:700;font-size:.95rem;display:flex;align-items:center;gap:.5rem; }
.txn-body { padding:1.25rem; }
.txn-desc { font-size:.85rem;color:#475569;margin-bottom:1rem;line-height:1.6; }
.txn-input-wrap { margin-bottom:1rem; }
.txn-input-group { display:flex;border:2px solid #e2e8f0;border-radius:12px;overflow:hidden;transition:all .2s; }
.txn-input-group:focus-within { border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.txn-prefix { background:#f8fafc;padding:.75rem 1rem;color:#2563eb;font-size:1rem;border-right:1.5px solid #e2e8f0;display:flex;align-items:center; }
.txn-input { flex:1;border:none;outline:none;padding:.75rem 1rem;font-family:'Courier New',monospace;font-size:.95rem;font-weight:700;color:#0f172a;letter-spacing:.5px;background:#fff; }
.txn-input::placeholder { color:#cbd5e1;font-weight:400;font-family:inherit;letter-spacing:0; }
.txn-submit-btn { background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border:none;padding:.75rem 1.25rem;font-family:inherit;font-weight:700;font-size:.85rem;cursor:pointer;display:flex;align-items:center;gap:.4rem;white-space:nowrap;transition:all .2s; }
.txn-submit-btn:hover { filter:brightness(1.1); }
.txn-submit-btn:disabled { background:#94a3b8;cursor:not-allowed; }
.txn-status { margin-top:.5rem;font-size:.82rem;min-height:20px;padding:0 .25rem; }
.txn-status.success { color:#16a34a; }
.txn-status.error { color:#dc2626; }
.txn-how { background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:.85rem 1rem; }
.txn-how-title { font-size:.78rem;font-weight:700;color:#475569;margin-bottom:.6rem;display:flex;align-items:center;gap:.4rem; }
.txn-how-grid { display:flex;flex-direction:column;gap:.4rem; }
.txn-how-item { display:flex;align-items:flex-start;gap:.5rem;font-size:.78rem;color:#64748b;line-height:1.5; }
.txn-how-item i { margin-top:2px;flex-shrink:0;font-size:.9rem; }
.txn-submitted-banner { margin-top:1rem;background:#fffbeb;border:2px solid #fde68a;border-radius:12px;padding:1rem 1.25rem;display:flex;align-items:center;gap:1rem; }
.qr-payment-section { margin-top:1.5rem;border:2px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.07); }
.qr-pay-header { background:linear-gradient(135deg,#1d4ed8,#7c3aed);color:#fff;padding:.85rem 1.25rem;font-weight:700;font-size:1rem;display:flex;align-items:center;gap:.5rem; }
.qr-pay-body { display:flex;gap:2rem;padding:1.5rem;background:#f8fafc;flex-wrap:wrap; }
.qr-code-wrap { display:flex;flex-direction:column;align-items:center;gap:.5rem; }
.qr-img { width:180px;height:180px;border-radius:12px;border:3px solid #e2e8f0;background:#fff;padding:6px; }
.qr-scan-label { font-size:.75rem;color:#64748b;font-weight:600;text-align:center; }
.qr-info-col { flex:1;min-width:220px; }
.qr-amount-display { background:#fff;border:2px solid #dbeafe;border-radius:10px;padding:.75rem 1rem;margin-bottom:.75rem; }
.qr-amount { font-size:1.6rem;font-weight:800;color:#1d4ed8; }
.upi-id-box { background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:.6rem 1rem;margin-bottom:.75rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.4rem; }
.upi-id-val { font-weight:700;color:#0f172a;font-family:monospace;font-size:.95rem; }
.btn-copy-upi { background:#f1f5f9;border:1.5px solid #e2e8f0;border-radius:6px;padding:.2rem .7rem;font-size:.78rem;cursor:pointer;font-weight:600;color:#475569;transition:all .2s; }
.btn-copy-upi:hover { background:#e2e8f0; }
.upi-app-btn { display:inline-flex;align-items:center;gap:.4rem;padding:.35rem .75rem;border-radius:8px;font-size:.78rem;font-weight:600;text-decoration:none;border:1.5px solid #e2e8f0;background:#fff;color:#1e293b;transition:all .2s; }
.upi-app-btn:hover { transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.1);color:#1e293b; }
.btn-pay-upi { display:inline-flex;align-items:center;gap:.5rem;padding:.7rem 1.5rem;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border-radius:10px;font-weight:700;font-size:.95rem;text-decoration:none;transition:all .2s;box-shadow:0 4px 14px rgba(22,163,74,.35); }
.btn-pay-upi:hover { transform:translateY(-2px);box-shadow:0 6px 20px rgba(22,163,74,.45);color:#fff; }
.qr-note { background:#fef9c3;padding:.65rem 1.25rem;font-size:.8rem;color:#854d0e;border-top:1px solid #fde68a; }
.paid-stamp { text-align:center;padding:1rem;font-size:1rem;font-weight:700;color:#16a34a;background:#f0fdf4;border-radius:10px;margin-top:1rem;border:1.5px solid #bbf7d0; }
@media print { .print-only { display:block !important; } }
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
        setTimeout(() => { location.reload(); }, 2000);
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