<?php
// INSTALLER PROCESSOR (SECURE)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}
header('Referrer-Policy: no-referrer');
$setupToken = (string)($_ENV['INSTALLER_SETUP_TOKEN'] ?? getenv('INSTALLER_SETUP_TOKEN') ?: getenv('INSTALLER_TOKEN') ?: '');
$requestToken = (string)($_SERVER['HTTP_X_SETUP_TOKEN'] ?? ($_POST['setup_token'] ?? ''));
$allowInstallerRaw = strtolower(trim((string)($_ENV['ENABLE_WEB_INSTALLER'] ?? getenv('ENABLE_WEB_INSTALLER') ?: '')));
$allowInstaller = in_array($allowInstallerRaw, ['1', 'true', 'yes', 'on'], true);
$sessionGranted = (int)($_SESSION['installer_access_granted'] ?? 0) === 1
    && (time() - (int)($_SESSION['installer_accessed_at'] ?? 0)) <= 1800;
$tokenValid = $requestToken !== '' && $setupToken !== '' && hash_equals($setupToken, $requestToken);
if (!$sessionGranted && $tokenValid) {
    session_regenerate_id(true);
}
if (!$allowInstaller || (!$sessionGranted && !$tokenValid)) {
    http_response_code(403);
    exit('Installer is locked');
}
if (file_exists('../../.env') || file_exists('../../storage/.installed.lock')) {
    http_response_code(409);
    exit('System already installed');
}

