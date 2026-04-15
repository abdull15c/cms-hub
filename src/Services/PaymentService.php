<?php
namespace Src\Services;
use Config\Database;
use Src\Services\Logger;
use Src\Core\Event;

class PaymentService {
    private $pdo;
    public function __construct() { $this->pdo = Database::connect(); }
    private function getSetting($key) { return \Src\Services\SettingsService::get($key); }
    private const WEBHOOK_TTL_SECONDS = 300;

    private function linkYooMoney($orderId, $amount) { $w = $this->getSetting('yoomoney_wallet'); return defined('BASE_URL') ? BASE_URL . "/payment/yoomoney_form?id=$orderId&sum=$amount&wallet=$w&label=$orderId" : '#'; }
    private function linkFreeKassa($orderId, $amount) { $m = $this->getSetting('fk_merchant_id'); $s = $this->getSetting('fk_secret_1'); $hash = md5("$m:$amount:$s:USD:$orderId"); return "https://pay.freekassa.ru/?m=$m&oa=$amount&o=$orderId&s=$hash&currency=USD"; }
    private function linkPayeer($orderId, $amount) {
        $merchantId = $this->getSetting('payeer_merchant_id');
        $secret = $this->getSetting('payeer_secret_key');
        if ($merchantId === '' || $secret === '') {
            return null;
        }
        $desc = base64_encode("Order #{$orderId}");
        $parts = [$merchantId, $orderId, $amount, 'USD', $desc, $secret];
        $sign = strtoupper(hash('sha256', implode(':', $parts)));
        return 'https://payeer.com/merchant/?' . http_build_query([
            'm_shop' => $merchantId,
            'm_orderid' => $orderId,
            'm_amount' => $amount,
            'm_curr' => 'USD',
            'm_desc' => $desc,
            'm_sign' => $sign,
        ]);
    }
    private function linkCrypto($orderId, $amount) {
        $shopId = $this->getSetting('crypto_shop_id');
        $apiKey = $this->getSetting('crypto_api_key');
        if ($shopId === '' || $apiKey === '') {
            return null;
        }
        return (defined('BASE_URL') ? BASE_URL : '') . '/payment/success?id=' . $orderId . '&provider=crypto_pending';
    }

