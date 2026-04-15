<?php
// Database connection test and import status checker
require_once __DIR__ . '/config/database.php';

echo "<h1>Database Status - Home Services</h1>";

try {
    $db = getDB();
    echo "<p style='color: green;'>&#10004; Database connection successful!</p>";
    
    // Check if tables exist
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Database Tables:</h2>";
    if (empty($tables)) {
        echo "<p style='color: orange;'>&#9888; No tables found. Database needs to be imported.</p>";
        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ul>";
        echo "<li>Wait for DNS propagation (5-10 minutes)</li>";
        echo "<li>Visit: https://homeservices-production-1503.up.railway.app/import_database.php</li>";
        echo "<li>Upload your homeservices (2).sql file</li>";
        echo "</ul>";
    } else {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li style='color: green;'>&#10004; $table</li>";
        }
        echo "</ul>";
        echo "<p style='color: green;'><strong>&#10004; Database appears to be imported!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>&#10008; Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Application Status:</h2>";
echo "<p><strong>Application URL:</strong> <a href='https://homeservices-production-1503.up.railway.app'>https://homeservices-production-1503.up.railway.app</a></p>";
echo "<p><strong>Database Import:</strong> <a href='https://homeservices-production-1503.up.railway.app/import_database.php'>https://homeservices-production-1503.up.railway.app/import_database.php</a></p>";
echo "<p><strong>This Status Page:</strong> <a href='https://homeservices-production-1503.up.railway.app/db_import_status.php'>https://homeservices-production-1503.up.railway.app/db_import_status.php</a></p>";
?>
