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

function hasIndexAnalytics(PDO $pdo, string $db, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        "SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1"
    );
    $stmt->execute([$db, $table, $index]);
    return (bool)$stmt->fetchColumn();
}

function addIndexAnalytics(PDO $pdo, string $db, string $table, string $index, string $definition): void
{
    if (!hasIndexAnalytics($pdo, $db, $table, $index)) {
        $pdo->exec("ALTER TABLE `$table` ADD INDEX `$index` ($definition)");
        echo "[OK] Added index $table.$index\n";
        return;
    }
    echo "[SKIP] Index exists: $table.$index\n";
}

$pdo->exec("CREATE TABLE IF NOT EXISTS analytics_page_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    path VARCHAR(255) NOT NULL,
    user_id INT NULL,
    session_id VARCHAR(128) NULL,
    ip_hash CHAR(64) NOT NULL,
    country_code VARCHAR(16) NULL,
    country_name VARCHAR(120) NULL,
    referer VARCHAR(1024) NULL,
    user_agent VARCHAR(1024) NULL,
    visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "[OK] Ensured table analytics_page_views\n";

$pdo->exec("CREATE TABLE IF NOT EXISTS analytics_logins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(128) NULL,
    ip_hash CHAR(64) NOT NULL,
    country_code VARCHAR(16) NULL,
    country_name VARCHAR(120) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "[OK] Ensured table analytics_logins\n";

$pdo->exec("CREATE TABLE IF NOT EXISTS analytics_ip_geo (
    ip_hash CHAR(64) PRIMARY KEY,
    country_code VARCHAR(16) NULL,
    country_name VARCHAR(120) NULL,
    source VARCHAR(20) NULL,
    resolved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "[OK] Ensured table analytics_ip_geo\n";

addIndexAnalytics($pdo, $dbName, 'analytics_page_views', 'idx_apv_visited_at', '`visited_at`');
addIndexAnalytics($pdo, $dbName, 'analytics_page_views', 'idx_apv_path_visited_at', '`path`, `visited_at`');
addIndexAnalytics($pdo, $dbName, 'analytics_page_views', 'idx_apv_country_visited_at', '`country_code`, `visited_at`');
addIndexAnalytics($pdo, $dbName, 'analytics_page_views', 'idx_apv_user_visited_at', '`user_id`, `visited_at`');
addIndexAnalytics($pdo, $dbName, 'analytics_logins', 'idx_al_created_at', '`created_at`');
addIndexAnalytics($pdo, $dbName, 'analytics_logins', 'idx_al_user_created_at', '`user_id`, `created_at`');
addIndexAnalytics($pdo, $dbName, 'analytics_logins', 'idx_al_country_created_at', '`country_code`, `created_at`');
addIndexAnalytics($pdo, $dbName, 'analytics_ip_geo', 'idx_aig_resolved_at', '`resolved_at`');

echo "[DONE] Analytics migration finished.\n";
