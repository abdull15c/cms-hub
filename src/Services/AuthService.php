<?php
namespace Src\Services;

use Config\Database;
use Src\Services\Logger;
use Src\Core\Event;
use Src\Services\TotpService;
use Src\Services\RateLimiter;

class AuthService {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::connect();
    }

    public function attempt($email, $password) {
        // 1. Rate Limit Check
        $ip = RateLimiter::getIp();
        if (!RateLimiter::check($ip)) {
            Logger::warning("Brute force blocked: $ip");
            throw new \Exception("Too many login attempts. Try again in 15 minutes.");
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            // 2. Log Failure
            RateLimiter::fail($ip);
            Logger::warning("Failed login: $email ($ip)");
            return false;
        }

        if ($user['is_banned']) {
            throw new \Exception("Account is banned.");
        }

        // 3. Clear Failures on Success
        RateLimiter::clear($ip);
        
        // 4. Rehash if needed (Upgrade BCRYPT to ARGON2ID)
        if (password_needs_rehash($user['password'], PASSWORD_ARGON2ID)) {
            $newHash = password_hash($password, PASSWORD_ARGON2ID);
            $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $user['id']]);
        }

        return $user;
    }

    public function loginUser($user) {
        if (!empty($user['is_banned'])) {
            throw new \Exception('Account is banned.');
        }
        SessionService::set('user_id', $user['id']);
        SessionService::set('user_email', $user['email']);
        SessionService::set('role', $user['role']);
        SessionService::regenerate();
        
        Logger::info("User logged in: {$user['email']} (ID: {$user['id']})");
        Event::fire('user.login', $user);
    }

    public function register($email, $password) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) throw new \Exception("Email already registered.");

        // Use ARGON2ID for new users
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $verifyToken = bin2hex(random_bytes(32));

        $stmt = $this->pdo->prepare("INSERT INTO users (email, password, role, verify_token) VALUES (?, ?, 'user', ?)");
        $stmt->execute([$email, $hash, $verifyToken]);
        
        $uid = $this->pdo->lastInsertId();
        $user = ['id' => $uid, 'email' => $email, 'role' => 'user', 'token' => $verifyToken, 'registration_source' => 'local'];

        Logger::info("New user registered: $email");
        Event::fire('user.registered', $user);
        
        return $user;
    }

    public function verify2FA($userId, $code) {
        $stmt = $this->pdo->prepare("SELECT totp_secret FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user || empty($user['totp_secret'])) return false;
        return (new TotpService())->verify($user['totp_secret'], $code);
    }

    public function createPasswordReset(string $email): bool {
        $stmt = $this->pdo->prepare("SELECT id, email, is_banned FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !empty($user['is_banned'])) {
            return false;
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);
        $this->pdo->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?")
            ->execute([$token, $expiresAt, $user['id']]);

        return (new MailService())->sendTemplate($user['email'], 'Reset your password', 'reset_password', [
            'link' => (defined('BASE_URL') ? BASE_URL : '') . '/reset/' . $token,
            'expires_at' => $expiresAt,
        ]);
    }

    public function validateResetToken(string $token) {
        $stmt = $this->pdo->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_expires_at IS NOT NULL AND reset_expires_at >= NOW() LIMIT 1");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    public function resetPassword(string $token, string $password): bool {
        $user = $this->validateResetToken($token);
        if (!$user) {
            return false;
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $this->pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL, api_token = NULL WHERE id = ?")
            ->execute([$hash, $user['id']]);
        AuditService::log('user', 'password_reset', (int)$user['id']);
        return true;
    }

    public function findOrCreateSocialUser(string $provider, array $profile) {
        $providerId = (string)($profile['id'] ?? '');
        $email = strtolower(trim((string)($profile['email'] ?? '')));
        if ($providerId === '') {
            throw new \Exception('Provider did not return a stable account id.');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE oauth_provider = ? AND oauth_provider_id = ? LIMIT 1");
        $stmt->execute([$provider, $providerId]);
        $user = $stmt->fetch();
        if ($user) {
            if (!empty($user['is_banned'])) {
                throw new \Exception('Account is banned.');
            }
            return $user;
        }

        if ($email !== '') {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                if (!empty($user['is_banned'])) {
                    throw new \Exception('Account is banned.');
                }
                $this->pdo->prepare("UPDATE users SET oauth_provider = ?, oauth_provider_id = ?, email_verified_at = COALESCE(email_verified_at, NOW()), verify_token = NULL WHERE id = ?")
                    ->execute([$provider, $providerId, $user['id']]);
                $stmt->execute([$email]);
                return $stmt->fetch();
            }
        }

        if ($email === '') {
            throw new \Exception('Provider account has no usable email address.');
        }

        $password = password_hash(bin2hex(random_bytes(24)), PASSWORD_ARGON2ID);
        $this->pdo->prepare(
            "INSERT INTO users (email, password, role, verify_token, email_verified_at, oauth_provider, oauth_provider_id) VALUES (?, ?, 'user', NULL, NOW(), ?, ?)"
        )->execute([$email, $password, $provider, $providerId]);

        $id = (int)$this->pdo->lastInsertId();
        AuditService::log('user', 'social_register', $id, $provider);
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if ($user) {
            Event::fire('user.registered', [
                'id' => (int)$user['id'],
                'email' => (string)$user['email'],
                'role' => (string)$user['role'],
                'token' => (string)($user['verify_token'] ?? ''),
                'registration_source' => $provider,
            ]);
        }
        return $user;
    }

    public function resendVerification(int $userId): bool {
        $stmt = $this->pdo->prepare("SELECT id, email, email_verified_at, verify_token FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user || !empty($user['email_verified_at'])) {
            return false;
        }

        $token = $user['verify_token'] ?: bin2hex(random_bytes(32));
        $this->pdo->prepare("UPDATE users SET verify_token = ? WHERE id = ?")->execute([$token, $user['id']]);

        return (new MailService())->sendTemplate($user['email'], 'Verify your email', 'verify_account', [
            'link' => (defined('BASE_URL') ? BASE_URL : '') . '/verify/' . $token,
        ]);
    }

}
