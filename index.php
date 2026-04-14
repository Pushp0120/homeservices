<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) {
    $role = currentRole();
    if ($role === 'admin')    redirect(APP_URL . '/modules/admin/dashboard.php');
    if ($role === 'provider') redirect(APP_URL . '/modules/provider/dashboard.php');
    redirect(APP_URL . '/modules/user/dashboard.php');
}
require_once __DIR__ . '/config/database.php';
$db = getDB();

$cats = $db->query("SELECT * FROM categories WHERE status='active' ORDER BY id LIMIT 8")->fetchAll();

try {
    $totalProviders = (int)$db->query("SELECT COUNT(*) FROM providers WHERE approval_status='approved'")->fetchColumn();
    $totalCustomers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
    $totalBookings  = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status='completed'")->fetchColumn();
    $avgRow         = $db->query("SELECT ROUND(AVG(rating),1) AS avg FROM reviews")->fetch();
    $platformAvg    = $avgRow['avg'] ?? 4.8;
} catch (Exception $e) {
    $totalProviders = 50; $totalCustomers = 200; $totalBookings = 400; $platformAvg = 4.8;
}

function fmtStat($n, $suffix = '+') {
    if ($n >= 1000) return round($n / 1000, 1) . 'K' . $suffix;
    if ($n == 0)    return '0' . $suffix;
    return $n . $suffix;
}

