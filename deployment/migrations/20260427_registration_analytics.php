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

function hasIndexReg(PDO $pdo, string $db, string $table, string $index): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1");
    $stmt->execute([$db, $table, $index]);
    return (bool)$stmt->fetchColumn();
}

function addIndexReg(PDO $pdo, string $db, string $table, string $index, string $definition): void
{
    if (!hasIndexReg($pdo, $db, $table, $index)) {
        $pdo->exec("ALTER TABLE `$table` ADD INDEX `$index` ($definition)");
        echo "[OK] Added index $table.$index\n";
        return;
    }
    echo "[SKIP] Index exists: $table.$index\n";
}

$pdo->exec("CREATE TABLE IF NOT EXISTS analytics_registrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    source VARCHAR(32) NOT NULL DEFAULT 'local',
    provider VARCHAR(32) NULL,
    session_id VARCHAR(128) NULL,
    ip_hash CHAR(64) NOT NULL,
    country_code VARCHAR(16) NULL,
    country_name VARCHAR(120) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "[OK] Ensured table analytics_registrations\n";

addIndexReg($pdo, $dbName, 'analytics_registrations', 'idx_ar_created_at', '`created_at`');
addIndexReg($pdo, $dbName, 'analytics_registrations', 'idx_ar_user_created_at', '`user_id`, `created_at`');
addIndexReg($pdo, $dbName, 'analytics_registrations', 'idx_ar_source_created_at', '`source`, `created_at`');
addIndexReg($pdo, $dbName, 'analytics_registrations', 'idx_ar_provider_created_at', '`provider`, `created_at`');
addIndexReg($pdo, $dbName, 'analytics_registrations', 'uq_ar_user_id', '`user_id`');

echo "[DONE] Registration analytics migration finished.\n";

