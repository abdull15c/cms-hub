<?php
namespace Src\Services;
use Config\Database;

class SettingsService {
    private static $cache = [];
    private static $loaded = false;

    public static function get($key) {
        if (!self::$loaded) {
            $pdo = Database::connect();
            $stmt = $pdo->query("SELECT * FROM settings");
            while ($row = $stmt->fetch()) {
                self::$cache[$row['setting_key']] = $row['setting_value'];
            }
            self::$loaded = true;
        }
        return self::$cache[$key] ?? '';
    }

    public static function set(string $key, string $value): void
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        $stmt->execute([$key, $value]);
        if (self::$loaded) {
            self::$cache[$key] = $value;
            return;
        }
        self::forgetCache();
    }

    public static function forgetCache(): void
    {
        self::$cache = [];
        self::$loaded = false;
    }
}
