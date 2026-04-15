<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap_test_env.php';

skipIfRootDbUnavailable();

$dbName = 'market_test_auth_' . date('Ymd_His') . '_' . random_int(1000, 9999);
setTestEnv($dbName);

function assertAuth(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "[INT-FAIL] {$message}\n");
        exit(1);
    }
}

$rootPdo = pdoRoot();
$rootPdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$rootPdo->exec("USE `$dbName`");

$rootPdo->exec("
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(191) UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(50) DEFAULT 'user',
  balance DECIMAL(10,2) DEFAULT 0.00,
  api_token VARCHAR(100) NULL,
  totp_secret VARCHAR(255) NULL,
  referrer_id INT NULL,
  is_banned TINYINT(1) DEFAULT 0,
  email_verified_at TIMESTAMP NULL,
  verify_token VARCHAR(100) NULL,
  reset_token VARCHAR(100) NULL,
  reset_expires_at DATETIME NULL,
  oauth_provider VARCHAR(50) NULL,
  oauth_provider_id VARCHAR(191) NULL,
  avatar VARCHAR(255) NULL,
  name VARCHAR(100) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$rootPdo->exec("
CREATE TABLE login_attempts (
  ip VARCHAR(45) PRIMARY KEY,
  attempts INT DEFAULT 0,
  last_attempt TIMESTAMP NULL
)");

forceDatabaseConnection($dbName);

\Src\Services\SessionService::start();
$auth = new \Src\Services\AuthService();

$passwordHash = password_hash('OldPassword123', PASSWORD_ARGON2ID);
$insert = pdoRoot()->prepare("INSERT INTO `$dbName`.users (email, password, role, verify_token) VALUES (?, ?, 'user', ?)");
$insert->execute(['reset@example.test', $passwordHash, 'verify-token-1']);

$auth->createPasswordReset('reset@example.test');
$pdo = forceDatabaseConnection($dbName);
$user = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$user->execute(['reset@example.test']);
$user = $user->fetch();
$userId = (int)($user['id'] ?? 0);
assertAuth(!empty($user['reset_token']), 'Password reset token was not created.');
assertAuth(!empty($user['reset_expires_at']), 'Password reset expiration was not set.');

$token = (string)$user['reset_token'];
$validated = $auth->validateResetToken($token);
assertAuth((int)($validated['id'] ?? 0) === $userId, 'validateResetToken did not return the expected user.');

$changed = $auth->resetPassword($token, 'NewPassword123');
assertAuth($changed, 'resetPassword returned false for a valid token.');
$userAfterReset = $pdo->query("SELECT * FROM users WHERE id = {$userId}")->fetch();
assertAuth(password_verify('NewPassword123', (string)$userAfterReset['password']), 'Password hash was not updated.');
assertAuth($userAfterReset['reset_token'] === null, 'Reset token was not cleared after password reset.');

$social = $auth->findOrCreateSocialUser('github', ['id' => 'gh-1', 'email' => 'social@example.test']);
assertAuth(!empty($social['id']), 'Social user was not created.');
assertAuth($social['oauth_provider'] === 'github', 'Social provider was not persisted on new user.');

$insert->execute(['existing@example.test', password_hash('Pass123456', PASSWORD_ARGON2ID), 'verify-token-2']);
$existing = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$existing->execute(['existing@example.test']);
$existingId = (int)$existing->fetchColumn();
$linked = $auth->findOrCreateSocialUser('google', ['id' => 'g-1', 'email' => 'existing@example.test']);
assertAuth((int)$linked['id'] === $existingId, 'Existing email was not linked to the social account.');
assertAuth($linked['oauth_provider'] === 'google', 'Existing account was not updated with social provider.');

$socialAuth = new \Src\Services\SocialAuthService();
$state = $socialAuth->createState();
assertAuth($state !== '', 'OAuth state was not generated.');
assertAuth($socialAuth->verifyAndConsumeState($state), 'OAuth state verification failed.');
assertAuth(!$socialAuth->verifyAndConsumeState($state), 'OAuth state should be one-time use.');

$rootPdo->exec("DROP DATABASE `$dbName`");
echo "[INT-OK] Password reset flow works.\n";
echo "[INT-OK] Social account create/link works.\n";
echo "[INT-OK] OAuth state is one-time use.\n";
echo "[INT-OK] Auth integration suite passed.\n";
exit(0);
