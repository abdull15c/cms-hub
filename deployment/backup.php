<?php
declare(strict_types=1);

require __DIR__ . '/backup_helpers.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$args = market_parse_args($argv);
$backupRoot = market_abs_path((string)($args['path'] ?? market_env('BACKUP_DIR', 'storage/backups')));
$keepDays = (int)($args['keep-days'] ?? market_env('BACKUP_KEEP_DAYS', '7'));
if ($keepDays <= 0) {
    $keepDays = 7;
}

$dbName = market_env('DB_NAME', '');
if ($dbName === '') {
    fwrite(STDERR, "DB_NAME is not configured.\n");
    exit(1);
}

$timestamp = date('Ymd_His');
$snapshotDir = rtrim($backupRoot, '/\\') . '/' . $timestamp;
$filesDir = $snapshotDir . '/files';
$sqlFile = $snapshotDir . '/database.sql';

try {
    market_ensure_dir($backupRoot);
    market_ensure_dir($snapshotDir);
    market_ensure_dir($filesDir);

    $dumpCommand = [
        market_env('MYSQLDUMP_BIN', 'mysqldump'),
        '--default-character-set=' . market_env('CHARSET', 'utf8mb4'),
        '--single-transaction',
        '--quick',
        '--skip-lock-tables',
        '--routines',
        '--triggers',
        '--host=' . market_env('DB_HOST', '127.0.0.1'),
    ];
    $dbPort = market_env('DB_PORT', '');
    if ($dbPort !== '') {
        $dumpCommand[] = '--port=' . $dbPort;
    }
    $dumpCommand[] = '--user=' . market_env('DB_USER', 'root');
    $dumpCommand[] = $dbName;

    $processEnv = [];
    $dbPass = market_env('DB_PASS', '');
    if ($dbPass !== '') {
        $processEnv['MYSQL_PWD'] = $dbPass;
    }

    market_run_process($dumpCommand, $processEnv, null, $sqlFile);

    $copiedPaths = [];
    foreach (['.env', 'storage/.installed.lock', 'storage/secure_uploads', 'public/uploads'] as $relativePath) {
        $source = market_abs_path($relativePath);
        if (!file_exists($source)) {
            continue;
        }

        market_copy_tree($source, $filesDir . '/' . str_replace('\\', '/', $relativePath));
        $copiedPaths[] = $relativePath;
    }

    $manifest = [
        'created_at' => date(DATE_ATOM),
        'snapshot' => basename($snapshotDir),
        'app_url' => market_env('APP_URL', ''),
        'db_name' => $dbName,
        'included_paths' => $copiedPaths,
    ];
    file_put_contents(
        $snapshotDir . '/manifest.json',
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
    );

    $cutoff = time() - ($keepDays * 86400);
    $items = scandir($backupRoot) ?: [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === basename($snapshotDir)) {
            continue;
        }

        $fullPath = $backupRoot . '/' . $item;
        if (!is_dir($fullPath) || filemtime($fullPath) === false || filemtime($fullPath) >= $cutoff) {
            continue;
        }

        $realBackupRoot = realpath($backupRoot);
        $realFullPath = realpath($fullPath);
        if ($realBackupRoot === false || $realFullPath === false) {
            continue;
        }
        if (strpos($realFullPath, $realBackupRoot . DIRECTORY_SEPARATOR) !== 0) {
            continue;
        }

        market_delete_tree($realFullPath);
    }

    fwrite(STDOUT, "[BACKUP] Snapshot created: {$snapshotDir}\n");
    fwrite(STDOUT, "[BACKUP] Database dump: {$sqlFile}\n");
    fwrite(STDOUT, "[BACKUP] Files copied: " . ($copiedPaths ? implode(', ', $copiedPaths) : 'none') . "\n");
} catch (Throwable $e) {
    fwrite(STDERR, "[BACKUP-FAIL] " . $e->getMessage() . "\n");
    exit(1);
}
