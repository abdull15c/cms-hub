<?php
namespace Src\Controllers\Admin;
use Config\Database;
use Src\Services\WalletService;
use Src\Services\AuditLogger;

class BankerController extends BaseAdminController {
    public function manage($userId) {
        $this->checkAuth();
        $pdo = Database::connect();
        // FIXED: PREPARED STATEMENT
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if(!$user) $this->redirect('/admin/users');
        
        $lStmt = $pdo->prepare("SELECT * FROM wallet_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $lStmt->execute([$userId]);
        
        $this->view('admin/banker_manage', ['user' => $user, 'logs' => $lStmt->fetchAll()]);
    }

    public function update() {
        $this->checkAuth();
        $this->verifyCsrf();
        $userId = $_POST['user_id'];
        $amount = floatval($_POST['amount']);
        $action = $_POST['action']; 
        $reason = $_POST['reason'] ?? 'Admin Adjustment';
        if ($amount <= 0) $this->redirect("/admin/users/manage/$userId", "Invalid amount");
        $finalAmount = ($action === 'sub') ? -$amount : $amount;
        try {
            WalletService::changeBalance($userId, $finalAmount, 'adjustment', $this->currentUserId(), $reason);
            AuditLogger::log('admin_balance_change', "User: $userId, Amount: $finalAmount, Reason: $reason");
            $this->redirect("/admin/users/manage/$userId", null, "Balance updated.");
        } catch (\Exception $e) {
            $this->redirect("/admin/users/manage/$userId", "Error: " . $e->getMessage());
        }
    }
}
