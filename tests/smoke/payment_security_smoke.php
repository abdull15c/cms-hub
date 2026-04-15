<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$target = $root . '/src/Services/PaymentService.php';
$code = file_get_contents($target);

if ($code === false) {
    fwrite(STDERR, "[SMOKE-FAIL] Cannot read PaymentService.php\n");
    exit(1);
}

$checks = [
    "hash_hmac('sha256'" => 'Crypto webhook signature verification',
    "hash_equals(\$expected, \$signature)" => 'Constant-time signature comparison',
    "isWebhookReplay(" => 'Replay protection check',
    "abs(\$expected - (float)\$paidAmount) > 0.01" => 'Paid amount validation',
];

foreach ($checks as $needle => $name) {
    if (strpos($code, $needle) === false) {
        fwrite(STDERR, "[SMOKE-FAIL] Missing payment security guard: {$name}\n");
        exit(1);
    }
}

echo "[SMOKE-OK] Payment security guards are present.\n";
exit(0);
