<?php
namespace Src\Services;
use Config\Database;

class RateLimiter {
    private const CACHE_DIR = '/cache/rate_limits';
    
    // SECURE IP DETECTION
    public static function getIp() {
        // Only trust proxies if explicitly allowed in ENV
        // Default to REMOTE_ADDR (Safest)
        $trustProxy = isset($_ENV['TRUST_PROXIES']) && $_ENV['TRUST_PROXIES'] === 'true';

        if ($trustProxy) {
            if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                return trim($list[0]);
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function check($ip = null) {
        $ip = $ip ?? self::getIp();
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip = ?");
        $stmt->execute([$ip]);
        $row = $stmt->fetch();

        if ($row && $row['attempts'] >= 5) {
            if ((time() - strtotime($row['last_attempt'])) < 900) return false;
            $pdo->prepare("UPDATE login_attempts SET attempts = 0 WHERE ip = ?")->execute([$ip]);
        }
        return true;
    }

    public static function fail($ip = null) {
        $ip = $ip ?? self::getIp();
        Database::connect()->prepare("INSERT INTO login_attempts (ip, attempts) VALUES (?, 1) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()")->execute([$ip]);
    }

    public static function clear($ip = null) {
        $ip = $ip ?? self::getIp();
        Database::connect()->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
    }

    public static function attempt(string $scope, int $limit, int $windowSeconds, $ip = null): bool
    {
        $ip = $ip ?? self::getIp();
        $limit = max(1, $limit);
        $windowSeconds = max(1, $windowSeconds);
        $file = self::scopeFile($scope, $ip);

        $state = self::readScopeState($file);
        $now = time();
        if (($state['reset_at'] ?? 0) <= $now) {
            $state = ['count' => 0, 'reset_at' => $now + $windowSeconds];
        }

        if (($state['count'] ?? 0) >= $limit) {
            return false;
        }

        $state['count'] = (int)($state['count'] ?? 0) + 1;
        self::writeScopeState($file, $state);
        return true;
    }

    public static function clearScope(string $scope, $ip = null): void
    {
        $ip = $ip ?? self::getIp();
        $file = self::scopeFile($scope, $ip);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private static function scopeFile(string $scope, string $ip): string
    {
        $dir = defined('STORAGE_PATH') ? STORAGE_PATH . self::CACHE_DIR : dirname(__DIR__, 2) . '/storage' . self::CACHE_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $key = hash('sha256', strtolower($scope) . '|' . $ip);
        return $dir . '/' . $key . '.json';
    }

    private static function readScopeState(string $file): array
    {
        if (!is_file($file)) {
            return ['count' => 0, 'reset_at' => 0];
        }

        $raw = file_get_contents($file);
        if (!is_string($raw) || $raw === '') {
            return ['count' => 0, 'reset_at' => 0];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return ['count' => 0, 'reset_at' => 0];
        }

        return [
            'count' => (int)($decoded['count'] ?? 0),
            'reset_at' => (int)($decoded['reset_at'] ?? 0),
        ];
    }

    private static function writeScopeState(string $file, array $state): void
    {
        file_put_contents($file, json_encode([
            'count' => (int)($state['count'] ?? 0),
            'reset_at' => (int)($state['reset_at'] ?? 0),
        ]), LOCK_EX);
    }
}
