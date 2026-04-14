<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
$db  = getDB();
$uid = currentUserId();
$error = ''; $success = '';
$activeTab = $_GET['tab'] ?? 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Profile update — OTP required only if phone changed ──────────────
    if ($action === 'profile') {
        verifyCsrf();
        $name     = sanitize($_POST['name']  ?? '');
        $newPhone = sanitize($_POST['phone'] ?? '');

        if (!$name) {
            $error = 'Name is required.';
        } else {
            // Fetch current phone from DB
            $cur = $db->prepare("SELECT phone FROM users WHERE id=?");
            $cur->execute([$uid]);
            $currentPhone = $cur->fetchColumn();

            $cleanPhone   = preg_replace('/[^0-9]/', '', $newPhone);
            $phoneChanged = ($cleanPhone !== '' && $cleanPhone !== $currentPhone);

            if ($phoneChanged) {
                if (strlen($cleanPhone) !== 10) {
                    $error = 'Please enter a valid 10-digit mobile number.';
                } else {
                    // OTP must have been verified for this exact new number
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
                        $db->prepare("UPDATE users SET name=?, phone=? WHERE id=?")
                           ->execute([$name, $cleanPhone, $uid]);
                        $_SESSION['name'] = $name;
                        foreach (['profile_otp_verified','profile_otp_phone','profile_otp_verified_at',
                                  'profile_otp','profile_otp_expiry','profile_otp_attempts'] as $k) {
                            unset($_SESSION[$k]);
                        }
                        $success = 'Profile updated successfully!';
                    }
                }
            } else {
                // Phone unchanged — just update name
                $db->prepare("UPDATE users SET name=?, phone=? WHERE id=?")
                   ->execute([$name, $currentPhone, $uid]);
                $_SESSION['name'] = $name;
                $success = 'Profile updated successfully!';
            }
        }
        $activeTab = 'profile';

    } elseif ($action === 'password') {
        $curr=$_POST['current_password']??''; $new=$_POST['new_password']??''; $conf=$_POST['confirm_password']??'';
        $u=$db->prepare("SELECT password FROM users WHERE id=?"); $u->execute([$uid]); $u=$u->fetch();
        if (!password_verify($curr,$u['password'])) $error='Current password incorrect.';
        elseif ($new!==$conf) $error='Passwords do not match.';
        elseif (strlen($new)<6) $error='Min. 6 characters.';
        else { $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([hashPassword($new),$uid]); $success='Password updated!'; }
        $activeTab = 'security';

    } elseif ($action === 'notifications') {
        $success = 'Preferences saved!'; $activeTab = 'settings';

    } elseif ($action === 'delete_account') {
        $pass=$_POST['delete_password']??'';
        $u=$db->prepare("SELECT password FROM users WHERE id=?"); $u->execute([$uid]); $u=$u->fetch();
        if (!password_verify($pass,$u['password'])) { $error='Password incorrect.'; $activeTab='danger'; }
        else { $db->prepare("UPDATE users SET status='suspended' WHERE id=?")->execute([$uid]); session_destroy(); redirect(APP_URL.'/login.php'); }
    }
}

