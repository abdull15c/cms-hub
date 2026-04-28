<?php
declare(strict_types=1);

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
}
if (!defined('STORAGE_PATH')) {
    define('STORAGE_PATH', ROOT_PATH . '/storage');
}

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

\Src\Core\Env::load();

function testDbConfig(): array
{
    $host = getenv('TEST_DB_HOST') ?: getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('TEST_DB_PORT') ?: getenv('DB_PORT') ?: '';
    $user = getenv('TEST_DB_USER') ?: getenv('DB_USER') ?: 'root';
    $pass = getenv('TEST_DB_PASS') ?: getenv('DB_PASS') ?: '';
    $charset = getenv('TEST_DB_CHARSET') ?: getenv('CHARSET') ?: 'utf8mb4';

    return [
        'host' => $host,
        'port' => $port,
        'user' => $user,
        'pass' => $pass,
        'charset' => $charset,
    ];
}

function mysqlDsn(string $dbName = ''): string
{
    $cfg = testDbConfig();
    $parts = ["host={$cfg['host']}"];
    if ($dbName !== '') {
        $parts[] = "dbname={$dbName}";
    }
    if ($cfg['port'] !== '') {
        $parts[] = "port={$cfg['port']}";
    }
    $parts[] = "charset={$cfg['charset']}";
    return 'mysql:' . implode(';', $parts);
}

function skipIfRootDbUnavailable(): void
{
    if (!extension_loaded('pdo_mysql')) {
        echo "[INT-SKIP] pdo_mysql extension is not enabled in current PHP binary.\n";
        exit(0);
    }

    $cfg = testDbConfig();
    try {
        new PDO(mysqlDsn(), $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        $portInfo = $cfg['port'] !== '' ? ':' . $cfg['port'] : '';
        echo "[INT-SKIP] MySQL is not reachable at {$cfg['host']}{$portInfo} for integration tests.\n";
        echo "[INT-SKIP] Start MySQL or set TEST_DB_HOST/TEST_DB_PORT/TEST_DB_USER/TEST_DB_PASS.\n";
        exit(0);
    }
}

function setTestEnv(string $dbName): void
{
    $cfg = testDbConfig();
    $secret = getenv('TEST_CRYPTOMUS_PAYMENT_KEY') ?: 'integration-secret';

    $_ENV['DB_HOST'] = $cfg['host'];
    $_ENV['DB_NAME'] = $dbName;
    $_ENV['DB_USER'] = $cfg['user'];
    $_ENV['DB_PASS'] = $cfg['pass'];
    $_ENV['CHARSET'] = $cfg['charset'];
    if ($cfg['port'] !== '') {
        $_ENV['DB_PORT'] = $cfg['port'];
    }
    $_ENV['CRYPTOMUS_PAYMENT_KEY'] = $secret;

    putenv("DB_HOST={$cfg['host']}");
    putenv("DB_NAME={$dbName}");
    putenv("DB_USER={$cfg['user']}");
    putenv("DB_PASS={$cfg['pass']}");
    putenv("CHARSET={$cfg['charset']}");
    if ($cfg['port'] !== '') {
        putenv("DB_PORT={$cfg['port']}");
    }
    putenv("CRYPTOMUS_PAYMENT_KEY={$secret}");
}

function pdoRoot(): PDO
{
    $cfg = testDbConfig();
    return new PDO(mysqlDsn(), $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function resetWebhookReplayLocks(): void
{
    $dir = STORAGE_PATH . '/cache/webhook';
    if (!is_dir($dir)) {
        return;
    }
    foreach (glob($dir . '/*.lock') ?: [] as $file) {
        @unlink($file);
    }
}

function forceDatabaseConnection(string $dbName): PDO
{
    $cfg = testDbConfig();
    $pdo = new PDO(mysqlDsn($dbName), $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $ref = new ReflectionClass(\Config\Database::class);
    $prop = $ref->getProperty('pdo');
    $prop->setAccessible(true);
    $prop->setValue(null, $pdo);

    return $pdo;
}
