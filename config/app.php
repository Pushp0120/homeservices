<?php
// config/app.php
define('APP_NAME', 'HomeServe');
define('APP_TAGLINE', 'Professional Home Services');
$_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_scheme = $_SERVER['HTTPS'] ? 'https' : 'http';
define('APP_URL', $_scheme . '://' . $_host);
define('APP_VERSION', '1.0.0');
define('TAX_RATE', 0); // No GST/VAT
define('CURRENCY', 'INR');
define('CURRENCY_SYMBOL', '₹');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');