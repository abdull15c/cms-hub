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
    "private const PROVIDERS = ['yoomoney', 'payeer', 'yookassa', 'cryptomus', 'lemonsqueezy', 'stripe']" => 'Provider list contains active gateways only',
    "createYooKassaPayment(" => 'YooKassa payment creation',
    "fetchYooKassaPayment(" => 'YooKassa webhook verification through API fetch',
    "createCryptomusPayment(" => 'Cryptomus payment creation',
    "verifyCryptomus(" => 'Cryptomus webhook verification',
    "createLemonSqueezyPayment(" => 'Lemon Squeezy payment creation',
    "verifyLemonSqueezy(" => 'Lemon Squeezy webhook verification',
    "hash_hmac('sha256'" => 'Lemon Squeezy webhook signature verification',
    "createStripePayment(" => 'Stripe payment creation',
    "verifyStripe(" => 'Stripe webhook verification',
    "HTTP_STRIPE_SIGNATURE" => 'Stripe webhook header validation',
    "md5(base64_encode(json_encode(\$payload, JSON_UNESCAPED_UNICODE))" => 'Cryptomus signature verification',
    "abs(\$expected - (float)\$paidAmount) > 0.01" => 'Paid amount validation',
    "Payment provider mismatch" => 'Payment provider reconciliation',
    "Product is not available for purchase." => 'Draft product purchase guard',
    "\$trx['status'] !== 'pending'" => 'Non-pending payment guard',
];

foreach ($checks as $needle => $name) {
    if (strpos($code, $needle) === false) {
        fwrite(STDERR, "[SMOKE-FAIL] Missing payment security guard: {$name}\n");
        exit(1);
    }
}

if (stripos($code, 'freekassa') !== false || strpos($code, 'fk_') !== false) {
    fwrite(STDERR, "[SMOKE-FAIL] FreeKassa code is still present in PaymentService.php\n");
    exit(1);
}

echo "[SMOKE-OK] Payment security guards are present.\n";
exit(0);
