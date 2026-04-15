<?php
namespace Src\Core;
use Src\Services\SessionService;

class Csrf {
    public static function token() {
        // Ensure session is started properly via Service
        $token = SessionService::get('csrf_token');
        if (empty($token)) {
            $token = bin2hex(random_bytes(32));
            SessionService::set('csrf_token', $token);
        }
        return $token;
    }

    public static function verify($token) {
        $stored = SessionService::get('csrf_token');
        if (empty($stored) || empty($token)) {
            return false;
        }
        return hash_equals($stored, $token);
    }

    public static function field() {
        $token = self::token();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}