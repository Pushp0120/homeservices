<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) {
    $role = currentRole();
    if ($role === 'admin')    redirect(APP_URL . '/modules/admin/dashboard.php');
    if ($role === 'provider') redirect(APP_URL . '/modules/provider/dashboard.php');
    redirect(APP_URL . '/modules/user/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us | HomeServe — Professional Home Services</title>
<meta name="description" content="Learn about HomeServe — our mission, values, and the team building India's most trusted home services platform.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="<?= APP_URL ?>/assets/css/main.css" rel="stylesheet">
<style>
:root{--navy:#080e1d;--navy2:#0d1627;--navy3:#111e35;--blue:#2563eb;--bluel:#3b82f6;--violet:#7c3aed;--cyan:#06b6d4;--accent:#60a5fa;--green:#10b981;--fh:'Sora',sans-serif;--fb:'DM Sans',sans-serif;}
*,*::before,*::after{box-sizing:border-box;}
body{font-family:var(--fb);background:var(--navy);color:#e2e8f0;overflow-x:hidden;}
::-webkit-scrollbar{width:5px;}::-webkit-scrollbar-track{background:var(--navy);}::-webkit-scrollbar-thumb{background:rgba(37,99,235,.35);border-radius:3px;}

/* NAV */
.site-nav{position:fixed;top:0;left:0;right:0;z-index:1000;padding:1.1rem 0;transition:all .3s ease;}
.site-nav.scrolled{background:rgba(8,14,29,.93);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,.07);padding:.65rem 0;}
.nav-brand{font-family:var(--fh);font-size:1.3rem;font-weight:800;color:#fff;text-decoration:none;display:flex;align-items:center;gap:.5rem;}
.nav-brand .bico{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--blue),var(--violet));display:flex;align-items:center;justify-content:center;font-size:.9rem;}
.nav-brand .acc{color:var(--accent);}
.nbtn-l{color:rgba(255,255,255,.7);font-weight:600;font-size:.86rem;text-decoration:none;padding:.44rem 1rem;border-radius:8px;border:1px solid rgba(255,255,255,.14);transition:all .2s;font-family:var(--fh);}
.nbtn-l:hover{color:#fff;background:rgba(255,255,255,.08);}
.nbtn-c{background:var(--blue);color:#fff;font-weight:700;font-size:.86rem;padding:.44rem 1.2rem;border-radius:8px;text-decoration:none;font-family:var(--fh);transition:all .2s;}
.nbtn-c:hover{background:var(--bluel);color:#fff;transform:translateY(-1px);}

/* PAGE HEADER */
.about-hero{padding:9rem 0 5rem;text-align:center;background:radial-gradient(ellipse 70% 60% at 50% 0%,rgba(37,99,235,.18) 0%,transparent 60%),var(--navy);}
.eyebrow{font-family:var(--fh);font-size:.71rem;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:var(--accent);margin-bottom:.55rem;}
.pg-title{font-family:var(--fh);font-size:clamp(2rem,5vw,3.2rem);font-weight:800;color:#fff;letter-spacing:-1px;line-height:1.1;}
.pg-sub{color:rgba(255,255,255,.5);font-size:1rem;max-width:520px;margin:1rem auto 0;line-height:1.75;}

/* SECTIONS */
.sec{padding:5rem 0;}
.sec-alt{background:var(--navy2);}
.card-dark{background:var(--navy3);border:1px solid rgba(255,255,255,.07);border-radius:18px;padding:2rem;transition:all .25s;}
.card-dark:hover{transform:translateY(-4px);border-color:rgba(37,99,235,.25);box-shadow:0 14px 36px rgba(0,0,0,.25);}
.val-icon{width:54px;height:54px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:1.1rem;}
.val-ttl{font-family:var(--fh);font-weight:700;color:#fff;font-size:1rem;margin-bottom:.4rem;}
.val-dsc{font-size:.83rem;color:rgba(255,255,255,.48);line-height:1.7;}

/* TEAM */
.team-card{background:var(--navy3);border:1px solid rgba(255,255,255,.07);border-radius:18px;padding:1.75rem 1.25rem;text-align:center;transition:all .25s;}
.team-card:hover{transform:translateY(-4px);border-color:rgba(37,99,235,.2);}
.team-av{width:72px;height:72px;border-radius:50%;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;font-family:var(--fh);font-weight:800;font-size:1.6rem;color:#fff;}
.team-nm{font-family:var(--fh);font-weight:700;color:#fff;font-size:.95rem;}
.team-role{font-size:.78rem;color:var(--accent);margin-top:.2rem;}

/* STATS */
.stat-band{background:rgba(37,99,235,.08);border-top:1px solid rgba(255,255,255,.055);border-bottom:1px solid rgba(255,255,255,.055);padding:3rem 0;}
.sbn{font-family:var(--fh);font-size:2.4rem;font-weight:800;background:linear-gradient(135deg,#fff 40%,var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1;}
.sbl{font-size:.8rem;color:rgba(255,255,255,.48);margin-top:.3rem;}

/* FOOTER */
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

<section class="about-hero">
  <div class="container">
    <div class="eyebrow">Our Story</div>
    <h1 class="pg-title">Built to bring trust<br>to home services.</h1>
    <p class="pg-sub">HomeServe was founded with one belief — finding a reliable home service professional should be simple, safe, and stress-free.</p>
  </div>
</section>

<!-- Mission -->
<section class="sec">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5">
        <div class="eyebrow">Our Mission</div>
        <h2 style="font-family:var(--fh);font-size:clamp(1.8rem,4vw,2.5rem);font-weight:800;color:#fff;letter-spacing:-.8px;margin-bottom:1rem;">Connecting homes<br>with trusted hands.</h2>
        <p style="color:rgba(255,255,255,.5);line-height:1.8;font-size:.95rem;">We're on a mission to make home services as easy as ordering a cab. Every provider on our platform is verified, skilled, and rated by real customers so you always know who's coming to your door.</p>
        <p style="color:rgba(255,255,255,.5);line-height:1.8;font-size:.95rem;">We also believe skilled tradespeople deserve better — better earnings, better tools, and the freedom to run their own business on their terms.</p>
      </div>
      <div class="col-lg-7">
        <div class="row g-3">
          <?php foreach([
            ['bi-shield-check-fill','#2563eb','Trust First','Every provider undergoes background verification before being listed on our platform.'],
            ['bi-star-fill','#f59e0b','Verified Reviews','All ratings come from genuine completed bookings — every star is earned honestly.'],
            ['bi-lightning-charge-fill','#7c3aed','Speed & Simplicity','Book a service in under 3 minutes. No calls, no haggling, no uncertainty.'],
            ['bi-heart-fill','#f43f5e','Built for India','Designed for Indian homes, Indian budgets, and Indian working professionals.'],
          ] as [$ico,$col,$ttl,$dsc]): ?>
          <div class="col-sm-6">
            <div class="card-dark">
              <div class="val-icon" style="background:<?= $col ?>14;border:1px solid <?= $col ?>28"><i class="bi <?= $ico ?>" style="color:<?= $col ?>"></i></div>
              <div class="val-ttl"><?= $ttl ?></div>
              <div class="val-dsc"><?= $dsc ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Stats -->
<div class="stat-band">
  <div class="container">
    <div class="row g-4 text-center">
      <?php foreach([['2023','Founded'],['8+','Service Categories'],['100%','Verified Providers'],['₹50L+','Platform Earnings']] as [$v,$l]): ?>
      <div class="col-6 col-md-3">
        <div class="sbn"><?= $v ?></div>
        <div class="sbl"><?= $l ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Values -->
<section class="sec sec-alt">
  <div class="container">
    <div class="text-center mb-5">
      <div class="eyebrow">What We Stand For</div>
      <h2 style="font-family:var(--fh);font-size:clamp(1.8rem,4vw,2.4rem);font-weight:800;color:#fff;letter-spacing:-.8px;">Our Core Values</h2>
    </div>
    <div class="row g-3">
      <?php foreach([
        ['bi-hand-thumbs-up-fill','#2563eb','Integrity','We do right by our customers and providers — even when no one is watching.'],
        ['bi-arrow-repeat','#059669','Reliability','If we say it, we mean it. Verified providers, on-time arrivals, guaranteed service.'],
        ['bi-people-fill','#7c3aed','Community','We believe in building livelihoods, not just filling bookings.'],
        ['bi-graph-up-arrow','#f59e0b','Growth','We help providers grow their business and customers get better service over time.'],
        ['bi-lock-fill','#06b6d4','Security','Safe payments, secure data, and verified identities for all parties.'],
        ['bi-chat-heart-fill','#f43f5e','Empathy','Every interaction is a human one. We put people first.'],
      ] as [$ico,$col,$ttl,$dsc]): ?>
      <div class="col-sm-6 col-lg-4">
        <div class="card-dark d-flex gap-3 align-items-start" style="padding:1.4rem">
          <div class="val-icon flex-shrink-0" style="background:<?= $col ?>14;border:1px solid <?= $col ?>28;margin-bottom:0;width:44px;height:44px;min-width:44px;border-radius:12px;font-size:1.1rem"><i class="bi <?= $ico ?>" style="color:<?= $col ?>"></i></div>
          <div><div class="val-ttl"><?= $ttl ?></div><div class="val-dsc"><?= $dsc ?></div></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section style="padding:5rem 0;text-align:center;background:radial-gradient(ellipse 55% 65% at 50% 50%,rgba(37,99,235,.09) 0%,transparent 65%),var(--navy);">
  <div class="container">
    <div style="max-width:560px;margin:0 auto">
      <div class="eyebrow">Join HomeServe</div>
      <h2 style="font-family:var(--fh);font-size:clamp(1.8rem,4vw,2.6rem);font-weight:800;color:#fff;letter-spacing:-.8px;margin-bottom:.8rem;">Ready to get started?</h2>
      <p style="color:rgba(255,255,255,.5);margin-bottom:2rem;">Book your first service in minutes — or join as a provider and start earning today.</p>
      <div class="d-flex gap-3 justify-content-center flex-wrap">
        <a href="<?= APP_URL ?>/register.php" style="display:inline-flex;align-items:center;gap:.5rem;background:#fff;color:#0f172a;font-family:var(--fh);font-weight:800;font-size:.95rem;padding:.85rem 1.9rem;border-radius:10px;text-decoration:none;box-shadow:0 8px 26px rgba(0,0,0,.28);">
          <i class="bi bi-house-heart-fill" style="color:#2563eb"></i> Book a Service
        </a>
        <a href="<?= APP_URL ?>/register.php?type=provider" style="display:inline-flex;align-items:center;gap:.5rem;color:rgba(255,255,255,.75);font-family:var(--fh);font-weight:600;font-size:.95rem;padding:.85rem 1.9rem;border-radius:10px;text-decoration:none;border:1px solid rgba(255,255,255,.2);">
          <i class="bi bi-briefcase"></i> Become a Provider
        </a>
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
    &copy; <?= date('Y') ?> HomeServe. Made with &hearts; in India.
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const nav=document.getElementById('siteNav');
window.addEventListener('scroll',()=>nav.classList.toggle('scrolled',window.scrollY>40),{passive:true});
</script>
</body>
</html>
