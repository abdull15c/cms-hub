<?php
namespace Src\Controllers;
use Config\Database;
use Src\Services\WalletService;

class WalletController extends Controller {
    public function index() {
        $this->requireAuth();
        $uid = $this->currentUserId();
        $balance = WalletService::getBalance($uid);
        
        $pdo = Database::connect();
        $logs = $pdo->prepare("SELECT * FROM wallet_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $logs->execute([$uid]);
        
        $this->view('wallet/index', ['balance' => $balance, 'logs' => $logs->fetchAll()]);
    }
}
