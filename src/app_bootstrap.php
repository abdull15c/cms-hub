<?php

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
if (!defined('APP_PATH')) {
    define('APP_PATH', ROOT_PATH . '/src');
}
if (!defined('VIEW_PATH')) {
    define('VIEW_PATH', ROOT_PATH . '/views');
}
if (!defined('STORAGE_PATH')) {
    define('STORAGE_PATH', ROOT_PATH . '/storage');
}
if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', ROOT_PATH . '/config');
}

spl_autoload_register(function ($class) {
    $prefixes = [
        'Src\\' => APP_PATH . '/',
        'Config\\' => CONFIG_PATH . '/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

require_once ROOT_PATH . '/src/Core/Env.php';

\Src\Core\Env::load();

if (php_sapi_name() !== 'cli') {
    \Src\Services\SessionService::start();
}

\Src\Services\Logger::init();
\Src\Core\ErrorHandler::register();

require_once CONFIG_PATH . '/Database.php';
\Config\Database::connect();

\Src\Core\Container::factory('auth', static function () { return new \Src\Services\AuthService(); });
\Src\Core\Container::factory('analytics', static function () { return new \Src\Services\AnalyticsService(); });
\Src\Core\Container::factory('payment', static function () { return new \Src\Services\PaymentService(); });
\Src\Core\Container::factory('social_auth', static function () { return new \Src\Services\SocialAuthService(); });
\Src\Core\Container::factory('mail', static function () { return new \Src\Services\MailService(); });

if (!defined('BASE_URL')) {
    $appUrl = \Src\Core\Env::get('APP_URL', 'http://localhost/mar/public');
    define('BASE_URL', rtrim($appUrl, '/'));
}

require_once ROOT_PATH . '/src/bootstrap.php';
