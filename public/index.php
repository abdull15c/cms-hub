<?php
// 1. INSTALLER CHECK (Hardened)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}
$envPath = dirname(__DIR__) . '/.env';
$installerPath = __DIR__ . '/install/index.php';
$installLock = dirname(__DIR__) . '/storage/.installed.lock';
if (!file_exists($envPath) && file_exists($installerPath) && !file_exists($installLock)) {
    $allowInstallerRaw = strtolower(trim((string)($_ENV['ENABLE_WEB_INSTALLER'] ?? getenv('ENABLE_WEB_INSTALLER') ?: '')));
    $allowInstaller = in_array($allowInstallerRaw, ['1', 'true', 'yes', 'on'], true);
    $databaseUrl = (string)($_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL')
        ?: $_ENV['DB_URL'] ?? getenv('DB_URL')
        ?: $_ENV['MYSQL_URL'] ?? getenv('MYSQL_URL')
        ?: $_ENV['INSTALL_DATABASE_URL'] ?? getenv('INSTALL_DATABASE_URL')
        ?: $_ENV['INSTALL_DB_URL'] ?? getenv('INSTALL_DB_URL')
        ?: '');
    $runtimeConfigured = (string)($_ENV['APP_URL'] ?? getenv('APP_URL') ?: '') !== ''
        && (
            $databaseUrl !== ''
            || (
                (string)($_ENV['INSTALL_DB_HOST'] ?? getenv('INSTALL_DB_HOST') ?: $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '') !== ''
                && (string)($_ENV['INSTALL_DB_NAME'] ?? getenv('INSTALL_DB_NAME') ?: $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '') !== ''
                && (string)($_ENV['INSTALL_DB_USER'] ?? getenv('INSTALL_DB_USER') ?: $_ENV['DB_USER'] ?? getenv('DB_USER') ?: '') !== ''
            )
        );

    if ($runtimeConfigured && !$allowInstaller) {
        goto bootstrap_app;
    }

    $setupToken = (string)($_ENV['INSTALLER_SETUP_TOKEN'] ?? getenv('INSTALLER_SETUP_TOKEN') ?: getenv('INSTALLER_TOKEN') ?: '');
    $requestToken = (string)($_SERVER['HTTP_X_SETUP_TOKEN'] ?? ($_POST['setup_token'] ?? ($_GET['setup_token'] ?? '')));

    if (!$allowInstaller || $setupToken === '' || !hash_equals($setupToken, $requestToken)) {
        http_response_code(403);
        exit('Installer is disabled. Provide valid setup token and enable web installer.');
    }
    $_SESSION['installer_access_granted'] = 1;
    $_SESSION['installer_accessed_at'] = time();
    header('Referrer-Policy: no-referrer');
    header('Location: install/index.php');
    exit;
}

bootstrap_app:
require_once dirname(__DIR__) . '/src/app_bootstrap.php';
if(!defined('CSP_NONCE')) define('CSP_NONCE', bin2hex(random_bytes(16)));

$requestedLang = strtolower(trim((string)($_GET['lang'] ?? '')));
if (in_array($requestedLang, ['ru', 'en'], true)) {
    \Src\Services\SessionService::set('lang', $requestedLang);
}

// ROUTING
use Src\Core\Router; 
$router = new Router();

// --- PUBLIC ROUTES ---
$router->add('GET', '/', 'HomeController', 'index');
$router->add('GET', '/product/{id}', 'ProductController', 'show');
$router->add('GET', '/download/{id}', 'DownloadController', 'download');
$router->add('GET', '/lang/{code}', 'LangController', 'switch');
$router->add('GET', '/currency/switch/{code}', 'CurrencyController', 'switch');

// --- AUTH ROUTES ---
$router->add('GET', '/login', 'AuthController', 'loginForm');
$router->add('POST', '/login', 'AuthController', 'login');
$router->add('GET', '/register', 'AuthController', 'registerForm');
$router->add('POST', '/register', 'AuthController', 'register');
$router->add('POST', '/logout', 'AuthController', 'logout');
$router->add('GET', '/profile', 'ProfileController', 'index');
$router->add('GET', '/profile/settings', 'ProfileController', 'edit');
$router->add('GET', '/profile/api', 'ProfileController', 'apiPage');
$router->add('POST', '/profile/settings/update', 'ProfileController', 'update');

$router->add('GET', '/forgot', 'AuthController', 'forgotForm');
$router->add('POST', '/forgot', 'AuthController', 'sendResetLink');
$router->add('GET', '/reset/{token}', 'AuthController', 'resetForm');
$router->add('POST', '/reset', 'AuthController', 'resetPassword');

