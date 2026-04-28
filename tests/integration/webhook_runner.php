<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap_test_env.php';

$dbName = $argv[1] ?? '';
$orderId = (int)($argv[2] ?? 0);
$amount = (string)($argv[3] ?? '0.00');
$signature = (string)($argv[4] ?? '');
$provider = strtolower((string)($argv[5] ?? 'cryptomus'));

if ($dbName === '' || $orderId <= 0 || $signature === '') {
    fwrite(STDERR, "[RUNNER-FAIL] Invalid runner arguments.\n");
    exit(2);
}
if (!in_array($provider, ['cryptomus', 'stripe'], true)) {
    fwrite(STDERR, "[RUNNER-FAIL] Unsupported provider.\n");
    exit(2);
}

setTestEnv($dbName);
forceDatabaseConnection($dbName);

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
if ($provider === 'stripe') {
    $payload = json_encode([
        'id' => 'evt_test_' . $orderId,
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_' . $orderId,
                'payment_intent' => 'pi_test_' . $orderId,
                'metadata' => ['order_id' => (string)$orderId],
                'currency' => 'usd',
                'amount_total' => (int)round((float)$amount * 100),
            ],
        ],
    ], JSON_UNESCAPED_UNICODE);
    $_SERVER['HTTP_STRIPE_SIGNATURE'] = $signature;
    (new \Src\Services\PaymentService())->handleWebhook(
        'stripe',
        json_decode($payload, true) ?: [],
        $payload
    );
    exit;
}

$payload = json_encode([
    'status' => 'paid',
    'order_id' => $orderId,
    'amount' => $amount,
    'currency' => 'USD',
    'uuid' => 'cryptomus-test-' . $orderId,
    'sign' => $signature,
], JSON_UNESCAPED_UNICODE);

(new \Src\Services\PaymentService())->handleWebhook(
    'cryptomus',
    json_decode($payload, true) ?: [],
    $payload
);
