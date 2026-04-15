<?php
namespace Src\Core;
use Src\Services\Logger;

class ErrorHandler {
    public static function register() {
        error_reporting(E_ALL);
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError($level, $message, $file, $line) {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    public static function handleException($e) {
        $code = $e->getCode();
        if ($code != 404 && $code != 403) {
            Logger::error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $code = 500;
        }

        self::renderError($code, $e);
    }

    public static function handleShutdown() {
        $error = error_get_last();
        if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
            self::handleException(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
        }
    }

    private static function renderError($code, $e) {
        if (php_sapi_name() === 'cli') {
            echo "[FATAL] $code: " . $e->getMessage() . PHP_EOL;
            return;
        }

        http_response_code($code);
        
        // Check if we are Local (Laragon default IP)
        $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
        
        if ($isLocal) {
            echo "<div style='background:#1a1a1a;color:#ff5555;padding:20px;font-family:monospace;'>";
            echo "<h1>System Error ($code)</h1>";
            echo "<h3>" . htmlspecialchars($e->getMessage()) . "</h3>";
            echo "<p>File: <strong>" . $e->getFile() . "</strong> Line: <strong>" . $e->getLine() . "</strong></p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        } else {
            // Production View
            $view = VIEW_PATH . "/errors/$code.php";
            if (file_exists($view)) {
                // Mocking data for view
                $BASE_URL = defined('BASE_URL') ? BASE_URL : '/';
                require $view;
            } else {
                echo "<h1>System Error $code</h1><p>Please contact support.</p>";
            }
        }
        exit;
    }
}