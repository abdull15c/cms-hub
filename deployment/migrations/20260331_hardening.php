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
$dbName = (string) Env::get('DB_NAME', '');
$user = (string) Env::get('DB_USER', 'root');
$pass = (string) Env::get('DB_PASS', '');
$charset = (string) Env::get('CHARSET', 'utf8mb4');
if ($dbName === '') {
    throw new RuntimeException('DB_NAME is not configured.');
}
$dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

function hasIndex(PDO $pdo, string $db, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        "SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1"
    );
    $stmt->execute([$db, $table, $index]);
    return (bool) $stmt->fetchColumn();
}

function hasColumn(PDO $pdo, string $db, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        "SELECT 1 FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ? LIMIT 1"
    );
    $stmt->execute([$db, $table, $column]);
    return (bool) $stmt->fetchColumn();
}

function addIndexIfMissing(PDO $pdo, string $db, string $table, string $index, string $definition): void
{
    if (!hasIndex($pdo, $db, $table, $index)) {
        $pdo->exec("ALTER TABLE `$table` ADD INDEX `$index` ($definition)");
        echo "[OK] Added index $table.$index\n";
        return;
    }
    echo "[SKIP] Index exists: $table.$index\n";
}

function addUniqueIfMissing(PDO $pdo, string $db, string $table, string $index, string $definition): void
{
    if (!hasIndex($pdo, $db, $table, $index)) {
        $pdo->exec("ALTER TABLE `$table` ADD UNIQUE `$index` ($definition)");
        echo "[OK] Added unique index $table.$index\n";
        return;
    }
    echo "[SKIP] Unique exists: $table.$index\n";
}

function addColumnIfMissing(PDO $pdo, string $db, string $table, string $column, string $ddl): void
{
    if (!hasColumn($pdo, $db, $table, $column)) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN $ddl");
        echo "[OK] Added column $table.$column\n";
        return;
    }
    echo "[SKIP] Column exists: $table.$column\n";
}

addUniqueIfMissing($pdo, $dbName, 'licenses', 'uq_licenses_license_key', '`license_key`');
addIndexIfMissing($pdo, $dbName, 'transactions', 'idx_transactions_status_created', '`status`, `created_at`');
addIndexIfMissing($pdo, $dbName, 'transactions', 'idx_transactions_user_created', '`user_id`, `created_at`');
addIndexIfMissing($pdo, $dbName, 'jobs', 'idx_jobs_status_created', '`status`, `created_at`');
addIndexIfMissing($pdo, $dbName, 'wallet_logs', 'idx_wallet_logs_user_created', '`user_id`, `created_at`');
addColumnIfMissing($pdo, $dbName, 'jobs', 'last_error', '`last_error` TEXT NULL');
addColumnIfMissing($pdo, $dbName, 'users', 'reset_token', '`reset_token` VARCHAR(100) NULL');
addColumnIfMissing($pdo, $dbName, 'users', 'reset_expires_at', '`reset_expires_at` DATETIME NULL');
addColumnIfMissing($pdo, $dbName, 'users', 'oauth_provider', '`oauth_provider` VARCHAR(50) NULL');
addColumnIfMissing($pdo, $dbName, 'users', 'oauth_provider_id', '`oauth_provider_id` VARCHAR(191) NULL');
addIndexIfMissing($pdo, $dbName, 'users', 'idx_users_oauth_provider', '`oauth_provider`, `oauth_provider_id`');

echo "[DONE] Hardening migration finished.\n";
