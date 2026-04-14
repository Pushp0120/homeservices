<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) redirect(APP_URL . '/index.php');

$db = getDB();
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = sanitize($_POST['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $db->prepare("SELECT id, name FROM users WHERE email=? AND status='active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate secure reset token
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            // Store token in session for demo (in production, store in DB table)
            $_SESSION['reset_token']   = $token;
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_expires'] = strtotime('+1 hour');

            // In production: send email. For demo, show the link directly.
            $resetLink = APP_URL . '/reset_password.php?token=' . $token;
            $_SESSION['demo_reset_link'] = $resetLink;
        }
        // Always show success to prevent user enumeration
        $success = 'If that email is registered, a password reset link has been generated.';
    }
}

$bodyClass = 'auth-page';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password | HomeServe</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?= APP_URL ?>/assets/css/main.css" rel="stylesheet">
</head>
<body class="auth-page">
<div class="auth-card">
<div class="auth-logo"><img src="<?= APP_URL ?>/assets/images/logo.svg" alt="HomeServe" height="45"></div>
  <div class="auth-subtitle">Reset your password</div>

  <?php if ($error): ?>
  <div class="alert alert-danger mt-3 py-2 d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
  </div>
  <?php endif; ?>

  <?php if ($success): ?>
  <div class="alert alert-success mt-3">
    <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
    <?php if (!empty($_SESSION['demo_reset_link'])): ?>
    <hr>
    <small class="d-block text-muted mb-1"><strong>Demo mode:</strong> In production, this link would be emailed. Click below to reset:</small>
    <a href="<?= $_SESSION['demo_reset_link'] ?>" class="btn btn-success btn-sm mt-1 w-100">
      <i class="bi bi-key me-1"></i> Open Reset Link
    </a>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <p class="text-muted small mt-3">Enter your registered email address. We'll generate a password reset link for you.</p>

  <form method="POST" class="mt-3">
    <?= csrfField() ?>
    <div class="mb-4">
      <label class="form-label">Email Address</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
        <input type="email" name="email" class="form-control" placeholder="you@example.com"
               value="<?= sanitize($_POST['email'] ?? '') ?>" required autofocus>
      </div>
    </div>
    <button type="submit" class="btn btn-primary w-100 py-2">
      <i class="bi bi-send me-1"></i> Send Reset Link
    </button>
  </form>
  <?php endif; ?>

  <div class="auth-divider mt-4"></div>
  <p class="text-center mb-0 small">
    Remembered your password? <a href="<?= APP_URL ?>/login.php" class="fw-600">Sign in</a>
  </p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
