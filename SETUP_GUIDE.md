# HomeServe — OTP + QR Payment Setup Guide

## ✅ Changes Made

### 1. 📱 Mobile OTP Verification (Twilio)
- **File added:** `api/send_otp.php` — Sends OTP via Twilio SMS
- **File added:** `api/verify_otp.php` — Verifies OTP input
- **File modified:** `modules/user/book.php` — Added OTP card + JS logic

### 2. 💳 QR Code Payment (Free UPI)
- **File modified:** `modules/user/invoice.php` — Added QR + UPI payment section

---

## ⚙️ Setup Steps

### Step 1 — Twilio Setup
1. Log in to [twilio.com/console](https://console.twilio.com)
2. Go to **Phone Numbers → Manage → Active Numbers**
3. Copy your Twilio phone number (e.g. `+15551234567`)
4. Open `api/send_otp.php` and replace this line:
   ```php
   $fromNumber = '+15005550006'; // ← Replace with your actual Twilio number
   ```
   With your real number, e.g.:
   ```php
   $fromNumber = '+15551234567';
   ```
5. **Trial account note:** With a Twilio trial, you can only send SMS to **verified numbers**.
   - Go to Twilio Console → **Verified Caller IDs**
   - Add and verify your test phone number there

### Step 2 — UPI ID Setup
1. Open `modules/user/invoice.php`
2. Find this line (around line 230):
   ```php
   $upiId = 'homeserve@upi'; // ← Change to your actual UPI ID
   ```
3. Replace with your real UPI ID, e.g.:
   ```php
   $upiId = 'yourname@okicici'; // or @okaxis, @ybl, @paytm etc
   ```

### Step 3 — Deploy Files
Copy the updated project to your XAMPP htdocs:
```
htdocs/homeservices/  ← all project files
```

### Step 4 — Run SQL (Optional)
Run `migration_otp_qr.sql` in phpMyAdmin if you want to track verified phones in DB.

---

## 🔄 How It Works

### OTP Flow
```
User fills booking form
    → Enters 10-digit mobile number
    → Clicks "Send OTP"
    → Twilio sends SMS with 6-digit OTP
    → User enters OTP
    → System verifies → Shows "Mobile Verified ✓"
    → User can now click "Confirm Booking"
```

### QR Payment Flow
```
Booking completed → Invoice generated
    → Invoice page shows QR code
    → User scans with PhonePe / GPay / Paytm
    → Pays exact invoice amount
    → Shares screenshot with provider
    → Provider marks booking as Paid
```

---

## 🆓 Cost
- **OTP:** Free on Twilio trial (limited to verified numbers). Paid plan: ~₹0.55/SMS
- **QR Payment:** 100% FREE — uses UPI (no payment gateway fees, no API key needed)

---

## Credentials Required
- Twilio Account SID: Set as environment variable `TWILIO_ACCOUNT_SID`
- Twilio Auth Token: Set as environment variable `TWILIO_AUTH_TOKEN`
- Twilio Phone Number: Set as environment variable `TWILIO_PHONE_NUMBER`
- These should be configured in your hosting environment (Railway)