    public function createPayment($provider, $user_id, $product_id) {
        $couponCode = $_POST['coupon_code'] ?? '';
        try {
            $this->pdo->beginTransaction();
            if ($product_id == 0) {
                $amountCents = (int)round((float)$_POST['amount'] * 100);
                $isDeposit = true;
            } else {
                $stmt = $this->pdo->prepare("SELECT price FROM products WHERE id = ?"); 
                $stmt->execute([$product_id]); 
                $product = $stmt->fetch();
                if (!$product) throw new \Exception("Invalid Product");
                $amountCents = (int)round($product['price'] * 100);
                $isDeposit = false;
            }

            $couponId = null;
            if (!$isDeposit && $couponCode) {
                // LOCK Coupon Row
                $cStmt = $this->pdo->prepare("SELECT * FROM coupons WHERE code = ? FOR UPDATE");
                $cStmt->execute([$couponCode]);
                $coupon = $cStmt->fetch();
                if ($coupon && $coupon['used_count'] < $coupon['max_uses']) {
                    $discountCents = (int)round($amountCents * ($coupon['discount_percent'] / 100));
                    $amountCents = max(0, $amountCents - $discountCents);
                    $couponId = $coupon['id'];
                }
            }
            
            $finalAmount = number_format($amountCents / 100, 2, '.', '');
            if (!$isDeposit && $amountCents <= 0) {
                $this->pdo->commit();
                return $this->processFreeOrder($user_id, $product_id, $couponId);
            }

            $stmt = $this->pdo->prepare("INSERT INTO transactions (user_id, product_id, provider, amount, status, coupon_id) VALUES (?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([$user_id, $product_id, $provider, $finalAmount, $couponId]);
            $orderId = $this->pdo->lastInsertId();
            $this->pdo->commit();

            if ($provider === 'yoomoney') return $this->linkYooMoney($orderId, $finalAmount);
            if ($provider === 'freekassa') return $this->linkFreeKassa($orderId, $finalAmount);
            if ($provider === 'payeer') return $this->linkPayeer($orderId, $finalAmount);
            if ($provider === 'crypto') return $this->linkCrypto($orderId, $finalAmount);
            return null;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return null;
        }
    }

    private function processFreeOrder($user_id, $product_id, $couponId = null) {
        $this->pdo->beginTransaction();
        $stmt = $this->pdo->prepare("INSERT INTO transactions (user_id, product_id, provider, amount, status, coupon_id) VALUES (?, ?, 'free', 0.00, 'paid', ?)");
        $stmt->execute([$user_id, $product_id, $couponId]);
        $trxId = (int)$this->pdo->lastInsertId();
        if (!empty($couponId)) {
            $this->pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$couponId]);
        }
        $this->pdo->commit();
        Event::fire('order.paid', ['id' => $trxId, 'user_id' => $user_id, 'product_id' => $product_id, 'amount' => 0.00]);
        return defined('BASE_URL') ? BASE_URL . "/payment/success?free=1&id=" . $product_id : '/';
    }
    
    public function handleWebhook($provider, $data, $rawBody = '') {
        if ($provider === 'yoomoney') $this->verifyYooMoney($data);
        if ($provider === 'freekassa') $this->verifyFreeKassa($data);
        if ($provider === 'payeer') $this->verifyPayeer($data);
        if ($provider === 'crypto') $this->verifyCrypto($data, $rawBody);
    }

    private function rejectWebhook(string $message, int $status = 403): void {
        http_response_code($status);
        exit($message);
    }

    private function verifyYooMoney($data) {
        $secret = $this->getSetting('yoomoney_secret');
        $str = $data['notification_type'].'&'.$data['operation_id'].'&'.$data['amount'].'&'.$data['currency'].'&'.$data['datetime'].'&'.$data['sender'].'&'.$data['codepro'].'&'.$secret.'&'.$data['label'];
        if (!hash_equals(sha1($str), $data['sha1_hash'] ?? '')) { $this->rejectWebhook('Hash Error'); }
        $this->processSuccess((int)$data['label'], (float)$data['amount']); echo "OK"; exit;
    }

    private function verifyFreeKassa($data) {
        $secret2 = $this->getSetting('fk_secret_2');
        $mid = $this->getSetting('fk_merchant_id');
        $mySign = md5($mid.':'.$data['AMOUNT'].':'.$secret2.':'.$data['MERCHANT_ORDER_ID']);
        if (!hash_equals($mySign, $data['SIGN'] ?? '')) { $this->rejectWebhook('Sign Error'); }
        $this->processSuccess((int)$data['MERCHANT_ORDER_ID'], (float)$data['AMOUNT']); echo "YES"; exit;
    }

    private function verifyPayeer($data) {
        if (!isset($data['m_sign'])) $this->rejectWebhook('Bad Request', 400);
        $m_key = $this->getSetting('payeer_secret_key');
        $arHash = [
            $data['m_operation_id'], $data['m_operation_ps'], $data['m_operation_date'],
            $data['m_operation_pay_date'], $data['m_shop'], $data['m_orderid'],
            $data['m_amount'], $data['m_curr'], $data['m_desc'], $data['m_status']
        ];
        if (isset($data['m_params'])) $arHash[] = $data['m_params'];
        $arHash[] = $m_key;
        $sign_hash = strtoupper(hash('sha256', implode(':', $arHash)));
        if (hash_equals($sign_hash, strtoupper($data['m_sign'])) && $data['m_status'] == 'success') {
            $this->processSuccess((int)$data['m_orderid'], (float)$data['m_amount']);
            echo $data['m_orderid'] . '|success'; exit;
        }
        $this->rejectWebhook('Sign Error');
    }

    private function verifyCrypto($data, $rawBody = '') {
        $status = (string)($data['status'] ?? '');
        $orderId = (int)($data['order_id'] ?? 0);
        $amount = (string)($data['amount_usd'] ?? '');
        $timestamp = (int)($_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'] ?? 0);
        $signature = (string)($_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '');
        $secret = (string)($_ENV['CRYPTO_WEBHOOK_SECRET'] ?? getenv('CRYPTO_WEBHOOK_SECRET') ?: $this->getSetting('crypto_webhook_secret'));

        if ($status !== 'paid' || $orderId <= 0 || $amount === '' || !$timestamp || $signature === '' || $secret === '') {
            $this->rejectWebhook('Invalid webhook payload');
        }
        if (abs(time() - $timestamp) > self::WEBHOOK_TTL_SECONDS) {
            $this->rejectWebhook('Expired webhook');
        }

        $payload = $rawBody !== '' ? $rawBody : json_encode($data, JSON_UNESCAPED_UNICODE);
        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        if (!hash_equals($expected, $signature)) {
            $this->rejectWebhook('Sign Error');
        }

        if ($this->isWebhookReplay($timestamp . '.' . $payload, $signature)) {
            $this->rejectWebhook('Replay detected');
        }

        $this->processSuccess($orderId, (float)$amount);
        echo "OK";
        exit;
    }

    private function isWebhookReplay($payload, $signature) {
        $dir = (defined('STORAGE_PATH') ? STORAGE_PATH : dirname(__DIR__, 2) . '/storage') . '/cache/webhook';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $key = hash('sha256', $payload . '|' . $signature);
        $file = $dir . '/' . $key . '.lock';
        $handle = @fopen($file, 'x');
        if ($handle === false) {
            return true;
        }
        fwrite($handle, (string)time());
        fclose($handle);
        return false;
    }

    public function approveManually($orderId) {
        return $this->processSuccess((int)$orderId, null);
    }

    // *** CRITICAL: ATOMIC PROCESSING ***
    private function processSuccess($orderId, $paidAmount = null) {
        try {
            $pdo = Database::connect();
            if (!$pdo->inTransaction()) $pdo->beginTransaction();
            
            // LOCK ROW FOR UPDATE
            $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? FOR UPDATE"); 
            $stmt->execute([$orderId]); 
            $trx = $stmt->fetch();
            
            // Idempotency check
            if (!$trx || $trx['status'] === 'paid') { 
                $pdo->commit(); 
                return; 
            }
            if ($paidAmount !== null) {
                $expected = (float)$trx['amount'];
                if (abs($expected - (float)$paidAmount) > 0.01) {
                    $pdo->prepare("UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE id = ?")->execute([$orderId]);
                    $pdo->commit();
                    Logger::warning("Payment amount mismatch for Order #$orderId", ['expected' => $expected, 'received' => (float)$paidAmount]);
                    return;
                }
            }

            $pdo->prepare("UPDATE transactions SET status = 'paid', updated_at = NOW() WHERE id = ?")->execute([$orderId]);
            
            if (!empty($trx['coupon_id'])) {
                $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$trx['coupon_id']]);
            }
            
            if ($trx['product_id'] == 0) {
                $uStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
                $uStmt->execute([$trx['user_id']]);
                $currentBalance = (float)$uStmt->fetchColumn();
                $newBalance = round($currentBalance + (float)$trx['amount'], 2);
                $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?")->execute([$newBalance, $trx['user_id']]);
                $pdo->prepare("INSERT INTO wallet_logs (user_id, amount, type, reference_id, description) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$trx['user_id'], $trx['amount'], 'deposit', $orderId, 'Gateway Deposit']);
            }
            
            $pdo->commit();
            Event::fire('order.paid', $trx);
            Logger::info("Payment processed securely for Order #$orderId");
        } catch (\Exception $e) {
            if(isset($pdo) && $pdo->inTransaction()) $pdo->rollBack(); 
            Logger::error("Payment Error: " . $e->getMessage());
        }
    }
}
