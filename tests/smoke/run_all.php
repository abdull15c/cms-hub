<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$tests = [
    $root . '/tests/smoke/basic_smoke.php',
    $root . '/tests/smoke/payment_security_smoke.php',
    $root . '/tests/smoke/idempotency_smoke.php',
    $root . '/tests/smoke/oauth_state_smoke.php',
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
    fwrite(STDERR, "[SMOKE-FAIL] {$failed} smoke tests failed.\n");
    exit(1);
}

echo "[SMOKE-OK] All smoke tests passed.\n";
exit(0);
