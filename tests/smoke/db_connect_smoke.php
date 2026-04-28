<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/Core/Env.php';

\Src\Core\Env::load();
$host = \Src\Core\Env::get('DB_HOST', 'localhost');
$port = \Src\Core\Env::get('DB_PORT', '');
$db = \Src\Core\Env::get('DB_NAME', '');
$user = \Src\Core\Env::get('DB_USER', 'root');
$pass = \Src\Core\Env::get('DB_PASS', '');
$charset = \Src\Core\Env::get('CHARSET', 'utf8mb4');

if ($db === '') {
    echo "[SMOKE-SKIP] DB_NAME is not configured.\n";
    exit(0);
}

try {
    $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
    if ((string)$port !== '') {
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
    }
    new PDO($dsn, (string)$user, (string)$pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    echo "[SMOKE-OK] DB connect works ({$host}/{$db}).\n";
    exit(0);
} catch (Throwable $e) {
    $required = in_array(strtolower((string)(getenv('DB_SMOKE_REQUIRED') ?: '')), ['1', 'true', 'yes', 'on'], true);
    $message = "[SMOKE-" . ($required ? 'FAIL' : 'SKIP') . "] " . $e->getMessage() . "\n";
    fwrite($required ? STDERR : STDOUT, $message);
    exit($required ? 1 : 0);
}
