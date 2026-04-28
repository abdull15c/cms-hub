<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap_test_env.php';

skipIfRootDbUnavailable();

$dbName = 'market_test_stripe_' . date('Ymd_His') . '_' . random_int(1000, 9999);
$secret = getenv('TEST_STRIPE_WEBHOOK_SECRET') ?: 'whsec_test_integration';
$phpBin = PHP_BINARY;
$runner = __DIR__ . '/webhook_runner.php';

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "[INT-FAIL] {$message}\n");
        exit(1);
    }
}

function execPhp(string $phpBin, array $args): array
{
    $escaped = array_map('escapeshellarg', $args);
    $cmd = escapeshellarg($phpBin) . ' ' . implode(' ', $escaped) . ' 2>&1';
    $output = [];
    $code = 0;
    exec($cmd, $output, $code);
    return [$code, implode("\n", $output)];
}

function buildStripeSignature(string $secret, string $rawBody, int $timestamp): string
{
    $sig = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
    return 't=' . $timestamp . ',v1=' . $sig;
}

setTestEnv($dbName);
resetWebhookReplayLocks();

$rootPdo = pdoRoot();
$rootPdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$rootPdo->exec("USE `$dbName`");

$rootPdo->exec("
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(191) UNIQUE,
  balance DECIMAL(10,2) DEFAULT 0.00
)");
$rootPdo->exec("
CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL DEFAULT 0,
  provider VARCHAR(50) NOT NULL,
  provider_payment_id VARCHAR(191) NULL,
  amount DECIMAL(10,2) NOT NULL,
  status VARCHAR(20) NOT NULL,
  coupon_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL
)");
$rootPdo->exec("CREATE TABLE settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT)");
$rootPdo->exec("
CREATE TABLE coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) UNIQUE,
  discount_percent INT,
  max_uses INT DEFAULT 100,
  used_count INT DEFAULT 0
)");
$rootPdo->exec("
CREATE TABLE wallet_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  type VARCHAR(20) NOT NULL,
  reference_id INT NULL,
  description VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$rootPdo->exec("INSERT INTO users (email, balance) VALUES ('int@test.local', 0.00)");
$rootPdo->exec(
    "INSERT INTO settings (setting_key, setting_value) VALUES ('stripe_webhook_secret', "
    . $rootPdo->quote($secret)
    . "), ('stripe_currency', 'USD')"
);
$rootPdo->exec("INSERT INTO transactions (id, user_id, product_id, provider, provider_payment_id, amount, status) VALUES (2001, 1, 0, 'stripe', 'cs_test_2001', 15.00, 'pending')");

$basePayload = [
    'id' => 'evt_test_2001',
    'type' => 'checkout.session.completed',
    'data' => [
        'object' => [
            'id' => 'cs_test_2001',
            'payment_intent' => 'pi_test_2001',
            'metadata' => ['order_id' => '2001'],
            'currency' => 'usd',
            'amount_total' => 1500,
        ],
    ],
];
$baseRaw = json_encode($basePayload, JSON_UNESCAPED_UNICODE);
assertTrue($baseRaw !== false, 'Stripe payload must encode to JSON.');
$ts = time();
$validSign = buildStripeSignature($secret, (string)$baseRaw, $ts);

// 1) Invalid signature must fail and keep transaction pending.
[$code1, $out1] = execPhp($phpBin, [$runner, $dbName, '2001', '15.00', 't=' . $ts . ',v1=invalid', 'stripe']);
$invalidRejected = strpos($out1, 'Sign Error') !== false;
assertTrue($invalidRejected, 'Invalid Stripe signature run must be rejected.');
$trxStatus = (string)$rootPdo->query("SELECT status FROM transactions WHERE id = 2001")->fetchColumn();
assertTrue($trxStatus === 'pending', 'Transaction must remain pending after invalid Stripe signature.');
echo "[INT-OK] Stripe invalid signature is rejected.\n";

// 2) Valid signature marks paid and applies deposit exactly once.
[$code2, $out2] = execPhp($phpBin, [$runner, $dbName, '2001', '15.00', $validSign, 'stripe']);
assertTrue(strpos($out2, 'OK') !== false, 'Valid Stripe signature run must return OK.');
$trxStatus2 = (string)$rootPdo->query("SELECT status FROM transactions WHERE id = 2001")->fetchColumn();
$balance2 = (float)$rootPdo->query("SELECT balance FROM users WHERE id = 1")->fetchColumn();
$walletLogs2 = (int)$rootPdo->query("SELECT COUNT(*) FROM wallet_logs WHERE reference_id = 2001")->fetchColumn();
assertTrue($trxStatus2 === 'paid', 'Stripe transaction must become paid after valid signature.');
assertTrue(abs($balance2 - 15.00) < 0.001, 'Balance must increase by 15.00 after valid Stripe signature.');
assertTrue($walletLogs2 === 1, 'Exactly one deposit log expected after valid Stripe signature.');
echo "[INT-OK] Stripe valid signature processes payment.\n";

// 3) Replay should be idempotent.
[$code3, $out3] = execPhp($phpBin, [$runner, $dbName, '2001', '15.00', $validSign, 'stripe']);
$balance3 = (float)$rootPdo->query("SELECT balance FROM users WHERE id = 1")->fetchColumn();
$walletLogs3 = (int)$rootPdo->query("SELECT COUNT(*) FROM wallet_logs WHERE reference_id = 2001")->fetchColumn();
assertTrue(abs($balance3 - 15.00) < 0.001, 'Stripe replay must not change balance.');
assertTrue($walletLogs3 === 1, 'Stripe replay must not create additional wallet logs.');
echo "[INT-OK] Stripe replay is idempotent.\n";

$rootPdo->exec("DROP DATABASE `$dbName`");
echo "[INT-OK] Stripe webhook integration suite passed.\n";
