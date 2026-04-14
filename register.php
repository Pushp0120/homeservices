<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) redirect(APP_URL . '/index.php');

$db   = getDB();
$type = $_GET['type'] ?? 'customer';
$error = '';

$categories = $db->query("SELECT * FROM categories WHERE status='active' ORDER BY name")->fetchAll();

// ── STEP 2: Final registration after OTP verified ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'complete_register') {
    verifyCsrf();

    // Check OTP was verified and is still fresh (15 min window)
    $otpOk = (
        isset($_SESSION['reg_otp_verified'])    &&
        $_SESSION['reg_otp_verified'] === true  &&
        isset($_SESSION['reg_otp_verified_at']) &&
        (time() - $_SESSION['reg_otp_verified_at']) < 900
    );

    if (!$otpOk) {
        $error = 'Mobile OTP verification expired or missing. Please start again.';
        // Clear stale session data so user restarts cleanly
        foreach (['reg_otp','reg_otp_expiry','reg_otp_phone','reg_otp_attempts','reg_otp_verified','reg_otp_verified_at','reg_form_data'] as $k) {
            unset($_SESSION[$k]);
        }
    } else {
        // Restore form data saved during step 1
        $fd       = $_SESSION['reg_form_data'] ?? [];
        $regType  = $fd['reg_type']  ?? 'customer';
        $name     = $fd['name']      ?? '';
        $email    = $fd['email']     ?? '';
        $phone    = $fd['phone']     ?? '';
        $password = $fd['password']  ?? '';

        if (!$name || !$email || !$phone || !$password) {
            $error = 'Session data lost. Please fill the form again.';
            foreach (['reg_otp','reg_otp_expiry','reg_otp_phone','reg_otp_attempts','reg_otp_verified','reg_otp_verified_at','reg_form_data'] as $k) {
                unset($_SESSION[$k]);
            }
        } else {
            $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $error = 'This email is already registered. <a href="login.php">Login instead?</a>';
            } else {
                $db->beginTransaction();
                try {
                    $hashed = hashPassword($password);
                    $ins = $db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?,?,?,?,'customer')");
                    $ins->execute([$name, $email, $phone, $hashed]);
                    $userId = $db->lastInsertId();

                    if ($regType === 'provider') {
                        $bizName = $fd['business_name']    ?? '';
                        $catId   = (int)($fd['category_id']       ?? 0);
                        $bio     = $fd['bio']              ?? '';
                        $exp     = (int)($fd['experience_years']   ?? 0);
                        $price   = (float)($fd['base_price']       ?? 0);
                        $address = $fd['address']          ?? '';
                        if (!$bizName || !$catId || !$price) {
                            throw new Exception('Please fill in all provider details (business name, category, base price).');
                        }
                        $insP = $db->prepare("INSERT INTO providers (user_id, category_id, business_name, bio, experience_years, base_price, address, approval_status) VALUES (?,?,?,?,?,?,?,'pending')");
                        $insP->execute([$userId, $catId, $bizName, $bio, $exp, $price, $address]);
                    }

                    $db->commit();

                    // Clean up registration session keys
                    foreach (['reg_otp','reg_otp_expiry','reg_otp_phone','reg_otp_attempts','reg_otp_verified','reg_otp_verified_at','reg_form_data'] as $k) {
                        unset($_SESSION[$k]);
                    }

                    $result = login($email, $password);
                    if ($result['success']) {
                        switch ($result['role']) {
                            case 'admin':    redirect(APP_URL . '/modules/admin/dashboard.php');
                            case 'provider': redirect(APP_URL . '/modules/provider/dashboard.php');
                            default:
                                // If this new user is a pending provider, send to the pending page
                                $provCheck = $db->prepare("SELECT id FROM providers WHERE user_id=? AND approval_status='pending'");
                                $provCheck->execute([$userId]);
                                if ($provCheck->fetch()) {
                                    redirect(APP_URL . '/modules/provider/pending.php');
                                }
                                redirect(APP_URL . '/modules/user/dashboard.php');
                        }
                    }
                    redirect(APP_URL . '/login.php');
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = $e->getMessage() ?: 'Registration failed. Please try again.';
                }
            }
        }
    }
    $type = $_SESSION['reg_form_data']['reg_type'] ?? 'customer';
}

