<?php
/**
 * KEDI Setup: Run new migrations (roles) + seed roles.
 * Visit: http://my-laravel-app.test/run-kedi-setup.php
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
    <title>KEDI Setup</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        pre { background: #fff; padding: 15px; border-radius: 5px; }
        .ok { color: green; } .err { color: red; }
    </style>
</head>
<body>
    <h1>KEDI Setup</h1>
    <pre>
<?php

try {
    $db = \Illuminate\Support\Facades\DB::connection();
    echo "<span class='ok'>✓ Connected to database</span>\n\n";

    // Run new migrations if not already run
    $migrations = [
        '2026_02_03_000001_create_roles_table.php',
        '2026_02_03_000002_add_role_id_to_users_table.php',
        '2026_02_03_100001_create_products_table.php',
    ];

    if (!$db->getSchemaBuilder()->hasTable('migrations')) {
        $db->statement("CREATE TABLE IF NOT EXISTS migrations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            migration varchar(255) NOT NULL,
            batch int(11) NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<span class='ok'>✓ Migrations table ready</span>\n";
    }

    $batch = (int) $db->table('migrations')->max('batch') + 1;

    foreach ($migrations as $file) {
        $name = str_replace('.php', '', $file);
        $path = __DIR__ . '/../database/migrations/' . $file;
        if ($db->table('migrations')->where('migration', $name)->exists()) {
            echo "⏭ Skipped (already run): $name\n";
            continue;
        }
        if (!file_exists($path)) {
            echo "<span class='err'>✗ File not found: $file</span>\n";
            continue;
        }
        $migration = require $path;
        $migration->up();
        $db->table('migrations')->insert(['migration' => $name, 'batch' => $batch]);
        echo "<span class='ok'>✓ Migrated: $name</span>\n";
    }

    echo "\n";
    // Seed roles
    (new \Database\Seeders\RoleSeeder())->run();
    echo "<span class='ok'>✓ Roles seeded (Super Admin, Wholesale Staff, Reseller, Customer, Accountant)</span>\n";
    // Seed products
    (new \Database\Seeders\ProductSeeder())->run();
    echo "<span class='ok'>✓ Products seeded (KEDI product list with BV, PV, prices)</span>\n\n";
    echo "<span class='ok'>✓ KEDI setup complete. Delete this file (run-kedi-setup.php) for security.</span>\n";
} catch (Throwable $e) {
    echo "<span class='err'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "\n" . htmlspecialchars($e->getTraceAsString());
}

?>
    </pre>
</body>
</html>
