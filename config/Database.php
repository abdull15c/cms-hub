<?php
namespace Config;
use Src\Core\Env;

class Database {
    private static $pdo;

    private static function parseDatabaseUrl(?string $url): array {
        $url = trim((string)$url);
        if ($url === '') {
            return [];
        }

        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme']) || stripos((string)$parts['scheme'], 'mysql') !== 0) {
            return [];
        }

        $config = [];
        if (!empty($parts['host'])) {
            $config['host'] = (string)$parts['host'];
        }
        if (!empty($parts['port'])) {
            $config['port'] = (string)$parts['port'];
        }
        if (array_key_exists('user', $parts)) {
            $config['user'] = (string)$parts['user'];
        }
        if (array_key_exists('pass', $parts)) {
            $config['pass'] = (string)$parts['pass'];
        }
        if (!empty($parts['path'])) {
            $config['db'] = ltrim((string)$parts['path'], '/');
        }

        return $config;
    }

    public static function connect() {
        if (!self::$pdo) {
            Env::load();
            $urlConfig = self::parseDatabaseUrl(
                Env::get('DATABASE_URL',
                    Env::get('DB_URL',
                        Env::get('MYSQL_URL',
                            Env::get('INSTALL_DATABASE_URL', Env::get('INSTALL_DB_URL', ''))
                        )
                    )
                )
            );

            $host = Env::get('DB_HOST', Env::get('INSTALL_DB_HOST', $urlConfig['host'] ?? 'localhost'));
            $port = Env::get('DB_PORT', Env::get('INSTALL_DB_PORT', $urlConfig['port'] ?? ''));
            $db   = Env::get('DB_NAME', Env::get('INSTALL_DB_NAME', $urlConfig['db'] ?? 'dle_market_db'));
            $user = Env::get('DB_USER', Env::get('INSTALL_DB_USER', $urlConfig['user'] ?? 'root'));
            $pass = Env::get('DB_PASS', Env::get('INSTALL_DB_PASS', $urlConfig['pass'] ?? ''));
            $charset = Env::get('CHARSET', 'utf8mb4');

            if (strtolower((string)$host) === 'localhost') {
                $host = '127.0.0.1';
            }

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
                error_log("Database Connection Error [host={$host} port={$port} db={$db} user={$user}]: " . $e->getMessage());
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
