<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$index = file_get_contents($root . '/public/index.php');
$installer = file_get_contents($root . '/public/install/index.php');
$processQueue = file_get_contents($root . '/public/process_queue.php');
$rateLimiter = file_get_contents($root . '/src/Services/RateLimiter.php');

if ($index === false || $installer === false || $processQueue === false || $rateLimiter === false) {
    fwrite(STDERR, "[SMOKE-FAIL] Cannot read hardening target files.\n");
    exit(1);
}

$checks = [
    [strpos($index, "install/index.php?setup_token=") === false, 'Installer token is not propagated in redirect URL'],
    [strpos($installer, '?step=2&amp;setup_token=') === false, 'Installer step link no longer leaks token in URL'],
    [strpos($processQueue, "SessionService::get('role')") === false, 'Queue endpoint is not unlocked by admin session'],
    [strpos($processQueue, "Method Not Allowed") !== false, 'Queue endpoint enforces POST for web requests'],
    [strpos($rateLimiter, 'flock($handle, LOCK_EX)') !== false, 'Scoped rate limiter uses atomic lock'],
];

foreach ($checks as [$ok, $name]) {
    if (!$ok) {
        fwrite(STDERR, "[SMOKE-FAIL] Hardening regression detected: {$name}\n");
        exit(1);
    }
}

echo "[SMOKE-OK] Hardening regression guards are present.\n";
exit(0);