// ── STEP 1: Validate form & save to session, show OTP panel ───────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'validate_form') {
    verifyCsrf();
    $regType  = $_POST['reg_type']  ?? 'customer';
    $name     = sanitize($_POST['name']  ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $phone    = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$phone || !$password || !$confirm) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = 'Please enter a valid 10-digit mobile number.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'This email is already registered. <a href="login.php">Login instead?</a>';
        } else {
            // Save form data to session for use after OTP
            $_SESSION['reg_form_data'] = [
                'reg_type'         => $regType,
                'name'             => $name,
                'email'            => $email,
                'phone'            => $phone,
                'password'         => $password,
                'business_name'    => sanitize($_POST['business_name']    ?? ''),
                'category_id'      => (int)($_POST['category_id']         ?? 0),
                'bio'              => sanitize($_POST['bio']               ?? ''),
                'experience_years' => (int)($_POST['experience_years']     ?? 0),
                'base_price'       => (float)($_POST['base_price']         ?? 0),
                'address'          => sanitize($_POST['address']           ?? ''),
            ];
            // Clear any previous OTP verification
            foreach (['reg_otp','reg_otp_expiry','reg_otp_phone','reg_otp_attempts','reg_otp_verified','reg_otp_verified_at'] as $k) {
                unset($_SESSION[$k]);
            }
            // Redirect to OTP step (GET to avoid resubmit on refresh)
            redirect(APP_URL . '/register.php?step=otp');
        }
    }
    $type = $regType;
}

