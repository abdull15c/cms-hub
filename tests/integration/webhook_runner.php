<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap_test_env.php';

$dbName = $argv[1] ?? '';
$orderId = (int)($argv[2] ?? 0);
$amount = (string)($argv[3] ?? '0.00');
$timestamp = (string)($argv[4] ?? '');
$signature = (string)($argv[5] ?? '');

if ($dbName === '' || $orderId <= 0 || $timestamp === '' || $signature === '') {
    fwrite(STDERR, "[RUNNER-FAIL] Invalid runner arguments.\n");
    exit(2);
}

setTestEnv($dbName);
forceDatabaseConnection($dbName);

$payload = json_encode([
    'status' => 'paid',
    'order_id' => $orderId,
    'amount_usd' => $amount,
], JSON_UNESCAPED_UNICODE);

$_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'] = $timestamp;
$_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] = $signature;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

(new \Src\Services\PaymentService())->handleWebhook(
    'crypto',
    json_decode($payload, true) ?: [],
    $payload
);
