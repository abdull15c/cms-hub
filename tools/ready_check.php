<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/src');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('CONFIG_PATH', ROOT_PATH . '/config');

spl_autoload_register(function ($class) {
    $prefixes = [
        'Src\\' => APP_PATH . '/',
        'Config\\' => CONFIG_PATH . '/',
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

use Src\Core\Env;

$checks = [];

$check = static function (string $label, bool $ok, string $details = '') use (&$checks): void {
    $checks[] = [$label, $ok, $details];
};

$check('APP_URL configured', (string)Env::get('APP_URL', '') !== '', 'APP_URL=' . (string)Env::get('APP_URL', 'missing'));
$check('DB_NAME configured', (string)Env::get('DB_NAME', '') !== '', 'DB_NAME=' . (string)Env::get('DB_NAME', 'missing'));
$check('CRON_TOKEN configured', (string)Env::get('CRON_TOKEN', '') !== '', 'CRON_TOKEN present=' . ((string)Env::get('CRON_TOKEN', '') !== '' ? 'yes' : 'no'));

$backupDir = (string)Env::get('BACKUP_DIR', ROOT_PATH . '/storage/backups');
if ($backupDir === '') {
    $backupDir = ROOT_PATH . '/storage/backups';
}
if (!preg_match('~^(?:[A-Za-z]:[\\\\/]|/)~', $backupDir)) {
    $backupDir = ROOT_PATH . '/' . ltrim(str_replace('\\', '/', $backupDir), '/');
}

$requiredDirs = [
    ROOT_PATH . '/storage',
    ROOT_PATH . '/storage/logs',
    $backupDir,
    ROOT_PATH . '/storage/cache',
    ROOT_PATH . '/storage/cache/webhook',
    ROOT_PATH . '/storage/secure_uploads',
    ROOT_PATH . '/storage/secure_uploads/products',
    ROOT_PATH . '/storage/secure_uploads/product_images',
    ROOT_PATH . '/public/uploads',
    ROOT_PATH . '/public/uploads/branding',
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $check('Directory ready: ' . str_replace(ROOT_PATH . '/', '', $dir), is_dir($dir));
}

$requiredPhpExtensions = ['pdo', 'pdo_mysql', 'curl', 'mbstring', 'json'];
foreach ($requiredPhpExtensions as $ext) {
    $check('PHP extension: ' . $ext, extension_loaded($ext));
}

try {
    $pdo = \Config\Database::connect();
    $pdo->query('SELECT 1');
    $check('Database connection', true);

    $requiredTables = ['users', 'products', 'product_translations', 'analytics_page_views', 'jobs', 'settings', 'webhook_failures'];
    $stmt = $pdo->query('SHOW TABLES');
    $tables = array_map('strtolower', array_map('current', $stmt->fetchAll()));
    foreach ($requiredTables as $table) {
        $check('Table exists: ' . $table, in_array(strtolower($table), $tables, true));
    }

    $dbName = (string)Env::get('DB_NAME', '');
    $requiredColumns = [
        ['transactions', 'updated_at'],
        ['transactions', 'provider_payment_id'],
        ['jobs', 'last_error'],
    ];
    $columnStmt = $pdo->prepare(
        'SELECT 1 FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ? LIMIT 1'
    );
    foreach ($requiredColumns as [$table, $column]) {
        $columnStmt->execute([$dbName, $table, $column]);
        $check('Column exists: ' . $table . '.' . $column, (bool)$columnStmt->fetchColumn());
    }
} catch (Throwable $e) {
    $check('Database connection', false, $e->getMessage());
}

$smtpHost = (string)Env::get('SMTP_HOST', '');
$smtpUser = (string)Env::get('SMTP_USER', '');
$smtpPass = (string)Env::get('SMTP_PASS', '');
$smtpEncryption = strtolower((string)Env::get('SMTP_ENCRYPTION', 'tls'));
$mailFrom = (string)Env::get('MAIL_FROM_ADDRESS', '');
$smtpCredsConsistent = ($smtpUser === '' && $smtpPass === '') || ($smtpUser !== '' && $smtpPass !== '');
$smtpReady = $smtpHost !== ''
    && $smtpHost !== 'smtp.mailtrap.io'
    && $smtpCredsConsistent
    && ($smtpUser === '' || ($smtpUser !== 'user' && $smtpPass !== 'pass'));
$check('SMTP configured', $smtpReady, $smtpReady ? ($smtpHost . ' encryption=' . $smtpEncryption) : 'Using placeholder or empty SMTP credentials');
$check('SMTP credentials consistent', $smtpCredsConsistent, $smtpCredsConsistent ? 'auth=' . (($smtpUser !== '' && $smtpPass !== '') ? 'enabled' : 'disabled') : 'Set both SMTP_USER and SMTP_PASS or leave both empty');
$check('MAIL_FROM_ADDRESS valid', $mailFrom === '' || filter_var($mailFrom, FILTER_VALIDATE_EMAIL) !== false, $mailFrom !== '' ? $mailFrom : 'Will fallback to noreply@your-domain');

$backupKeepDays = trim((string)Env::get('BACKUP_KEEP_DAYS', ''));
if ($backupKeepDays === '') {
    $backupKeepDays = '7';
}
$check('BACKUP_KEEP_DAYS valid', ctype_digit($backupKeepDays) && (int)$backupKeepDays > 0, 'BACKUP_KEEP_DAYS=' . $backupKeepDays);

$workerSleepSeconds = trim((string)Env::get('WORKER_SLEEP_SECONDS', ''));
$workerMaxJobs = trim((string)Env::get('WORKER_MAX_JOBS', ''));
$workerMaxRuntime = trim((string)Env::get('WORKER_MAX_RUNTIME', ''));
if ($workerSleepSeconds === '') {
    $workerSleepSeconds = '3';
}
if ($workerMaxJobs === '') {
    $workerMaxJobs = '500';
}
if ($workerMaxRuntime === '') {
    $workerMaxRuntime = '3600';
}
$check('WORKER_SLEEP_SECONDS valid', ctype_digit($workerSleepSeconds) && (int)$workerSleepSeconds > 0, 'WORKER_SLEEP_SECONDS=' . $workerSleepSeconds);
$check('WORKER_MAX_JOBS valid', ctype_digit($workerMaxJobs), 'WORKER_MAX_JOBS=' . $workerMaxJobs);
$check('WORKER_MAX_RUNTIME valid', ctype_digit($workerMaxRuntime), 'WORKER_MAX_RUNTIME=' . $workerMaxRuntime);

$okCount = 0;
$failCount = 0;
foreach ($checks as [$label, $ok, $details]) {
    if ($ok) {
        $okCount++;
    } else {
        $failCount++;
    }
    $status = $ok ? '[OK]' : '[FAIL]';
    $line = $status . ' ' . $label;
    if ($details !== '') {
        $line .= ' :: ' . $details;
    }
    fwrite(STDOUT, $line . PHP_EOL);
}

fwrite(STDOUT, PHP_EOL . "Summary: OK={$okCount}, FAIL={$failCount}" . PHP_EOL);
exit($failCount > 0 ? 1 : 0);