// Determine current step
$step = $_GET['step'] ?? 'form';
// If someone visits ?step=otp without going through form, redirect back
if ($step === 'otp' && empty($_SESSION['reg_form_data'])) {
    redirect(APP_URL . '/register.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register | HomeServe</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?= APP_URL ?>/assets/css/main.css" rel="stylesheet">
<style>
body {
  font-family: 'Inter', sans-serif;
  background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #312e81 100%);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}
.register-card {
  background: #fff;
  border-radius: 24px;
  box-shadow: 0 25px 60px rgba(0,0,0,.35);
  width: 100%;
  max-width: 580px;
  overflow: hidden;
}
.register-header {
  background: linear-gradient(135deg, #0f172a, #1e3a8a);
  padding: 1.75rem 2rem;
  text-align: center;
  color: #fff;
}
.register-header .logo { font-size: 1.5rem; font-weight: 800; }
.register-header .logo i { color: #60a5fa; }
.register-body { padding: 2rem; }
.role-toggle { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-bottom: 1.5rem; background: #f1f5f9; border-radius: 12px; padding: .4rem; }
.role-btn { display: flex; align-items: center; justify-content: center; gap: .5rem; padding: .65rem 1rem; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: .875rem; color: #64748b; transition: all .2s ease; border: none; background: transparent; width: 100%; }
.role-btn.active { background: #fff; color: #1e293b; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
.role-btn.active.customer { color: #2563eb; }
.role-btn.active.provider { color: #059669; }
.step-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.25rem; margin-bottom: 1rem; }
.step-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: .875rem; display: flex; align-items: center; gap: .4rem; }
.step-num { width: 20px; height: 20px; border-radius: 50%; background: #2563eb; color: #fff; font-size: .65rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
.provider-box { background: #f0fdf4; border-color: #bbf7d0; }
.provider-box .step-num { background: #059669; }
.strength-bar { height: 4px; border-radius: 2px; background: #e2e8f0; margin-top: 5px; overflow: hidden; }
.strength-fill { height: 100%; border-radius: 2px; transition: all .3s; }

/* ── OTP Step styles ───────────────────────────────────── */
.otp-wrapper {
  text-align: center;
  padding: 1rem 0 .5rem;
}
.otp-icon {
  width: 72px; height: 72px; border-radius: 50%;
  background: linear-gradient(135deg,#2563eb,#7c3aed);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 1rem;
  font-size: 2rem; color: #fff;
}
.otp-inputs { display: flex; gap: .5rem; justify-content: center; margin: 1.25rem 0; }
.otp-digit {
  width: 48px; height: 56px;
  border: 2px solid #e2e8f0; border-radius: 12px;
  text-align: center; font-size: 1.4rem; font-weight: 700;
  color: #1e293b; outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.otp-digit:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.15); }
.otp-digit.filled { border-color: #2563eb; background: #eff6ff; }
.otp-digit.error  { border-color: #dc2626; background: #fef2f2; }
.otp-digit.success{ border-color: #059669; background: #f0fdf4; }
.progress-steps { display: flex; align-items: center; gap: 0; margin-bottom: 1.5rem; }
.prog-step { flex: 1; text-align: center; }
.prog-dot {
  width: 28px; height: 28px; border-radius: 50%;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: .75rem; font-weight: 700; margin-bottom: .25rem;
}
.prog-dot.done  { background: #059669; color: #fff; }
.prog-dot.active{ background: #2563eb; color: #fff; }
.prog-dot.pending{ background: #e2e8f0; color: #94a3b8; }
.prog-line { flex: 1; height: 2px; }
.prog-line.done { background: #059669; }
.prog-line.pending { background: #e2e8f0; }
.prog-label { font-size: .65rem; font-weight: 600; color: #64748b; }
</style>
</head>
<body>
<div class="register-card">
  <div class="register-header">
    <div class="logo"><img src="<?= APP_URL ?>/assets/images/logo.svg" alt="HomeServe" height="45"></div>
    <p class="mb-0 mt-1 opacity-75" style="font-size:.82rem">Create your free account</p>
  </div>
  <div class="register-body">

    <?php if ($error): ?>
    <div class="alert alert-danger py-2 d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
      <span><?= $error ?></span>
    </div>
    <?php endif; ?>

    <?php if ($step === 'otp'): ?>
    <!-- ════════════════════════════════════════════════
         STEP 2 — OTP Verification
    ════════════════════════════════════════════════ -->

    <!-- Progress indicator -->
    <div class="progress-steps">
      <div class="prog-step">
        <div class="prog-dot done"><i class="bi bi-check-lg"></i></div>
        <div class="prog-label">Details</div>
      </div>
      <div class="prog-line done"></div>
      <div class="prog-step">
        <div class="prog-dot active">2</div>
        <div class="prog-label">Verify</div>
      </div>
      <div class="prog-line pending"></div>
      <div class="prog-step">
        <div class="prog-dot pending">3</div>
        <div class="prog-label">Done</div>
      </div>
    </div>

    <div class="otp-wrapper">
      <div class="otp-icon"><i class="bi bi-phone-vibrate"></i></div>
      <h5 class="fw-800 mb-1">Verify Your Mobile Number</h5>
      <p class="text-muted small mb-0">
        Enter your mobile number to receive a 6-digit OTP.<br>
        <strong>+91 <?= htmlspecialchars($_SESSION['reg_form_data']['phone'] ?? '') ?></strong>
      </p>

      <!-- Phone row (shown first) -->
      <div id="phoneRow" class="mt-3">
        <div class="input-group" style="max-width:320px;margin:0 auto">
          <span class="input-group-text"><i class="bi bi-telephone-fill text-primary"></i></span>
          <input type="tel" id="otpPhone" class="form-control fw-600"
            value="<?= htmlspecialchars($_SESSION['reg_form_data']['phone'] ?? '') ?>"
            placeholder="10-digit mobile number" maxlength="10"
            oninput="this.value=this.value.replace(/[^0-9]/g,'').substring(0,10)">
          <button type="button" class="btn btn-primary" id="sendOtpBtn" onclick="sendOTP()">
            <i class="bi bi-send"></i> Send OTP
          </button>
        </div>
        <div id="otpSendStatus" class="small mt-2"></div>
      </div>

      <!-- OTP digit input row (shown after send) -->
      <div id="otpInputRow" style="display:none" class="mt-3">
        <div class="alert alert-info py-2 small mb-3" id="otpSentMsg"></div>
        <div class="otp-inputs" id="otpDigits">
          <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" data-idx="0">
          <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" data-idx="1">
          <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" data-idx="2">
          <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" data-idx="3">
          <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" data-idx="4">
          <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" data-idx="5">
        </div>
        <div id="otpVerifyStatus" class="small mb-2"></div>
        <button type="button" class="btn btn-success px-4 py-2 fw-700" id="verifyOtpBtn" onclick="verifyOTP()">
          <i class="bi bi-check-circle-fill me-1"></i> Verify & Create Account
        </button>
        <div class="mt-3 d-flex gap-2 justify-content-center">
          <a href="<?= APP_URL ?>/register.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Edit Details
          </a>
          <button type="button" class="btn btn-sm btn-outline-primary" id="resendBtn" onclick="sendOTP(true)" disabled>
            <i class="bi bi-arrow-clockwise"></i> Resend (<span id="resendTimer">60</span>s)
          </button>
        </div>
      </div>

      <!-- Verified state (briefly shown before redirect) -->
      <div id="otpVerifiedRow" style="display:none" class="mt-3 text-center">
        <div id="lottieVerified" style="width:160px;height:160px;margin:0 auto"></div>
        <p class="fw-700 text-success mb-1" style="font-size:1.1rem">Mobile Verified!</p>
        <p class="text-muted small mb-0">Creating your account...</p>
      </div>
    </div>

    <!-- Hidden form submitted via JS after OTP verify -->
    <form method="POST" id="completeRegForm" style="display:none">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="complete_register">
    </form>

    <?php else: ?>
    <!-- ════════════════════════════════════════════════
         STEP 1 — Registration Form
    ════════════════════════════════════════════════ -->

    <!-- Progress indicator -->
    <div class="progress-steps">
      <div class="prog-step">
        <div class="prog-dot active">1</div>
        <div class="prog-label">Details</div>
      </div>
      <div class="prog-line pending"></div>
      <div class="prog-step">
        <div class="prog-dot pending">2</div>
        <div class="prog-label">Verify</div>
      </div>
      <div class="prog-line pending"></div>
      <div class="prog-step">
        <div class="prog-dot pending">3</div>
        <div class="prog-label">Done</div>
      </div>
    </div>

    <!-- Role Toggle -->
    <div class="role-toggle">
      <button type="button" class="role-btn customer <?= $type !== 'provider' ? 'active' : '' ?>" onclick="switchRole('customer')">
        <i class="bi bi-person-fill"></i> I'm a Customer
      </button>
      <button type="button" class="role-btn provider <?= $type === 'provider' ? 'active' : '' ?>" onclick="switchRole('provider')">
        <i class="bi bi-tools"></i> I'm a Provider
      </button>
    </div>

    <form method="POST" id="registerForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="validate_form">
      <input type="hidden" name="reg_type" id="regType" value="<?= $type === 'provider' ? 'provider' : 'customer' ?>">

      <!-- Personal Info -->
      <div class="step-box">
        <div class="step-label"><span class="step-num">1</span> Personal Information</div>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input type="text" name="name" class="form-control" placeholder="Your full name"
                     value="<?= sanitize($_POST['name'] ?? '') ?>" required autofocus>
            </div>
          </div>
          <div class="col-sm-6">
            <label class="form-label">Email Address <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="email" name="email" class="form-control" placeholder="you@example.com"
                     value="<?= sanitize($_POST['email'] ?? '') ?>" required>
            </div>
          </div>
          <div class="col-sm-6">
            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-telephone"></i></span>
              <input type="tel" name="phone" class="form-control" placeholder="98765 43210"
                     value="<?= sanitize($_POST['phone'] ?? '') ?>"
                     maxlength="10" pattern="[0-9]{10}"
                     oninput="this.value=this.value.replace(/[^0-9]/g,'').substring(0,10)"
                     required>
            </div>
          </div>
        </div>
      </div>

      <!-- Password -->
      <div class="step-box">
        <div class="step-label"><span class="step-num" style="background:#7c3aed">2</span> Set Password</div>
        <div class="row g-3">
          <div class="col-sm-6">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" name="password" id="password" class="form-control"
                     placeholder="Min 6 characters" required oninput="checkStrength(this.value)">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password','eye1')">
                <i id="eye1" class="bi bi-eye"></i>
              </button>
            </div>
            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
            <small id="strengthText" style="font-size:.7rem"></small>
          </div>
          <div class="col-sm-6">
            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
              <input type="password" name="confirm_password" id="confirm" class="form-control"
                     placeholder="Repeat password" required oninput="checkMatch()">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('confirm','eye2')">
                <i id="eye2" class="bi bi-eye"></i>
              </button>
            </div>
            <small id="matchText" style="font-size:.7rem"></small>
          </div>
        </div>
      </div>

      <!-- Provider Details -->
      <div id="providerSection" style="display:<?= $type === 'provider' ? 'block' : 'none' ?>">
        <div class="step-box provider-box">
          <div class="step-label"><span class="step-num">3</span> Provider Details</div>
          <div class="alert alert-success py-2 mb-3" style="font-size:.78rem">
            <i class="bi bi-info-circle me-1"></i>
            Your profile will be reviewed by admin before appearing to customers. This usually takes 24 hours.
          </div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Business Name <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-building"></i></span>
                <input type="text" name="business_name" class="form-control"
                       placeholder="e.g. Patel Plumbing Services"
                       value="<?= sanitize($_POST['business_name'] ?? '') ?>">
              </div>
            </div>
            <div class="col-sm-6">
              <label class="form-label">Service Category <span class="text-danger">*</span></label>
              <select name="category_id" class="form-select">
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                  <?= sanitize($cat['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-sm-6">
              <label class="form-label">Base Price (₹) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">₹</span>
                <input type="number" name="base_price" class="form-control" placeholder="500"
                       min="0" value="<?= sanitize($_POST['base_price'] ?? '') ?>">
              </div>
            </div>
            <div class="col-sm-6">
              <label class="form-label">Years of Experience</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-briefcase"></i></span>
                <input type="number" name="experience_years" class="form-control" placeholder="5"
                       min="0" max="50" value="<?= sanitize($_POST['experience_years'] ?? '') ?>">
              </div>
            </div>
            <div class="col-sm-6">
              <label class="form-label">Service Area</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                <input type="text" name="address" class="form-control" placeholder="Your city/area"
                       value="<?= sanitize($_POST['address'] ?? '') ?>">
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">About Your Services</label>
              <textarea name="bio" class="form-control" rows="2"
                        placeholder="Describe your expertise and what you offer..."
                        style="resize:none"><?= sanitize($_POST['bio'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Terms -->
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="terms" required>
        <label class="form-check-label" for="terms" style="font-size:.82rem">
          I agree to the <a href="#" class="fw-600">Terms of Service</a> and
          <a href="#" class="fw-600">Privacy Policy</a>
        </label>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-2 fw-700">
        <i class="bi bi-arrow-right-circle-fill me-1"></i>
        <span id="submitText">Continue to Mobile Verification</span>
      </button>
    </form>

    <div class="text-center mt-3" style="font-size:.875rem">
      Already have an account? <a href="<?= APP_URL ?>/login.php" class="fw-700">Sign in</a>
    </div>

    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php if ($step !== 'otp'): ?>
// ── STEP 1 JS ──────────────────────────────────────────
function switchRole(role) {
  document.getElementById('regType').value = role;
  document.getElementById('providerSection').style.display = role === 'provider' ? 'block' : 'none';
  document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
  document.querySelector('.role-btn.' + role).classList.add('active');
}
function togglePwd(id, eyeId) {
  const p = document.getElementById(id), i = document.getElementById(eyeId);
  p.type = p.type === 'password' ? 'text' : 'password';
  i.className = p.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
function checkStrength(val) {
  let s = 0;
  if (val.length >= 6) s++;
  if (val.length >= 10) s++;
  if (/[A-Z]/.test(val)) s++;
  if (/[0-9]/.test(val)) s++;
  if (/[^A-Za-z0-9]/.test(val)) s++;
  const levels = [
    {w:'0%', c:'#e2e8f0', t:''},
    {w:'25%', c:'#dc2626', t:'Weak'},
    {w:'50%', c:'#d97706', t:'Fair'},
    {w:'75%', c:'#2563eb', t:'Good'},
    {w:'100%', c:'#059669', t:'Strong'}
  ];
  const l = levels[Math.min(s, 4)];
  const f = document.getElementById('strengthFill');
  f.style.width = l.w; f.style.background = l.c;
  const t = document.getElementById('strengthText');
  t.textContent = l.t; t.style.color = l.c;
}
function checkMatch() {
  const p1 = document.getElementById('password').value;
  const p2 = document.getElementById('confirm').value;
  const el  = document.getElementById('matchText');
  if (!p2) { el.textContent = ''; return; }
  el.textContent = p1 === p2 ? '✓ Passwords match' : '✗ Passwords do not match';
  el.style.color  = p1 === p2 ? '#059669' : '#dc2626';
}

<?php else: ?>
// ── STEP 2 JS ──────────────────────────────────────────

// ── OTP digit-box navigation ──
const digits = document.querySelectorAll('.otp-digit');
digits.forEach((inp, i) => {
  inp.addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g,'').slice(-1);
    if (this.value) {
      this.classList.add('filled');
      if (i < 5) digits[i+1].focus();
      // Auto-verify when all filled
      if (i === 5 && getOTPValue().length === 6) verifyOTP();
    } else {
      this.classList.remove('filled');
    }
  });
  inp.addEventListener('keydown', function(e) {
    if (e.key === 'Backspace' && !this.value && i > 0) {
      digits[i-1].focus();
    }
  });
  inp.addEventListener('paste', function(e) {
    e.preventDefault();
    const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g,'');
    if (pasted.length === 6) {
      digits.forEach((d, j) => { d.value = pasted[j] || ''; d.classList.toggle('filled', !!d.value); });
      digits[5].focus();
      verifyOTP();
    }
  });
});

function getOTPValue() {
  return Array.from(digits).map(d => d.value).join('');
}

let resendInterval = null;

function sendOTP(isResend = false) {
  const phone = document.getElementById('otpPhone').value.trim();
  if (phone.length !== 10) {
    document.getElementById('otpSendStatus').innerHTML =
      '<span class="text-danger"><i class="bi bi-x-circle"></i> Please enter a valid 10-digit mobile number.</span>';
    return;
  }
  const btn = document.getElementById('sendOtpBtn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...'; }

  const formData = new FormData();
  formData.append('phone', phone);

  fetch('<?= APP_URL ?>/api/send_otp.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        document.getElementById('phoneRow').style.display    = 'none';
        document.getElementById('otpInputRow').style.display = 'block';
        document.getElementById('otpSentMsg').innerHTML =
          '<i class="bi bi-check-circle-fill text-success"></i> ' + data.message;
        digits.forEach(d => { d.value = ''; d.classList.remove('filled','error','success'); });
        digits[0].focus();
        startResendTimer();
      } else {
        document.getElementById('otpSendStatus').innerHTML =
          '<span class="text-danger"><i class="bi bi-x-circle"></i> ' + data.message + '</span>';
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-send"></i> Send OTP'; }
      }
    })
    .catch(() => {
      document.getElementById('otpSendStatus').innerHTML =
        '<span class="text-danger"><i class="bi bi-x-circle"></i> Network error. Please try again.</span>';
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-send"></i> Send OTP'; }
    });
}

function verifyOTP() {
  const otp = getOTPValue();
  if (otp.length !== 6) {
    document.getElementById('otpVerifyStatus').innerHTML =
      '<span class="text-danger"><i class="bi bi-x-circle"></i> Please enter the full 6-digit OTP.</span>';
    return;
  }
  const btn = document.getElementById('verifyOtpBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Verifying...';
  digits.forEach(d => d.classList.remove('error','success'));

  const formData = new FormData();
  formData.append('otp', otp);

  fetch('<?= APP_URL ?>/api/verify_otp.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        clearInterval(resendInterval);
        digits.forEach(d => { d.classList.add('success'); d.classList.remove('error'); });
        document.getElementById('otpVerifyStatus').innerHTML =
          '<span class="text-success fw-700"><i class="bi bi-check-circle-fill"></i> Verified! Creating your account...</span>';
        document.getElementById('otpInputRow').style.display    = 'none';
        document.getElementById('otpVerifiedRow').style.display = 'block';
        // Submit hidden form to complete registration
        // Play Lottie animation then submit
        lottie.loadAnimation({
          container: document.getElementById('lottieVerified'),
          renderer: 'svg',
          loop: false,
          autoplay: true,
          path: '<?= APP_URL ?>/assets/animations/Verified.json'
        });
        setTimeout(() => document.getElementById('completeRegForm').submit(), 2200);
      } else {
        digits.forEach(d => { d.classList.add('error'); d.classList.remove('success'); });
        document.getElementById('otpVerifyStatus').innerHTML =
          '<span class="text-danger"><i class="bi bi-x-circle"></i> ' + data.message + '</span>';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Verify & Create Account';
        // Clear digits for re-entry
        setTimeout(() => {
          digits.forEach(d => { d.value = ''; d.classList.remove('error'); });
          digits[0].focus();
        }, 1200);
      }
    })
    .catch(() => {
      document.getElementById('otpVerifyStatus').innerHTML =
        '<span class="text-danger"><i class="bi bi-x-circle"></i> Network error. Please try again.</span>';
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Verify & Create Account';
    });
}

function startResendTimer() {
  let seconds = 60;
  const timerEl  = document.getElementById('resendTimer');
  const resendBtn = document.getElementById('resendBtn');
  resendBtn.disabled = true;
  timerEl.textContent = seconds;
  clearInterval(resendInterval);
  resendInterval = setInterval(() => {
    seconds--;
    timerEl.textContent = seconds;
    if (seconds <= 0) {
      clearInterval(resendInterval);
      resendBtn.disabled = false;
      resendBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Resend OTP';
    }
  }, 1000);
}

// Auto-send OTP on page load if phone already known from session
window.addEventListener('DOMContentLoaded', () => {
  const phoneEl = document.getElementById('otpPhone');
  if (phoneEl && phoneEl.value.length === 10) {
    // Small delay so page renders first
    setTimeout(sendOTP, 400);
  }
});

<?php endif; ?>
</script>
</body>
</html>