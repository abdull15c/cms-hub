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
$dsn = $port !== ''
    ? "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}"
    : "mysql:host={$host};dbname={$dbName};charset={$charset}";

$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$stmt = $pdo->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = ? AND table_name = 'products' AND column_name = 'status' LIMIT 1");
$stmt->execute([$dbName]);
if (!$stmt->fetchColumn()) {
    $pdo->exec("ALTER TABLE products ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'published' AFTER file_path");
    echo "[OK] Added products.status\n";
} else {
    echo "[SKIP] products.status already exists\n";
}

$stmt = $pdo->prepare("SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = 'products' AND index_name = 'idx_products_status' LIMIT 1");
$stmt->execute([$dbName]);
if (!$stmt->fetchColumn()) {
    $pdo->exec("ALTER TABLE products ADD INDEX idx_products_status (status)");
    echo "[OK] Added idx_products_status\n";
} else {
    echo "[SKIP] idx_products_status already exists\n";
}

echo "[DONE] Product status migration finished.\n";
