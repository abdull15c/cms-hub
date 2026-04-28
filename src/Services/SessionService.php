<?php
namespace Src\Services;

class SessionService {
    
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
            ini_set('session.use_only_cookies', '1');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
             
            session_start();
            self::checkFingerprint();
        }
    }

    // Improved Fingerprint: User Agent + Accept Language (More stable than IP on mobile)
    private static function checkFingerprint() {
        $hash = md5(
            ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . 
            ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en')
        );

        if (!isset($_SESSION['fingerprint'])) {
            $_SESSION['fingerprint'] = $hash;
        } elseif ($_SESSION['fingerprint'] !== $hash) {
            // Hijack attempt or browser change -> logout
            self::logout();
        }
    }

    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function forget($key) {
        self::start();
        if (isset($_SESSION[$key])) unset($_SESSION[$key]);
    }

    public static function setFlash($type, $message) {
        self::start();
        $_SESSION['flash_messages'][$type][] = $message;
    }

    public static function getFlashes() {
        self::start();
        $flashes = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $flashes;
    }

    public static function regenerate(): void {
        self::start();
        session_regenerate_id(true);
    }

    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            self::start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                !empty($params['secure']),
                !empty($params['httponly'])
            );
        }

        session_destroy();
    }
}
