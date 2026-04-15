<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$requiredFiles = [
    $root . '/src/Services/PaymentService.php',
    $root . '/src/Controllers/AuthController.php',
    $root . '/src/Core/Middleware.php',
    $root . '/src/Services/QueueService.php',
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        fwrite(STDERR, "[SMOKE-FAIL] Missing required file: {$file}\n");
        exit(1);
    }
}

echo "[SMOKE-OK] Critical payment/auth/security files exist.\n";
exit(0);
