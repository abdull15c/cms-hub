<?php
namespace Src\Services;
use Config\Database;
use Src\Services\SessionService;

class Gate {
    private static $permissions = [];
    private static $loaded = false;

    public static function load($roleName) {
        if (self::$loaded) return;
        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare("
                SELECT p.slug FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                JOIN roles r ON rp.role_id = r.id
                WHERE r.name = ?
            ");
            $stmt->execute([$roleName]);
            self::$permissions = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            self::$loaded = true;
        } catch (\Exception $e) {
            self::$permissions = []; // Fallback
        }
    }

    public static function authorize($permission) {
        $role = SessionService::get('role', 'guest');
        $userId = (int)SessionService::get('user_id', 0);
        if ($userId > 0) {
            try {
                $stmt = Database::connect()->prepare('SELECT role, is_banned FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                if (!$user || !empty($user['is_banned'])) {
                    throw new \RuntimeException('Account is unavailable.', 403);
                }
                $role = (string)$user['role'];
                if ((string)SessionService::get('role', '') !== $role) {
                    SessionService::set('role', $role);
                }
            } catch (\RuntimeException $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw new \RuntimeException('Authorization check failed.', 403);
            }
        }
        
        if ($role === 'admin') return true; // Admin allows all
        
        self::load($role);
        
        if (!in_array($permission, self::$permissions)) {
            throw new \RuntimeException("Missing permission: {$permission}", 403);
        }
        return true;
    }
}
