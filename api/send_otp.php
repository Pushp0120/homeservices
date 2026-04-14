<?php
// api/send_otp.php — Sends OTP via Twilio SMS
// Works for: registration (guest), booking (logged-in), profile phone update (logged-in)
require_once __DIR__ . '/../includes/auth.php';

// Allow guests ONLY if they have reg_form_data in session (registration flow)
// All other callers must be logged in
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

$phone = trim($_POST['phone'] ?? '');

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number is required.']);
    exit;
}

// Normalize Indian phone number to E.164
$phone = preg_replace('/[^0-9+]/', '', $phone);
if (substr($phone, 0, 1) !== '+') {
    $phone = preg_replace('/^0/', '', $phone);
    if (strlen($phone) === 10) {
        $phone = '+91' . $phone;
    }
}

if (!preg_match('/^\+[1-9]\d{7,14}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format. Use 10-digit mobile number.']);
    exit;
}

// Generate 6-digit OTP
$otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

// Store OTP in session with expiry (5 minutes)
// Single unified key used by all flows
$_SESSION['booking_otp']          = $otp;
$_SESSION['booking_otp_phone']    = $phone;
$_SESSION['booking_otp_expiry']   = time() + 300;
$_SESSION['booking_otp_attempts'] = 0;

// Also set profile OTP keys so profile.php verify check works
$_SESSION['profile_otp']          = $otp;
$_SESSION['profile_otp_phone']    = $phone;
$_SESSION['profile_otp_expiry']   = time() + 300;
$_SESSION['profile_otp_attempts'] = 0;

// ── TWILIO CREDENTIALS ────────────────────────────────────
// Twilio credentials - use environment variables
$accountSid = $_ENV['TWILIO_ACCOUNT_SID'] ?? '';
$authToken  = $_ENV['TWILIO_AUTH_TOKEN'] ?? '';
$fromNumber = $_ENV['TWILIO_PHONE_NUMBER'] ?? '';

$message = "Your HomeServe OTP is: $otp. Valid for 5 minutes. Do not share this code.";

$url  = "https://api.twilio.com/2010-04-01/Accounts/$accountSid/Messages.json";
$data = [
    'From' => $fromNumber,
    'To'   => $phone,
    'Body' => $message,
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($data),
    CURLOPT_USERPWD        => "$accountSid:$authToken",
    CURLOPT_SSL_VERIFYPEER => true,
]);
$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    error_log("Twilio cURL error: $curlError");
    echo json_encode(['success' => false, 'message' => 'Network error while sending OTP. Please try again.']);
    exit;
}

$result = json_decode($response, true);

if ($httpCode >= 200 && $httpCode < 300 && isset($result['sid'])) {
    $maskedPhone = substr($phone, 0, -4) . 'XXXX';
    echo json_encode([
        'success'      => true,
        'message'      => "OTP sent successfully to $maskedPhone",
        'masked_phone' => $maskedPhone,
    ]);
} else {
    $errMsg = $result['message'] ?? 'Failed to send OTP.';
    error_log("Twilio error ($httpCode): $errMsg | Response: $response");
    echo json_encode(['success' => false, 'message' => "SMS Error: $errMsg"]);
}