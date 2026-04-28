<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap_test_env.php';

skipIfRootDbUnavailable();

$dbName = 'market_test_webhook_' . date('Ymd_His') . '_' . random_int(1000, 9999);
$secret = getenv('TEST_CRYPTOMUS_PAYMENT_KEY') ?: 'integration-secret';
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

function buildSignature(string $secret, array $payload): string
{
    return md5(base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE)) . $secret);
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
$rootPdo->exec("INSERT INTO settings (setting_key, setting_value) VALUES ('cryptomus_payment_key', " . $rootPdo->quote($secret) . "), ('cryptomus_currency', 'USD')");
$rootPdo->exec("INSERT INTO transactions (id, user_id, product_id, provider, provider_payment_id, amount, status) VALUES (1001, 1, 0, 'cryptomus', 'cryptomus-test-1001', 10.00, 'pending')");

$payload = [
    'status' => 'paid',
    'order_id' => 1001,
    'amount' => '10.00',
    'currency' => 'USD',
    'uuid' => 'cryptomus-test-1001',
];

// 1) Invalid signature should fail and keep transaction pending.
[$code1, $out1] = execPhp($phpBin, [$runner, $dbName, '1001', '10.00', 'invalid-signature']);
$invalidRejected = (strpos($out1, 'Sign Error') !== false) || (strpos($out1, 'Invalid webhook payload') !== false);
assertTrue($invalidRejected, 'Invalid signature run must be rejected by webhook guard.');
$trxStatus = (string)$rootPdo->query("SELECT status FROM transactions WHERE id = 1001")->fetchColumn();
assertTrue($trxStatus === 'pending', 'Transaction must remain pending after invalid signature.');
echo "[INT-OK] Invalid signature is rejected.\n";

// 2) Valid signature should mark paid and apply deposit exactly once.
$validSign = buildSignature($secret, $payload);
[$code2, $out2] = execPhp($phpBin, [$runner, $dbName, '1001', '10.00', $validSign]);
assertTrue(strpos($out2, 'OK') !== false, 'Valid signature run must return OK.');
$trxStatus2 = (string)$rootPdo->query("SELECT status FROM transactions WHERE id = 1001")->fetchColumn();
$balance2 = (float)$rootPdo->query("SELECT balance FROM users WHERE id = 1")->fetchColumn();
$walletLogs2 = (int)$rootPdo->query("SELECT COUNT(*) FROM wallet_logs WHERE reference_id = 1001")->fetchColumn();
assertTrue($trxStatus2 === 'paid', 'Transaction must become paid after valid signature.');
assertTrue(abs($balance2 - 10.00) < 0.001, 'Balance must increase by 10.00 after valid signature.');
assertTrue($walletLogs2 === 1, 'Exactly one deposit log expected after valid signature.');
echo "[INT-OK] Valid signature processes payment.\n";

// 3) Replay should be idempotent and must not duplicate wallet effect.
[$code3, $out3] = execPhp($phpBin, [$runner, $dbName, '1001', '10.00', $validSign]);
$balance3 = (float)$rootPdo->query("SELECT balance FROM users WHERE id = 1")->fetchColumn();
$walletLogs3 = (int)$rootPdo->query("SELECT COUNT(*) FROM wallet_logs WHERE reference_id = 1001")->fetchColumn();
assertTrue(abs($balance3 - 10.00) < 0.001, 'Replay must not change balance.');
assertTrue($walletLogs3 === 1, 'Replay must not create additional wallet logs.');
echo "[INT-OK] Replay is idempotent.\n";

$rootPdo->exec("DROP DATABASE `$dbName`");
echo "[INT-OK] Webhook integration suite passed.\n";
