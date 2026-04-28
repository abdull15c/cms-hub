<?php
namespace Src\Core;

class Env {
    private static $loaded = false;
    private static $data = [];

    public static function load($path = null) {
        if (self::$loaded) return;

        if ($path === null) {
            $path = dirname(__DIR__, 2) . '/.env';
        }

        if (!file_exists($path)) return;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignore comments
            if (strpos(trim($line), '#') === 0) continue;

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Remove quotes if present
                if (preg_match('/^"((?:[^"\\\\]|\\\\.)*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'((?:[^'\\\\]|\\\\.)*)'$/", $value, $matches)) {
                    $value = $matches[1];
                } else {
                    // Remove inline comments (value # comment)
                    $value = trim(explode(' #', $value, 2)[0]);
                }

                self::$data[$name] = $value;
                
                // Duplicate to $_ENV and putenv for compatibility
                if (!array_key_exists($name, $_ENV)) {
                    $_ENV[$name] = $value;
                    putenv("$name=$value");
                }
            }
        }
        self::$loaded = true;
    }

    public static function get($key, $default = null) {
        if (!self::$loaded) self::load();
        return self::$data[$key] ?? $_ENV[$key] ?? getenv($key) ?? $default;
    }
}