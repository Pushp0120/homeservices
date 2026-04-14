<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) {
    $role = currentRole();
    if ($role === 'admin')    redirect(APP_URL . '/modules/admin/dashboard.php');
    if ($role === 'provider') redirect(APP_URL . '/modules/provider/dashboard.php');
    redirect(APP_URL . '/modules/user/dashboard.php');
}

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name    = sanitize($_POST['name'] ?? '');
    $email   = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    if (!$name || !$email || !$subject || !$message) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Log to DB or send email — for now just show success
        try {
            $db = getDB();
            $db->prepare("INSERT INTO support_tickets (user_id, name, email, subject, message, created_at) VALUES (NULL, ?, ?, ?, ?, NOW())")
               ->execute([$name, $email, $subject, $message]);
        } catch(Exception $e) { /* table may not exist yet */ }
        $success = "Thank you, <strong>$name</strong>! We've received your message and will respond within 24 hours.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us | HomeServe</title>
<meta name="description" content="Get in touch with the HomeServe team. We're here to help with any questions about bookings, providers, or our platform.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="<?= APP_URL ?>/assets/css/main.css" rel="stylesheet">
<style>
:root{--navy:#080e1d;--navy2:#0d1627;--navy3:#111e35;--blue:#2563eb;--bluel:#3b82f6;--violet:#7c3aed;--cyan:#06b6d4;--accent:#60a5fa;--green:#10b981;--fh:'Sora',sans-serif;--fb:'DM Sans',sans-serif;}
*,*::before,*::after{box-sizing:border-box;}
body{font-family:var(--fb);background:var(--navy);color:#e2e8f0;overflow-x:hidden;}
::-webkit-scrollbar{width:5px;}::-webkit-scrollbar-track{background:var(--navy);}::-webkit-scrollbar-thumb{background:rgba(37,99,235,.35);border-radius:3px;}
.site-nav{position:fixed;top:0;left:0;right:0;z-index:1000;padding:1.1rem 0;transition:all .3s ease;}
.site-nav.scrolled{background:rgba(8,14,29,.93);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,.07);padding:.65rem 0;}
.nav-brand{font-family:var(--fh);font-size:1.3rem;font-weight:800;color:#fff;text-decoration:none;display:flex;align-items:center;gap:.5rem;}
.nav-brand .bico{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--blue),var(--violet));display:flex;align-items:center;justify-content:center;font-size:.9rem;}
.nav-brand .acc{color:var(--accent);}
.nbtn-l{color:rgba(255,255,255,.7);font-weight:600;font-size:.86rem;text-decoration:none;padding:.44rem 1rem;border-radius:8px;border:1px solid rgba(255,255,255,.14);transition:all .2s;font-family:var(--fh);}
.nbtn-l:hover{color:#fff;background:rgba(255,255,255,.08);}
.nbtn-c{background:var(--blue);color:#fff;font-weight:700;font-size:.86rem;padding:.44rem 1.2rem;border-radius:8px;text-decoration:none;font-family:var(--fh);transition:all .2s;}
.nbtn-c:hover{background:var(--bluel);color:#fff;}
.pg-hero{padding:9rem 0 5rem;background:radial-gradient(ellipse 70% 60% at 50% 0%,rgba(37,99,235,.18) 0%,transparent 60%),var(--navy);text-align:center;}
.eyebrow{font-family:var(--fh);font-size:.71rem;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:var(--accent);margin-bottom:.55rem;}
.pg-title{font-family:var(--fh);font-size:clamp(2rem,5vw,3rem);font-weight:800;color:#fff;letter-spacing:-1px;}
.pg-sub{color:rgba(255,255,255,.5);font-size:.97rem;max-width:480px;margin:.75rem auto 0;line-height:1.7;}
.contact-card{background:var(--navy3);border:1px solid rgba(255,255,255,.07);border-radius:18px;padding:1.5rem;display:flex;gap:1rem;align-items:flex-start;transition:all .25s;}
.contact-card:hover{border-color:rgba(37,99,235,.25);transform:translateY(-2px);}
.ci{width:46px;height:46px;border-radius:12px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.1rem;}
.ct{font-family:var(--fh);font-weight:700;color:#fff;font-size:.9rem;margin-bottom:.2rem;}
.cd{font-size:.82rem;color:rgba(255,255,255,.48);line-height:1.65;}
.form-box{background:var(--navy3);border:1px solid rgba(255,255,255,.07);border-radius:22px;padding:2.5rem;}
.form-box label{color:rgba(255,255,255,.7);font-size:.84rem;font-weight:600;margin-bottom:.4rem;display:block;}
.form-box .form-control,.form-box .form-select{background:rgba(255,255,255,.05);border:1.5px solid rgba(255,255,255,.1);color:#fff;border-radius:10px;padding:.65rem 1rem;font-size:.875rem;transition:all .2s;}
.form-box .form-control::placeholder{color:rgba(255,255,255,.3);}
.form-box .form-control:focus,.form-box .form-select:focus{background:rgba(255,255,255,.08);border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.15);color:#fff;outline:none;}
.form-box .form-select option{background:var(--navy2);color:#fff;}
.btn-send{background:linear-gradient(135deg,var(--blue),var(--violet));color:#fff;font-family:var(--fh);font-weight:700;border:none;border-radius:10px;padding:.75rem 2rem;font-size:.93rem;transition:all .25s;cursor:pointer;}
.btn-send:hover{opacity:.9;transform:translateY(-2px);box-shadow:0 8px 24px rgba(37,99,235,.35);}
.mini-footer{background:var(--navy2);border-top:1px solid rgba(255,255,255,.055);padding:2rem 0;font-size:.8rem;color:rgba(255,255,255,.3);text-align:center;}
.mini-footer a{color:rgba(255,255,255,.45);text-decoration:none;margin:0 .75rem;}
.mini-footer a:hover{color:#fff;}
</style>
</head>
<body>

<nav class="site-nav" id="siteNav">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="<?= APP_URL ?>" class="nav-brand">
      <div class="bico"><i class="bi bi-house-heart-fill text-white" style="font-size:.85rem"></i></div>
      Home<span class="acc">Serve</span>
    </a>
    <div class="d-flex align-items-center gap-2">
      <a href="<?= APP_URL ?>/login.php" class="nbtn-l d-none d-sm-inline-flex">Login</a>
      <a href="<?= APP_URL ?>/register.php" class="nbtn-c">Get Started</a>
    </div>
  </div>
</nav>

<div class="pg-hero">
  <div class="container">
    <div class="eyebrow">Get in Touch</div>
    <h1 class="pg-title">We're here to help.</h1>
    <p class="pg-sub">Have a question, feedback, or need help with a booking? Reach out — we typically respond within a few hours.</p>
  </div>
</div>

<section style="padding:4rem 0 6rem;background:var(--navy)">
  <div class="container">
    <div class="row g-5">

      <!-- Contact Info -->
      <div class="col-lg-4">
        <h3 style="font-family:var(--fh);font-weight:700;color:#fff;font-size:1.2rem;margin-bottom:1.5rem">Contact Information</h3>
        <div class="d-flex flex-column gap-3">
          <?php foreach([
            ['bi-envelope-fill','#2563eb','Email Us','support@homeserve.in','We reply within 24 hours.'],
            ['bi-telephone-fill','#059669','Call Us','+91 98765 43210','Mon–Sat, 8 AM – 8 PM IST'],
            ['bi-geo-alt-fill','#7c3aed','Our Location','India','Serving pan-India'],
            ['bi-clock-fill','#f59e0b','Support Hours','Mon–Sat, 8 AM – 8 PM','Closed on Sundays & national holidays'],
          ] as [$ico,$col,$ttl,$val,$sub]): ?>
          <div class="contact-card">
            <div class="ci" style="background:<?= $col ?>14;border:1px solid <?= $col ?>28"><i class="bi <?= $ico ?>" style="color:<?= $col ?>"></i></div>
            <div>
              <div class="ct"><?= $ttl ?></div>
              <div class="cd"><strong style="color:rgba(255,255,255,.7)"><?= $val ?></strong><br><?= $sub ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Contact Form -->
      <div class="col-lg-8">
        <div class="form-box">
          <h3 style="font-family:var(--fh);font-weight:700;color:#fff;font-size:1.25rem;margin-bottom:1.5rem">Send Us a Message</h3>

          <?php if ($success): ?>
          <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);border-radius:12px;padding:1rem 1.25rem;color:#6ee7b7;margin-bottom:1.5rem;font-size:.88rem">
            <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
          </div>
          <?php endif; ?>

          <?php if ($error): ?>
          <div style="background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.25);border-radius:12px;padding:1rem 1.25rem;color:#fca5a5;margin-bottom:1.5rem;font-size:.88rem">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
          </div>
          <?php endif; ?>

          <form method="POST">
            <?= csrfField() ?>
            <div class="row g-3 mb-3">
              <div class="col-sm-6">
                <label for="contact_name">Your Name</label>
                <input type="text" class="form-control" id="contact_name" name="name" placeholder="Rahul Sharma" value="<?= sanitize($_POST['name'] ?? '') ?>" required>
              </div>
              <div class="col-sm-6">
                <label for="contact_email">Email Address</label>
                <input type="email" class="form-control" id="contact_email" name="email" placeholder="you@example.com" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
              </div>
            </div>
            <div class="mb-3">
              <label for="contact_subject">Subject</label>
              <select class="form-select" id="contact_subject" name="subject" required>
                <option value="" disabled <?= empty($_POST['subject'])?'selected':'' ?>>Select a topic</option>
                <option value="booking_issue" <?= ($_POST['subject']??'')==='booking_issue'?'selected':'' ?>>Booking Issue</option>
                <option value="payment_query" <?= ($_POST['subject']??'')==='payment_query'?'selected':'' ?>>Payment Query</option>
                <option value="provider_complaint" <?= ($_POST['subject']??'')==='provider_complaint'?'selected':'' ?>>Provider Complaint</option>
                <option value="become_provider" <?= ($_POST['subject']??'')==='become_provider'?'selected':'' ?>>Become a Provider</option>
                <option value="feedback" <?= ($_POST['subject']??'')==='feedback'?'selected':'' ?>>General Feedback</option>
                <option value="other" <?= ($_POST['subject']??'')==='other'?'selected':'' ?>>Other</option>
              </select>
            </div>
            <div class="mb-4">
              <label for="contact_message">Your Message</label>
              <textarea class="form-control" id="contact_message" name="message" rows="5" placeholder="Tell us how we can help..." required><?= sanitize($_POST['message'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn-send w-100">
              <i class="bi bi-send-fill me-2"></i>Send Message
            </button>
          </form>
        </div>
      </div>

    </div>
  </div>
</section>

<footer class="mini-footer">
  <div class="container">
    <div class="mb-2">
      <a href="<?= APP_URL ?>">Home</a>
      <a href="<?= APP_URL ?>/about.php">About</a>
      <a href="<?= APP_URL ?>/contact.php">Contact</a>
      <a href="<?= APP_URL ?>/faq.php">FAQ</a>
      <a href="<?= APP_URL ?>/terms.php">Terms</a>
      <a href="<?= APP_URL ?>/privacy.php">Privacy</a>
    </div>
    &copy; <?= date('Y') ?> HomeServe. All rights reserved.
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const nav=document.getElementById('siteNav');
window.addEventListener('scroll',()=>nav.classList.toggle('scrolled',window.scrollY>40),{passive:true});
</script>
</body>
</html>
