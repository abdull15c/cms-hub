<?php
namespace Src\Controllers;
use Src\Core\Container;

use Src\Services\SessionService;
use Src\Services\SettingsService;
use Config\Database;

class Controller {
    protected function service(string $key, ?callable $factory = null) {
        if (Container::has($key)) {
            return Container::get($key);
        }

        if ($factory === null) {
            throw new \RuntimeException('Service not found: ' . $key);
        }

        $instance = $factory();
        Container::set($key, $instance);
        return $instance;
    }

    protected function jsonError($code, $message, $status = 400) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'error' => ['code' => $code, 'message' => $message]
        ]);
        exit;
    }

    protected function verifyCsrf() {
        return;
    }


    protected function requireAuth(): void {
        SessionService::start();
        $userId = (int)SessionService::get('user_id', 0);
        if ($userId <= 0) {
            $this->redirect('/login');
        }
        $stmt = Database::connect()->prepare('SELECT id, role, is_banned FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user || !empty($user['is_banned'])) {
            SessionService::logout();
            $this->redirect('/login', 'Account is unavailable.');
        }
        if ((string)SessionService::get('role', '') !== (string)$user['role']) {
            SessionService::set('role', $user['role']);
        }
    }

    protected function requireAdmin(): void {
        $this->requireAuth();
        if (SessionService::get('role') !== 'admin') {
            $this->abort(403, 'Access denied. Admins only.');
        }
    }

    protected function currentUserId(): int {
        return (int) SessionService::get('user_id', 0);
    }

    protected function abort(int $status, string $message): void {
        http_response_code($status);
        echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        exit;
    }

    protected function view($view, $data = []) {
        SessionService::start();
        $langCode = SessionService::get('lang', 'ru');
        $langFile = ROOT_PATH . '/languages/' . $langCode . '.php';
        $lang = file_exists($langFile) ? (require $langFile) : [];
        $t = static function (string $key, string $default = '') use ($lang) {
            return $lang[$key] ?? $default;
        };
        
        // Global Data
        $data['BASE_URL'] = defined('BASE_URL') ? BASE_URL : '';
        $data['user_id'] = SessionService::get('user_id');
        $data['role'] = SessionService::get('role', 'guest');
        $data['lang'] = $lang;
        $data['t'] = $t;
        
        // Inject Flash Messages
        $data['flashes'] = SessionService::getFlashes();
        $data['view_name'] = $view;
        $data['is_admin_view'] = strpos($view, 'admin/') === 0;
        $data['theme'] = \Src\Services\ThemeService::forContext((bool) $data['is_admin_view']);
        $data['current_path'] = $this->currentPath();
        $data['current_query'] = $this->currentQuery(['preview_theme']);
        $data['current_url'] = $this->currentUrl();

        $siteTitle = SettingsService::get('site_title') ?: ($lang['site_title'] ?? 'CMS-HUB');
        $defaultMeta = [
            'title' => $siteTitle,
            'description' => '',
            'keywords' => '',
            'robots' => 'index,follow',
            'canonical' => $this->currentUrl(),
            'alternates' => $this->languageAlternates(),
            'og_type' => 'website',
            'og_image' => $this->defaultOgImage(),
            'locale' => $this->localeForLanguage($langCode),
            'structured_data' => [],
        ];
        $data['page_meta'] = array_merge($defaultMeta, is_array($data['page_meta'] ?? null) ? $data['page_meta'] : []);

        extract($data);
        
        if (file_exists(VIEW_PATH . '/layouts/header.php')) require VIEW_PATH . '/layouts/header.php';
        $vFile = VIEW_PATH . '/' . $view . '.php';
        if (file_exists($vFile)) require $vFile; else echo "View [$view] not found.";
        if (file_exists(VIEW_PATH . '/layouts/footer.php')) require VIEW_PATH . '/layouts/footer.php';
    }

    protected function redirect($path, $error = null, $success = null) {
        if ($error) SessionService::setFlash('error', $error);
        if ($success) SessionService::setFlash('success', $success);
        
        $url = (defined('BASE_URL') ? BASE_URL : '') . $path;
        header("Location: " . $url);
        exit;
    }

    protected function currentPath(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        if ($scriptDir !== '/' && strpos((string) $uri, $scriptDir) === 0) {
            $uri = substr((string) $uri, strlen($scriptDir));
        }

        if ($uri === '' || $uri === false) {
            return '/';
        }

        return str_starts_with((string) $uri, '/') ? (string) $uri : '/' . $uri;
    }

    protected function currentQuery(array $remove = []): array
    {
        $query = $_GET;
        foreach ($remove as $key) {
            unset($query[$key]);
        }
        return $query;
    }

    protected function currentUrl(array $overrides = [], array $remove = ['preview_theme']): string
    {
        $query = $this->currentQuery($remove);
        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                unset($query[$key]);
                continue;
            }
            $query[$key] = $value;
        }

        $url = (defined('BASE_URL') ? BASE_URL : '') . ($this->currentPath() === '/' ? '' : $this->currentPath());
        $queryString = http_build_query($query);
        return $url . ($queryString !== '' ? '?' . $queryString : '');
    }

    protected function languageAlternates(array $languages = ['ru', 'en']): array
    {
        $alternates = [];
        foreach ($languages as $language) {
            $alternates[$language] = $this->currentUrl(['lang' => $language]);
        }
        return $alternates;
    }

    protected function localeForLanguage(string $language): string
    {
        return $language === 'ru' ? 'ru_RU' : 'en_US';
    }

    protected function seoDescription(string $html, int $maxLength = 160): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
        if ($text === '') {
            return '';
        }
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, $maxLength - 1)) . '...';
    }

    protected function seoKeywords(array $items): string
    {
        $clean = [];
        foreach ($items as $item) {
            $value = trim(preg_replace('/\s+/u', ' ', (string) $item) ?? '');
            if ($value === '') {
                continue;
            }
            $clean[mb_strtolower($value)] = $value;
        }
        return implode(', ', array_values($clean));
    }

    protected function defaultOgImage(): ?string
    {
        $logo = SettingsService::get('site_logo');
        if ($logo === '') {
            return null;
        }
        return rtrim((string) (defined('BASE_URL') ? BASE_URL : ''), '/') . '/uploads/branding/' . rawurlencode($logo);
    }
}
