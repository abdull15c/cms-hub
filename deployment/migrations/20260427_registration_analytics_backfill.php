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

function hasIndexBackfill(PDO $pdo, string $db, string $table, string $index): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1");
    $stmt->execute([$db, $table, $index]);
    return (bool)$stmt->fetchColumn();
}

if (!hasIndexBackfill($pdo, $dbName, 'analytics_registrations', 'uq_ar_user_id')) {
    $pdo->exec(
        "DELETE ar1 FROM analytics_registrations ar1
         INNER JOIN analytics_registrations ar2
           ON ar1.user_id = ar2.user_id
          AND ar1.id > ar2.id"
    );
    $pdo->exec("ALTER TABLE analytics_registrations ADD UNIQUE INDEX uq_ar_user_id (`user_id`)");
    echo "[OK] Added unique index analytics_registrations.uq_ar_user_id\n";
} else {
    echo "[SKIP] Unique index exists: analytics_registrations.uq_ar_user_id\n";
}

$sql = "INSERT INTO analytics_registrations (user_id, source, provider, session_id, ip_hash, country_code, country_name, created_at)
        SELECT
            u.id AS user_id,
            CASE
                WHEN COALESCE(TRIM(u.oauth_provider), '') = '' THEN 'local'
                ELSE 'social'
            END AS source,
            CASE
                WHEN COALESCE(TRIM(u.oauth_provider), '') = '' THEN NULL
                ELSE LOWER(TRIM(u.oauth_provider))
            END AS provider,
            NULL AS session_id,
            SHA2(CONCAT('legacy-registration:', u.id), 256) AS ip_hash,
            'ZZ' AS country_code,
            'Unknown' AS country_name,
            u.created_at AS created_at
        FROM users u
        LEFT JOIN analytics_registrations ar ON ar.user_id = u.id
        WHERE ar.user_id IS NULL";

$inserted = $pdo->exec($sql);
echo "[OK] Backfilled rows: " . (int)$inserted . "\n";
echo "[DONE] Registration analytics backfill finished.\n";

