<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$paymentController = $root . '/src/Controllers/PaymentController.php';
$middleware = $root . '/src/Core/Middleware.php';
$paymentService = $root . '/src/Services/PaymentService.php';

$files = [$paymentController, $middleware, $paymentService];
foreach ($files as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "[SMOKE-FAIL] Missing file: {$file}\n");
        exit(1);
    }
}

$controllerCode = file_get_contents($paymentController);
$middlewareCode = file_get_contents($middleware);
$serviceCode = file_get_contents($paymentService);

if ($controllerCode === false || $middlewareCode === false || $serviceCode === false) {
    fwrite(STDERR, "[SMOKE-FAIL] Cannot read webhook hardening files.\n");
    exit(1);
}

$checks = [
    [strpos($controllerCode, 'HTTPS required') !== false, 'Webhook HTTPS-only guard'],
    [strpos($middlewareCode, "'/payment/webhook'") !== false, 'Webhook rate-limit route rule'],
    [strpos($serviceCode, 'recordWebhookFailure(') !== false, 'Webhook failure persistence hook'],
    [strpos($serviceCode, 'Webhook failure spike detected') !== false, 'Webhook failure spike alerting'],
];

foreach ($checks as [$ok, $name]) {
    if (!$ok) {
        fwrite(STDERR, "[SMOKE-FAIL] Missing webhook hardening guard: {$name}\n");
        exit(1);
    }
}

echo "[SMOKE-OK] Webhook hardening guards are present.\n";
exit(0);
