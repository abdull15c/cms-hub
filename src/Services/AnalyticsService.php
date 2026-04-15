<?php
namespace Src\Services;

use Config\Database;
use Src\Core\Env;

class AnalyticsService {
    private const GEO_CACHE_TTL_SECONDS = 2592000;
    private const GEO_LOOKUP_URL = 'https://ipwho.is/';
    private const GEO_TIMEOUT_SECONDS = 2;

    private $pdo;

    public function __construct() {
        $this->pdo = Database::connect();
    }

    public function trackPageView(string $uri, string $method = 'GET'): void {
        if (php_sapi_name() === 'cli' || !$this->shouldTrack($uri, $method) || $this->isBotRequest()) {
            return;
        }

        $path = $this->normalizePath($uri);
        if ($path === null) {
            return;
        }

        $context = $this->buildContext();

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO analytics_page_views (path, user_id, session_id, ip_hash, country_code, country_name, referer, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $this->truncate($path, 255) ?: '/',
                $context['user_id'],
                $context['session_id'],
                $context['ip_hash'],
                $context['country_code'],
                $context['country_name'],
                $this->truncate((string)($_SERVER['HTTP_REFERER'] ?? ''), 1024),
                $this->truncate((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 1024),
            ]);
        } catch (\Throwable $e) {
            Logger::warning('Analytics page view tracking skipped', ['error' => $e->getMessage()]);
        }
    }

    public function trackLogin(array $user): void {
        if (php_sapi_name() === 'cli' || empty($user['id']) || $this->isBotRequest()) {
            return;
        }

        $context = $this->buildContext();

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO analytics_logins (user_id, session_id, ip_hash, country_code, country_name) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                (int)$user['id'],
                $context['session_id'],
                $context['ip_hash'],
                $context['country_code'],
                $context['country_name'],
            ]);
        } catch (\Throwable $e) {
            Logger::warning('Analytics login tracking skipped', ['error' => $e->getMessage()]);
        }
    }

    public function getDashboardSummary(int $days = 7): array {
        $days = $this->normalizeDays($days);

        return [
            'products_total' => $this->scalarInt("SELECT COUNT(*) FROM products"),
            'registered_users_total' => $this->scalarInt("SELECT COUNT(*) FROM users"),
            'registrations_today' => $this->scalarInt("SELECT COUNT(*) FROM users WHERE created_at >= CURDATE()"),
            'unique_visitors_period' => $this->uniqueVisitorsSince($days),
            'page_views_period' => $this->scalarInt("SELECT COUNT(*) FROM analytics_page_views WHERE visited_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)"),
            'logins_today' => $this->scalarInt("SELECT COUNT(*) FROM analytics_logins WHERE created_at >= CURDATE()"),
            'top_page' => $this->mostVisitedPage($days),
            'chart' => $this->dailyActivity($days),
        ];
    }

    public function getOverview(int $days = 30): array {
        $days = $this->normalizeDays($days);
        $topPage = $this->mostVisitedPage($days);

        return [
            'days' => $days,
            'registrations_total' => $this->scalarInt("SELECT COUNT(*) FROM users"),
            'registrations_today' => $this->scalarInt("SELECT COUNT(*) FROM users WHERE created_at >= CURDATE()"),
            'logins_total' => $this->scalarInt("SELECT COUNT(*) FROM analytics_logins"),
            'logins_today' => $this->scalarInt("SELECT COUNT(*) FROM analytics_logins WHERE created_at >= CURDATE()"),
            'page_views_total' => $this->scalarInt("SELECT COUNT(*) FROM analytics_page_views"),
            'page_views_today' => $this->scalarInt("SELECT COUNT(*) FROM analytics_page_views WHERE visited_at >= CURDATE()"),
            'unique_visitors_today' => $this->uniqueVisitorsFromCondition("visited_at >= CURDATE()"),
            'unique_visitors_period' => $this->uniqueVisitorsSince($days),
            'top_page' => $topPage,
        ];
    }

    public function getTopPages(int $days = 30, int $limit = 10): array {
        $days = $this->normalizeDays($days);
        $limit = max(1, min(50, $limit));
        $visitorExpr = $this->visitorKeyExpression('v');

        try {
            $sql = "SELECT v.path, COUNT(*) AS views, COUNT(DISTINCT {$visitorExpr}) AS unique_visitors FROM analytics_page_views v WHERE v.visited_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY) GROUP BY v.path ORDER BY views DESC, unique_visitors DESC LIMIT {$limit}";
            return $this->pdo->query($sql)->fetchAll() ?: [];
        } catch (\Throwable $e) {
            Logger::warning('Analytics top pages unavailable', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function getTopCountries(int $days = 30, int $limit = 10): array {
        $days = $this->normalizeDays($days);
        $limit = max(1, min(50, $limit));
        $visitorExpr = $this->visitorKeyExpression('v');

        try {
            $sql = "SELECT COALESCE(NULLIF(v.country_code, ''), 'ZZ') AS country_code, COALESCE(NULLIF(v.country_name, ''), 'Unknown') AS country_name, COUNT(*) AS views, COUNT(DISTINCT {$visitorExpr}) AS unique_visitors FROM analytics_page_views v WHERE v.visited_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY) GROUP BY country_code, country_name ORDER BY unique_visitors DESC, views DESC LIMIT {$limit}";
            return $this->pdo->query($sql)->fetchAll() ?: [];
        } catch (\Throwable $e) {
            Logger::warning('Analytics top countries unavailable', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function getRecentLogins(int $limit = 12): array {
        $limit = max(1, min(50, $limit));

        try {
            $sql = "SELECT l.user_id, l.country_code, l.country_name, l.created_at, u.email FROM analytics_logins l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.created_at DESC LIMIT {$limit}";
            return $this->pdo->query($sql)->fetchAll() ?: [];
        } catch (\Throwable $e) {
            Logger::warning('Analytics recent logins unavailable', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function dailyActivity(int $days = 14): array {
        $days = $this->normalizeDays($days);
        $rows = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $day = date('Y-m-d', strtotime("-{$offset} days"));
            $rows[$day] = [
                'label' => date('d M', strtotime($day)),
                'registrations' => 0,
                'logins' => 0,
                'page_views' => 0,
                'unique_visitors' => 0,
            ];
        }

        foreach ($this->groupByDay("SELECT DATE(created_at) AS day_key, COUNT(*) AS total FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY) GROUP BY DATE(created_at)") as $row) {
            if (isset($rows[$row['day_key']])) {
                $rows[$row['day_key']]['registrations'] = (int)$row['total'];
            }
        }

        foreach ($this->groupByDay("SELECT DATE(created_at) AS day_key, COUNT(*) AS total FROM analytics_logins WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY) GROUP BY DATE(created_at)") as $row) {
            if (isset($rows[$row['day_key']])) {
                $rows[$row['day_key']]['logins'] = (int)$row['total'];
            }
        }

        $visitorExpr = $this->visitorKeyExpression();
        foreach ($this->groupByDay("SELECT DATE(visited_at) AS day_key, COUNT(*) AS page_views, COUNT(DISTINCT {$visitorExpr}) AS unique_visitors FROM analytics_page_views WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY) GROUP BY DATE(visited_at)") as $row) {
            if (isset($rows[$row['day_key']])) {
                $rows[$row['day_key']]['page_views'] = (int)$row['page_views'];
                $rows[$row['day_key']]['unique_visitors'] = (int)$row['unique_visitors'];
            }
        }

        return [
            'labels' => array_column($rows, 'label'),
            'registrations' => array_column($rows, 'registrations'),
            'logins' => array_column($rows, 'logins'),
            'page_views' => array_column($rows, 'page_views'),
            'unique_visitors' => array_column($rows, 'unique_visitors'),
        ];
    }

    private function buildContext(): array {
        $ip = RateLimiter::getIp();
        $country = $this->resolveCountry($ip);

        return [
            'user_id' => SessionService::get('user_id') ? (int)SessionService::get('user_id') : null,
            'session_id' => $this->truncate(session_id(), 128),
            'ip_hash' => $this->hashIp($ip),
            'country_code' => $country['code'],
            'country_name' => $country['name'],
        ];
    }

    private function shouldTrack(string $uri, string $method): bool {
        if (strtoupper($method) !== 'GET') {
            return false;
        }

        $path = $this->normalizePath($uri);
        if ($path === null) {
            return false;
        }

        $excludedPrefixes = [
            '/admin',
            '/api/',
            '/payment/webhook',
            '/download/',
            '/lang/',
            '/currency/switch/',
            '/notifications/poll',
            '/payment/yoomoney_form',
            '/install',
            '/logout',
        ];
        foreach ($excludedPrefixes as $prefix) {
            if (strpos($path, $prefix) === 0) {
                return false;
            }
        }

        return !in_array($path, ['/robots.txt', '/sitemap.xml'], true);
    }

    private function normalizePath(string $uri): ?string {
        $path = parse_url($uri, PHP_URL_PATH);
        if ($path === false || $path === null) {
            return null;
        }

        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        if ($scriptDir !== '/' && $scriptDir !== '.' && strpos($path, $scriptDir) === 0) {
            $path = substr($path, strlen($scriptDir));
        }

        return $path === '' ? '/' : $path;
    }

    private function isBotRequest(): bool {
        $ua = strtolower((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if ($ua === '') {
            return false;
        }

        return (bool)preg_match('/bot|crawl|spider|slurp|preview|headless|facebookexternalhit|monitoring/i', $ua);
    }

    private function resolveCountry(string $ip): array {
        $headerCountry = $this->proxyCountry();
        $ipHash = $this->hashIp($ip);

        if ($headerCountry !== null) {
            $this->cacheCountry($ipHash, $headerCountry['code'], $headerCountry['name'], $headerCountry['source']);
            return $headerCountry;
        }

        $cached = $this->cachedCountry($ipHash);
        if ($cached !== null && (time() - strtotime((string)$cached['resolved_at'])) < self::GEO_CACHE_TTL_SECONDS) {
            return ['code' => $cached['country_code'], 'name' => $cached['country_name']];
        }

        if (!$this->isPublicIp($ip)) {
            $local = ['code' => 'LOCAL', 'name' => 'Local'];
            $this->cacheCountry($ipHash, $local['code'], $local['name'], 'local');
            return $local;
        }

        $lookup = $this->lookupCountryByIp($ip);
        if ($lookup !== null) {
            $this->cacheCountry($ipHash, $lookup['code'], $lookup['name'], 'api');
            return $lookup;
        }

        if ($cached !== null) {
            return ['code' => $cached['country_code'], 'name' => $cached['country_name']];
        }

        $unknown = ['code' => 'ZZ', 'name' => 'Unknown'];
        $this->cacheCountry($ipHash, $unknown['code'], $unknown['name'], 'unknown');
        return $unknown;
    }

    private function proxyCountry(): ?array {
        $headers = [
            'HTTP_CF_IPCOUNTRY' => 'cf',
            'HTTP_X_COUNTRY_CODE' => 'proxy',
            'HTTP_X_COUNTRY' => 'proxy',
        ];

        foreach ($headers as $header => $source) {
            $value = strtoupper(trim((string)($_SERVER[$header] ?? '')));
            if ($value !== '' && preg_match('/^[A-Z]{2}$/', $value)) {
                return [
                    'code' => $value,
                    'name' => $this->countryNameFromCode($value),
                    'source' => $source,
                ];
            }
        }

        return null;
    }

    private function lookupCountryByIp(string $ip): ?array {
        if (!function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::GEO_LOOKUP_URL . rawurlencode($ip));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::GEO_TIMEOUT_SECONDS);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::GEO_TIMEOUT_SECONDS);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, 'CMS-HUB Analytics');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        $cacert = ROOT_PATH . '/config/cacert.pem';
        if (file_exists($cacert)) {
            curl_setopt($ch, CURLOPT_CAINFO, $cacert);
        }

        $response = curl_exec($ch);
        if ($response === false) {
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['success'])) {
            return null;
        }

        $code = strtoupper(trim((string)($data['country_code'] ?? '')));
        $name = trim((string)($data['country'] ?? ''));
        if ($code === '' || $name === '') {
            return null;
        }

        return ['code' => $this->truncate($code, 16), 'name' => $this->truncate($name, 120)];
    }

    private function cachedCountry(string $ipHash): ?array {
        try {
            $stmt = $this->pdo->prepare("SELECT country_code, country_name, resolved_at FROM analytics_ip_geo WHERE ip_hash = ? LIMIT 1");
            $stmt->execute([$ipHash]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function cacheCountry(string $ipHash, string $code, string $name, string $source): void {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO analytics_ip_geo (ip_hash, country_code, country_name, source, resolved_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE country_code = VALUES(country_code), country_name = VALUES(country_name), source = VALUES(source), resolved_at = NOW()"
            );
            $stmt->execute([
                $ipHash,
                $this->truncate($code, 16),
                $this->truncate($name, 120),
                $this->truncate($source, 20),
            ]);
        } catch (\Throwable $e) {
            Logger::warning('Analytics geo cache write skipped', ['error' => $e->getMessage()]);
        }
    }

    private function uniqueVisitorsSince(int $days): int {
        return $this->uniqueVisitorsFromCondition("visited_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)");
    }

    private function uniqueVisitorsFromCondition(string $condition): int {
        $visitorExpr = $this->visitorKeyExpression();
        return $this->scalarInt("SELECT COUNT(DISTINCT {$visitorExpr}) FROM analytics_page_views WHERE {$condition}");
    }

    private function mostVisitedPage(int $days): array {
        try {
            $stmt = $this->pdo->query("SELECT path, COUNT(*) AS views FROM analytics_page_views WHERE visited_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY) GROUP BY path ORDER BY views DESC LIMIT 1");
            $row = $stmt->fetch();
            return $row ?: ['path' => '/', 'views' => 0];
        } catch (\Throwable $e) {
            return ['path' => '/', 'views' => 0];
        }
    }

    private function groupByDay(string $sql): array {
        try {
            return $this->pdo->query($sql)->fetchAll() ?: [];
        } catch (\Throwable $e) {
            Logger::warning('Analytics grouped day query skipped', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function scalarInt(string $sql): int {
        try {
            return (int)$this->pdo->query($sql)->fetchColumn();
        } catch (\Throwable $e) {
            Logger::warning('Analytics scalar query skipped', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    private function visitorKeyExpression(string $alias = ''): string {
        $prefix = $alias !== '' ? rtrim($alias, '.') . '.' : '';
        return "CASE WHEN {$prefix}user_id IS NOT NULL THEN CONCAT('u:', {$prefix}user_id) WHEN {$prefix}session_id IS NOT NULL AND {$prefix}session_id <> '' THEN CONCAT('s:', {$prefix}session_id) ELSE CONCAT('i:', {$prefix}ip_hash) END";
    }

    private function hashIp(string $ip): string {
        $salt = (string)Env::get('ANALYTICS_IP_HASH_SALT', Env::get('CRON_TOKEN', 'analytics-salt'));
        return hash_hmac('sha256', $ip !== '' ? $ip : '0.0.0.0', $salt);
    }

    private function countryNameFromCode(string $code): string {
        if (class_exists('Locale')) {
            $name = \Locale::getDisplayRegion('-' . $code, 'en');
            if (is_string($name) && $name !== '') {
                return $this->truncate($name, 120) ?: $code;
            }
        }
        return $code;
    }

    private function truncate(string $value, int $max): ?string {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $max);
    }

    private function isPublicIp(string $ip): bool {
        return (bool)filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    private function normalizeDays(int $days): int {
        return max(1, min(365, $days));
    }
}
