<?php
namespace Src\Services;

class Logger {
    private static $logDir;
    const MAX_LOG_SIZE = 5242880; // 5 MB

    public static function init() {
        self::$logDir = defined('STORAGE_PATH') ? STORAGE_PATH . '/logs' : dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir(self::$logDir)) mkdir(self::$logDir, 0755, true);
    }

    public static function error($message, $context = []) { self::write('ERROR', $message, $context); }
    public static function info($message, $context = []) { self::write('INFO', $message, $context); }
    public static function warning($message, $context = []) { self::write('WARNING', $message, $context); }

    private static function write($level, $message, $context = []) {
        if (!self::$logDir) self::init();

        $date = date('Y-m-d');
        $file = self::$logDir . "/app-{$date}.log";
        
        // ROTATION CHECK
        if (file_exists($file) && filesize($file) > self::MAX_LOG_SIZE) {
            rename($file, self::$logDir . "/app-{$date}-" . time() . ".bak");
        }
        
        $requestId = $_SERVER['REQUEST_ID'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $record = [
            'ts' => date('c'),
            'level' => $level,
            'message' => self::sanitizeMessage((string)$message),
            'request_id' => $requestId,
            'ip' => self::maskIp($ip),
            'context' => self::sanitizeContext($context)
        ];
        $logLine = json_encode($record, JSON_UNESCAPED_UNICODE) . PHP_EOL;

        file_put_contents($file, $logLine, FILE_APPEND);
    }

    private static function sanitizeContext($context) {
        if (!is_array($context)) {
            return [];
        }
        $sensitive = ['password', 'token', 'secret', 'authorization', 'cookie', 'api_key'];
        foreach ($context as $k => $v) {
            $key = strtolower((string)$k);
            foreach ($sensitive as $needle) {
                if (strpos($key, $needle) !== false) {
                    $context[$k] = '[redacted]';
                    continue 2;
                }
            }
        }
        return $context;
    }

    private static function sanitizeMessage($message) {
        $message = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[redacted-email]', $message);
        return (string)$message;
    }

    private static function maskIp($ip) {
        if (!$ip) return null;
        if (strpos($ip, ':') !== false) {
            return preg_replace('/:[0-9a-f]{1,4}$/i', ':xxxx', $ip);
        }
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = 'x';
            return implode('.', $parts);
        }
        return $ip;
    }
}
