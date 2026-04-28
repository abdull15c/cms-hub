<?php
declare(strict_types=1);

use Src\Core\Env;

define('ROOT_PATH', dirname(__DIR__, 2));

spl_autoload_register(function ($class) {
    $prefixes = [
        'Src\\' => ROOT_PATH . '/src/',
        'Config\\' => ROOT_PATH . '/config/',
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
Env::load();

$host = (string)Env::get('DB_HOST', 'localhost');
$port = (string)Env::get('DB_PORT', '');
$dbName = (string)Env::get('DB_NAME', '');
$user = (string)Env::get('DB_USER', 'root');
$pass = (string)Env::get('DB_PASS', '');
$charset = (string)Env::get('CHARSET', 'utf8mb4');
if ($dbName === '') {
    throw new RuntimeException('DB_NAME is not configured.');
}

$dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";
if ($port !== '') {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";
}

$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$pdo->exec("CREATE TABLE IF NOT EXISTS product_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    lang VARCHAR(5) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    meta_title VARCHAR(255) NULL,
    meta_desc TEXT NULL,
    meta_keywords TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_product_lang (product_id, lang),
    KEY idx_pt_lang (lang),
    KEY idx_pt_title (title(100))
)");
echo "[OK] Ensured table product_translations\n";

$products = $pdo->query("SELECT id, title, description, meta_title, meta_desc, meta_keywords FROM products")->fetchAll();
$insert = $pdo->prepare(
    "INSERT INTO product_translations (product_id, lang, title, description, meta_title, meta_desc, meta_keywords)
     VALUES (?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), meta_title = VALUES(meta_title), meta_desc = VALUES(meta_desc), meta_keywords = VALUES(meta_keywords)"
);

foreach ($products as $product) {
    foreach (['ru', 'en'] as $lang) {
        $insert->execute([
            $product['id'],
            $lang,
            (string)$product['title'],
            $product['description'],
            $product['meta_title'],
            $product['meta_desc'],
            $product['meta_keywords'],
        ]);
    }
}

echo "[DONE] Product translations backfilled for existing products.\n";
