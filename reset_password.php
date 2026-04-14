<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) redirect(APP_URL . '/index.php');

$db    = getDB();
$token = $_GET['token'] ?? '';
$error = '';
$done  = false;

// Validate token from session
$validToken = (
    $token &&
    !empty($_SESSION['reset_token']) &&
    hash_equals($_SESSION['reset_token'], $token) &&
    !empty($_SESSION['reset_expires']) &&
    time() < $_SESSION['reset_expires']
);

if (!$validToken) {
    $error = 'This reset link is invalid or has expired. Please <a href="forgot_password.php">request a new one</a>.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    verifyCsrf();
    $pass1 = $_POST['password'] ?? '';
    $pass2 = $_POST['confirm_password'] ?? '';

    if (strlen($pass1) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass1 !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = hashPassword($pass1);
        $stmt   = $db->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->execute([$hashed, $_SESSION['reset_user_id']]);
        // Invalidate token
        unset($_SESSION['reset_token'], $_SESSION['reset_user_id'], $_SESSION['reset_expires'], $_SESSION['demo_reset_link']);
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password | HomeServe</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?= APP_URL ?>/assets/css/main.css" rel="stylesheet">
</head>
<body class="auth-page">
<div class="auth-card">
<div class="auth-logo"><img src="<?= APP_URL ?>/assets/images/logo.svg" alt="HomeServe" height="45"></div>
  <div class="auth-subtitle">Set a new password</div>

  <?php if ($done): ?>
  <div class="alert alert-success mt-4 text-center">
    <i class="bi bi-check-circle-fill fs-2 text-success d-block mb-2"></i>
    <strong>Password changed successfully!</strong>
    <p class="mb-0 mt-1 small">You can now log in with your new password.</p>
  </div>
  <a href="<?= APP_URL ?>/login.php" class="btn btn-primary w-100 mt-3">Go to Login</a>

  <?php elseif ($error): ?>
  <div class="alert alert-danger mt-4"><?= $error ?></div>

  <?php else: ?>
  <?php if ($error): ?>
  <div class="alert alert-danger mt-3 py-2"><?= $error ?></div>
  <?php endif; ?>
  <form method="POST" class="mt-4">
    <?= csrfField() ?>
    <div class="mb-3">
      <label class="form-label">New Password</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-lock"></i></span>
        <input type="password" name="password" id="pwd1" class="form-control" placeholder="Min 6 characters" required>
        <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('pwd1','eye1')"><i id="eye1" class="bi bi-eye"></i></button>
      </div>
    </div>
    <div class="mb-4">
      <label class="form-label">Confirm New Password</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
        <input type="password" name="confirm_password" id="pwd2" class="form-control" placeholder="Repeat password" required>
      </div>
    </div>
    <button type="submit" class="btn btn-primary w-100 py-2">
      <i class="bi bi-key me-1"></i> Change Password
    </button>
  </form>
  <?php endif; ?>
</div>
<script>
function togglePwd(id, eyeId) {
  const p = document.getElementById(id), i = document.getElementById(eyeId);
  p.type = p.type === 'password' ? 'text' : 'password';
  i.className = p.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
</body>
</html>
