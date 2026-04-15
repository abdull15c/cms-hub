<?php
namespace Src\Controllers;
use Config\Database;
use Src\Services\SessionService;

class NotificationController extends Controller {
    public function readAll() {
        if (!SessionService::get('user_id')) return;
        $this->verifyCsrf();
        
        // FIXED: Prepared Statement
        Database::connect()->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$this->currentUserId()]);
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
        exit;
    }
    
    public function poll() {
        if (!SessionService::get('user_id')) return;
        
        $pdo = Database::connect();
        $uid = $this->currentUserId();
        
        // FIXED: Prepared Statement
        $stmtCount = $pdo->prepare("SELECT count(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmtCount->execute([$uid]);
        $count = $stmtCount->fetchColumn();
        
        $stmtList = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmtList->execute([$uid]);
        $latest = $stmtList->fetchAll();
        
        header('Content-Type: application/json');
        echo json_encode(['count' => $count, 'notifications' => $latest]);
        exit;
    }
}
