<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
if (currentRole() !== 'provider') redirect(APP_URL . '/modules/user/dashboard.php');
$db  = getDB();
$pid = currentProviderId();
$uid = currentUserId();
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'photo') {
        verifyCsrf();
        if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Please select a valid image file.';
        } else {
            $file     = $_FILES['profile_photo'];
            $allowed  = ['image/jpeg','image/jpg','image/png','image/webp'];
            $maxSize  = 2 * 1024 * 1024;
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mimeType, $allowed)) {
                $error = 'Only JPG, PNG, or WEBP images are allowed.';
            } elseif ($file['size'] > $maxSize) {
                $error = 'Image must be under 2MB.';
            } else {
                $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename  = 'provider_' . $pid . '_' . time() . '.' . strtolower($ext);
                $uploadDir = __DIR__ . '/../../uploads/providers/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $oldStmt = $db->prepare("SELECT profile_photo FROM providers WHERE id=?");
                $oldStmt->execute([$pid]);
                $oldPhoto = $oldStmt->fetchColumn();
                if ($oldPhoto && file_exists($uploadDir . $oldPhoto)) unlink($uploadDir . $oldPhoto);
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    $db->prepare("UPDATE providers SET profile_photo=? WHERE id=?")->execute([$filename, $pid]);
                    $success = 'Profile photo updated successfully!';
                } else {
                    $error = 'Upload failed. Please check folder permissions.';
                }
            }
        }

    } elseif ($action === 'profile') {
        verifyCsrf();
        $name     = sanitize($_POST['name']  ?? '');
        $newPhone = sanitize($_POST['phone'] ?? '');
        $bname    = sanitize($_POST['business_name']    ?? '');
        $bio      = sanitize($_POST['bio']              ?? '');
        $exp      = (int)($_POST['experience_years']    ?? 0);
        $price    = (float)($_POST['base_price']        ?? 0);
        $addr     = sanitize($_POST['address']          ?? '');

        if (!$name) {
            $error = 'Name is required.';
        } else {
            // Get current phone
            $cur = $db->prepare("SELECT phone FROM users WHERE id=?");
            $cur->execute([$uid]);
            $currentPhone = $cur->fetchColumn();

            $cleanPhone   = preg_replace('/[^0-9]/', '', $newPhone);
            $phoneChanged = ($cleanPhone !== '' && $cleanPhone !== $currentPhone);

            if ($phoneChanged) {
                if (strlen($cleanPhone) !== 10) {
                    $error = 'Please enter a valid 10-digit mobile number.';
                } else {
                    $otpOk = (
                        isset($_SESSION['profile_otp_verified'])      &&
                        $_SESSION['profile_otp_verified'] === true    &&
                        isset($_SESSION['profile_otp_phone'])         &&
                        $_SESSION['profile_otp_phone'] === '+91' . $cleanPhone &&
                        isset($_SESSION['profile_otp_verified_at'])   &&
                        (time() - $_SESSION['profile_otp_verified_at']) < 600
                    );
                    if (!$otpOk) {
                        $error = 'Please verify the new mobile number with OTP before saving.';
                    } else {
                        $db->prepare("UPDATE users SET name=?, phone=? WHERE id=?")->execute([$name, $cleanPhone, $uid]);
                        $db->prepare("UPDATE providers SET business_name=?, bio=?, experience_years=?, base_price=?, address=? WHERE id=?")->execute([$bname, $bio, $exp, $price, $addr, $pid]);
                        $_SESSION['name'] = $name;
                        foreach (['profile_otp_verified','profile_otp_phone','profile_otp_verified_at',
                                  'profile_otp','profile_otp_expiry','profile_otp_attempts'] as $k) {
                            unset($_SESSION[$k]);
                        }
                        $success = 'Profile updated successfully!';
                    }
                }
            } else {
                // Phone unchanged — save everything normally
                $db->prepare("UPDATE users SET name=?, phone=? WHERE id=?")->execute([$name, $currentPhone, $uid]);
                $db->prepare("UPDATE providers SET business_name=?, bio=?, experience_years=?, base_price=?, address=? WHERE id=?")->execute([$bname, $bio, $exp, $price, $addr, $pid]);
                $_SESSION['name'] = $name;
                $success = 'Profile updated successfully!';
            }
        }

    } elseif ($action === 'password') {
        verifyCsrf();
        $curr = $_POST['current_password'] ?? '';
        $new  = $_POST['new_password']     ?? '';
        $conf = $_POST['confirm_password'] ?? '';
        $u    = $db->prepare("SELECT password FROM users WHERE id=?");
        $u->execute([$uid]); $u = $u->fetch();
        if (!password_verify($curr, $u['password']))  $error = 'Current password is incorrect.';
        elseif ($new !== $conf)                        $error = 'New passwords do not match.';
        elseif (strlen($new) < 6)                     $error = 'Password must be at least 6 characters.';
        else {
            $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([hashPassword($new), $uid]);
            $success = 'Password updated successfully!';
        }

    } elseif ($action === 'remove_photo') {
        verifyCsrf();
        $oldStmt = $db->prepare("SELECT profile_photo FROM providers WHERE id=?");
        $oldStmt->execute([$pid]);
        $oldPhoto  = $oldStmt->fetchColumn();
        $uploadDir = __DIR__ . '/../../uploads/providers/';
        if ($oldPhoto && file_exists($uploadDir . $oldPhoto)) unlink($uploadDir . $oldPhoto);
        $db->prepare("UPDATE providers SET profile_photo=NULL WHERE id=?")->execute([$pid]);
        $success = 'Profile photo removed.';
    }
}