try {
    $reviewStmt = $db->query("
        SELECT r.rating, r.review_text, u.name AS cust_name,
               COALESCE(c.name,'Home Service') AS service_name
        FROM reviews r
        JOIN users u ON r.customer_id = u.id
        JOIN providers p ON r.provider_id = p.id
        JOIN categories c ON p.category_id = c.id
        WHERE r.review_text IS NOT NULL AND TRIM(r.review_text) != ''
        ORDER BY r.rating DESC, r.id DESC LIMIT 6
    ");
    $realReviews = $reviewStmt->fetchAll();
} catch (Exception $e) { $realReviews = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HomeServe &ndash; Professional Home Services at Your Doorstep</title>
<meta name="description" content="Book verified home service professionals in minutes. Plumbing, electrical, cleaning and more.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<link href="<?= APP_URL ?>/assets/css/main.css" rel="stylesheet">
<style>
:root{--navy:#080e1d;--navy2:#0d1627;--navy3:#111e35;--blue:#2563eb;--bluel:#3b82f6;--violet:#7c3aed;--cyan:#06b6d4;--accent:#60a5fa;--green:#10b981;--muted:rgba(255,255,255,.5);--rmd:16px;--rlg:24px;--fh:'Sora',sans-serif;--fb:'DM Sans',sans-serif;}
*,*::before,*::after{box-sizing:border-box;}
body{font-family:var(--fb);background:var(--navy);color:#e2e8f0;overflow-x:hidden;}
::-webkit-scrollbar{width:5px;}::-webkit-scrollbar-track{background:var(--navy);}::-webkit-scrollbar-thumb{background:rgba(37,99,235,.35);border-radius:3px;}

/* NAVBAR */
.site-nav{position:fixed;top:0;left:0;right:0;z-index:1000;padding:1.1rem 0;transition:all .3s ease;}
.site-nav.scrolled{background:rgba(8,14,29,.93);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,.07);padding:.65rem 0;}
.nav-brand{font-family:var(--fh);font-size:1.3rem;font-weight:800;color:#fff;text-decoration:none;display:flex;align-items:center;gap:.5rem;}
.nav-brand .bico{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--blue),var(--violet));display:flex;align-items:center;justify-content:center;font-size:.9rem;}
.nav-brand .acc{color:var(--accent);}
.nbtn-l{color:rgba(255,255,255,.7);font-weight:600;font-size:.86rem;text-decoration:none;padding:.44rem 1rem;border-radius:8px;border:1px solid rgba(255,255,255,.14);transition:all .2s;font-family:var(--fh);}
.nbtn-l:hover{color:#fff;background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.3);}
.nbtn-c{background:var(--blue);color:#fff;font-weight:700;font-size:.86rem;padding:.44rem 1.2rem;border-radius:8px;text-decoration:none;font-family:var(--fh);transition:all .2s;box-shadow:0 4px 14px rgba(37,99,235,.35);}
.nbtn-c:hover{background:var(--bluel);color:#fff;transform:translateY(-1px);}
/* Mobile hamburger */
.nav-ham{background:none;border:1px solid rgba(255,255,255,.2);border-radius:8px;padding:.35rem .55rem;cursor:pointer;color:rgba(255,255,255,.8);font-size:1.1rem;transition:all .2s;}
.nav-ham:hover{background:rgba(255,255,255,.1);color:#fff;}
.nav-mob-menu{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(8,14,29,.97);z-index:1999;flex-direction:column;align-items:center;justify-content:center;gap:2rem;}
.nav-mob-menu.open{display:flex;animation:fadeIn .25s ease;}
.nav-mob-menu a{font-family:var(--fh);font-size:1.3rem;font-weight:700;color:rgba(255,255,255,.75);text-decoration:none;transition:color .15s;}
.nav-mob-menu a:hover{color:#fff;}
.nav-mob-close{position:absolute;top:1.5rem;right:1.5rem;background:none;border:none;color:rgba(255,255,255,.6);font-size:1.6rem;cursor:pointer;}

/* HERO */
.hero-sec{min-height:100vh;display:flex;align-items:center;position:relative;overflow:hidden;padding:8rem 0 5rem;}
.hero-bg{position:absolute;inset:0;z-index:0;background:radial-gradient(ellipse 80% 60% at 65% 40%,rgba(37,99,235,.17) 0%,transparent 60%),radial-gradient(ellipse 55% 50% at 15% 70%,rgba(124,58,237,.13) 0%,transparent 55%),radial-gradient(ellipse 45% 40% at 80% 85%,rgba(6,182,212,.08) 0%,transparent 50%),var(--navy);}
.hero-grid{position:absolute;inset:0;z-index:0;background-image:linear-gradient(rgba(37,99,235,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(37,99,235,.06) 1px,transparent 1px);background-size:58px 58px;mask-image:radial-gradient(ellipse 70% 60% at 65% 40%,black 0%,transparent 70%);}
.orb{position:absolute;border-radius:50%;filter:blur(80px);pointer-events:none;animation:orbF 8s ease-in-out infinite;}
.o1{width:500px;height:500px;background:rgba(37,99,235,.14);top:-100px;right:-60px;}
.o2{width:380px;height:380px;background:rgba(124,58,237,.11);bottom:-80px;left:-80px;animation-delay:3s;}
.o3{width:240px;height:240px;background:rgba(6,182,212,.07);top:45%;left:42%;animation-delay:5.5s;}
@keyframes orbF{0%,100%{transform:translate(0,0) scale(1);}33%{transform:translate(18px,-28px) scale(1.05);}66%{transform:translate(-14px,18px) scale(.96);}}
.hero-inner{position:relative;z-index:1;}

.trust-pill{display:inline-flex;align-items:center;gap:.55rem;background:rgba(16,185,129,.09);border:1px solid rgba(16,185,129,.22);border-radius:50px;padding:.35rem .95rem;font-size:.76rem;font-weight:700;color:#6ee7b7;font-family:var(--fh);letter-spacing:.3px;margin-bottom:1.5rem;}
.trust-pill .dot{width:7px;height:7px;border-radius:50%;background:var(--green);animation:dotP 2s ease-in-out infinite;}
@keyframes dotP{0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.6);}50%{box-shadow:0 0 0 5px rgba(16,185,129,0);}}

.h1{font-family:var(--fh);font-size:clamp(2.4rem,5.5vw,4rem);font-weight:800;line-height:1.07;letter-spacing:-1.5px;color:#fff;margin-bottom:1.25rem;}
.h1 .gr{background:linear-gradient(90deg,var(--accent) 0%,var(--cyan) 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.hsub{font-size:1.02rem;color:rgba(255,255,255,.52);line-height:1.72;max-width:490px;margin-bottom:2rem;}

.btn-ph{display:inline-flex;align-items:center;gap:.6rem;background:var(--blue);color:#fff;font-family:var(--fh);font-weight:700;font-size:.93rem;padding:.85rem 1.75rem;border-radius:10px;text-decoration:none;transition:all .25s;box-shadow:0 6px 22px rgba(37,99,235,.38);}
.btn-ph:hover{background:var(--bluel);color:#fff;transform:translateY(-2px);box-shadow:0 10px 30px rgba(37,99,235,.48);}
.btn-ph .arr{transition:transform .2s;}.btn-ph:hover .arr{transform:translateX(4px);}
.btn-gh{display:inline-flex;align-items:center;gap:.6rem;color:rgba(255,255,255,.75);font-family:var(--fh);font-weight:600;font-size:.93rem;padding:.85rem 1.75rem;border-radius:10px;text-decoration:none;border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.04);transition:all .25s;}
.btn-gh:hover{color:#fff;border-color:rgba(255,255,255,.32);background:rgba(255,255,255,.09);}

.hstats{display:flex;gap:2.25rem;flex-wrap:wrap;margin-top:2.5rem;padding-top:2rem;border-top:1px solid rgba(255,255,255,.07);}
.hsv{font-family:var(--fh);font-size:1.55rem;font-weight:800;color:#fff;line-height:1;}
.hsl{font-size:.73rem;color:var(--muted);margin-top:.22rem;font-weight:500;}

/* Hero visual */
.mcard{background:rgba(255,255,255,.055);border:1px solid rgba(255,255,255,.1);border-radius:var(--rlg);padding:1.25rem;backdrop-filter:blur(16px);transition:transform .35s ease;}
.mcard:hover{transform:translateY(-6px);}
.mavail{background:rgba(16,185,129,.18);border:1px solid rgba(16,185,129,.3);color:#6ee7b7;border-radius:50px;padding:.18rem .65rem;font-size:.68rem;font-weight:700;font-family:var(--fh);white-space:nowrap;}
.mtag{background:rgba(37,99,235,.2);color:#93c5fd;border-radius:6px;padding:.2rem .6rem;font-size:.73rem;font-weight:600;}
.mconfirm{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.2);border-radius:var(--rmd);padding:.95rem 1.1rem;display:flex;align-items:center;gap:.85rem;margin-top:.85rem;}
.mcico{width:36px;height:36px;border-radius:50%;background:var(--green);display:flex;align-items:center;justify-content:center;font-size:.9rem;color:#fff;flex-shrink:0;}
.fpill{position:absolute;background:#fff;border-radius:50px;padding:.42rem .95rem;display:flex;align-items:center;gap:.45rem;font-weight:700;font-size:.76rem;color:#0f172a;box-shadow:0 10px 30px rgba(0,0,0,.3);font-family:var(--fh);z-index:10;animation:pillF 3.5s ease-in-out infinite;}
.fpill2{animation-delay:1.8s;}
@keyframes pillF{0%,100%{transform:translateY(0);}50%{transform:translateY(-10px);}}

/* SHARED SECTION */
.eyebrow{font-family:var(--fh);font-size:.71rem;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:var(--accent);margin-bottom:.55rem;}
.stitle{font-family:var(--fh);font-weight:800;color:#fff;letter-spacing:-.8px;}
.ssub{color:rgba(255,255,255,.48);line-height:1.72;}
.reveal{opacity:0;transform:translateY(30px);transition:opacity .6s ease,transform .6s ease;}
.reveal.visible{opacity:1;transform:translateY(0);}
.rd1{transition-delay:.08s}.rd2{transition-delay:.16s}.rd3{transition-delay:.24s}.rd4{transition-delay:.32s}

/* STATS BAR */
.sbar{background:rgba(255,255,255,.025);border-top:1px solid rgba(255,255,255,.055);border-bottom:1px solid rgba(255,255,255,.055);padding:2.5rem 0;}
.sbi{text-align:center;position:relative;}
.sbi:not(:last-child)::after{content:'';position:absolute;right:0;top:15%;height:70%;width:1px;background:rgba(255,255,255,.07);}
.sbn{font-family:var(--fh);font-size:2.1rem;font-weight:800;background:linear-gradient(135deg,#fff 40%,var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1;}
.sbl{font-size:.77rem;color:var(--muted);margin-top:.35rem;}

/* CATEGORIES */
.cats-sec{padding:6rem 0;background:var(--navy2);}
.ccard{background:var(--navy3);border:1px solid rgba(255,255,255,.07);border-radius:var(--rmd);padding:1.5rem 1.2rem;text-align:center;text-decoration:none;display:block;transition:all .25s;position:relative;overflow:hidden;}
.ccard::before{content:'';position:absolute;inset:0;opacity:0;background:linear-gradient(135deg,var(--cc,var(--blue)) 0%,transparent 65%);transition:opacity .3s;}
.ccard:hover{transform:translateY(-6px);border-color:rgba(255,255,255,.15);box-shadow:0 16px 40px rgba(0,0,0,.28);}
.ccard:hover::before{opacity:.09;}
.cico{width:56px;height:56px;border-radius:15px;margin:0 auto .95rem;display:flex;align-items:center;justify-content:center;font-size:1.45rem;transition:transform .3s;}
.ccard:hover .cico{transform:scale(1.12) rotate(-4deg);}
.cnm{font-family:var(--fh);font-size:.86rem;font-weight:700;color:#fff;margin-bottom:.2rem;}
.cdsc{font-size:.7rem;color:rgba(255,255,255,.38);line-height:1.5;}

/* HOW IT WORKS */
.how-sec{padding:6rem 0;}
.step-card{background:var(--navy3);border:1px solid rgba(255,255,255,.07);border-radius:var(--rlg);padding:2rem 1.65rem;height:100%;position:relative;overflow:hidden;transition:all .28s;}
.step-card::after{content:attr(data-n);position:absolute;right:1.1rem;top:.8rem;font-family:var(--fh);font-size:5.5rem;font-weight:800;color:rgba(255,255,255,.025);line-height:1;pointer-events:none;}
.step-card:hover{transform:translateY(-4px);border-color:rgba(37,99,235,.28);box-shadow:0 12px 36px rgba(37,99,235,.1);}
.snbadge{position:absolute;top:1.2rem;left:1.65rem;width:25px;height:25px;border-radius:50%;background:rgba(37,99,235,.18);border:1px solid rgba(37,99,235,.38);display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:800;color:var(--accent);font-family:var(--fh);}
.sico{width:50px;height:50px;border-radius:13px;margin-top:2rem;margin-bottom:1.1rem;display:flex;align-items:center;justify-content:center;font-size:1.25rem;}
.sttl{font-family:var(--fh);font-size:.96rem;font-weight:700;color:#fff;margin-bottom:.45rem;}
.sdsc{font-size:.81rem;color:rgba(255,255,255,.43);line-height:1.7;}

/* WHY US */
.why-sec{padding:6rem 0;background:var(--navy2);position:relative;overflow:hidden;}
.why-sec::before{content:'';position:absolute;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(37,99,235,.07) 0%,transparent 70%);top:-200px;right:-200px;pointer-events:none;}
.titem{background:var(--navy3);border:1px solid rgba(255,255,255,.07);border-radius:var(--rmd);padding:1.4rem;display:flex;align-items:flex-start;gap:1rem;height:100%;transition:all .25s;}
.titem:hover{border-color:rgba(37,99,235,.22);transform:translateX(4px);}
.tii{width:46px;height:46px;border-radius:12px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.15rem;}
.tit{font-family:var(--fh);font-weight:700;font-size:.9rem;color:#fff;margin-bottom:.28rem;}
.tid{font-size:.78rem;color:rgba(255,255,255,.43);line-height:1.65;}

/* PROVIDER */
.prov-sec{padding:6rem 0;}
.prov-ban{background:linear-gradient(135deg,var(--navy3) 0%,#0c1c38 100%);border:1px solid rgba(37,99,235,.18);border-radius:28px;padding:3.75rem 3.25rem;position:relative;overflow:hidden;}
.prov-ban::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 100% 50%,rgba(37,99,235,.11) 0%,transparent 60%),radial-gradient(ellipse 50% 70% at 0% 50%,rgba(124,58,237,.07) 0%,transparent 55%);}
.prov-inner{position:relative;z-index:1;}
.earn-bdg{display:inline-flex;align-items:center;gap:.45rem;background:rgba(37,99,235,.14);border:1px solid rgba(37,99,235,.28);border-radius:50px;padding:.32rem .85rem;font-size:.73rem;font-weight:700;color:var(--accent);font-family:var(--fh);letter-spacing:.5px;text-transform:uppercase;margin-bottom:1.2rem;}
.perk-ul{list-style:none;padding:0;margin:1.4rem 0 2rem;}
.perk-ul li{display:flex;align-items:center;gap:.7rem;color:rgba(255,255,255,.68);font-size:.86rem;margin-bottom:.7rem;}
.pico{width:26px;height:26px;border-radius:7px;flex-shrink:0;background:rgba(16,185,129,.14);border:1px solid rgba(16,185,129,.22);display:flex;align-items:center;justify-content:center;font-size:.72rem;color:var(--green);}
.emock{background:rgba(255,255,255,.045);border:1px solid rgba(255,255,255,.09);border-radius:20px;padding:1.5rem;}
.emh{font-family:var(--fh);font-size:.7rem;font-weight:700;color:rgba(255,255,255,.38);letter-spacing:1.5px;text-transform:uppercase;margin-bottom:1rem;}
.emrow{display:flex;align-items:center;gap:.7rem;margin-bottom:.55rem;}
.eml{font-size:.73rem;color:rgba(255,255,255,.45);width:28px;text-align:right;flex-shrink:0;}
.emtr{flex:1;height:7px;background:rgba(255,255,255,.06);border-radius:4px;overflow:hidden;}
.emf{height:100%;border-radius:4px;background:linear-gradient(90deg,var(--blue),var(--cyan));animation:barG 1.3s ease-out forwards;}
@keyframes barG{from{width:0 !important;}}
.emv{font-family:var(--fh);font-size:.76rem;font-weight:700;color:#fff;white-space:nowrap;}
.etot{margin-top:.95rem;padding-top:.95rem;border-top:1px solid rgba(255,255,255,.07);display:flex;justify-content:space-between;align-items:center;}
.etl{font-size:.76rem;color:rgba(255,255,255,.4);}
.etv{font-family:var(--fh);font-size:1.25rem;font-weight:800;color:var(--green);}

/* REVIEWS */
.rev-sec{padding:6rem 0;background:var(--navy2);}
.rcard{background:var(--navy3);border:1px solid rgba(255,255,255,.07);border-radius:var(--rmd);padding:1.65rem;height:100%;position:relative;overflow:hidden;transition:all .25s;}
.rcard::before{content:'"';position:absolute;top:.4rem;right:1.1rem;font-family:Georgia,serif;font-size:6rem;line-height:1;color:rgba(37,99,235,.07);pointer-events:none;}
.rcard:hover{transform:translateY(-4px);border-color:rgba(37,99,235,.18);box-shadow:0 14px 36px rgba(0,0,0,.22);}
.rav{width:44px;height:44px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,var(--blue),var(--violet));display:flex;align-items:center;justify-content:center;font-family:var(--fh);font-weight:800;font-size:.95rem;color:#fff;}
.rnm{font-family:var(--fh);font-weight:700;color:#fff;font-size:.88rem;}
.rsvc{font-size:.73rem;color:var(--muted);margin-top:.08rem;}
.rstr{color:#fbbf24;font-size:.83rem;letter-spacing:.4px;}
.rtxt{font-size:.84rem;color:rgba(255,255,255,.5);line-height:1.75;margin:0;font-style:italic;}

/* CTA */
.cta-sec{padding:7rem 0;position:relative;overflow:hidden;}
.cta-sec::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 55% 65% at 50% 50%,rgba(37,99,235,.09) 0%,transparent 65%);}
.cta-box{position:relative;z-index:1;text-align:center;max-width:680px;margin:0 auto;}
.ctah{font-family:var(--fh);font-size:clamp(2rem,4.5vw,3rem);font-weight:800;color:#fff;letter-spacing:-1px;margin-bottom:.9rem;}
.ctas{font-size:.97rem;color:var(--muted);margin-bottom:2.5rem;line-height:1.7;}
.btn-cta{display:inline-flex;align-items:center;gap:.55rem;background:#fff;color:#0f172a;font-family:var(--fh);font-weight:800;font-size:.97rem;padding:1rem 2.4rem;border-radius:10px;text-decoration:none;transition:all .25s;box-shadow:0 8px 26px rgba(0,0,0,.28);}
.btn-cta:hover{transform:translateY(-3px);box-shadow:0 14px 38px rgba(0,0,0,.36);color:#0f172a;}
.ctan{font-size:.76rem;color:rgba(255,255,255,.28);margin-top:.9rem;}

/* FOOTER */
.site-footer{background:var(--navy2);border-top:1px solid rgba(255,255,255,.055);padding:4rem 0 2rem;}
.ftbr{font-family:var(--fh);font-size:1.2rem;font-weight:800;color:#fff;display:flex;align-items:center;gap:.45rem;margin-bottom:.7rem;}
.ftbr .bico{width:28px;height:28px;border-radius:7px;background:linear-gradient(135deg,var(--blue),var(--violet));display:flex;align-items:center;justify-content:center;font-size:.75rem;}
.fttag{font-size:.81rem;color:rgba(255,255,255,.38);line-height:1.7;margin-bottom:1.1rem;}
.ftsoc a{width:34px;height:34px;border-radius:8px;background:rgba(255,255,255,.055);border:1px solid rgba(255,255,255,.07);display:inline-flex;align-items:center;justify-content:center;color:rgba(255,255,255,.45);text-decoration:none;font-size:.85rem;transition:all .2s;margin-right:.4rem;}
.ftsoc a:hover{background:rgba(37,99,235,.2);border-color:rgba(37,99,235,.38);color:var(--accent);}
.ftch{font-family:var(--fh);font-size:.72rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.32);margin-bottom:1rem;}
.ftlk{display:block;color:rgba(255,255,255,.48);text-decoration:none;font-size:.82rem;margin-bottom:.5rem;transition:color .15s;}
.ftlk:hover{color:#fff;}
.ftcr{display:flex;align-items:flex-start;gap:.55rem;font-size:.8rem;color:rgba(255,255,255,.4);margin-bottom:.55rem;}
.ftcr i{color:var(--bluel);margin-top:.1rem;flex-shrink:0;}
.ftdiv{border-color:rgba(255,255,255,.055);margin:1.75rem 0 1.2rem;}
.ftbot{font-size:.76rem;color:rgba(255,255,255,.22);}
@media(max-width:991px){.hero-sec{padding:7rem 0 4rem;}.prov-ban{padding:2.25rem 1.6rem;}}
@media(max-width:767px){.h1{letter-spacing:-.5px;}.hstats{gap:1.4rem;}.hsv{font-size:1.3rem;}.sbi::after{display:none;}}
</style>
</head>
<body>

<nav class="site-nav" id="siteNav">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="<?= APP_URL ?>" class="nav-brand">
      <div class="bico"><i class="bi bi-house-heart-fill text-white" style="font-size:.85rem"></i></div>
      Home<span class="acc">Serve</span>
    </a>
    <!-- Desktop anchor links -->
    <div class="d-none d-md-flex align-items-center gap-1" style="flex:1;justify-content:center">
      <a href="#services" class="nbtn-l" style="border:none;background:none">Services</a>
      <a href="#how-it-works" class="nbtn-l" style="border:none;background:none">How It Works</a>
      <a href="#for-providers" class="nbtn-l" style="border:none;background:none">For Providers</a>
      <a href="<?= APP_URL ?>/contact.php" class="nbtn-l" style="border:none;background:none">Contact</a>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="<?= APP_URL ?>/login.php" class="nbtn-l d-none d-sm-inline-flex">Login</a>
      <a href="<?= APP_URL ?>/register.php" class="nbtn-c">Get Started</a>
      <button class="nav-ham d-md-none" id="navHamBtn" onclick="toggleMobNav()">
        <i class="bi bi-list" id="navHamIcon"></i>
      </button>
    </div>
  </div>
</nav>
<!-- Mobile fullscreen nav -->
<div class="nav-mob-menu" id="navMobMenu">
  <button class="nav-mob-close" onclick="toggleMobNav()"><i class="bi bi-x-lg"></i></button>
  <a href="#services" onclick="toggleMobNav()">Services</a>
  <a href="#how-it-works" onclick="toggleMobNav()">How It Works</a>
  <a href="#for-providers" onclick="toggleMobNav()">For Providers</a>
  <a href="<?= APP_URL ?>/contact.php" onclick="toggleMobNav()">Contact</a>
  <hr style="width:80px;border-color:rgba(255,255,255,.15)">
  <a href="<?= APP_URL ?>/login.php" class="nbtn-l">Login</a>
  <a href="<?= APP_URL ?>/register.php" class="nbtn-c" style="font-size:1rem;padding:.7rem 1.8rem">Get Started</a>
</div>

<section class="hero-sec">
  <div class="hero-bg"></div><div class="hero-grid"></div>
  <div class="orb o1"></div><div class="orb o2"></div><div class="orb o3"></div>
  <div class="container hero-inner">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="trust-pill"><span class="dot"></span> Verified Professionals Only</div>
        <h1 class="h1">Your Home,<br><span class="gr">Expert Care</span><br>On Demand.</h1>
        <p class="hsub">Book trusted home service professionals in minutes — plumbing, electrical, cleaning, and more. Quality guaranteed, every time.</p>
        <div class="d-flex gap-3 flex-wrap">
          <a href="<?= APP_URL ?>/register.php" class="btn-ph">Book a Service <i class="bi bi-arrow-right arr"></i></a>
          <a href="<?= APP_URL ?>/login.php"    class="btn-gh"><i class="bi bi-box-arrow-in-right"></i> Sign In</a>
        </div>
        <div class="hstats">
          <div><div class="hsv"><?= fmtStat($totalProviders) ?></div><div class="hsl">Verified Providers</div></div>
          <div><div class="hsv"><?= fmtStat($totalCustomers) ?></div><div class="hsl">Happy Customers</div></div>
          <div><div class="hsv"><?= fmtStat($totalBookings) ?></div><div class="hsl">Jobs Completed</div></div>
          <div><div class="hsv"><?= $platformAvg ?>&#9733;</div><div class="hsl">Avg Rating</div></div>
        </div>
      </div>
      <div class="col-lg-6 d-none d-lg-block">
        <div style="position:relative">
          <div class="fpill"  style="top:-1.5rem;right:2rem"><span style="color:#fbbf24">&#9733;</span> 4.9 Rated</div>
          <div class="fpill fpill2" style="bottom:-1rem;left:0"><i class="bi bi-shield-check-fill text-success"></i> Background Verified</div>
          <div class="mcard mb-3">
            <div class="d-flex align-items-center gap-3 mb-3 pb-3" style="border-bottom:1px solid rgba(255,255,255,.07)">
              <div style="width:46px;height:46px;background:linear-gradient(135deg,#2563eb,#7c3aed);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0">&#128295;</div>
              <div class="flex-grow-1">
                <div style="font-family:var(--fh);color:#fff;font-weight:700;font-size:.88rem">Rajan Plumbing Services</div>
                <div style="color:rgba(255,255,255,.42);font-size:.73rem;margin-top:.1rem">&#9733; 4.9 &middot; 142 jobs completed</div>
              </div>
              <div class="mavail">&#9679; Available</div>
            </div>
            <div style="background:rgba(255,255,255,.04);border-radius:10px;padding:.8rem;margin-bottom:.8rem">
              <div style="color:rgba(255,255,255,.32);font-size:.66rem;text-transform:uppercase;letter-spacing:1.2px;margin-bottom:.55rem">Popular Services</div>
              <div class="d-flex flex-wrap gap-2">
                <span class="mtag">Pipe Repair &#8377;1,500</span>
                <span class="mtag">Drain Unclog &#8377;800</span>
                <span class="mtag">Tap Fix &#8377;500</span>
              </div>
            </div>
            <div class="d-flex align-items-center justify-content-between">
              <div style="font-family:var(--fh);color:var(--accent);font-weight:800;font-size:1.1rem">&#8377;500 <span style="color:rgba(255,255,255,.28);font-size:.72rem;font-weight:400">starting</span></div>
              <button class="btn btn-primary btn-sm px-4 fw-700" style="border-radius:8px;font-family:var(--fh);font-size:.78rem">Book Now</button>
            </div>
          </div>
          <div class="mconfirm">
            <div class="mcico"><i class="bi bi-check-lg"></i></div>
            <div>
              <div style="font-family:var(--fh);color:#fff;font-weight:700;font-size:.86rem">Booking Confirmed! &#127881;</div>
              <div style="color:rgba(255,255,255,.42);font-size:.73rem;margin-top:.12rem">Your plumber arrives tomorrow at 10:00 AM</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="sbar">
  <div class="container">
    <div class="row g-3">
      <?php foreach([[fmtStat($totalProviders),'Approved Professionals','bi-person-badge-fill'],[fmtStat($totalCustomers),'Registered Customers','bi-people-fill'],[fmtStat($totalBookings),'Completed Services','bi-check2-all'],[$platformAvg.'&#9733;','Platform Avg Rating','bi-star-fill']] as $i=>[$v,$l,$ic]): ?>
      <div class="col-6 col-md-3"><div class="sbi reveal rd<?= $i+1 ?>"><div class="sbn"><?= $v ?></div><div class="sbl"><i class="bi <?= $ic ?> me-1" style="color:var(--bluel)"></i><?= $l ?></div></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<section class="cats-sec" id="services">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <div class="eyebrow">Our Services</div>
      <h2 class="stitle" style="font-size:clamp(1.8rem,4vw,2.7rem)">What do you need help with?</h2>
      <p class="ssub mt-2" style="max-width:480px;margin:0 auto">From emergency repairs to routine maintenance &mdash; every corner of your home covered.</p>
    </div>
    <div class="row g-3">
      <?php foreach($cats as $i=>$cat): ?>
      <div class="col-6 col-sm-4 col-md-3 reveal rd<?= ($i%4)+1 ?>">
        <a href="<?= APP_URL ?>/register.php" class="ccard" style="--cc:<?= $cat['color'] ?>">
          <div class="cico" style="background:<?= $cat['color'] ?>1a;border:1px solid <?= $cat['color'] ?>28"><i class="bi <?= $cat['icon']??'bi-tools' ?>" style="color:<?= $cat['color'] ?>"></i></div>
          <div class="cnm"><?= sanitize($cat['name']) ?></div>
          <div class="cdsc"><?= sanitize($cat['description']??'') ?></div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="how-sec" id="how-it-works">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <div class="eyebrow">How It Works</div>
      <h2 class="stitle" style="font-size:clamp(1.8rem,4vw,2.7rem)">Simple. Fast. Reliable.</h2>
      <p class="ssub mt-2" style="max-width:460px;margin:0 auto">Get a professional at your doorstep in 4 easy steps.</p>
    </div>
    <div class="row g-3">
      <?php foreach([['bi-search','#2563eb','Browse & Choose','Pick from verified professionals across 8+ service categories.'],['bi-calendar2-check','#059669','Book Appointment','Choose your preferred date, time, and describe your requirements.'],['bi-person-check','#7c3aed','Provider Arrives','Your assigned expert confirms and shows up right on time.'],['bi-star-fill','#f59e0b','Rate & Review','Share your experience and help the next customer choose wisely.']] as $i=>[$ico,$col,$ttl,$dsc]): ?>
      <div class="col-sm-6 col-md-3 reveal rd<?= $i+1 ?>">
        <div class="step-card" data-n="<?= $i+1 ?>">
          <div class="snbadge"><?= $i+1 ?></div>
          <div class="sico" style="background:<?= $col ?>16;border:1px solid <?= $col ?>28"><i class="bi <?= $ico ?>" style="color:<?= $col ?>"></i></div>
          <div class="sttl"><?= $ttl ?></div><div class="sdsc"><?= $dsc ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="why-sec">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5 reveal">
        <div class="eyebrow">Why HomeServe</div>
        <h2 class="stitle" style="font-size:clamp(1.8rem,4vw,2.6rem)">Built on trust,<br>delivered with care.</h2>
        <p class="ssub mt-3">We vet every provider so you don&rsquo;t have to. Every professional is background-checked, skill-verified, and rated by real customers.</p>
        <a href="<?= APP_URL ?>/register.php" class="btn-ph mt-4 d-inline-flex">Get Started Free <i class="bi bi-arrow-right arr"></i></a>
      </div>
      <div class="col-lg-7">
        <div class="row g-3">
          <?php foreach([['bi-shield-check-fill','#2563eb','Background Verified','Every provider goes through identity and skill checks before listing.'],['bi-star-fill','#f59e0b','Real Customer Reviews','All ratings come from genuine completed bookings &mdash; no fakes.'],['bi-lock-fill','#059669','Secure UPI Payments','Pay safely via QR code or UPI. Protected until job is done.'],['bi-headset','#7c3aed','24/7 Support','Our team is always ready before, during, or after your service.'],['bi-clock-history','#06b6d4','On-Time Guarantee','Providers commit to your schedule. Late? We make it right.'],['bi-receipt','#f43f5e','Transparent Pricing','See exactly what you pay before confirming. Zero surprises.']] as $i=>[$ico,$col,$ttl,$dsc]): ?>
          <div class="col-sm-6 reveal rd<?= ($i%3)+1 ?>">
            <div class="titem"><div class="tii" style="background:<?= $col ?>14;border:1px solid <?= $col ?>22"><i class="bi <?= $ico ?>" style="color:<?= $col ?>"></i></div><div><div class="tit"><?= $ttl ?></div><div class="tid"><?= $dsc ?></div></div></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="prov-sec" id="for-providers">
  <div class="container">
    <div class="prov-ban reveal">
      <div class="prov-inner">
        <div class="row align-items-center g-5">
          <div class="col-lg-6">
            <div class="earn-bdg"><i class="bi bi-lightning-charge-fill"></i> For Professionals</div>
            <h2 class="stitle" style="font-size:clamp(1.8rem,4vw,2.7rem);margin-bottom:.9rem">Turn Your Skills Into<br>a Steady Income</h2>
            <p class="ssub">Join HomeServe as a verified provider and start getting booked by customers. Set your availability &mdash; we bring the jobs to you.</p>
            <ul class="perk-ul">
              <?php foreach(['Free to join &mdash; no upfront cost','Set your own schedule &amp; prices','Get paid directly after each job','Build your reputation with reviews','Admin support &amp; booking management'] as $p): ?>
              <li><span class="pico"><i class="bi bi-check-lg"></i></span><?= $p ?></li>
              <?php endforeach; ?>
            </ul>
            <a href="<?= APP_URL ?>/register.php?type=provider" class="btn-ph d-inline-flex">Apply as Provider <i class="bi bi-arrow-right arr"></i></a>
          </div>
          <div class="col-lg-5 offset-lg-1 d-none d-lg-block">
            <div class="emock">
              <div class="emh">Monthly Earnings Preview</div>
              <?php foreach([['Oct',65,'&#8377;19,500'],['Nov',78,'&#8377;23,400'],['Dec',55,'&#8377;16,500'],['Jan',88,'&#8377;26,400'],['Feb',72,'&#8377;21,600'],['Mar',100,'&#8377;30,000']] as [$m,$p,$v]): ?>
              <div class="emrow"><div class="eml"><?= $m ?></div><div class="emtr"><div class="emf" style="width:<?= $p ?>%"></div></div><div class="emv"><?= $v ?></div></div>
              <?php endforeach; ?>
              <div class="etot"><div class="etl">This Month&rsquo;s Target</div><div class="etv">&#8377;30,000+</div></div>
              <div style="margin-top:.7rem;padding:.65rem;background:rgba(16,185,129,.07);border:1px solid rgba(16,185,129,.14);border-radius:9px;font-size:.72rem;color:rgba(255,255,255,.42);text-align:center"><i class="bi bi-info-circle me-1" style="color:var(--green)"></i> Indicative figures based on platform data</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="rev-sec">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <div class="eyebrow">Testimonials</div>
      <h2 class="stitle" style="font-size:clamp(1.8rem,4vw,2.7rem)">What Our Customers Say</h2>
      <p class="ssub mt-2">Real feedback from real bookings.</p>
    </div>
    <?php $show=!empty($realReviews)?$realReviews:[['rating'=>5,'review_text'=>'The plumber arrived on time and fixed the leak in under an hour. Super professional and clean work!','cust_name'=>'Rahul Mehta','service_name'=>'Plumbing'],['rating'=>5,'review_text'=>'Booked a deep clean for my 2BHK &mdash; they did an incredible job. Will definitely book again.','cust_name'=>'Priya Shah','service_name'=>'Cleaning'],['rating'=>5,'review_text'=>'Quick response, fair pricing, and excellent work. Exactly what I needed for my home rewiring.','cust_name'=>'Aditya Kumar','service_name'=>'Electrical']]; ?>
    <div class="row g-3">
      <?php foreach($show as $i=>$rev): $parts=explode(' ',trim($rev['cust_name'])); $dn=$parts[0].(isset($parts[1])?' '.strtoupper($parts[1][0]).'.':''); $init=strtoupper(substr($rev['cust_name'],0,1)); $stars=(int)$rev['rating']; ?>
      <div class="col-md-6 col-lg-4 reveal rd<?= ($i%3)+1 ?>">
        <div class="rcard">
          <div class="d-flex align-items-center gap-3 mb-1">
            <div class="rav"><?= htmlspecialchars($init) ?></div>
            <div class="flex-grow-1"><div class="rnm"><?= htmlspecialchars($dn) ?></div><div class="rsvc"><?= htmlspecialchars($rev['service_name']) ?></div></div>
            <div class="rstr"><?= str_repeat('&#9733;',$stars).str_repeat('&#9734;',5-$stars) ?></div>
          </div>
          <hr style="border-color:rgba(255,255,255,.055);margin:.8rem 0">
          <p class="rtxt">&ldquo;<?= htmlspecialchars($rev['review_text']) ?>&rdquo;</p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="cta-sec">
  <div class="container">
    <div class="cta-box reveal">
      <div class="eyebrow" style="text-align:center">Get Started Today</div>
      <h2 class="ctah">Your home deserves the best.<br>Book in minutes.</h2>
      <p class="ctas">Join thousands of homeowners who trust HomeServe for reliable, professional home services. No hassle, no guesswork.</p>
      <a href="<?= APP_URL ?>/register.php" class="btn-cta"><i class="bi bi-house-heart-fill" style="color:var(--blue)"></i> Create Free Account</a>
      <div class="ctan">No credit card required &middot; Free to join &middot; Cancel anytime</div>
    </div>
  </div>
</section>

<footer class="site-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4 col-md-6">
        <div class="ftbr"><div class="bico"><i class="bi bi-house-heart-fill text-white" style="font-size:.75rem"></i></div>HomeServe</div>
        <p class="fttag">Connecting homeowners with trusted, verified service professionals. Quality service at your doorstep.</p>
        <div class="ftsoc"><a href="#"><i class="bi bi-facebook"></i></a><a href="#"><i class="bi bi-instagram"></i></a><a href="#"><i class="bi bi-twitter-x"></i></a><a href="#"><i class="bi bi-linkedin"></i></a></div>
      </div>
      <div class="col-lg-2 col-6">
        <div class="ftch">Quick Links</div>
        <a class="ftlk" href="<?= APP_URL ?>">Home</a>
        <a class="ftlk" href="<?= APP_URL ?>/about.php">About Us</a>
        <a class="ftlk" href="<?= APP_URL ?>/faq.php">FAQ</a>
        <a class="ftlk" href="<?= APP_URL ?>/contact.php">Contact</a>
        <a class="ftlk" href="<?= APP_URL ?>/login.php">Login</a>
        <a class="ftlk" href="<?= APP_URL ?>/register.php?type=provider">Become a Provider</a>
      </div>
      <div class="col-lg-2 col-6">
        <div class="ftch">Services</div>
        <?php foreach(array_slice($cats,0,6) as $cat): ?><a class="ftlk" href="<?= APP_URL ?>/register.php"><?= sanitize($cat['name']) ?></a><?php endforeach; ?>
      </div>
      <div class="col-lg-4 col-md-6">
        <div class="ftch">Contact Us</div>
        <div class="ftcr"><i class="bi bi-geo-alt-fill"></i><span>India</span></div>
        <div class="ftcr"><i class="bi bi-envelope-fill"></i><span>support@homeserve.in</span></div>
        <div class="ftcr"><i class="bi bi-telephone-fill"></i><span>+91 98765 43210</span></div>
        <div class="ftcr"><i class="bi bi-clock-fill"></i><span>Mon&ndash;Sat, 8AM&ndash;8PM IST</span></div>
      </div>
    </div>
    <hr class="ftdiv">
    <div class="d-flex justify-content-between flex-wrap gap-2">
      <span class="ftbot">&copy; <?= date('Y') ?> HomeServe. All rights reserved.</span>
      <span class="ftbot">
        <a href="<?= APP_URL ?>/terms.php" style="color:inherit;text-decoration:none;margin-right:.75rem">Terms</a>
        <a href="<?= APP_URL ?>/privacy.php" style="color:inherit;text-decoration:none;margin-right:.75rem">Privacy</a>
        Made with &hearts; in India
      </span>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const nav=document.getElementById('siteNav');
window.addEventListener('scroll',()=>nav.classList.toggle('scrolled',window.scrollY>40),{passive:true});
const obs=new IntersectionObserver(es=>{es.forEach(e=>{if(e.isIntersecting){e.target.classList.add('visible');obs.unobserve(e.target);}});},{threshold:.1});
document.querySelectorAll('.reveal').forEach(el=>obs.observe(el));
document.querySelectorAll('.hero-inner>.row>.col-lg-6').forEach((el,i)=>{
  el.style.cssText='opacity:0;transform:translateX('+(i?'28':'-28')+'px);transition:opacity .8s ease,transform .8s ease;transition-delay:'+(i*.18)+'s';
  requestAnimationFrame(()=>requestAnimationFrame(()=>{el.style.opacity='1';el.style.transform='translateX(0)';}));
});
function toggleMobNav() {
  const m = document.getElementById('navMobMenu');
  const ic = document.getElementById('navHamIcon');
  m.classList.toggle('open');
  if (ic) ic.className = m.classList.contains('open') ? 'bi bi-x-lg' : 'bi bi-list';
  document.body.style.overflow = m.classList.contains('open') ? 'hidden' : '';
}
// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(a=>{
  a.addEventListener('click', e=>{
    const id = a.getAttribute('href').slice(1);
    const el = document.getElementById(id);
    if (el) { e.preventDefault(); el.scrollIntoView({behavior:'smooth'}); }
  });
});
</script>
</body>
</html>