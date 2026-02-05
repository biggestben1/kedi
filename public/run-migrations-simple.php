<?php
/**
 * Simple Migration Runner - Run through web browser
 * Visit: http://my-laravel-app.test/run-migrations-simple.php
 * 
 * WARNING: Delete this file after running migrations!
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Run Migrations</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        pre { background: #fff; padding: 15px; border-radius: 5px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>Running Laravel Migrations</h1>
    <pre>
<?php

try {
    $db = $app->make('db')->connection();
    
    echo "Connecting to database...\n";
    
    // Create migrations table if it doesn't exist
    if (!$db->getSchemaBuilder()->hasTable('migrations')) {
        echo "Creating migrations table...\n";
        $db->statement("CREATE TABLE IF NOT EXISTS migrations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            migration varchar(255) NOT NULL,
            batch int(11) NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<span class='success'>✅ Created migrations table</span>\n\n";
    }
    
    // Get current batch number
    $batch = (int) $db->table('migrations')->max('batch') + 1;
    
    // Run each migration
    $migrations = [
        '0001_01_01_000000_create_users_table.php',
        '0001_01_01_000001_create_cache_table.php',
        '0001_01_01_000002_create_jobs_table.php',
    ];
    
    foreach ($migrations as $file) {
        $migrationName = str_replace('.php', '', $file);
        $migrationPath = __DIR__ . '/../database/migrations/' . $file;
        
        // Check if already run
        $exists = $db->table('migrations')
            ->where('migration', $migrationName)
            ->exists();
        
        if ($exists) {
            echo "<span class='warning'>⏭️  Skipped (already run): $migrationName</span>\n";
            continue;
        }
        
        if (!file_exists($migrationPath)) {
            echo "<span class='error'>❌ Migration file not found: $file</span>\n";
            continue;
        }
        
        echo "Running migration: $migrationName...\n";
        
        try {
            // Load and run the migration
            $migration = require $migrationPath;
            $migration->up();
            
            // Record migration
            $db->table('migrations')->insert([
                'migration' => $migrationName,
                'batch' => $batch,
            ]);
            
            echo "<span class='success'>✅ Successfully ran: $migrationName</span>\n\n";
        } catch (Exception $e) {
            echo "<span class='error'>❌ Error running $migrationName: " . $e->getMessage() . "</span>\n\n";
            throw $e;
        }
    }
    
    echo "\n<span class='success'>✅ All migrations completed successfully!</span>\n\n";
    echo "<span class='warning'>⚠️  IMPORTANT: Delete this file (run-migrations-simple.php) for security!</span>\n";
    
} catch (Exception $e) {
    echo "\n<span class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "\nStack trace:\n" . htmlspecialchars($e->getTraceAsString()) . "\n";
}

?>
    </pre>
</body>
</html>
