<?php
namespace Config;
use Src\Core\Env;

class Database {
    private static $pdo;

    public static function connect() {
        if (!self::$pdo) {
            Env::load();
            $host = Env::get('DB_HOST', Env::get('INSTALL_DB_HOST', 'localhost'));
            $port = Env::get('DB_PORT', Env::get('INSTALL_DB_PORT', ''));
            $db   = Env::get('DB_NAME', Env::get('INSTALL_DB_NAME', 'dle_market_db'));
            $user = Env::get('DB_USER', Env::get('INSTALL_DB_USER', 'root'));
            $pass = Env::get('DB_PASS', Env::get('INSTALL_DB_PASS', ''));
            $charset = Env::get('CHARSET', 'utf8mb4');

            try {
                $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
                if ($port !== '') {
                    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
                }
                self::$pdo = new \PDO($dsn, $user, $pass, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false, 
                ]);
            } catch (\PDOException $e) {
                error_log("Database Connection Error: " . $e->getMessage());
                if (php_sapi_name() === 'cli') {
                    throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
                }
                http_response_code(500);
                die("<h1>Service Unavailable</h1><p>The system is currently experiencing technical difficulties.</p>");
            }
        }
        return self::$pdo;
    }
}
