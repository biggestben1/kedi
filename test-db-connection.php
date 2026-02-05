<?php
/**
 * Test Database Connection
 * Visit: http://my-laravel-app.test/test-db-connection.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test DB Connection</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        pre { background: #fff; padding: 15px; border-radius: 5px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Database Connection Test</h1>
    <pre>
<?php

try {
    echo "Testing database connection...\n\n";
    
    $db = $app->make('db')->connection();
    
    echo "Connection driver: " . $db->getDriverName() . "\n";
    echo "Database name: " . $db->getDatabaseName() . "\n";
    
    // Test query
    $result = $db->select('SELECT 1 as test');
    echo "<span class='success'>✅ Database connection successful!</span>\n\n";
    
    // Check if migrations table exists
    $tables = $db->select("SHOW TABLES");
    echo "Tables in database: " . count($tables) . "\n";
    
    if (count($tables) > 0) {
        echo "\nExisting tables:\n";
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            echo "  - $tableName\n";
        }
    } else {
        echo "\n<span class='error'>⚠️  No tables found. Migrations need to be run.</span>\n";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Connection failed!</span>\n\n";
    echo "Error: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "\nStack trace:\n" . htmlspecialchars($e->getTraceAsString()) . "\n";
}

?>
    </pre>
    <p><a href="{{ url('/') }}">← Back to Home</a></p>
</body>
</html>
