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
        
        if ($role === 'admin') return true; // Admin allows all
        
        self::load($role);
        
        if (!in_array($permission, self::$permissions)) {
            throw new \RuntimeException("Missing permission: {$permission}", 403);
        }
        return true;
    }
}
