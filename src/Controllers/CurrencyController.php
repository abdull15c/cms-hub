<?php
namespace Src\Controllers;
use Src\Services\SessionService;

class CurrencyController extends Controller {
    public function switch($code) {
        $allowed = ['RUB', 'USD', 'EUR'];
        if (in_array(strtoupper($code), $allowed)) {
            SessionService::set('currency', strtoupper($code));
        }
        header('Location: ' . $this->resolveReturnTo());
        exit;
    }

    private function resolveReturnTo(): string
    {
        $fallback = (defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '') . '/';
        $candidate = (string)($_SERVER['HTTP_REFERER'] ?? '');
        if ($candidate === '') {
            return $fallback;
        }

        $parsed = parse_url($candidate);
        if ($parsed === false) {
            return $fallback;
        }

        if (!empty($parsed['host'])) {
            $currentHost = (string)parse_url((string)(defined('BASE_URL') ? BASE_URL : ''), PHP_URL_HOST);
            if ($currentHost === '' || !hash_equals(strtolower($currentHost), strtolower((string)$parsed['host']))) {
                return $fallback;
            }
        }

        $path = (string)($parsed['path'] ?? '/');
        if ($path === '' || !str_starts_with($path, '/')) {
            $path = '/';
        }

        $query = isset($parsed['query']) && $parsed['query'] !== '' ? '?' . $parsed['query'] : '';
        $basePath = (string)(parse_url((string)(defined('BASE_URL') ? BASE_URL : ''), PHP_URL_PATH) ?: '');
        if ($basePath !== '' && $basePath !== '/' && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath)) ?: '/';
            if (!str_starts_with($path, '/')) {
                $path = '/' . $path;
            }
        }

        return rtrim((string)(defined('BASE_URL') ? BASE_URL : ''), '/') . $path . $query;
    }
}
