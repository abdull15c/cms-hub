<?php
namespace Src\Listeners;

use Config\Database;
use Src\Services\LicenseService;
use Src\Services\MailService;
use Src\Services\Logger;

class ProductDeliveryListener {
    public function handle($order) {
        // $order is the transaction array
        if ($order['product_id'] == 0) return; // Skip deposits

        try {
            $pdo = Database::connect();
            
            // 1. Get Product Info
            $stmt = $pdo->prepare("SELECT title, has_license FROM products WHERE id = ?");
            $stmt->execute([$order['product_id']]);
            $prod = $stmt->fetch();
            
            if (!$prod) return;

            // 2. Generate License if needed
            $keyText = "Not Required";
            if ($prod['has_license']) {
                $licenseService = new LicenseService();
                $keyText = $licenseService->generateKey($order['product_id'], $order['user_id']);
            }

            // 3. Send Email
            $uStmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $uStmt->execute([$order['user_id']]);
            $user = $uStmt->fetch();

            if ($user) {
                $mail = new MailService();
                $mail->sendTemplate($user['email'], "Your Order: " . $prod['title'], 'receipt', [
                    'product_name' => $prod['title'],
                    'price' => $order['amount'],
                    'order_id' => $order['id'],
                    'license_key' => $keyText,
                    'download_link' => defined('BASE_URL') ? BASE_URL . '/download/' . $order['product_id'] : '#'
                ]);
            }

            Logger::info("Delivery complete for Order #{$order['id']}");

        } catch (\Exception $e) {
            Logger::error("Delivery Failed for Order #{$order['id']}: " . $e->getMessage());
        }
    }
}