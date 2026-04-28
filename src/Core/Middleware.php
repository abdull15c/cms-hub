<?php
namespace Src\Core;
use Src\Core\Container;
use Src\Services\RateLimiter;
use Src\Services\SettingsService;
use Src\Core\Csrf;
use Src\Services\SessionService;

class Middleware {
    public static function run() {
        $uri = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];
        $ip = RateLimiter::getIp();
        if (empty($_SERVER['REQUEST_ID'])) {
            $_SERVER['REQUEST_ID'] = bin2hex(random_bytes(8));
        }
        header("X-Request-Id: " . $_SERVER['REQUEST_ID']);
        $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? bin2hex(random_bytes(12));
        $_SERVER['REQUEST_ID'] = $requestId;
        header('X-Request-Id: ' . $requestId);
        
        // 0. MAINTENANCE MODE CHECK
        // Allow access to /admin, /login, /auth (for admins to login)
        $isMaintenance = SettingsService::get('maintenance_mode') === '1';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $isAdminPath = str_starts_with($path, '/admin') || $path === '/login' || str_starts_with($path, '/auth');
        
        if ($isMaintenance && !$isAdminPath) {
            // Check if user is already logged in as admin to bypass
            if (SessionService::get('role') !== 'admin') {
                http_response_code(503);
                exit('<body style="background:#0b0f19;color:#fff;display:flex;justify-content:center;align-items:center;height:100vh;font-family:sans-serif;text-align:center;">
                        <div><h1 style="color:#00f2ea;font-size:3rem;">System Maintenance</h1><p>We are currently upgrading the mainframe.</p></div>
                     </body>');
            }
        }

        // 1. SECURITY HEADERS
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: SAMEORIGIN");
        header("Referrer-Policy: no-referrer");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }
        $nonce = defined('CSP_NONCE') ? CSP_NONCE : '';
        if ($nonce !== '') {
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-$nonce' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:; img-src 'self' data: https:; connect-src 'self' https:; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
        }
        
        // 2. CSRF PROTECTION
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $isWebhook = (strpos($uri, '/payment/webhook') !== false);
            $isStatelessLicenseApi = (strpos($uri, '/api/license/check') !== false);
            if (!$isWebhook && !$isStatelessLicenseApi) {
                $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
                if (empty($token)) {
                    $input = json_decode(file_get_contents('php://input'), true);
                    $token = $input['csrf_token'] ?? '';
                }

                if (!Csrf::verify($token)) {
                    http_response_code(419);
                    header('Content-Type: application/json; charset=utf-8');
                    if(isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false) {
                        exit(json_encode(['status' => 'error', 'error' => ['code' => 'csrf_mismatch', 'message' => 'CSRF Token Mismatch']]));
                    }
                    exit(json_encode(['status' => 'error', 'error' => ['code' => 'csrf_mismatch', 'message' => 'CSRF Token Invalid. Please refresh the page.']]));
                }
            }
        }
        
        // 3. RATE LIMITING
        if ($method === 'POST' && strpos($uri, '/login') !== false) {
            if (!RateLimiter::check($ip)) {
                http_response_code(429);
                header('Content-Type: application/json; charset=utf-8');
                exit(json_encode(['status' => 'error', 'error' => ['code' => 'rate_limited', 'message' => 'Too many requests. Please slow down.']]));
            }
        }

        $scopedLimits = [
            ['needle' => '/payment/webhook', 'methods' => ['POST'], 'scope' => 'payment_webhook', 'limit' => 120, 'window' => 60],
            ['needle' => '/api/license/check', 'methods' => ['POST'], 'scope' => 'api_license_check', 'limit' => 60, 'window' => 300],
            ['needle' => '/api/me', 'methods' => ['GET'], 'scope' => 'api_me', 'limit' => 120, 'window' => 300],
            ['needle' => '/auth/token/generate', 'methods' => ['POST'], 'scope' => 'api_token_generate', 'limit' => 5, 'window' => 3600],
            ['needle' => '/chat/send', 'methods' => ['POST'], 'scope' => 'chat_send', 'limit' => 30, 'window' => 60],
            ['needle' => '/register', 'methods' => ['POST'], 'scope' => 'register_submit', 'limit' => 10, 'window' => 900],
            ['needle' => '/forgot', 'methods' => ['POST'], 'scope' => 'forgot_submit', 'limit' => 10, 'window' => 900],
            ['needle' => '/reset', 'methods' => ['POST'], 'scope' => 'reset_submit', 'limit' => 20, 'window' => 900],
        ];
        foreach ($scopedLimits as $rule) {
            if (strpos($uri, $rule['needle']) === false || !in_array($method, $rule['methods'], true)) {
                continue;
            }
            if (!RateLimiter::attempt($rule['scope'], $rule['limit'], $rule['window'], $ip)) {
                http_response_code(429);
                header('Content-Type: application/json; charset=utf-8');
                exit(json_encode(['status' => 'error', 'error' => ['code' => 'rate_limited', 'message' => 'Too many requests. Please slow down.']]));
            }
        }

        if ($method === 'GET') {
            try {
                $analytics = Container::has('analytics') ? Container::get('analytics') : new \Src\Services\AnalyticsService();
                $analytics->trackPageView($uri, $method);
            } catch (\Throwable $e) {
                \Src\Services\Logger::warning('Analytics middleware tracking skipped', ['error' => $e->getMessage()]);
            }
        }
    }
}
