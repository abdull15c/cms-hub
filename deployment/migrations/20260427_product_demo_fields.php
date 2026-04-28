<?php
declare(strict_types=1);

use Src\Core\Env;

define('ROOT_PATH', dirname(__DIR__, 2));

spl_autoload_register(function ($class) {
    $prefixes = [
        'Src\\' => ROOT_PATH . '/src/',
        'Config\\' => ROOT_PATH . '/config/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

require_once ROOT_PATH . '/src/Core/Env.php';
Env::load();

$host = (string)Env::get('DB_HOST', 'localhost');
$port = (string)Env::get('DB_PORT', '');
$dbName = (string)Env::get('DB_NAME', '');
$user = (string)Env::get('DB_USER', 'root');
$pass = (string)Env::get('DB_PASS', '');
$charset = (string)Env::get('CHARSET', 'utf8mb4');
if ($dbName === '') {
    throw new RuntimeException('DB_NAME is not configured.');
}

$dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";
if ($port !== '') {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";
}

$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

function hasColumnDemo(PDO $pdo, string $db, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        "SELECT 1 FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ? LIMIT 1"
    );
    $stmt->execute([$db, $table, $column]);
    return (bool)$stmt->fetchColumn();
}

if (!hasColumnDemo($pdo, $dbName, 'products', 'demo_enabled')) {
    $pdo->exec("ALTER TABLE products ADD COLUMN demo_enabled TINYINT(1) NOT NULL DEFAULT 0");
    echo "[OK] Added products.demo_enabled\n";
} else {
    echo "[SKIP] Column exists: products.demo_enabled\n";
}

if (!hasColumnDemo($pdo, $dbName, 'products', 'demo_url')) {
    $pdo->exec("ALTER TABLE products ADD COLUMN demo_url VARCHAR(255) NULL");
    echo "[OK] Added products.demo_url\n";
} else {
    echo "[SKIP] Column exists: products.demo_url\n";
}

if (!hasColumnDemo($pdo, $dbName, 'products', 'demo_login')) {
    $pdo->exec("ALTER TABLE products ADD COLUMN demo_login VARCHAR(191) NULL");
    echo "[OK] Added products.demo_login\n";
} else {
    echo "[SKIP] Column exists: products.demo_login\n";
}

if (!hasColumnDemo($pdo, $dbName, 'products', 'demo_password')) {
    $pdo->exec("ALTER TABLE products ADD COLUMN demo_password VARCHAR(191) NULL");
    echo "[OK] Added products.demo_password\n";
} else {
    echo "[SKIP] Column exists: products.demo_password\n";
}

echo "[DONE] Product demo fields migration finished.\n";

