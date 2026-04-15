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

if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
}

\Src\Services\Logger::init();
\Src\Core\ErrorHandler::register();

require_once CONFIG_PATH . '/Database.php';
\Config\Database::connect();

\Src\Core\Container::set('auth', new \Src\Services\AuthService());
\Src\Core\Container::set('analytics', new \Src\Services\AnalyticsService());
\Src\Core\Container::set('payment', new \Src\Services\PaymentService());
\Src\Core\Container::set('social_auth', new \Src\Services\SocialAuthService());
\Src\Core\Container::set('mail', new \Src\Services\MailService());

if (!defined('BASE_URL')) {
    $appUrl = \Src\Core\Env::get('APP_URL', 'http://localhost/market/public');
    define('BASE_URL', rtrim($appUrl, '/'));
}

require_once ROOT_PATH . '/src/bootstrap.php';