$user = $db->prepare("SELECT u.*, p.*, c.name as cat_name
    FROM users u JOIN providers p ON p.user_id=u.id JOIN categories c ON p.category_id=c.id WHERE u.id=?");
$user->execute([$uid]);
$user = $user->fetch();

$avgRating = $db->prepare("SELECT ROUND(AVG(rating),1) as avg, COUNT(*) as total FROM reviews WHERE provider_id=?");
$avgRating->execute([$pid]);
$ratingData = $avgRating->fetch();

$photoUrl = $user['profile_photo']
    ? APP_URL . '/uploads/providers/' . $user['profile_photo']
    : null;

$pageTitle = 'Provider Profile';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<style>
/* ── Phone OTP widget (same as user profile) ── */
.phone-otp-box{background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:12px;padding:1rem;margin-top:.6rem}
.phone-otp-box.verified{background:#f0fdf4;border-color:#86efac}
.otp-mini-digits{display:flex;gap:.35rem;margin:.5rem 0}
.otp-mini-digit{width:38px;height:44px;border:2px solid #e2e8f0;border-radius:10px;text-align:center;font-size:1.2rem;font-weight:700;color:#1e293b;outline:none;transition:border-color .15s}
.otp-mini-digit:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12)}
.otp-mini-digit.filled {border-color:#2563eb;background:#eff6ff}
.otp-mini-digit.success{border-color:#059669;background:#f0fdf4}
.otp-mini-digit.error  {border-color:#dc2626;background:#fef2f2}
</style>
<div class="dashboard-wrapper">
<div class="overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../../includes/sidebar_provider.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle"><i class="bi bi-list fs-5"></i></button>
      <div class="topbar-title">My Profile</div>
    </div>
  </div>
  <div class="page-content">
    <?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2">
      <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
    </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success d-flex align-items-center gap-2">
      <i class="bi bi-check-circle-fill"></i> <?= $success ?>
    </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- LEFT: Profile Card -->
      <div class="col-md-4">
        <div class="card text-center p-4">
          <div class="position-relative d-inline-block mx-auto mb-3" style="width:100px">
            <?php if ($photoUrl): ?>
            <img src="<?= $photoUrl ?>?v=<?= time() ?>" alt="Profile Photo"
                 style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0;box-shadow:0 4px 16px rgba(0,0,0,.12)">
            <?php else: ?>
            <div style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#059669,#16a34a);color:#fff;display:flex;align-items:center;justify-content:center;font-size:2.2rem;font-weight:800;border:3px solid #e2e8f0;box-shadow:0 4px 16px rgba(0,0,0,.12)">
              <?= strtoupper(substr($user['name'],0,1)) ?>
            </div>
            <?php endif; ?>
            <label for="quickPhotoInput" style="position:absolute;bottom:2px;right:2px;width:28px;height:28px;background:#2563eb;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,.2)" title="Change photo">
              <i class="bi bi-camera-fill text-white" style="font-size:.75rem"></i>
            </label>
          </div>

          <h5 class="fw-700 mb-0"><?= sanitize($user['name']) ?></h5>
          <div class="fw-600 text-success mt-1"><?= sanitize($user['business_name']) ?></div>
          <div class="text-muted small mt-1"><?= sanitize($user['cat_name']) ?></div>
          <div class="d-flex align-items-center justify-content-center gap-1 mt-2">
            <?php $r = $ratingData['avg'] ?? 0; for ($i=1;$i<=5;$i++) echo $i<=$r ? '<i class="bi bi-star-fill text-warning" style="font-size:.8rem"></i>' : '<i class="bi bi-star text-muted opacity-25" style="font-size:.8rem"></i>'; ?>
            <span class="fw-700 small ms-1"><?= $r ?: '–' ?></span>
            <span class="text-muted small">(<?= $ratingData['total'] ?> reviews)</span>
          </div>
          <div class="mt-2 fw-700 text-primary">₹<?= number_format($user['base_price'], 0) ?>/start</div>
          <div class="text-muted small"><?= $user['experience_years'] ?> yrs experience</div>
          <div class="mt-2"><span class="badge bg-success">✓ Approved Provider</span></div>

          <form method="POST" enctype="multipart/form-data" id="quickPhotoForm" class="mt-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="photo">
            <input type="file" name="profile_photo" id="quickPhotoInput" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="submitPhotoForm()">
          </form>

          <?php if ($user['profile_photo']): ?>
          <form method="POST" class="mt-2" onsubmit="return confirm('Remove profile photo?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="remove_photo">
            <button class="btn btn-sm btn-outline-danger w-100">
              <i class="bi bi-trash me-1"></i> Remove Photo
            </button>
          </form>
          <?php endif; ?>
        </div>

        <div class="card mt-3 p-3" style="background:#fffbeb;border-color:#fde68a">
          <div class="fw-700 small mb-2" style="color:#92400e"><i class="bi bi-lightbulb me-1"></i>Photo Tips</div>
          <ul class="mb-0 ps-3" style="font-size:.78rem;color:#78350f;line-height:1.8">
            <li>Use a clear, professional photo</li>
            <li>Face should be clearly visible</li>
            <li>JPG, PNG or WEBP format</li>
            <li>Maximum file size: 2MB</li>
          </ul>
        </div>
      </div>

      <!-- RIGHT: Forms -->
      <div class="col-md-8">

        <!-- Edit Profile -->
        <div class="card mb-4">
          <div class="card-header"><span class="card-title"><i class="bi bi-person-circle me-1"></i>Edit Profile</span></div>
          <div class="card-body">
            <form method="POST" id="providerProfileForm">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="profile">
              <div class="row g-3">
                <div class="col-sm-6">
                  <label class="form-label">Full Name</label>
                  <input type="text" name="name" class="form-control" value="<?= sanitize($user['name']) ?>" required>
                </div>

                <!-- ── Phone with inline OTP ── -->
                <div class="col-sm-6">
                  <label class="form-label">
                    Phone
                    <span id="phoneVerifiedBadge" class="badge bg-success ms-1" style="display:none;font-size:.65rem">
                      <i class="bi bi-check-circle-fill"></i> Verified
                    </span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone-fill text-success"></i></span>
                    <input type="tel" name="phone" id="phoneInput" class="form-control"
                      value="<?= sanitize($user['phone'] ?? '') ?>"
                      placeholder="10-digit mobile"
                      maxlength="10"
                      oninput="this.value=this.value.replace(/[^0-9]/g,'').substring(0,10);onPhoneChange()">
                    <button type="button" class="btn btn-outline-success" id="sendPhoneOtpBtn"
                      style="display:none" onclick="sendPhoneOTP()">
                      <i class="bi bi-send"></i> Verify
                    </button>
                  </div>
                  <div id="phoneOtpStatus" class="small mt-1"></div>

                  <!-- OTP digit entry -->
                  <div id="phoneOtpBox" class="phone-otp-box" style="display:none">
                    <div class="small fw-600 mb-1 text-success">
                      <i class="bi bi-phone-vibrate"></i> Enter the 6-digit OTP sent to your number
                    </div>
                    <div class="otp-mini-digits">
                      <input type="text" class="otp-mini-digit" maxlength="1" inputmode="numeric" data-idx="0">
                      <input type="text" class="otp-mini-digit" maxlength="1" inputmode="numeric" data-idx="1">
                      <input type="text" class="otp-mini-digit" maxlength="1" inputmode="numeric" data-idx="2">
                      <input type="text" class="otp-mini-digit" maxlength="1" inputmode="numeric" data-idx="3">
                      <input type="text" class="otp-mini-digit" maxlength="1" inputmode="numeric" data-idx="4">
                      <input type="text" class="otp-mini-digit" maxlength="1" inputmode="numeric" data-idx="5">
                    </div>
                    <div id="miniOtpVerifyStatus" class="small mb-2"></div>
                    <div class="d-flex gap-2 flex-wrap">
                      <button type="button" class="btn btn-success btn-sm" id="verifyPhoneOtpBtn" onclick="verifyPhoneOTP()">
                        <i class="bi bi-check-circle"></i> Confirm
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-secondary" id="phoneResendBtn"
                        onclick="sendPhoneOTP(true)" disabled>
                        <i class="bi bi-arrow-clockwise"></i> Resend (<span id="phoneResendTimer">60</span>s)
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelPhoneOTP()">
                        Cancel
                      </button>
                    </div>
                  </div>

                  <!-- Verified state -->
                  <div id="phoneVerifiedBox" class="phone-otp-box verified" style="display:none">
                    <i class="bi bi-check-circle-fill text-success"></i>
                    <strong class="text-success"> New number verified!</strong>
                    <span class="text-muted small ms-1" id="verifiedPhoneNum"></span>
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" onclick="resetPhoneOTP()">Change</button>
                  </div>
                </div>
                <!-- ── end phone field ── -->

                <div class="col-12">
                  <label class="form-label">Business Name</label>
                  <input type="text" name="business_name" class="form-control" value="<?= sanitize($user['business_name']) ?>" required>
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Experience (years)</label>
                  <input type="number" name="experience_years" class="form-control" value="<?= $user['experience_years'] ?>" min="0">
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Base Price (₹)</label>
                  <input type="number" name="base_price" class="form-control" value="<?= $user['base_price'] ?>" min="0" step="0.01">
                </div>
                <div class="col-12">
                  <label class="form-label">Service Area / Address</label>
                  <input type="text" name="address" class="form-control" placeholder="Your city or area" value="<?= sanitize($user['address'] ?? '') ?>">
                </div>
                <div class="col-12">
                  <label class="form-label">About Your Services</label>
                  <textarea name="bio" class="form-control" rows="3" placeholder="Describe your expertise..."><?= sanitize($user['bio'] ?? '') ?></textarea>
                </div>
              </div>
              <button type="submit" class="btn btn-success mt-3 px-4">
                <i class="bi bi-check-circle me-1"></i> Save Changes
              </button>
            </form>
          </div>
        </div>

        <!-- Change Password -->
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="bi bi-shield-lock me-1"></i>Change Password</span></div>
          <div class="card-body">
            <form method="POST">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="password">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Current Password</label>
                  <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="col-sm-6">
                  <label class="form-label">New Password</label>
                  <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Confirm New Password</label>
                  <input type="password" name="confirm_password" class="form-control" required>
                </div>
              </div>
              <button type="submit" class="btn btn-warning mt-3 px-4">
                <i class="bi bi-key me-1"></i> Update Password
              </button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
</div>

<script>
function submitPhotoForm() {
  const file = document.getElementById('quickPhotoInput').files[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) { alert('File is too large. Maximum size is 2MB.'); return; }
  document.getElementById('quickPhotoForm').submit();
}

// ── Phone OTP ──────────────────────────────────────────
const originalPhone   = '<?= addslashes($user['phone'] ?? '') ?>';
let phoneOtpVerified  = false;
let phoneResendInterval = null;

function onPhoneChange() {
  const val = document.getElementById('phoneInput').value.trim();
  const sendBtn = document.getElementById('sendPhoneOtpBtn');
  if (phoneOtpVerified) resetPhoneOTP();
  if (val.length === 10 && val !== originalPhone) {
    sendBtn.style.display = 'block';
    document.getElementById('phoneOtpStatus').innerHTML =
      '<span class="text-warning small"><i class="bi bi-exclamation-triangle"></i> New number — please verify with OTP before saving.</span>';
  } else {
    sendBtn.style.display = 'none';
    document.getElementById('phoneOtpStatus').innerHTML = '';
  }
}

function sendPhoneOTP(isResend = false) {
  const phone = document.getElementById('phoneInput').value.trim();
  if (phone.length !== 10) return;
  const btn = document.getElementById('sendPhoneOtpBtn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
  document.getElementById('phoneOtpStatus').innerHTML =
    '<span class="text-muted small"><i class="bi bi-hourglass-split"></i> Sending OTP...</span>';
  const fd = new FormData(); fd.append('phone', phone);
  fetch('<?= APP_URL ?>/api/send_otp.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        document.getElementById('sendPhoneOtpBtn').style.display = 'none';
        document.getElementById('phoneOtpBox').style.display     = 'block';
        document.getElementById('phoneOtpStatus').innerHTML =
          '<span class="text-success small"><i class="bi bi-check-circle"></i> ' + data.message + '</span>';
        document.querySelectorAll('.otp-mini-digit').forEach(d => {
          d.value = ''; d.classList.remove('filled','success','error');
        });
        document.querySelector('.otp-mini-digit[data-idx="0"]').focus();
        startPhoneResendTimer();
      } else {
        document.getElementById('phoneOtpStatus').innerHTML =
          '<span class="text-danger small"><i class="bi bi-x-circle"></i> ' + data.message + '</span>';
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-send"></i> Verify'; }
      }
    })
    .catch(() => {
      document.getElementById('phoneOtpStatus').innerHTML =
        '<span class="text-danger small"><i class="bi bi-x-circle"></i> Network error. Try again.</span>';
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-send"></i> Verify'; }
    });
}

function verifyPhoneOTP() {
  const digits = document.querySelectorAll('.otp-mini-digit');
  const otp = Array.from(digits).map(d => d.value).join('');
  if (otp.length !== 6) {
    document.getElementById('miniOtpVerifyStatus').innerHTML =
      '<span class="text-danger"><i class="bi bi-x-circle"></i> Enter full 6-digit OTP.</span>';
    return;
  }
  const btn = document.getElementById('verifyPhoneOtpBtn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
  const fd = new FormData(); fd.append('otp', otp);
  fetch('<?= APP_URL ?>/api/verify_otp.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        clearInterval(phoneResendInterval);
        phoneOtpVerified = true;
        const phone = document.getElementById('phoneInput').value;
        document.querySelectorAll('.otp-mini-digit').forEach(d => {
          d.classList.add('success'); d.classList.remove('error');
        });
        document.getElementById('phoneOtpBox').style.display      = 'none';
        document.getElementById('phoneVerifiedBox').style.display  = 'block';
        document.getElementById('phoneVerifiedBadge').style.display= 'inline-block';
        document.getElementById('verifiedPhoneNum').textContent    = '+91 ' + phone;
        document.getElementById('phoneOtpStatus').innerHTML        = '';
        document.getElementById('phoneInput').readOnly             = true;
      } else {
        document.querySelectorAll('.otp-mini-digit').forEach(d => {
          d.classList.add('error'); d.classList.remove('success');
        });
        document.getElementById('miniOtpVerifyStatus').innerHTML =
          '<span class="text-danger"><i class="bi bi-x-circle"></i> ' + data.message + '</span>';
        btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-circle"></i> Confirm';
        setTimeout(() => {
          document.querySelectorAll('.otp-mini-digit').forEach(d => { d.value=''; d.classList.remove('error'); });
          document.querySelector('.otp-mini-digit[data-idx="0"]').focus();
        }, 1200);
      }
    })
    .catch(() => {
      document.getElementById('miniOtpVerifyStatus').innerHTML =
        '<span class="text-danger"><i class="bi bi-x-circle"></i> Network error.</span>';
      btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-circle"></i> Confirm';
    });
}

function cancelPhoneOTP() {
  document.getElementById('phoneOtpBox').style.display      = 'none';
  document.getElementById('sendPhoneOtpBtn').style.display  = 'block';
  document.getElementById('sendPhoneOtpBtn').disabled       = false;
  document.getElementById('sendPhoneOtpBtn').innerHTML      = '<i class="bi bi-send"></i> Verify';
  document.getElementById('phoneOtpStatus').innerHTML       = '';
  clearInterval(phoneResendInterval);
}

function resetPhoneOTP() {
  phoneOtpVerified = false;
  document.getElementById('phoneVerifiedBox').style.display   = 'none';
  document.getElementById('phoneVerifiedBadge').style.display = 'none';
  document.getElementById('phoneInput').readOnly              = false;
  document.getElementById('phoneOtpStatus').innerHTML         = '';
  document.getElementById('phoneOtpBox').style.display        = 'none';
  onPhoneChange();
}

// Block form submit if phone changed but not verified
document.getElementById('providerProfileForm').addEventListener('submit', function(e) {
  const phone = document.getElementById('phoneInput').value.trim();
  if (phone.length === 10 && phone !== originalPhone && !phoneOtpVerified) {
    e.preventDefault();
    document.getElementById('phoneOtpStatus').innerHTML =
      '<span class="text-danger fw-600"><i class="bi bi-exclamation-triangle"></i> Please verify the new number with OTP before saving.</span>';
    document.getElementById('sendPhoneOtpBtn').style.display = 'block';
    document.getElementById('sendPhoneOtpBtn').scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
});

// ── Mini digit keyboard navigation ────────────────────
document.querySelectorAll('.otp-mini-digit').forEach((inp, i, all) => {
  inp.addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g,'').slice(-1);
    if (this.value) {
      this.classList.add('filled');
      if (i < 5) all[i+1].focus();
      if (i===5 && Array.from(all).map(d=>d.value).join('').length===6) verifyPhoneOTP();
    } else { this.classList.remove('filled'); }
  });
  inp.addEventListener('keydown', function(e) {
    if (e.key==='Backspace' && !this.value && i>0) all[i-1].focus();
  });
  inp.addEventListener('paste', function(e) {
    e.preventDefault();
    const p = (e.clipboardData||window.clipboardData).getData('text').replace(/[^0-9]/g,'');
    if (p.length===6) {
      all.forEach((d,j) => { d.value=p[j]||''; d.classList.toggle('filled',!!d.value); });
      all[5].focus(); verifyPhoneOTP();
    }
  });
});

function startPhoneResendTimer() {
  let s = 60;
  const timerEl = document.getElementById('phoneResendTimer');
  const btn     = document.getElementById('phoneResendBtn');
  btn.disabled  = true; timerEl.textContent = s;
  clearInterval(phoneResendInterval);
  phoneResendInterval = setInterval(() => {
    s--; timerEl.textContent = s;
    if (s <= 0) {
      clearInterval(phoneResendInterval);
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Resend OTP';
    }
  }, 1000);
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>