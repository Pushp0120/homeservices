<?php require_once __DIR__ . '/includes/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FAQ | HomeServe</title>
<meta name="description" content="Frequently asked questions about HomeServe — bookings, payments, providers, and more.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
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
.pg-hero{padding:9rem 0 5rem;background:radial-gradient(ellipse 70% 60% at 50% 0%,rgba(37,99,235,.18) 0%,transparent 60%),var(--navy);text-align:center;}
.eyebrow{font-family:var(--fh);font-size:.71rem;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:var(--accent);margin-bottom:.55rem;}
.tab-pills{display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap;margin-bottom:3rem;}
.tab-pill{padding:.5rem 1.4rem;border-radius:50px;font-family:var(--fh);font-size:.83rem;font-weight:700;cursor:pointer;background:rgba(255,255,255,.06);color:rgba(255,255,255,.55);border:1.5px solid rgba(255,255,255,.1);transition:all .2s;}
.tab-pill:hover{background:rgba(255,255,255,.1);color:#fff;}
.tab-pill.active{background:var(--blue);color:#fff;border-color:var(--blue);}
.faq-section{display:none;}.faq-section.active{display:block;}
.faq-item{background:var(--navy3);border:1px solid rgba(255,255,255,.07);border-radius:14px;margin-bottom:.75rem;overflow:hidden;transition:border-color .2s;}
.faq-item.open{border-color:rgba(37,99,235,.28);}
.faq-q{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.2rem 1.4rem;cursor:pointer;font-family:var(--fh);font-weight:600;color:#fff;font-size:.92rem;}
.faq-q:hover{background:rgba(255,255,255,.03);}
.faq-q .faq-icon{width:28px;height:28px;border-radius:8px;background:rgba(37,99,235,.14);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--accent);font-size:.85rem;transition:transform .3s;}
.faq-item.open .faq-icon{transform:rotate(45deg);background:rgba(37,99,235,.25);}
.faq-a{max-height:0;overflow:hidden;transition:max-height .35s ease,padding .25s;}
.faq-item.open .faq-a{max-height:400px;padding:0 1.4rem 1.2rem;}
.faq-a p{font-size:.85rem;color:rgba(255,255,255,.52);line-height:1.8;margin:0;}
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
    <div class="eyebrow">Help Center</div>
    <h1 style="font-family:var(--fh);font-size:clamp(2rem,5vw,3rem);font-weight:800;color:#fff;letter-spacing:-1px;">Frequently Asked Questions</h1>
    <p style="color:rgba(255,255,255,.5);max-width:480px;margin:.75rem auto 0;line-height:1.7;font-size:.97rem;">Find answers to common questions about HomeServe. Can't find what you're looking for? <a href="<?= APP_URL ?>/contact.php" style="color:var(--accent)">Contact us</a>.</p>
  </div>
</div>

<section style="padding:3rem 0 6rem">
  <div class="container" style="max-width:780px">

    <div class="tab-pills">
      <button class="tab-pill active" onclick="switchTab('customers',this)"><i class="bi bi-person me-1"></i>For Customers</button>
      <button class="tab-pill" onclick="switchTab('providers',this)"><i class="bi bi-briefcase me-1"></i>For Providers</button>
      <button class="tab-pill" onclick="switchTab('payments',this)"><i class="bi bi-credit-card me-1"></i>Payments</button>
      <button class="tab-pill" onclick="switchTab('general',this)"><i class="bi bi-info-circle me-1"></i>General</button>
    </div>

    <!-- Customers -->
    <div class="faq-section active" id="tab-customers">
      <?php foreach([
        ['How do I book a service?','Register or log in, browse available service providers in your category, choose a provider that suits your needs, then select a date & time and confirm your booking. You will receive a confirmation instantly.'],
        ['Can I choose my preferred provider?','Yes! Browse all verified providers in your area, filter by category, price, or rating, and book directly with the professional of your choice.'],
        ['How do I cancel a booking?','Open your booking from "My Bookings" and click the Cancel button. Cancellations are free up to 2 hours before the scheduled time.'],
        ['What if the provider doesn\'t show up?','If your provider is a no-show, please contact our support team immediately. We will rebook you with another provider or issue a full refund.'],
        ['Can I rate a service after completion?','Yes - once a booking is marked complete, you will see a "Rate" button in My Bookings. Your honest review helps future customers make better decisions.'],
        ['Is my personal information safe?','Absolutely. We never share your phone number or address with anyone outside the platform. All data is encrypted and stored securely.'],
      ] as [$q,$a]): ?>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">
          <span><?= $q ?></span>
          <div class="faq-icon"><i class="bi bi-plus"></i></div>
        </div>
        <div class="faq-a"><p><?= $a ?></p></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Providers -->
    <div class="faq-section" id="tab-providers">
      <?php foreach([
        ['How do I join as a provider?','Click "Get Started" → "Register as Provider". Fill in your profile, service details, experience, and pricing. An admin will review and approve your application within 48 hours.'],
        ['Is there any joining fee?','No — joining HomeServe as a provider is completely free. We never charge upfront fees.'],
        ['How do I get paid?','After a booking is completed and invoiced, payment is collected via UPI/QR code from the customer. You receive your full payment directly.'],
        ['Can I set my own prices?','Yes. You set your base price when creating your profile and can update it any time from your Profile settings.'],
        ['What happens if I need to cancel a booking?','We understand emergencies happen. You can reject a pending booking or contact support for an accepted booking. Frequent cancellations may affect your rating.'],
        ['How do I improve my visibility on the platform?','Maintain a high rating, complete jobs on time, keep your profile updated with photos and a detailed bio, and respond quickly to booking requests.'],
      ] as [$q,$a]): ?>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">
          <span><?= $q ?></span><div class="faq-icon"><i class="bi bi-plus"></i></div>
        </div>
        <div class="faq-a"><p><?= $a ?></p></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Payments -->
    <div class="faq-section" id="tab-payments">
      <?php foreach([
        ['What payment methods are accepted?','We support UPI payments (GPay, PhonePe, Paytm, BHIM). Customers can scan a QR code provided by the provider at the time of service.'],
        ['Is online payment mandatory?','Payment is made after service completion. The provider generates an invoice and shares a UPI QR code for you to scan.'],
        ['What if I\'m overcharged?','Review your invoice — it shows a full breakdown of the charges. If you believe you were overcharged, contact support with your booking ID within 48 hours.'],
        ['Are there any hidden charges?','No. The price you see on the booking is the base price. Any additional charges (materials etc.) must be disclosed by the provider before work begins.'],
        ['How do I get an invoice?','After service completion and payment, your invoice is automatically generated. You can find it under "Invoices" in your dashboard and download or print it.'],
      ] as [$q,$a]): ?>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">
          <span><?= $q ?></span><div class="faq-icon"><i class="bi bi-plus"></i></div>
        </div>
        <div class="faq-a"><p><?= $a ?></p></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- General -->
    <div class="faq-section" id="tab-general">
      <?php foreach([
        ['What is HomeServe?','HomeServe is an online platform that connects homeowners with trusted, verified home service professionals — plumbers, electricians, cleaners, and more.'],
        ['Which cities does HomeServe operate in?','We\'re currently growing across India. Check the Browse page to see if providers are available in your area.'],
        ['How do you verify providers?','Every provider goes through an identity check, skill assessment review, and reference verification before being approved on the platform.'],
        ['How can I contact support?','Visit our Contact Us page, or email support@homeserve.in. We respond within 24 hours, Mon–Sat 8AM–8PM IST.'],
        ['What if I have a complaint?','Submit a complaint through our Support section in your dashboard, or email us directly. All complaints are reviewed within 24 hours.'],
      ] as [$q,$a]): ?>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">
          <span><?= $q ?></span><div class="faq-icon"><i class="bi bi-plus"></i></div>
        </div>
        <div class="faq-a"><p><?= $a ?></p></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center;margin-top:3rem;padding:2rem;background:rgba(37,99,235,.07);border:1px solid rgba(37,99,235,.15);border-radius:16px;">
      <div style="font-family:var(--fh);font-weight:700;color:#fff;margin-bottom:.4rem">Still have questions?</div>
      <div style="font-size:.85rem;color:rgba(255,255,255,.48);margin-bottom:1rem">Our support team is just a message away.</div>
      <a href="<?= APP_URL ?>/contact.php" style="display:inline-flex;align-items:center;gap:.5rem;background:var(--blue);color:#fff;font-family:var(--fh);font-weight:700;font-size:.88rem;padding:.6rem 1.4rem;border-radius:9px;text-decoration:none;transition:all .2s">
        <i class="bi bi-chat-dots-fill"></i> Contact Support
      </a>
    </div>
  </div>
</section>

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
<script>
const nav=document.getElementById('siteNav');
window.addEventListener('scroll',()=>nav.classList.toggle('scrolled',window.scrollY>40),{passive:true});
function toggleFaq(el){
  const item=el.closest('.faq-item');
  item.classList.toggle('open');
}
function switchTab(id,btn){
  document.querySelectorAll('.faq-section').forEach(s=>s.classList.remove('active'));
  document.querySelectorAll('.tab-pill').forEach(b=>b.classList.remove('active'));
  document.getElementById('tab-'+id).classList.add('active');
  btn.classList.add('active');
}
</script>
</body>
</html>