$host = trim((string)($_POST['db_host'] ?? ($_ENV['INSTALL_DB_HOST'] ?? getenv('INSTALL_DB_HOST') ?: $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '')));
$port = trim((string)($_POST['db_port'] ?? ($_ENV['INSTALL_DB_PORT'] ?? getenv('INSTALL_DB_PORT') ?: $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306')));
$name = trim((string)($_POST['db_name'] ?? ($_ENV['INSTALL_DB_NAME'] ?? getenv('INSTALL_DB_NAME') ?: $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '')));
$user = trim((string)($_POST['db_user'] ?? ($_ENV['INSTALL_DB_USER'] ?? getenv('INSTALL_DB_USER') ?: $_ENV['DB_USER'] ?? getenv('DB_USER') ?: '')));
$pass = (string)($_POST['db_pass'] ?? '');
if ($pass === '') {
    $pass = (string)($_ENV['INSTALL_DB_PASS'] ?? getenv('INSTALL_DB_PASS') ?: $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '');
}
$admEmail = trim((string)($_POST['admin_email'] ?? ($_ENV['INSTALL_ADMIN_EMAIL'] ?? getenv('INSTALL_ADMIN_EMAIL') ?: '')));
$rawAdminPass = (string)($_POST['admin_pass'] ?? '');
if ($rawAdminPass === '') {
    $rawAdminPass = (string)($_ENV['INSTALL_ADMIN_PASSWORD'] ?? getenv('INSTALL_ADMIN_PASSWORD') ?: '');
}
$url = rtrim(trim((string)($_POST['app_url'] ?? ($_ENV['INSTALL_APP_URL'] ?? getenv('INSTALL_APP_URL') ?: $_ENV['APP_URL'] ?? getenv('APP_URL') ?: ''))), '/');
if ($host === '' || $name === '' || $user === '' || $admEmail === '' || $rawAdminPass === '' || $url === '') {
    http_response_code(422);
    exit('Missing required installation fields');
}
if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
    http_response_code(422);
    exit('Database name may contain only letters, numbers and underscores');
}
if (!filter_var($admEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    exit('Invalid admin email');
}
if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array((string) parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
    http_response_code(422);
    exit('Invalid site URL');
}

if (preg_match('/^(.+);port=(\d+)$/i', $host, $matches)) {
    $host = trim($matches[1]);
    $port = trim($matches[2]);
}

if (strtolower($host) === 'localhost') {
    $host = '127.0.0.1';
}

$port = preg_replace('/\D+/', '', $port);
$admPass = password_hash($rawAdminPass, PASSWORD_ARGON2ID);
$analyticsSalt = bin2hex(random_bytes(32));
$mailHost = strtolower((string)(parse_url($url, PHP_URL_HOST) ?: 'example.test'));
$mailHost = preg_replace('/[^a-z0-9.-]/', '', $mailHost);
if ($mailHost === '' || strpos($mailHost, '.') === false) {
    $mailHost = 'example.test';
}
$mailFrom = 'noreply@' . $mailHost;

try {
    $dsn = "mysql:host=$host;charset=utf8mb4";
    if ($port !== '') {
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    }
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $columnExists = static function (PDO $pdo, string $table, string $column): bool {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    };

    $indexExists = static function (PDO $pdo, string $table, string $index): bool {
        $stmt = $pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
        $stmt->execute([$index]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    };
    
    // 1. Create DB
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name`");
    $pdo->exec("USE `$name`");

    // 2. Import Structure
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        email VARCHAR(191) UNIQUE, 
        password VARCHAR(255), 
        role VARCHAR(50) DEFAULT 'user', 
        balance DECIMAL(10,2) DEFAULT 0.00, 
        api_token VARCHAR(100) NULL,
        totp_secret VARCHAR(255) NULL,
        referrer_id INT NULL,
        is_banned TINYINT(1) DEFAULT 0,
        email_verified_at TIMESTAMP NULL,
        verify_token VARCHAR(100) NULL,
        reset_token VARCHAR(100) NULL,
        reset_expires_at DATETIME NULL,
        oauth_provider VARCHAR(50) NULL,
        oauth_provider_id VARCHAR(191) NULL,
        avatar VARCHAR(255) NULL,
        name VARCHAR(100) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT);
    CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NULL,
        title VARCHAR(255),
        slug VARCHAR(255),
        price DECIMAL(10,2),
        sale_price DECIMAL(10,2) NULL,
        sale_end DATETIME NULL,
        demo_enabled TINYINT(1) NOT NULL DEFAULT 0,
        demo_url VARCHAR(255) NULL,
        demo_login VARCHAR(191) NULL,
        demo_password VARCHAR(191) NULL,
        description TEXT,
        file_path VARCHAR(255),
        status VARCHAR(20) NOT NULL DEFAULT 'published',
        has_license TINYINT(1) DEFAULT 0,
        meta_title VARCHAR(255),
        meta_desc TEXT,
        meta_keywords TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_products_status (status)
    );
    CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100), slug VARCHAR(100));
    CREATE TABLE IF NOT EXISTS product_images (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT, image_path VARCHAR(255), is_main TINYINT(1) DEFAULT 0);
    CREATE TABLE IF NOT EXISTS transactions (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, product_id INT, provider VARCHAR(50), provider_payment_id VARCHAR(191) NULL, amount DECIMAL(10,2), status VARCHAR(20), coupon_id INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL, INDEX idx_transactions_status_created (status, created_at), INDEX idx_transactions_user_created (user_id, created_at), INDEX idx_transactions_provider_payment (provider, provider_payment_id));
    CREATE TABLE IF NOT EXISTS licenses (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, product_id INT, license_key VARCHAR(100), domain VARCHAR(255) NULL, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, activated_at DATETIME NULL);
    CREATE TABLE IF NOT EXISTS reviews (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, product_id INT, rating INT, comment TEXT, reply TEXT NULL, is_approved TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_reviews_user_product (user_id, product_id));
    CREATE TABLE IF NOT EXISTS coupons (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(50) UNIQUE, discount_percent INT, max_uses INT DEFAULT 100, used_count INT DEFAULT 0);
    CREATE TABLE IF NOT EXISTS tickets (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, transaction_id INT NULL, subject VARCHAR(255), department VARCHAR(50), priority VARCHAR(20), status VARCHAR(20) DEFAULT 'open', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS ticket_messages (id INT AUTO_INCREMENT PRIMARY KEY, ticket_id INT, user_id INT, is_admin TINYINT(1) DEFAULT 0, message TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS posts (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), slug VARCHAR(255), content LONGTEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS faqs (id INT AUTO_INCREMENT PRIMARY KEY, question VARCHAR(255), answer TEXT, sort_order INT DEFAULT 0);
    CREATE TABLE IF NOT EXISTS wallet_logs (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, amount DECIMAL(10,2), type VARCHAR(20), reference_id INT NULL, description VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS notifications (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, message TEXT, type VARCHAR(20), link VARCHAR(255) NULL, is_read TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS jobs (id INT AUTO_INCREMENT PRIMARY KEY, handler VARCHAR(255), payload TEXT, status VARCHAR(20) DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, last_error TEXT NULL, INDEX idx_jobs_status_created (status, created_at));
    CREATE TABLE IF NOT EXISTS login_attempts (ip VARCHAR(45) PRIMARY KEY, attempts INT DEFAULT 0, last_attempt TIMESTAMP NULL);
    CREATE TABLE IF NOT EXISTS chat_threads (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, product_id INT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_chat_threads_user_product (user_id, product_id));
    CREATE TABLE IF NOT EXISTS chat_messages (id INT AUTO_INCREMENT PRIMARY KEY, thread_id INT, sender_type VARCHAR(10), message TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS messages (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100), email VARCHAR(100), message TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS wishlists (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, product_id INT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS admin_logs (id INT AUTO_INCREMENT PRIMARY KEY, admin_id INT, action VARCHAR(50), details TEXT, ip VARCHAR(45), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS audit_logs (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, event_type VARCHAR(50), action VARCHAR(50), target_id INT NULL, details TEXT, ip VARCHAR(45), user_agent VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE IF NOT EXISTS analytics_page_views (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, path VARCHAR(255) NOT NULL, user_id INT NULL, session_id VARCHAR(128) NULL, ip_hash CHAR(64) NOT NULL, country_code VARCHAR(16) NULL, country_name VARCHAR(120) NULL, referer VARCHAR(1024) NULL, user_agent VARCHAR(1024) NULL, visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_apv_visited_at (visited_at), INDEX idx_apv_path_visited_at (path, visited_at), INDEX idx_apv_country_visited_at (country_code, visited_at), INDEX idx_apv_user_visited_at (user_id, visited_at));
    CREATE TABLE IF NOT EXISTS analytics_logins (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, session_id VARCHAR(128) NULL, ip_hash CHAR(64) NOT NULL, country_code VARCHAR(16) NULL, country_name VARCHAR(120) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_al_created_at (created_at), INDEX idx_al_user_created_at (user_id, created_at), INDEX idx_al_country_created_at (country_code, created_at));
    CREATE TABLE IF NOT EXISTS analytics_ip_geo (ip_hash CHAR(64) PRIMARY KEY, country_code VARCHAR(16) NULL, country_name VARCHAR(120) NULL, source VARCHAR(20) NULL, resolved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_aig_resolved_at (resolved_at));
    CREATE TABLE IF NOT EXISTS product_translations (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, lang VARCHAR(5) NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NULL, meta_title VARCHAR(255) NULL, meta_desc TEXT NULL, meta_keywords TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_product_lang (product_id, lang), KEY idx_pt_lang (lang), KEY idx_pt_title (title(100)));
    ";
    
    $pdo->exec($sql);

    if (!$columnExists($pdo, 'users', 'oauth_provider')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN oauth_provider VARCHAR(50) NULL");
    }

    if (!$columnExists($pdo, 'users', 'oauth_provider_id')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN oauth_provider_id VARCHAR(191) NULL");
    }

    if (!$indexExists($pdo, 'users', 'idx_users_oauth_provider')) {
        $pdo->exec("ALTER TABLE users ADD INDEX idx_users_oauth_provider (oauth_provider, oauth_provider_id)");
    }

    // 3. Secure Admin Insert (Prepared Statement)
    $stmt = $pdo->prepare("INSERT INTO users (email, password, role, name, email_verified_at) VALUES (?, ?, 'admin', 'Administrator', NOW())");
    $stmt->execute([$admEmail, $admPass]);

    // 4. Persist installation markers
    $env = "APP_URL=$url\nAPP_ENV=production\nDB_HOST=$host\nDB_PORT=$port\nDB_NAME=$name\nDB_USER=$user\nDB_PASS=$pass\nAPP_DEBUG=false\nCHARSET=utf8mb4\nCRON_TOKEN=" . bin2hex(random_bytes(16)) . "\nANALYTICS_IP_HASH_SALT={$analyticsSalt}\nMAIL_FROM_NAME=Market\nMAIL_FROM_ADDRESS={$mailFrom}\nSMTP_HOST=\nSMTP_PORT=587\nSMTP_ENCRYPTION=tls\nSMTP_USER=\nSMTP_PASS=\nSMTP_TIMEOUT=15\nSMTP_VERIFY_PEER=true\nBACKUP_DIR=storage/backups\nBACKUP_KEEP_DAYS=7\nMYSQLDUMP_BIN=mysqldump\nMYSQL_BIN=mysql";

    if (!is_dir('../../storage')) {
        @mkdir('../../storage', 0755, true);
    }
    $envWritten = @file_put_contents('../../.env', $env) !== false;
    if ($envWritten) {
        @chmod('../../.env', 0600);
    }
    $lockPayload = json_encode([
        'installed_at' => date('c'),
        'env_written' => $envWritten,
        'db_host' => $host,
        'db_port' => $port,
        'db_name' => $name,
        'db_user' => $user,
    ], JSON_UNESCAPED_SLASHES);
    $lockWritten = @file_put_contents('../../storage/.installed.lock', $lockPayload) !== false;

    if (!$envWritten && !$lockWritten) {
        throw new RuntimeException('Installation succeeded, but config could not be persisted. Ensure storage/ is writable or disable installer and keep DB_* env variables in Coolify.');
    }

    header('Location: ' . $url . '/login');
    exit;
} catch(Exception $e) {
    $retryUrl = 'index.php?step=2';
    exit("<div style='font-family:sans-serif;padding:20px;color:red;'><h3>Installation Error</h3>" . htmlspecialchars($e->getMessage()) . "<br><br><a href='" . htmlspecialchars($retryUrl, ENT_QUOTES, 'UTF-8') . "'>Try Again</a></div>");
}
