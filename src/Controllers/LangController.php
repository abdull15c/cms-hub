<?php
namespace Src\Controllers;
use Src\Services\SessionService;

class LangController extends Controller {
    public function switch($code) {
        // Allowed languages whitelist
        $allowed = ['ru', 'en'];
        if (in_array($code, $allowed)) {
            SessionService::set('lang', $code);
        }

        $returnTo = $this->resolveReturnTo();

        // Clear buffer before redirect to prevent "headers already sent" errors
        if (ob_get_level()) ob_end_clean();

        header('Location: ' . $returnTo);
        exit;
    }

    private function resolveReturnTo(): string
    {
        $candidate = (string)($_GET['return_to'] ?? $_POST['return_to'] ?? ($_SERVER['HTTP_REFERER'] ?? ''));
        if ($candidate === '') {
            return (defined('BASE_URL') ? BASE_URL : '') . '/';
        }

        $parsed = parse_url($candidate);
        $path = (string)($parsed['path'] ?? '/');
        $query = isset($parsed['query']) && $parsed['query'] !== '' ? '?' . $parsed['query'] : '';

        if ($path === '') {
            $path = '/';
        }

        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        $basePath = parse_url((string)(defined('BASE_URL') ? BASE_URL : ''), PHP_URL_PATH) ?: '';
        if ($basePath !== '' && $basePath !== '/' && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath)) ?: '/';
            if (!str_starts_with($path, '/')) {
                $path = '/' . $path;
            }
        }

        $base = defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '';
        return $base . $path . $query;
    }
}
