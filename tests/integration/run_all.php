<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$tests = [
    $root . '/tests/integration/auth_integration.php',
    $root . '/tests/integration/webhook_integration.php',
];

$failed = 0;
foreach ($tests as $test) {
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($test);
    passthru($cmd, $exitCode);
    if ($exitCode !== 0) {
        $failed++;
    }
}

if ($failed > 0) {
    fwrite(STDERR, "[INT-FAIL] {$failed} integration test suites failed.\n");
    exit(1);
}

echo "[INT-OK] All integration test suites finished.\n";
exit(0);
