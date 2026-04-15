<?php
namespace Src\Services;
use Config\Database;

class AuditLogger {
    public static function log($action, $details = '') {
        $adminId = (int) SessionService::get('user_id', 0);
        
        // Use central IP logic from RateLimiter
        $ip = RateLimiter::getIp();
        
        $pdo = Database::connect();
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details, ip) VALUES (?, ?, ?, ?)");
        $stmt->execute([$adminId, $action, $details, $ip]);
    }
}
