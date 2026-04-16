<?php
// config/database.php
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASSWORD'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'homeservices');
define('DB_PORT', $_ENV['DB_PORT'] ?? 3306);

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // Debug: Check environment variables
            $debug_info = [
                'DB_HOST' => DB_HOST,
                'DB_USER' => DB_USER,
                'DB_NAME' => DB_NAME,
                'DB_PORT' => DB_PORT,
                'DB_PASS_SET' => !empty(DB_PASS)
            ];
            
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode([
                'error' => 'Database connection failed: ' . $e->getMessage(),
                'debug' => $debug_info ?? []
            ]));
        }
    }
    return $pdo;
}
