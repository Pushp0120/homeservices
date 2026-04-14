<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) redirect(APP_URL . '/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email && $password) {
        verifyCsrf();
        $result = login($email, $password);
        if ($result['success']) {
            switch ($result['role']) {
                case 'admin':    redirect(APP_URL . '/modules/admin/dashboard.php');
                case 'provider': redirect(APP_URL . '/modules/provider/dashboard.php');
                default:
                    // Pending provider? Send to the waiting room
                    $db = getDB();
                    $pendingChk = $db->prepare("SELECT id FROM providers WHERE user_id=? AND approval_status='pending'");
                    $pendingChk->execute([$_SESSION['user_id']]);
                    if ($pendingChk->fetch()) {
                        redirect(APP_URL . '/modules/provider/pending.php');
                    }
                    redirect(APP_URL . '/modules/user/dashboard.php');
            }
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
$bodyClass = 'auth-page';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | HomeServe</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@700;800&display=swap" rel="stylesheet">
<link href="<?= APP_URL ?>/assets/css/main.css" rel="stylesheet">
<style>
.auth-page { background: linear-gradient(135deg, #080e1d 0%, #0f2150 50%, #080e1d 100%); }
.auth-card { border-radius: 24px; box-shadow: 0 32px 80px rgba(0,0,0,.45); }
.auth-brand { font-family: 'Sora', sans-serif; font-size: 1.55rem; font-weight: 800; color: #0f172a; letter-spacing: -1px; display: flex; align-items: center; gap: .5rem; }
.auth-brand .brand-icon { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #2563eb, #7c3aed); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .9rem; }
.auth-brand .brand-accent { color: #2563eb; }
.auth-divider::after { content: 'or'; }
.input-group-text { border-right: none; }
.form-control { border-left: none; }
.form-control:focus { border-color: #2563eb; }
.input-group:focus-within .input-group-text { border-color: #2563eb; }
.btn-signin { background: linear-gradient(135deg, #2563eb, #7c3aed); border: none; font-weight: 700; letter-spacing: .3px; padding: .65rem 1rem; }
.btn-signin:hover { opacity: .92; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,.38); }
.auth-footer-link { color: #2563eb; font-weight: 600; text-decoration: none; }
.auth-footer-link:hover { text-decoration: underline; }
</style>
</head>
<body class="auth-page">
<div class="auth-card">
  <div class="auth-brand mb-1">
    <div class="brand-icon"><i class="bi bi-house-heart-fill"></i></div>
    Home<span class="brand-accent">Serve</span>
  </div>
  <div class="auth-subtitle">Welcome back — sign in to your account</div>

  <?php if ($error): ?>
  <div class="alert alert-danger mt-3 py-2 d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
  </div>
  <?php endif; ?>

  <form method="POST" class="mt-4">
    <?= csrfField() ?>
    <div class="mb-3">
      <label class="form-label">Email Address</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
        <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= sanitize($_POST['email'] ?? '') ?>" required autofocus>
      </div>
    </div>
    <div class="mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <label class="form-label mb-0">Password</label>
        <a href="<?= APP_URL ?>/forgot_password.php" class="text-muted small" style="font-size:.8rem">Forgot password?</a>
      </div>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-lock"></i></span>
        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
        <button class="btn btn-outline-secondary" type="button" onclick="togglePwd()"><i class="bi bi-eye" id="eyeIcon"></i></button>
      </div>
    </div>
    <button type="submit" class="btn btn-signin btn-primary w-100 py-2" id="signinBtn">Sign In <i class="bi bi-arrow-right-short"></i></button>
  </form>

  <div class="auth-divider mt-4"></div>
  <p class="text-center mb-0 small">
    Don't have an account? <a href="<?= APP_URL ?>/register.php" class="auth-footer-link">Create free account →</a>
  </p>

  <div class="text-center mt-3">
    <a href="<?= APP_URL ?>" class="text-muted small" style="font-size:.78rem; text-decoration:none">
      <i class="bi bi-arrow-left me-1"></i> Back to HomeServe
    </a>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// If someone lands on login page via back button while session exists,
// push a new history state so back button doesn't return to dashboard
history.pushState(null, null, location.href);
window.addEventListener('popstate', function () {
  history.pushState(null, null, location.href);
});
function togglePwd() {
  const p = document.getElementById('password');
  const i = document.getElementById('eyeIcon');
  p.type = p.type === 'password' ? 'text' : 'password';
  i.className = p.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
</body>
</html>
