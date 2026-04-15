<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/Core/Env.php';

\Src\Core\Env::load();
$host = \Src\Core\Env::get('DB_HOST', 'localhost');
$db = \Src\Core\Env::get('DB_NAME', 'dle_market_db');
$user = \Src\Core\Env::get('DB_USER', 'root');
$pass = \Src\Core\Env::get('DB_PASS', '');
$charset = \Src\Core\Env::get('CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
    new PDO($dsn, (string)$user, (string)$pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    echo "[SMOKE-OK] DB connect works ({$host}/{$db}).\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "[SMOKE-FAIL] " . $e->getMessage() . "\n");
    exit(1);
}
