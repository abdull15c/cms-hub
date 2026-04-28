<?php
namespace Src\Services;
use Config\Database;

class AuditService {
    
    public static function log($type, $action, $targetId = null, $details = []) {
        $userId = SessionService::get('user_id');
        $ip = RateLimiter::getIp(); // Use our secure IP getter
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Convert array details to readable string/json
        $detailsStr = is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : $details;

        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, event_type, action, target_id, details, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $type, $action, $targetId, $detailsStr, $ip, $ua]);
        } catch (\Exception $e) {
            // Fail silently so we don't break the app logic if logging fails
            error_log("Audit Fail: " . $e->getMessage());
        }
    }
}
