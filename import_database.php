<?php
// Database import script for Railway
require_once __DIR__ . '/config/database.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    try {
        $db = getDB();
        
        $sqlFile = $_FILES['sql_file']['tmp_name'];
        $sqlContent = file_get_contents($sqlFile);
        
        // Split SQL file into individual statements
        $statements = explode(';', $sqlContent);
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $db->exec($statement);
                    $successCount++;
                } catch (PDOException $e) {
                    $errorCount++;
                    $errors[] = "Error: " . $e->getMessage();
                }
            }
        }
        
        echo "<h2>Import Complete</h2>";
        echo "<p><strong>Success:</strong> $successCount statements executed</p>";
        echo "<p><strong>Errors:</strong> $errorCount statements failed</p>";
        
        if (!empty($errors)) {
            echo "<h3>Errors:</h3><pre>" . implode("\n", array_slice($errors, 0, 10)) . "</pre>";
        }
        
    } catch (Exception $e) {
        echo "<h2>Database Connection Error</h2>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Import Database - Home Services</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 600px; margin: 0 auto; }
        .form-group { margin: 20px 0; }
        input[type="file"] { padding: 10px; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Import Database</h1>
        <p>Upload your SQL file to import the database schema and data.</p>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="sql_file">Select SQL File:</label>
                <input type="file" name="sql_file" id="sql_file" accept=".sql" required>
            </div>
            <button type="submit" class="btn">Import Database</button>
        </form>
        
        <p><strong>Note:</strong> Upload your <code>homeservices (2).sql</code> file here.</p>
    </div>
</body>
</html>