// --- SSO ROUTES ---
$router->add('GET', '/auth/google', 'AuthController', 'loginGoogle');
$router->add('GET', '/auth/github', 'AuthController', 'loginGithub');
$router->add('GET', '/auth/callback/{provider}', 'AuthController', 'callback');

// --- 2FA & SECURITY ---
$router->add('GET', '/auth/2fa/setup', 'AuthController', 'setup2fa');
$router->add('POST', '/auth/2fa/enable', 'AuthController', 'enable2fa');
$router->add('POST', '/auth/2fa/disable', 'AuthController', 'disable2fa');
$router->add('POST', '/auth/token/generate', 'AuthController', 'generateToken');

// --- WALLET & WISHLIST ---
$router->add('GET', '/wallet', 'WalletController', 'index');
$router->add('POST', '/payment/deposit', 'PaymentController', 'deposit');
$router->add('GET', '/wishlist', 'WishlistController', 'index');
$router->add('POST', '/wishlist/toggle/{id}', 'WishlistController', 'toggle');
$router->add('GET', '/affiliate', 'AffiliateController', 'index');
$router->add('GET', '/verify/{token}', 'VerifyController', 'verify');
$router->add('POST', '/verify/resend', 'VerifyController', 'resend');

// --- PAYMENT & API ---
$router->add('POST', '/checkout/{id}', 'PaymentController', 'checkout');
$router->add('GET', '/payment/success', 'PaymentController', 'success');
$router->add('POST', '/payment/webhook/{provider}', 'PaymentController', 'webhook');
$router->add('GET', '/payment/yoomoney_form', 'PaymentController', 'yoomoney_form');
$router->add('POST', '/api/check_coupon', 'CouponApiController', 'check');
$router->add('GET', '/notifications/poll', 'NotificationController', 'poll');
$router->add('POST', '/notifications/read', 'NotificationController', 'readAll');

// --- PAGES & INTERACTION ---
$router->add('GET', '/page/terms', 'PageController', 'terms');
$router->add('GET', '/page/privacy', 'PageController', 'privacy');
$router->add('GET', '/page/contact', 'PageController', 'contact');
$router->add('POST', '/page/contact/send', 'PageController', 'sendMessage');
$router->add('POST', '/review/store/{id}', 'ReviewController', 'store');
$router->add('POST', '/review/delete/{id}', 'ReviewController', 'delete');
$router->add('POST', '/chat/send/{id}', 'ChatController', 'send');
$router->add('GET', '/blog', 'ContentController', 'blog');
$router->add('GET', '/blog/{id}', 'ContentController', 'post');
$router->add('GET', '/faq', 'ContentController', 'faq');

// --- ADMIN ROUTES ---
$router->add('GET', '/admin/login', 'AuthController', 'loginForm');
$router->add('GET', '/admin/reviews', 'Admin\ReviewController', 'index');
    $router->add('POST', '/admin/reviews/approve/{id}', 'Admin\ReviewController', 'approve');
    $router->add('POST', '/admin/reviews/delete/{id}', 'Admin\ReviewController', 'delete');
    $router->add('POST', '/admin/reviews/reply/{id}', 'Admin\ReviewController', 'reply');
$router->add('GET', '/admin/dashboard', 'Admin\DashboardController', 'index');
$router->add('GET', '/admin/analytics', 'Admin\AnalyticsController', 'index');
$router->add('GET', '/admin/logs', 'Admin\LogViewerController', 'index');
$router->add('POST', '/admin/logs/clear', 'Admin\LogViewerController', 'clear');

$router->add('GET', '/admin/settings', 'Admin\SettingsController', 'index');
$router->add('GET', '/admin/themes', 'Admin\ThemeController', 'index');
$router->add('GET', '/admin/update', 'Admin\UpdaterController', 'index');
$router->add('POST', '/admin/update/run', 'Admin\UpdaterController', 'run');

$router->add('POST', '/admin/settings/save', 'Admin\SettingsController', 'save');
$router->add('POST', '/admin/themes/activate/{slug}', 'Admin\ThemeController', 'activate');
$router->add('GET', '/admin/pulse', 'Admin\PulseController', 'index');
$router->add('GET', '/admin/audit', 'Admin\AuditController', 'index');
$router->add('GET', '/admin/transactions', 'Admin\TransactionController', 'index');
$router->add('POST', '/admin/transactions/approve/{id}', 'Admin\TransactionController', 'approve');
$router->add('POST', '/admin/transactions/cancel/{id}', 'Admin\TransactionController', 'cancel');
$router->add('POST', '/admin/tools/test_email', 'Admin\SettingsController', 'testEmail');

