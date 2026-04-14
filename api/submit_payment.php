<?php
// api/submit_payment.php — Save UPI Transaction ID after payment
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$db          = getDB();
$uid         = currentUserId();
$bookingId   = (int)($_POST['booking_id'] ?? 0);
$txnId       = trim($_POST['transaction_id'] ?? '');
$amount      = trim($_POST['amount'] ?? '');

// Validate
if (!$bookingId || empty($txnId) || empty($amount)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if (strlen($txnId) < 6 || strlen($txnId) > 50) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid UPI Transaction ID.']);
    exit;
}

// Verify booking belongs to this customer
$stmt = $db->prepare("SELECT b.id, inv.id as inv_id, inv.payment_status, inv.grand_total 
    FROM bookings b 
    JOIN invoices inv ON inv.booking_id = b.id 
    WHERE b.id = ? AND b.customer_id = ?");
$stmt->execute([$bookingId, $uid]);
$booking = $stmt->fetch();

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found.']);
    exit;
}

if ($booking['payment_status'] === 'paid') {
    echo json_encode(['success' => false, 'message' => 'This invoice is already marked as paid.']);
    exit;
}

// Check if transaction ID already submitted
$check = $db->prepare("SELECT id FROM payments WHERE transaction_id = ?");
$check->execute([$txnId]);
if ($check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'This Transaction ID has already been submitted.']);
    exit;
}

// Save to payments table and instantly mark as paid
try {
    $db->beginTransaction();

    // Insert payment record as verified instantly
    $ins = $db->prepare("INSERT INTO payments (booking_id, transaction_id, amount, status, verified_at) VALUES (?, ?, ?, 'verified', NOW())");
    $ins->execute([$bookingId, $txnId, $booking['grand_total']]);

    // Mark invoice as paid immediately
    $db->prepare("UPDATE invoices SET payment_status='paid' WHERE booking_id=?")
       ->execute([$bookingId]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Payment verified successfully! Your invoice has been marked as Paid.',
    ]);
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to save. Please try again.']);
}