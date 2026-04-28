<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$targets = [$root . '/src', $root . '/public', $root . '/views', $root . '/config', $root . '/deployment', $root . '/tests'];
$riiFlags = \FilesystemIterator::SKIP_DOTS;

function lintPhpFile(string $path): bool {
    $cmd = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    exec($cmd, $output, $code);
    if ($code !== 0) {
        echo "[LINT-FAIL] {$path}\n" . implode("\n", $output) . "\n";
        return false;
    }
    return true;
}

$ok = true;
foreach ($targets as $target) {
    if (!is_dir($target)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target, $riiFlags));
    foreach ($it as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
            continue;
        }
        $ok = lintPhpFile($file->getPathname()) && $ok;
    }
}

$smokeSuite = $root . '/tests/smoke/run_all.php';
if (file_exists($smokeSuite)) {
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($smokeSuite), $smokeCode);
    $ok = ($smokeCode === 0) && $ok;
}

$integrationSuites = [
    $root . '/tests/integration/webhook_integration.php',
    $root . '/tests/integration/stripe_webhook_integration.php',
    $root . '/tests/integration/auth_integration.php',
];

if (extension_loaded('pdo_mysql')) {
    foreach ($integrationSuites as $suite) {
        if (!file_exists($suite)) {
            continue;
        }
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($suite), $suiteCode);
        $ok = ($suiteCode === 0) && $ok;
    }
}

$analyticsRegistrationCheck = $root . '/tools/analytics_registration_check.php';
if (extension_loaded('pdo_mysql') && file_exists($analyticsRegistrationCheck)) {
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($analyticsRegistrationCheck), $analyticsCode);
    if ($analyticsCode !== 0) {
        echo "[ANALYTICS-REG-FAIL] Registration analytics consistency check failed.\n";
    }
    $ok = ($analyticsCode === 0) && $ok;
}

exit($ok ? 0 : 1);