$router->add('POST', '/admin/product/ai_seo', 'Admin\ProductController', 'ajaxSeo');
$router->add('POST', '/admin/product/ai_code', 'Admin\ProductController', 'ajaxAiCode');
$router->add('POST', '/admin/product/ai_translate', 'Admin\ProductController', 'ajaxTranslate');
$router->add('POST', '/admin/product/ai_marketing', 'Admin\ProductController', 'ajaxMarketing');
$router->add('POST', '/admin/product/ai_source', 'Admin\ProductController', 'ajaxSourceAnalyze');
$router->add('GET', '/admin/products', 'Admin\ProductController', 'index');
$router->add('GET', '/admin/product/new', 'Admin\ProductController', 'create');
$router->add('POST', '/admin/product/store', 'Admin\ProductController', 'store');
$router->add('GET', '/admin/product/edit/{id}', 'Admin\ProductController', 'edit');
$router->add('POST', '/admin/product/update/{id}', 'Admin\ProductController', 'update');
$router->add('POST', '/admin/product/duplicate/{id}', 'Admin\ProductController', 'duplicate');
$router->add('POST', '/admin/product/delete/{id}', 'Admin\ProductController', 'delete');

$router->add('GET', '/admin/categories', 'Admin\CategoryController', 'index');
$router->add('POST', '/admin/categories/store', 'Admin\CategoryController', 'store');
$router->add('POST', '/admin/categories/delete/{id}', 'Admin\CategoryController', 'delete');

$router->add('GET', '/admin/users', 'Admin\UserController', 'index');
$router->add('GET', '/admin/users/manage/{id}', 'Admin\BankerController', 'manage');
$router->add('POST', '/admin/users/ban/{id}', 'Admin\UserController', 'ban');
$router->add('POST', '/admin/banker/update', 'Admin\BankerController', 'update');
$router->add('GET', '/admin/messages', 'Admin\SettingsController', 'messages');

$router->add('GET', '/admin/chat', 'AdminChatController', 'inbox');
$router->add('GET', '/admin/chat/{id}', 'AdminChatController', 'viewThread');
$router->add('POST', '/admin/chat/reply/{id}', 'AdminChatController', 'reply');

$router->add('GET', '/admin/coupons', 'Admin\CouponController', 'index');
$router->add('POST', '/admin/coupons/store', 'Admin\CouponController', 'store');
$router->add('POST', '/admin/coupons/delete/{id}', 'Admin\CouponController', 'delete');

$router->add('POST', '/admin/tickets/ai_reply', 'Admin\AdminTicketController', 'ajaxAiReply');
$router->add('GET', '/admin/tickets', 'Admin\AdminTicketController', 'index');
$router->add('GET', '/admin/tickets/view/{id}', 'Admin\AdminTicketController', 'show');
$router->add('POST', '/admin/tickets/reply/{id}', 'Admin\AdminTicketController', 'reply');

$router->add('GET', '/admin/blog', 'Admin\AdminContentController', 'index');
$router->add('POST', '/admin/blog/ai_generate', 'Admin\AdminContentController', 'ajaxAiPost');
$router->add('GET', '/admin/blog/create', 'Admin\AdminContentController', 'createPost');
$router->add('POST', '/admin/blog/store', 'Admin\AdminContentController', 'storePost');
$router->add('POST', '/admin/blog/delete/{id}', 'Admin\AdminContentController', 'deletePost');
$router->add('GET', '/admin/faq', 'Admin\AdminContentController', 'faq');
$router->add('POST', '/admin/faq/store', 'Admin\AdminContentController', 'storeFaq');
$router->add('POST', '/admin/faq/delete/{id}', 'Admin\AdminContentController', 'deleteFaq');

$router->add('GET', '/tickets', 'TicketController', 'index');
$router->add('GET', '/tickets/new', 'TicketController', 'create');
$router->add('POST', '/tickets/store', 'TicketController', 'store');
$router->add('GET', '/tickets/view/{id}', 'TicketController', 'show');
$router->add('POST', '/tickets/reply/{id}', 'TicketController', 'reply');

$router->add('GET', '/api/me', 'ApiController', 'me');
$router->add('GET', '/api/products', 'ApiController', 'products');
$router->add('POST', '/api/license/check', 'LicenseApiController', 'check');

// --- DISPATCH LOGIC ---
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if ($scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
    $uri = substr($uri, strlen($scriptDir));
}
if ($uri === '' || $uri === false) $uri = '/';

if (class_exists('Src\Core\Middleware')) { \Src\Core\Middleware::run(); }

try {
    $router->dispatch($uri, $_SERVER['REQUEST_METHOD']);
} catch (\Exception $e) {
    // Should be caught by ErrorHandler, but just in case
    \Src\Core\ErrorHandler::handleException($e);
}
