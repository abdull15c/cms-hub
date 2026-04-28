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

$host = (string) Env::get('DB_HOST', 'localhost');
$port = (string) Env::get('DB_PORT', '');
$dbName = (string) Env::get('DB_NAME', '');
$user = (string) Env::get('DB_USER', 'root');
$pass = (string) Env::get('DB_PASS', '');
$charset = (string) Env::get('CHARSET', 'utf8mb4');

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

function hasIndex(PDO $pdo, string $db, string $table, string $index): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.statistics WHERE table_schema=? AND table_name=? AND index_name=? LIMIT 1");
    $stmt->execute([$db, $table, $index]);
    return (bool)$stmt->fetchColumn();
}

$pdo->exec("
DELETE r1 FROM reviews r1
JOIN reviews r2 ON r1.user_id = r2.user_id AND r1.product_id = r2.product_id AND r1.id > r2.id
");
if (!hasIndex($pdo, $dbName, 'reviews', 'uniq_reviews_user_product')) {
    $pdo->exec("ALTER TABLE reviews ADD UNIQUE KEY uniq_reviews_user_product (user_id, product_id)");
    echo "[OK] Added unique index reviews(user_id, product_id)\n";
}

$pdo->exec("
DELETE c1 FROM chat_threads c1
JOIN chat_threads c2 ON c1.user_id = c2.user_id AND c1.product_id = c2.product_id AND c1.id > c2.id
");
if (!hasIndex($pdo, $dbName, 'chat_threads', 'uniq_chat_threads_user_product')) {
    $pdo->exec("ALTER TABLE chat_threads ADD UNIQUE KEY uniq_chat_threads_user_product (user_id, product_id)");
    echo "[OK] Added unique index chat_threads(user_id, product_id)\n";
}

echo "[DONE] Data integrity migration applied.\n";
