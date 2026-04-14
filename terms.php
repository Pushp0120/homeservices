<?php require_once __DIR__ . '/includes/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Terms of Service | HomeServe</title>
<meta name="description" content="Read HomeServe's Terms of Service — the rules and conditions governing use of our home services platform.">
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
    <h1 style="font-family:var(--fh);font-size:clamp(1.9rem,4vw,2.8rem);font-weight:800;color:#fff;letter-spacing:-.8px;">Terms of Service</h1>
  </div>
</div>

<div class="container">
  <div class="legal-body">
    <div class="last-updated"><i class="bi bi-calendar3 me-1"></i> Last Updated: <?= date('F Y') ?></div>

    <p>Welcome to <strong>HomeServe</strong>. By accessing or using our platform — including our website, applications, and services — you agree to be bound by these Terms of Service. Please read them carefully.</p>

    <h2>1. Acceptance of Terms</h2>
    <p>By registering an account or using HomeServe, you confirm that you are at least 18 years of age, have read and understood these Terms, and agree to be legally bound by them. If you do not agree, please do not use the platform.</p>

    <h2>2. Services Overview</h2>
    <p>HomeServe is a marketplace that connects customers ("Users") with independent home service professionals ("Providers"). We facilitate the booking process but are <strong>not</strong> a party to the service contract between Users and Providers. HomeServe does not directly provide home services.</p>

    <h2>3. User Accounts</h2>
    <ul>
      <li>You must provide accurate and up-to-date registration information.</li>
      <li>You are responsible for maintaining the security of your account credentials.</li>
      <li>You must notify us immediately of any unauthorized use of your account.</li>
      <li>One person may only maintain one account. Multiple accounts for the same individual may be terminated.</li>
    </ul>

    <h2>4. Bookings and Cancellations</h2>
    <ul>
      <li>Bookings are confirmed once a Provider accepts your request.</li>
      <li>Users may cancel bookings at any time before service commencement through the platform.</li>
      <li>Providers must respond to booking requests in a timely manner. Repeated non-response may result in account suspension.</li>
      <li>HomeServe reserves the right to cancel any booking at its discretion.</li>
    </ul>

    <h2>5. Payments</h2>
    <ul>
      <li>Payments are made directly to Providers via UPI or other accepted methods upon service completion.</li>
      <li>HomeServe is not responsible for payment disputes between Users and Providers.</li>
      <li>All prices displayed are indicative. Final charges are confirmed in the invoice generated post-service.</li>
    </ul>

    <h2>6. Provider Verification</h2>
    <p>HomeServe performs verification checks on Providers before listing them on the platform. However, we do not guarantee the accuracy or completeness of Provider information. Users should exercise their own judgment when selecting a Provider.</p>

    <h2>7. Prohibited Conduct</h2>
    <p>You agree not to:</p>
    <ul>
      <li>Post false, misleading, or fraudulent content or reviews.</li>
      <li>Use the platform for any unlawful purpose.</li>
      <li>Attempt to circumvent the platform by contacting Providers directly to avoid platform fees.</li>
      <li>Harass, threaten, or abuse other users or Providers.</li>
      <li>Use automated tools to scrape, access, or interfere with the platform.</li>
    </ul>

    <h2>8. Limitation of Liability</h2>
    <p>HomeServe is provided "as is" without warranties of any kind. To the maximum extent permitted by applicable law, HomeServe shall not be liable for any indirect, incidental, special, or consequential damages arising out of your use (or inability to use) the platform.</p>

    <h2>9. Reviews and Ratings</h2>
    <p>Reviews must be honest, based on genuine experiences, and free from offensive content. HomeServe reserves the right to remove reviews that violate these guidelines.</p>

    <h2>10. Termination</h2>
    <p>We may suspend or terminate your account at any time, with or without notice, for conduct that we believe violates these Terms or is harmful to other users, Providers, HomeServe, or third parties.</p>

    <h2>11. Changes to Terms</h2>
    <p>We may update these Terms from time to time. We'll notify registered users of significant changes by email. Continued use of the platform after changes constitutes acceptance of the new Terms.</p>

    <h2>12. Governing Law</h2>
    <p>These Terms are governed by the laws of India. Any disputes arising shall be subject to the exclusive jurisdiction of the courts of India.</p>

    <h2>13. Contact</h2>
    <p>For questions about these Terms, contact us at <a href="mailto:legal@homeserve.in">legal@homeserve.in</a> or visit our <a href="<?= APP_URL ?>/contact.php">Contact page</a>.</p>
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
