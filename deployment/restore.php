<?php
declare(strict_types=1);

require __DIR__ . '/backup_helpers.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$args = market_parse_args($argv);
$requestedPath = (string)($args['from'] ?? '');
if ($requestedPath === '') {
    fwrite(STDERR, "Usage: php deployment/restore.php --from=storage/backups/20260414_020000 --force\n");
    exit(1);
}

$backupRoot = market_abs_path(market_env('BACKUP_DIR', 'storage/backups'));
$backupPath = market_abs_path($requestedPath);
if (!is_dir($backupPath)) {
    $candidate = rtrim($backupRoot, '/\\') . '/' . ltrim(str_replace('\\', '/', $requestedPath), '/');
    if (is_dir($candidate)) {
        $backupPath = $candidate;
    }
}

if (!is_dir($backupPath)) {
    fwrite(STDERR, "[RESTORE-FAIL] Backup directory not found: {$requestedPath}\n");
    exit(1);
}

if (!isset($args['force'])) {
    fwrite(STDERR, "Restore is destructive. Re-run with --force after putting the app into maintenance mode.\n");
    exit(1);
}

$sqlFile = $backupPath . '/database.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "[RESTORE-FAIL] Missing database.sql in {$backupPath}\n");
    exit(1);
}

$manifest = [];
$manifestFile = $backupPath . '/manifest.json';
if (is_file($manifestFile)) {
    $manifest = json_decode((string)file_get_contents($manifestFile), true) ?: [];
}

$backupEnv = market_parse_env_file($backupPath . '/files/.env');
$dbHost = market_env('DB_HOST', $backupEnv['DB_HOST'] ?? '127.0.0.1');
$dbPort = market_env('DB_PORT', $backupEnv['DB_PORT'] ?? '');
$dbName = market_env('DB_NAME', $backupEnv['DB_NAME'] ?? '');
$dbUser = market_env('DB_USER', $backupEnv['DB_USER'] ?? 'root');
$dbPass = market_env('DB_PASS', $backupEnv['DB_PASS'] ?? '');
$charset = market_env('CHARSET', $backupEnv['CHARSET'] ?? 'utf8mb4');

if ($dbName === '') {
    fwrite(STDERR, "[RESTORE-FAIL] DB_NAME is not configured in current or backup environment.\n");
    exit(1);
}

try {
    $restoreCommand = [
        market_env('MYSQL_BIN', $backupEnv['MYSQL_BIN'] ?? 'mysql'),
        '--default-character-set=' . $charset,
        '--host=' . $dbHost,
    ];
    if ($dbPort !== '') {
        $restoreCommand[] = '--port=' . $dbPort;
    }
    $restoreCommand[] = '--user=' . $dbUser;
    $restoreCommand[] = $dbName;

    $processEnv = [];
    if ($dbPass !== '') {
        $processEnv['MYSQL_PWD'] = $dbPass;
    }

    market_run_process($restoreCommand, $processEnv, $sqlFile, null);

    $filesDir = $backupPath . '/files';
    if (is_dir($filesDir)) {
        market_copy_tree($filesDir, ROOT_PATH);
    }

    fwrite(STDOUT, "[RESTORE] Database restored from {$sqlFile}\n");
    fwrite(STDOUT, "[RESTORE] Files restored from {$filesDir}\n");
    if (!empty($manifest['created_at'])) {
        fwrite(STDOUT, "[RESTORE] Snapshot timestamp: {$manifest['created_at']}\n");
    }
} catch (Throwable $e) {
    fwrite(STDERR, "[RESTORE-FAIL] " . $e->getMessage() . "\n");
    exit(1);
}
