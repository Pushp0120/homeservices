<?php
// api/verify_otp.php — Verifies OTP entered by user
// Works for: registration (guest), booking (logged-in), profile phone update (logged-in)
require_once __DIR__ . '/../includes/auth.php';

// Allow guests ONLY if they have reg_form_data in session (registration flow)
$isRegistrationFlow = !empty($_SESSION['reg_form_data']);
if (!$isRegistrationFlow && !isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$enteredOtp = trim($_POST['otp'] ?? '');

if (empty($enteredOtp) || !ctype_digit($enteredOtp)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 6-digit OTP.']);
    exit;
}

// Check if OTP session exists
if (empty($_SESSION['booking_otp'])) {
    echo json_encode(['success' => false, 'message' => 'No OTP found. Please request a new OTP.']);
    exit;
}

// Check expiry
if (time() > ($_SESSION['booking_otp_expiry'] ?? 0)) {
    unset($_SESSION['booking_otp'], $_SESSION['booking_otp_phone'], $_SESSION['booking_otp_expiry'],
          $_SESSION['booking_otp_attempts'], $_SESSION['profile_otp'], $_SESSION['profile_otp_phone'],
          $_SESSION['profile_otp_expiry'], $_SESSION['profile_otp_attempts']);
    echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
    exit;
}

// Track failed attempts (max 5)
$_SESSION['booking_otp_attempts'] = ($_SESSION['booking_otp_attempts'] ?? 0) + 1;
if ($_SESSION['booking_otp_attempts'] > 5) {
    unset($_SESSION['booking_otp'], $_SESSION['booking_otp_phone'], $_SESSION['booking_otp_expiry'],
          $_SESSION['booking_otp_attempts'], $_SESSION['profile_otp'], $_SESSION['profile_otp_phone'],
          $_SESSION['profile_otp_expiry'], $_SESSION['profile_otp_attempts']);
    echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Please request a new OTP.']);
    exit;
}

// Compare OTP
if ($enteredOtp === $_SESSION['booking_otp']) {
    $verifiedPhone = $_SESSION['booking_otp_phone'];
    $verifiedAt    = time();

    // ── Set verified flags for ALL flows ─────────────────
    // book.php uses these:
    $_SESSION['otp_verified_phone'] = $verifiedPhone;
    $_SESSION['otp_verified_at']    = $verifiedAt;

    // profile.php uses these:
    $_SESSION['profile_otp_verified']    = true;
    $_SESSION['profile_otp_phone']       = $verifiedPhone;
    $_SESSION['profile_otp_verified_at'] = $verifiedAt;

    // register.php uses these (complete_register action checks them via reg_form_data):
    $_SESSION['reg_otp_verified']    = true;
    $_SESSION['reg_otp_verified_at'] = $verifiedAt;

    // Clear OTP session
    unset($_SESSION['booking_otp'], $_SESSION['booking_otp_phone'],
          $_SESSION['booking_otp_expiry'], $_SESSION['booking_otp_attempts'],
          $_SESSION['profile_otp'], $_SESSION['profile_otp_phone'],
          $_SESSION['profile_otp_expiry'], $_SESSION['profile_otp_attempts']);

    echo json_encode(['success' => true, 'message' => 'OTP verified successfully!']);
} else {
    $remaining = 5 - $_SESSION['booking_otp_attempts'];
    echo json_encode(['success' => false, 'message' => "Incorrect OTP. $remaining attempt(s) remaining."]);
}