$user=$db->prepare("SELECT * FROM users WHERE id=?"); $user->execute([$uid]); $user=$user->fetch();
$stats=$db->prepare("SELECT COUNT(*) as total,SUM(status='completed') as completed FROM bookings WHERE customer_id=?"); $stats->execute([$uid]); $stats=$stats->fetch();
$rev=$db->prepare("SELECT COUNT(*) as cnt FROM reviews WHERE customer_id=?"); $rev->execute([$uid]); $reviewCount=$rev->fetch()['cnt'];
$pageTitle='Profile & Settings';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<style>
.settings-layout{display:grid;grid-template-columns:250px 1fr;gap:1.5rem}
.settings-sidebar{background:#fff;border-radius:16px;border:1px solid #f1f5f9;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;height:fit-content;position:sticky;top:80px}
.settings-profile-header{background:linear-gradient(135deg,#2563eb,#7c3aed);padding:1.5rem;text-align:center;color:#fff}
.settings-avatar{width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;color:#fff;margin:0 auto 1rem;border:3px solid rgba(255,255,255,.4)}
.settings-nav{list-style:none;padding:.5rem;margin:0}
.settings-nav-item a{display:flex;align-items:center;gap:.75rem;padding:.65rem .875rem;border-radius:10px;text-decoration:none;color:#374151;font-size:.875rem;font-weight:500;transition:all .15s}
.settings-nav-item a:hover{background:#f8fafc}
.settings-nav-item a.active{background:#eff6ff;color:#2563eb;font-weight:700}
.settings-nav-item a i{width:18px;text-align:center}
.settings-divider{height:1px;background:#f1f5f9;margin:.5rem}
.settings-panel{background:#fff;border-radius:16px;border:1px solid #f1f5f9;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden}
.settings-panel-header{padding:1.5rem;border-bottom:1px solid #f1f5f9}
.settings-panel-header h5{font-weight:800;color:#0f172a;margin:0;font-size:1.1rem}
.settings-panel-header p{color:#64748b;font-size:.85rem;margin:.25rem 0 0}
.settings-panel-body{padding:1.5rem}
.stat-pill{background:#f8fafc;border-radius:10px;padding:.75rem;text-align:center;border:1px solid #f1f5f9}
.stat-pill .num{font-size:1.4rem;font-weight:800;color:#0f172a}
.stat-pill .lbl{font-size:.7rem;color:#64748b}
.toggle-switch{position:relative;display:inline-block;width:44px;height:24px}
.toggle-switch input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;cursor:pointer;inset:0;background:#e2e8f0;border-radius:34px;transition:.3s}
.toggle-slider:before{content:'';position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
input:checked + .toggle-slider{background:#2563eb}
input:checked + .toggle-slider:before{transform:translateX(20px)}
.setting-row{display:flex;justify-content:space-between;align-items:center;padding:1rem 0;border-bottom:1px solid #f8fafc}
.setting-row:last-child{border-bottom:none}
.setting-row-label{font-weight:600;font-size:.9rem;color:#1e293b}
.setting-row-desc{font-size:.78rem;color:#94a3b8;margin-top:.15rem}
.danger-zone{border:2px solid #fecaca;border-radius:12px;padding:1.25rem;background:#fff5f5}
.danger-zone-title{color:#dc2626;font-weight:700;margin-bottom:.25rem}
/* ── Phone OTP widget ── */
.phone-otp-box{background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:12px;padding:1rem;margin-top:.6rem}
.phone-otp-box.verified{background:#f0fdf4;border-color:#86efac}
.otp-mini-digits{display:flex;gap:.35rem;margin:.5rem 0}
.otp-mini-digit{width:38px;height:44px;border:2px solid #e2e8f0;border-radius:10px;text-align:center;font-size:1.2rem;font-weight:700;color:#1e293b;outline:none;transition:border-color .15s}
.otp-mini-digit:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12)}
.otp-mini-digit.filled {border-color:#2563eb;background:#eff6ff}
.otp-mini-digit.success{border-color:#059669;background:#f0fdf4}
.otp-mini-digit.error  {border-color:#dc2626;background:#fef2f2}
@media(max-width:768px){.settings-layout{grid-template-columns:1fr}.settings-sidebar{position:static}}
</style>
<?php require_once __DIR__ . '/../../includes/topnav_user.php'; ?>
<div class="user-main-content">
<div class="page-content">
<?php if($error): ?><div class="alert alert-danger mb-3"><i class="bi bi-exclamation-triangle"></i> <?=$error?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success mb-3"><i class="bi bi-check-circle-fill"></i> <?=$success?></div><?php endif; ?>

<div class="settings-layout">
  <!-- SIDEBAR -->
  <div class="settings-sidebar">
    <div class="settings-profile-header">
      <div class="settings-avatar"><?=strtoupper(substr($user['name'],0,1))?></div>
      <div style="font-weight:700"><?=sanitize($user['name'])?></div>
      <div style="font-size:.78rem;opacity:.8"><?=sanitize($user['email'])?></div>
    </div>
    <div class="d-flex gap-2 p-3">
      <div class="stat-pill flex-fill"><div class="num"><?=$stats['total']?></div><div class="lbl">Bookings</div></div>
      <div class="stat-pill flex-fill"><div class="num"><?=$stats['completed']?></div><div class="lbl">Done</div></div>
      <div class="stat-pill flex-fill"><div class="num"><?=$reviewCount?></div><div class="lbl">Reviews</div></div>
    </div>
    <ul class="settings-nav">
      <li class="settings-nav-item"><a href="?tab=profile" class="<?=$activeTab==='profile'?'active':''?>"><i class="bi bi-person-circle"></i> My Profile</a></li>
      <li class="settings-nav-item"><a href="?tab=security" class="<?=$activeTab==='security'?'active':''?>"><i class="bi bi-shield-lock"></i> Security</a></li>
      <li class="settings-nav-item"><a href="?tab=settings" class="<?=$activeTab==='settings'?'active':''?>"><i class="bi bi-gear"></i> Preferences</a></li>
      <div class="settings-divider"></div>
      <li class="settings-nav-item"><a href="?tab=danger" class="<?=$activeTab==='danger'?'active':''?>" style="color:#dc2626"><i class="bi bi-exclamation-triangle"></i> Danger Zone</a></li>
    </ul>
  </div>

  <!-- PANELS -->
  <div>
    <?php if($activeTab==='profile'): ?>
    <div class="settings-panel">
      <div class="settings-panel-header">
        <h5><i class="bi bi-person-circle text-primary me-2"></i>My Profile</h5>
        <p>Update your personal information</p>
      </div>
      <div class="settings-panel-body">
        <form method="POST" id="profileForm">
          <input type="hidden" name="action" value="profile">
          <?= csrfField() ?>
          <div class="row g-3">
            <div class="col-sm-6">
              <label class="form-label fw-600">Full Name</label>
              <input type="text" name="name" class="form-control" value="<?=sanitize($user['name'])?>" required>
            </div>

            <!-- ── Phone with inline OTP verification ── -->
            <div class="col-sm-6">
              <label class="form-label fw-600">
                Phone
                <span id="phoneVerifiedBadge" class="badge bg-success ms-1" style="display:none;font-size:.65rem">
                  <i class="bi bi-check-circle-fill"></i> Verified
                </span>
              </label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-telephone-fill text-primary"></i></span>
                <input type="tel" name="phone" id="phoneInput" class="form-control"
                  value="<?=sanitize($user['phone']??'')?>"
                  placeholder="10-digit mobile"
                  maxlength="10"
                  oninput="this.value=this.value.replace(/[^0-9]/g,'').substring(0,10);onPhoneChange()">
                <button type="button" class="btn btn-outline-primary" id="sendPhoneOtpBtn"
                  style="display:none" onclick="sendPhoneOTP()">
                  <i class="bi bi-send"></i> Verify
                </button>
              </div>
              <div id="phoneOtpStatus" class="small mt-1"></div>

              <!-- OTP digit entry -->
              <div id="phoneOtpBox" class="phone-otp-box" style="display:none">
                <div class="small fw-600 mb-1 text-primary">
                  <i class="bi bi-phone-vibrate"></i> Enter the 6-digit OTP sent to your number
                </div>
                <div class="otp-mini-digits" id="miniDigits">
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

              <!-- Verified confirmation -->
              <div id="phoneVerifiedBox" class="phone-otp-box verified" style="display:none">
                <i class="bi bi-check-circle-fill text-success"></i>
                <strong class="text-success"> New number verified!</strong>
                <span class="text-muted small ms-1" id="verifiedPhoneNum"></span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" onclick="resetPhoneOTP()">Change</button>
              </div>
            </div>
            <!-- ── end phone field ── -->

            <div class="col-12">
              <label class="form-label fw-600">Email</label>
              <input type="email" class="form-control" value="<?=sanitize($user['email'])?>" disabled>
              <div class="form-text">Email cannot be changed.</div>
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-600">Member Since</label>
              <input class="form-control" value="<?=formatDate($user['created_at'])?>" disabled>
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-600">Account Status</label>
              <input class="form-control text-success fw-600" value="Active" disabled>
            </div>
            <div class="col-12 pt-2">
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-lg"></i> Save Changes
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <?php elseif($activeTab==='security'): ?>
    <div class="settings-panel">
      <div class="settings-panel-header"><h5><i class="bi bi-shield-lock text-success me-2"></i>Security</h5><p>Change your password</p></div>
      <div class="settings-panel-body">
        <div class="d-flex gap-3 mb-4">
          <div class="stat-pill flex-fill"><div style="font-size:1.5rem">🔒</div><div class="lbl">Password Set</div></div>
          <div class="stat-pill flex-fill"><div style="font-size:1.5rem">✅</div><div class="lbl">Account Active</div></div>
          <div class="stat-pill flex-fill"><div style="font-size:1.5rem">📧</div><div class="lbl">Email Verified</div></div>
        </div>
        <form method="POST"><input type="hidden" name="action" value="password"><?= csrfField() ?>
          <div class="mb-3"><label class="form-label fw-600">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
          <div class="row g-3">
            <div class="col-sm-6">
              <label class="form-label fw-600">New Password</label>
              <input type="password" name="new_password" class="form-control" required oninput="checkStrength(this.value)">
              <div class="mt-2" id="strengthBar" style="display:none"><div style="height:4px;background:#f1f5f9;border-radius:4px;overflow:hidden"><div id="strengthFill" style="height:100%;border-radius:4px;transition:all .3s"></div></div><div id="strengthText" class="small mt-1"></div></div>
            </div>
            <div class="col-sm-6"><label class="form-label fw-600">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required></div>
          </div>
          <button type="submit" class="btn btn-success mt-3 px-4"><i class="bi bi-key"></i> Update Password</button>
        </form>
      </div>
    </div>

    <?php elseif($activeTab==='settings'): ?>
    <div class="settings-panel">
      <div class="settings-panel-header"><h5><i class="bi bi-gear text-primary me-2"></i>Preferences</h5><p>Customize your experience</p></div>
      <div class="settings-panel-body">
        <form method="POST"><input type="hidden" name="action" value="notifications">
          <h6 class="fw-700 mb-3 text-muted" style="font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase">Notifications</h6>
          <div class="setting-row"><div><div class="setting-row-label">Booking Confirmations</div><div class="setting-row-desc">Get notified when booking is confirmed</div></div><label class="toggle-switch"><input type="checkbox" name="n1" checked><span class="toggle-slider"></span></label></div>
          <div class="setting-row"><div><div class="setting-row-label">Provider Updates</div><div class="setting-row-desc">When provider accepts or starts work</div></div><label class="toggle-switch"><input type="checkbox" name="n2" checked><span class="toggle-slider"></span></label></div>
          <div class="setting-row"><div><div class="setting-row-label">Invoice Ready</div><div class="setting-row-desc">When provider generates your invoice</div></div><label class="toggle-switch"><input type="checkbox" name="n3" checked><span class="toggle-slider"></span></label></div>
          <div class="setting-row"><div><div class="setting-row-label">Promotional Offers</div><div class="setting-row-desc">Discounts and special offers</div></div><label class="toggle-switch"><input type="checkbox" name="n4"><span class="toggle-slider"></span></label></div>
          <hr class="my-4">
          <h6 class="fw-700 mb-3 text-muted" style="font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase">Display</h6>
          <div class="setting-row"><div><div class="setting-row-label">Default City</div><div class="setting-row-desc">Pre-fill in service address</div></div><input type="text" name="city" class="form-control form-control-sm" style="width:150px" placeholder="e.g. Mumbai"></div>
          <div class="setting-row"><div><div class="setting-row-label">Language</div><div class="setting-row-desc">Interface language</div></div><select name="lang" class="form-select form-select-sm" style="width:130px"><option>English</option><option>Hindi</option><option>Gujarati</option><option>Marathi</option></select></div>
          <div class="mt-4"><button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg"></i> Save Preferences</button></div>
        </form>
      </div>
    </div>

    <?php elseif($activeTab==='danger'): ?>
    <div class="settings-panel">
      <div class="settings-panel-header"><h5><i class="bi bi-exclamation-triangle text-danger me-2"></i>Danger Zone</h5><p>Irreversible actions</p></div>
      <div class="settings-panel-body">
        <div class="danger-zone">
          <div class="danger-zone-title"><i class="bi bi-trash3"></i> Delete Account</div>
          <p class="small text-muted mb-3">All bookings, invoices, and data will be permanently removed.</p>
          <form method="POST" onsubmit="return confirm('Permanently delete your account? This cannot be undone.')">
            <input type="hidden" name="action" value="delete_account">
            <div class="mb-3"><label class="form-label fw-600 small">Enter password to confirm</label><input type="password" name="delete_password" class="form-control" style="max-width:300px" required></div>
            <button type="submit" class="btn btn-danger"><i class="bi bi-trash3"></i> Delete My Account</button>
          </form>
        </div>
        <div class="mt-4 p-3 bg-light rounded">
          <h6 class="fw-700 mb-2">Export Your Data</h6>
          <p class="small text-muted mb-2">View and download your invoices.</p>
          <a href="<?=APP_URL?>/modules/user/invoices.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download"></i> View Invoices</a>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script>
// ── Password strength ──────────────────────────────────
function checkStrength(p){
  const b=document.getElementById('strengthBar'),f=document.getElementById('strengthFill'),t=document.getElementById('strengthText');
  if(!b)return; b.style.display=p.length?'block':'none';
  let s=0;if(p.length>=6)s++;if(p.length>=10)s++;if(/[A-Z]/.test(p))s++;if(/[0-9]/.test(p))s++;if(/[^A-Za-z0-9]/.test(p))s++;
  const l=[{w:'20%',c:'#dc2626',t:'Very Weak'},{w:'40%',c:'#f97316',t:'Weak'},{w:'60%',c:'#eab308',t:'Fair'},{w:'80%',c:'#22c55e',t:'Strong'},{w:'100%',c:'#059669',t:'Very Strong'}][Math.max(0,s-1)];
  f.style.width=l.w;f.style.background=l.c;t.innerHTML='<span style="color:'+l.c+';font-weight:600">'+l.t+'</span>';
}

// ── Phone OTP ──────────────────────────────────────────
const originalPhone = '<?= addslashes($user['phone'] ?? '') ?>';
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
  fetch('<?= APP_URL ?>/api/send_otp.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        document.getElementById('sendPhoneOtpBtn').style.display = 'none';
        document.getElementById('phoneOtpBox').style.display     = 'block';
        document.getElementById('phoneOtpStatus').innerHTML =
          '<span class="text-success small"><i class="bi bi-check-circle"></i> ' + data.message + '</span>';
        document.querySelectorAll('.otp-mini-digit').forEach(d => {
          d.value=''; d.classList.remove('filled','success','error');
        });
        document.querySelector('.otp-mini-digit[data-idx="0"]').focus();
        startPhoneResendTimer();
      } else {
        document.getElementById('phoneOtpStatus').innerHTML =
          '<span class="text-danger small"><i class="bi bi-x-circle"></i> ' + data.message + '</span>';
        if (btn) { btn.disabled=false; btn.innerHTML='<i class="bi bi-send"></i> Verify'; }
      }
    })
    .catch(() => {
      document.getElementById('phoneOtpStatus').innerHTML =
        '<span class="text-danger small"><i class="bi bi-x-circle"></i> Network error. Try again.</span>';
      if (btn) { btn.disabled=false; btn.innerHTML='<i class="bi bi-send"></i> Verify'; }
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
  fetch('<?= APP_URL ?>/api/verify_otp.php', { method:'POST', body:fd })
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
        btn.disabled=false; btn.innerHTML='<i class="bi bi-check-circle"></i> Confirm';
        setTimeout(() => {
          document.querySelectorAll('.otp-mini-digit').forEach(d => { d.value=''; d.classList.remove('error'); });
          document.querySelector('.otp-mini-digit[data-idx="0"]').focus();
        }, 1200);
      }
    })
    .catch(() => {
      document.getElementById('miniOtpVerifyStatus').innerHTML =
        '<span class="text-danger"><i class="bi bi-x-circle"></i> Network error.</span>';
      btn.disabled=false; btn.innerHTML='<i class="bi bi-check-circle"></i> Confirm';
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

// Block form submit if phone changed but not OTP-verified
document.getElementById('profileForm').addEventListener('submit', function(e) {
  const phone = document.getElementById('phoneInput').value.trim();
  if (phone.length === 10 && phone !== originalPhone && !phoneOtpVerified) {
    e.preventDefault();
    document.getElementById('phoneOtpStatus').innerHTML =
      '<span class="text-danger fw-600"><i class="bi bi-exclamation-triangle"></i> Please verify the new number with OTP before saving.</span>';
    document.getElementById('sendPhoneOtpBtn').style.display = 'block';
    document.getElementById('sendPhoneOtpBtn').scrollIntoView({ behavior:'smooth', block:'center' });
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
      all.forEach((d,j)=>{ d.value=p[j]||''; d.classList.toggle('filled',!!d.value); });
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