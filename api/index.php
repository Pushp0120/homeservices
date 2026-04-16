<?php
// Vercel PHP entry point
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

// Handle routing
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove query string
$path = explode('?', $path)[0];

// Route to appropriate file
switch ($path) {
    case '/':
    case '/index.php':
        include __DIR__ . '/../index.php';
        break;
    case '/login':
        include __DIR__ . '/../login.php';
        break;
    case '/register':
        include __DIR__ . '/../register.php';
        break;
    case '/dashboard':
        include __DIR__ . '/../dashboard.php';
        break;
    case '/admin':
        include __DIR__ . '/../admin.php';
        break;
    case '/api/send_otp':
        include __DIR__ . '/../api/send_otp.php';
        break;
    default:
        // Try to serve the file directly
        $file_path = __DIR__ . '/..' . $path;
        if (file_exists($file_path) && is_file($file_path)) {
            include $file_path;
        } else {
            // 404
            http_response_code(404);
            echo "<h1>404 - Page Not Found</h1>";
        }
        break;
}
?>
