<?php
namespace Src\Listeners;

use Config\Database;
use Src\Services\WalletService;
use Src\Services\Logger;

class AffiliateListener {
    public function handle($order) {
        if ($order['product_id'] == 0) return; // No commission on deposits

        try {
            $pdo = Database::connect();
            
            // Find referrer
            $stmt = $pdo->prepare("SELECT referrer_id FROM users WHERE id = ?");
            $stmt->execute([$order['user_id']]);
            $user = $stmt->fetch();

            if ($user && $user['referrer_id']) {
                $commission = round($order['amount'] * 0.10, 2); // 10%
                if ($commission > 0) {
                    WalletService::changeBalance(
                        $user['referrer_id'], 
                        $commission, 
                        'referral', 
                        $order['id'], 
                        "Reward for Order #{$order['id']}"
                    );
                    Logger::info("Affiliate reward ($commission) sent to User #{$user['referrer_id']}");
                }
            }
        } catch (\Exception $e) {
            Logger::error("Affiliate Error: " . $e->getMessage());
        }
    }
}