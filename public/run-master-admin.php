<?php
/**
 * Create Master Admin user.
 * Visit: http://my-laravel-app.test/run-master-admin.php
 * DELETE THIS FILE AFTER RUNNING!
 */

ini_set('session.use_cookies', 0);
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Master Admin Setup</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        pre { background: #fff; padding: 15px; border-radius: 5px; }
        .ok { color: green; } .err { color: red; }
    </style>
</head>
<body>
    <h1>Master Admin Setup</h1>
    <pre>
<?php

try {
    $db = \Illuminate\Support\Facades\DB::connection();
    echo "<span class='ok'>✓ Connected to database</span>\n\n";

    // 1) Add phone column to users if missing
    if (!$db->getSchemaBuilder()->hasColumn('users', 'phone')) {
        $db->getSchemaBuilder()->table('users', function ($t) {
            $t->string('phone', 20)->nullable()->after('email');
        });
        echo "<span class='ok'>✓ Added phone column to users</span>\n";
    } else {
        echo "⏭ users.phone already exists\n";
    }

    // 2) Ensure roles table and super_admin role exist
    if (!$db->getSchemaBuilder()->hasTable('roles')) {
        $db->statement("CREATE TABLE roles (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            display_name varchar(255) NOT NULL,
            description text,
            created_at timestamp NULL,
            updated_at timestamp NULL,
            PRIMARY KEY (id),
            UNIQUE KEY (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<span class='ok'>✓ Created roles table</span>\n";
        $db->table('roles')->insert([
            ['name' => 'super_admin', 'display_name' => 'Super Admin', 'description' => 'Full system control.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'wholesale_staff', 'display_name' => 'Wholesale Staff', 'description' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reseller', 'display_name' => 'Reseller', 'description' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'customer', 'display_name' => 'Customer', 'description' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'accountant', 'display_name' => 'Accountant', 'description' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
        echo "<span class='ok'>✓ Seeded roles</span>\n";
    }

    // Ensure role_id column exists on users
    if (!$db->getSchemaBuilder()->hasColumn('users', 'role_id')) {
        $db->statement('ALTER TABLE users ADD COLUMN role_id bigint(20) unsigned NULL AFTER id');
        echo "<span class='ok'>✓ Added role_id to users</span>\n";
    }

    $superAdminId = $db->table('roles')->where('name', 'super_admin')->value('id');

    $passwordHash = \Illuminate\Support\Facades\Hash::make('admin');
    $exists = $db->table('users')->where('email', 'admin@admin.com')->exists();
    if ($exists) {
        $db->table('users')->where('email', 'admin@admin.com')->update([
            'name' => 'Master Admin',
            'phone' => '+2348050921999',
            'password' => $passwordHash,
            'role_id' => $superAdminId,
            'updated_at' => now(),
        ]);
        echo "<span class='ok'>✓ Updated Master Admin (admin@admin.com)</span>\n";
    } else {
        $db->table('users')->insert([
            'name' => 'Master Admin',
            'email' => 'admin@admin.com',
            'phone' => '+2348050921999',
            'password' => $passwordHash,
            'role_id' => $superAdminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "<span class='ok'>✓ Created Master Admin (admin@admin.com)</span>\n";
    }

    echo "\n<span class='ok'>✓ Master Admin ready.</span>\n";
    echo "\nLogin at: http://my-laravel-app.test/login\n";
    echo "Email: admin@admin.com\n";
    echo "Password: admin\n";
    echo "Phone: +2348050921999\n";
    echo "\n<span class='err'>⚠ Delete this file (run-master-admin.php) for security.</span>\n";
} catch (Throwable $e) {
    echo "<span class='err'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "\n" . htmlspecialchars($e->getTraceAsString());
}

?>
    </pre>
</body>
</html>
