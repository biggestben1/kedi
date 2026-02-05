<?php
/**
 * Run products migration + seed.
 * Visit: http://my-laravel-app.test/run-products-setup.php
 * DELETE THIS FILE AFTER RUNNING!
 */

ini_set('session.use_cookies', 0);
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Products Setup</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        pre { background: #fff; padding: 15px; border-radius: 5px; }
        .ok { color: green; } .err { color: red; }
    </style>
</head>
<body>
    <h1>Products Setup</h1>
    <pre>
<?php

try {
    $db = \Illuminate\Support\Facades\DB::connection();
    echo "<span class='ok'>✓ Connected to database</span>\n\n";

    $migrationName = '2026_02_03_100001_create_products_table';
    $migrationFile = '2026_02_03_100001_create_products_table.php';
    $path = __DIR__ . '/../database/migrations/' . $migrationFile;

    if (!file_exists($path)) {
        echo "<span class='err'>✗ Migration file not found: $migrationFile</span>\n";
        exit;
    }

    if (!$db->getSchemaBuilder()->hasTable('migrations')) {
        $db->statement("CREATE TABLE IF NOT EXISTS migrations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            migration varchar(255) NOT NULL,
            batch int(11) NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<span class='ok'>✓ Migrations table ready</span>\n";
    }

    $alreadyRun = $db->table('migrations')->where('migration', $migrationName)->exists();

    if (!$alreadyRun) {
        echo "Running migration: $migrationName...\n";
        $migration = require $path;
        $migration->up();
        $batch = (int) $db->table('migrations')->max('batch') + 1;
        $db->table('migrations')->insert(['migration' => $migrationName, 'batch' => $batch]);
        echo "<span class='ok'>✓ Products table created</span>\n";
    } else {
        echo "⏭ Migration already run: $migrationName\n";
    }

    echo "\n";
    (new \Database\Seeders\ProductSeeder())->run();
    echo "<span class='ok'>✓ Products seeded (KEDI product list)</span>\n\n";
    echo "<span class='ok'>✓ Done. Delete this file (run-products-setup.php) for security.</span>\n";
} catch (Throwable $e) {
    echo "<span class='err'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "\n" . htmlspecialchars($e->getTraceAsString());
}

?>
    </pre>
</body>
</html>
