<?php
// includes/auth.php — Session, Auth, Helpers

// ── START SESSION ─────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

// ── NO-CACHE HEADERS ──────────────────────────────────────
function preventBackAccess() {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
}

// ── AUTH CHECKS ───────────────────────────────────────────
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    preventBackAccess();
    if (!isLoggedIn()) {
        redirect(APP_URL . '/login.php');
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        switch ($_SESSION['role']) {
            case 'admin':    redirect(APP_URL . '/modules/admin/dashboard.php'); break;
            case 'provider': redirect(APP_URL . '/modules/provider/dashboard.php'); break;
            default:         redirect(APP_URL . '/modules/user/dashboard.php');
        }
    }
}

// ── CURRENT USER HELPERS ──────────────────────────────────
function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'     => $_SESSION['user_id'],
        'name'   => $_SESSION['name'],
        'email'  => $_SESSION['email'],
        'role'   => $_SESSION['role'],
        'avatar' => $_SESSION['avatar'] ?? null,
    ];
}

function currentUserId()    { return $_SESSION['user_id']    ?? null; }
function currentRole()      { return $_SESSION['role']       ?? null; }
function currentProviderId(){ return $_SESSION['provider_id']?? null; }
function isProvider()       { return isset($_SESSION['provider_id']); }

// ── REDIRECT ──────────────────────────────────────────────
function redirect($url) {
    header("Location: $url");
    exit;
}

// ── CSRF PROTECTION ───────────────────────────────────────
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf() {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('<p style="font-family:sans-serif;padding:2rem;color:#dc2626;">⚠️ Security token mismatch. Please go back and try again.</p>');
    }
}

// ── LOGIN ─────────────────────────────────────────────────
function login($email, $password) {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    // Regenerate session ID — prevents session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['name']       = $user['name'];
    $_SESSION['email']      = $user['email'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['avatar']     = $user['avatar'];
    $_SESSION['login_time'] = time();

    // ── Provider check ────────────────────────────────────
    // Regardless of DB role (some providers stored as 'customer'),
    // always check the providers table for an approved entry.
    $stmt2 = $db->prepare("SELECT id, approval_status FROM providers WHERE user_id = ?");
    $stmt2->execute([$user['id']]);
    $provider = $stmt2->fetch();

    if ($provider && $provider['approval_status'] === 'approved') {
        // This user is an approved provider — set provider session
        $_SESSION['provider_id'] = $provider['id'];
        $_SESSION['role']        = 'provider';
    } else {
        // Not a provider (or pending/rejected) — treat as customer
        unset($_SESSION['provider_id']);
        // Only override role to 'customer' if DB role is not 'admin'
        if ($user['role'] !== 'admin') {
            $_SESSION['role'] = 'customer';
        }
    }

    return ['success' => true, 'role' => $_SESSION['role']];
}

// ── PROVIDER SESSION REFRESH ──────────────────────────────
// Call on any customer-facing page to auto-upgrade/downgrade
// the session if the admin approval status has changed.
function refreshProviderSession() {
    if (!isLoggedIn()) return;

    // Don't interfere if user is already on the pending page
    $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
    if ($currentPage === 'pending.php') return;

    $db    = getDB();
    $stmt  = $db->prepare("SELECT id, approval_status FROM providers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $provider = $stmt->fetch();

    if ($provider && $provider['approval_status'] === 'approved') {
        // Newly approved — upgrade session and redirect to provider dashboard
        if ($_SESSION['role'] !== 'provider') {
            $_SESSION['provider_id'] = $provider['id'];
            $_SESSION['role']        = 'provider';
            redirect(APP_URL . '/modules/provider/dashboard.php');
        }
    } else {
        // Not approved (pending/suspended/no record) — ensure customer role
        if ($_SESSION['role'] === 'provider') {
            unset($_SESSION['provider_id']);
            $_SESSION['role'] = 'customer';
        }
    }
}

// Returns provider approval status for logged-in user, or null
function getPendingProviderStatus() {
    if (!isLoggedIn()) return null;
    $db   = getDB();
    $stmt = $db->prepare("SELECT approval_status FROM providers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    return $row ? $row['approval_status'] : null;
}

// ── LOGOUT ────────────────────────────────────────────────
function logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    redirect(APP_URL . '/login.php');
}

// ── HELPERS ───────────────────────────────────────────────
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function formatCurrency($amount) {
    return '₹' . number_format($amount, 0);
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function formatDateTime($dt) {
    return date('d M Y, h:i A', strtotime($dt));
}

function generateInvoiceNumber() {
    return 'INV-' . strtoupper(uniqid());
}

function statusBadge($status) {
    $map = [
        'pending'     => 'warning',
        'accepted'    => 'info',
        'in_progress' => 'primary',
        'completed'   => 'success',
        'cancelled'   => 'danger',
        'approved'    => 'success',
        'suspended'   => 'danger',
        'unpaid'      => 'warning',
        'paid'        => 'success',
    ];
    $color = $map[$status] ?? 'secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff/60)    . 'm ago';
    if ($diff < 86400)  return floor($diff/3600)  . 'h ago';
    if ($diff < 604800) return floor($diff/86400) . 'd ago';
    return date('d M', strtotime($datetime));
}