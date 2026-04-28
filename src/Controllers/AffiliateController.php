<?php
namespace Src\Controllers;
use Config\Database;

class AffiliateController extends Controller {
    public function index() {
        $this->requireAuth();
        $uid = $this->currentUserId();
        $pdo = Database::connect();
        
        // Strict Binding
        $cStmt = $pdo->prepare("SELECT count(*) FROM users WHERE referrer_id = ?");
        $cStmt->execute([$uid]);
        $refCount = $cStmt->fetchColumn();
        
        $eStmt = $pdo->prepare("SELECT sum(amount) FROM wallet_logs WHERE user_id = ? AND description LIKE ?");
        $eStmt->execute([$uid, 'Referral Reward%']);
        $earnings = $eStmt->fetchColumn();
        
        // Strict Binding for Limit is tricky in PDO, but here we just list 20 hardcoded or bindParam
        $lStmt = $pdo->prepare("SELECT id, email, created_at FROM users WHERE referrer_id = ? ORDER BY created_at DESC LIMIT 20");
        $lStmt->execute([$uid]);
        $referrals = $lStmt->fetchAll();
        
        $this->view('auth/affiliate', [
            'refLink' => BASE_URL . '/?ref=' . $uid,
            'count' => $refCount,
            'earnings' => number_format($earnings ?? 0, 2),
            'referrals' => $referrals
        ]);
    }
}
