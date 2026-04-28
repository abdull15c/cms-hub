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

$pdo->exec("
CREATE TABLE IF NOT EXISTS webhook_failures (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50) NOT NULL,
    reason VARCHAR(120) NOT NULL,
    status_code SMALLINT NOT NULL,
    ip_address VARCHAR(64) NOT NULL,
    payload_hash CHAR(64) NOT NULL,
    request_id VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_webhook_failures_provider_reason_created (provider, reason, created_at),
    INDEX idx_webhook_failures_created_at (created_at),
    INDEX idx_webhook_failures_payload_hash (payload_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "[DONE] Webhook monitoring migration applied.\n";
