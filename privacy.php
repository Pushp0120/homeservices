<?php require_once __DIR__ . '/includes/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Privacy Policy | HomeServe</title>
<meta name="description" content="Learn how HomeServe collects, uses, and protects your personal information.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="<?= APP_URL ?>/assets/css/main.css" rel="stylesheet">
<style>
:root{--navy:#080e1d;--navy2:#0d1627;--navy3:#111e35;--blue:#2563eb;--violet:#7c3aed;--accent:#60a5fa;--fh:'Sora',sans-serif;--fb:'DM Sans',sans-serif;}
*,*::before,*::after{box-sizing:border-box;}
body{font-family:var(--fb);background:var(--navy);color:#e2e8f0;overflow-x:hidden;}
::-webkit-scrollbar{width:5px;}::-webkit-scrollbar-track{background:var(--navy);}::-webkit-scrollbar-thumb{background:rgba(37,99,235,.35);border-radius:3px;}
.site-nav{position:fixed;top:0;left:0;right:0;z-index:1000;padding:1.1rem 0;transition:all .3s;}
.site-nav.scrolled{background:rgba(8,14,29,.93);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,.07);padding:.65rem 0;}
.nav-brand{font-family:var(--fh);font-size:1.3rem;font-weight:800;color:#fff;text-decoration:none;display:flex;align-items:center;gap:.5rem;}
.nav-brand .bico{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--blue),var(--violet));display:flex;align-items:center;justify-content:center;font-size:.9rem;}
.nav-brand .acc{color:var(--accent);}
.nbtn-l,.nbtn-c{font-family:var(--fh);font-weight:700;font-size:.86rem;text-decoration:none;border-radius:8px;transition:all .2s;}
.nbtn-l{color:rgba(255,255,255,.7);padding:.44rem 1rem;border:1px solid rgba(255,255,255,.14);}
.nbtn-l:hover{color:#fff;background:rgba(255,255,255,.08);}
.nbtn-c{background:var(--blue);color:#fff;padding:.44rem 1.2rem;}
.nbtn-c:hover{background:#3b82f6;color:#fff;}
.pg-hero{padding:9rem 0 4rem;background:radial-gradient(ellipse 70% 60% at 50% 0%,rgba(37,99,235,.18) 0%,transparent 60%),var(--navy);text-align:center;}
.eyebrow{font-family:var(--fh);font-size:.71rem;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:var(--accent);margin-bottom:.55rem;}
.legal-body{max-width:760px;margin:0 auto;padding:3rem 0 6rem;}
.legal-body h2{font-family:var(--fh);font-size:1.15rem;font-weight:700;color:#fff;margin-top:2.5rem;margin-bottom:.75rem;padding-bottom:.5rem;border-bottom:1px solid rgba(255,255,255,.07);}
.legal-body p,.legal-body li{font-size:.875rem;color:rgba(255,255,255,.55);line-height:1.85;}
.legal-body ul{padding-left:1.25rem;}
.legal-body li{margin-bottom:.4rem;}
.legal-body strong{color:rgba(255,255,255,.8);}
.legal-body a{color:var(--accent);}
.last-updated{display:inline-block;background:rgba(37,99,235,.12);border:1px solid rgba(37,99,235,.22);border-radius:8px;padding:.3rem .9rem;font-size:.78rem;color:var(--accent);font-family:var(--fh);font-weight:600;margin-bottom:2rem;}
.data-table{width:100%;border-collapse:collapse;margin:1rem 0;}
.data-table th{background:rgba(37,99,235,.1);color:rgba(255,255,255,.7);font-size:.8rem;font-weight:600;padding:.7rem 1rem;text-align:left;border:1px solid rgba(255,255,255,.08);}
.data-table td{font-size:.82rem;color:rgba(255,255,255,.5);padding:.65rem 1rem;border:1px solid rgba(255,255,255,.06);vertical-align:top;line-height:1.7;}
.mini-footer{background:var(--navy2);border-top:1px solid rgba(255,255,255,.055);padding:2rem 0;font-size:.8rem;color:rgba(255,255,255,.3);text-align:center;}
.mini-footer a{color:rgba(255,255,255,.45);text-decoration:none;margin:0 .75rem;}
.mini-footer a:hover{color:#fff;}
</style>
</head>
<body>
<nav class="site-nav" id="siteNav">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="<?= APP_URL ?>" class="nav-brand"><div class="bico"><i class="bi bi-house-heart-fill text-white" style="font-size:.85rem"></i></div>Home<span class="acc">Serve</span></a>
    <div class="d-flex align-items-center gap-2">
      <a href="<?= APP_URL ?>/login.php" class="nbtn-l d-none d-sm-inline-flex">Login</a>
      <a href="<?= APP_URL ?>/register.php" class="nbtn-c">Get Started</a>
    </div>
  </div>
</nav>

<div class="pg-hero">
  <div class="container">
    <div class="eyebrow">Legal</div>
    <h1 style="font-family:var(--fh);font-size:clamp(1.9rem,4vw,2.8rem);font-weight:800;color:#fff;letter-spacing:-.8px;">Privacy Policy</h1>
  </div>
</div>

<div class="container">
  <div class="legal-body">
    <div class="last-updated"><i class="bi bi-shield-check me-1"></i> Last Updated: <?= date('F Y') ?></div>

    <p>At <strong>HomeServe</strong>, your privacy matters to us. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform. By using HomeServe, you agree to this policy.</p>

    <h2>1. Information We Collect</h2>
    <table class="data-table">
      <tr><th>Data Type</th><th>What We Collect</th><th>Why We Collect It</th></tr>
      <tr><td><strong>Account Data</strong></td><td>Name, email address, phone number, password (hashed)</td><td>Account creation and authentication</td></tr>
      <tr><td><strong>Profile Data</strong></td><td>Profile photo, address, bio, experience (for providers)</td><td>Display on marketplace</td></tr>
      <tr><td><strong>Booking Data</strong></td><td>Service type, date, time, location, booking status</td><td>Service fulfilment and history</td></tr>
      <tr><td><strong>Payment Data</strong></td><td>Invoice amounts (no card numbers — we use UPI)</td><td>Billing and reporting</td></tr>
      <tr><td><strong>Reviews</strong></td><td>Ratings and written reviews you submit</td><td>Trust and transparency</td></tr>
      <tr><td><strong>Usage Data</strong></td><td>Pages visited, actions taken, device info</td><td>Platform improvement</td></tr>
    </table>

    <h2>2. How We Use Your Information</h2>
    <ul>
      <li>To create and manage your account.</li>
      <li>To facilitate bookings between Users and Providers.</li>
      <li>To generate invoices and payment records.</li>
      <li>To send booking confirmations, updates, and support communications.</li>
      <li>To improve the platform through analytics and feedback.</li>
      <li>To verify Provider identities and maintain platform integrity.</li>
    </ul>

    <h2>3. How We Share Your Information</h2>
    <p>We share your data only in limited circumstances:</p>
    <ul>
      <li><strong>With Providers:</strong> When you book a service, the Provider receives your name, contact info, and service details to complete the job.</li>
      <li><strong>With Admins:</strong> Platform administrators can access all booking and account data to manage the platform.</li>
      <li><strong>Legal Requirements:</strong> We may disclose information if required by law or to protect our legal rights.</li>
      <li>We do <strong>not</strong> sell your personal data to third parties.</li>
    </ul>

    <h2>4. Data Security</h2>
    <p>We implement industry-standard security measures including:</p>
    <ul>
      <li>Password hashing using bcrypt.</li>
      <li>HTTPS encryption for all data in transit.</li>
      <li>CSRF token protection on all forms.</li>
      <li>Session management with secure, HTTP-only cookies.</li>
    </ul>
    <p>Despite our best efforts, no method of transmission over the internet is 100% secure. We cannot guarantee absolute security.</p>

    <h2>5. Your Rights</h2>
    <p>You have the right to:</p>
    <ul>
      <li><strong>Access</strong> — Request a copy of the personal data we hold about you.</li>
      <li><strong>Correction</strong> — Update your information at any time from your Profile settings.</li>
      <li><strong>Deletion</strong> — Request deletion of your account and associated data.</li>
      <li><strong>Portability</strong> — Request your data in a portable format.</li>
    </ul>
    <p>To exercise any of these rights, contact us at <a href="mailto:privacy@homeserve.in">privacy@homeserve.in</a>.</p>

    <h2>6. Cookies</h2>
    <p>We use session cookies to keep you logged in. We do not use tracking cookies or third-party advertising cookies. You can disable cookies in your browser, but this may affect platform functionality.</p>

    <h2>7. Data Retention</h2>
    <p>We retain your account data for as long as your account is active. Booking records and invoices are retained for 7 years for legal and accounting compliance. You may request deletion of non-essential data at any time.</p>

    <h2>8. Children's Privacy</h2>
    <p>HomeServe is not intended for users under the age of 18. We do not knowingly collect personal information from anyone under 18. If we become aware of such data, we will delete it immediately.</p>

    <h2>9. Changes to This Policy</h2>
    <p>We may update this Privacy Policy periodically. We'll notify registered users of material changes via email. Continued use of the platform constitutes acceptance of the updated policy.</p>

    <h2>10. Contact Us</h2>
    <p>For privacy-related inquiries, contact our Data Protection team at <a href="mailto:privacy@homeserve.in">privacy@homeserve.in</a> or through our <a href="<?= APP_URL ?>/contact.php">Contact page</a>.</p>
  </div>
</div>

<footer class="mini-footer">
  <div class="container">
    <div class="mb-2">
      <a href="<?= APP_URL ?>">Home</a><a href="<?= APP_URL ?>/about.php">About</a>
      <a href="<?= APP_URL ?>/contact.php">Contact</a><a href="<?= APP_URL ?>/faq.php">FAQ</a>
      <a href="<?= APP_URL ?>/terms.php">Terms</a><a href="<?= APP_URL ?>/privacy.php">Privacy</a>
    </div>
    &copy; <?= date('Y') ?> HomeServe. All rights reserved.
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>const nav=document.getElementById('siteNav');window.addEventListener('scroll',()=>nav.classList.toggle('scrolled',window.scrollY>40),{passive:true});</script>
</body>
</html>
