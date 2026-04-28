<?php
namespace Src\Controllers;
use Src\Services\PaymentService;
use Src\Services\MoneyService;
use Config\Database;

class PaymentController extends Controller {
    private function paymentService(): PaymentService {
        return $this->service('payment', function () {
            return new PaymentService();
        });
    }

    private function jsonError($message, $code = 500) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'error' => ['code' => 'payment_error', 'message' => $message]]);
        exit;
    }
    
    public function checkout($id) {
        $this->requireAuth();
        $this->verifyCsrf();
        
        $provider = strtolower(trim((string)($_POST['provider'] ?? 'yoomoney')));
        $userId = $this->currentUserId();
        
        // WALLET PAYMENT
        if ($provider === 'wallet') {
            $this->processWalletPayment($userId, $id, $_POST['coupon_code'] ?? '');
            return;
        }
        
        // GATEWAY PAYMENT
        $service = $this->paymentService();
        $link = $service->createPayment($provider, $userId, (int)$id, null, (string)($_POST['coupon_code'] ?? ''));
        
        if ($link) {
            header("Location: " . $link);
            exit;
        } else {
            $this->jsonError('Payment gateway init failed.', 502);
        }
    }

    private function processWalletPayment($userId, $productId, $couponCode) {
        $pdo = Database::connect();

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT price, sale_price, sale_end, status, title FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            if (!$product || ($product['status'] ?? '') !== 'published') {
                throw new \Exception('Product is not available for purchase.');
            }
            $priceCents = MoneyService::toCents($this->currentProductPrice($product));
            $couponId = null;

            if ($couponCode) {
                $cStmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? FOR UPDATE");
                $cStmt->execute([$couponCode]);
                $coupon = $cStmt->fetch();
                if ($coupon && $coupon['used_count'] < $coupon['max_uses']) {
                    $priceCents = MoneyService::applyPercentDiscountCents($priceCents, (float)$coupon['discount_percent']);
                    $couponId = (int)$coupon['id'];
                }
            }
            $uStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
            $uStmt->execute([$userId]);
            $currentBalanceCents = MoneyService::toCents((float)$uStmt->fetchColumn());
            $newBalanceCents = $currentBalanceCents - $priceCents;
            if ($newBalanceCents < 0) {
                throw new \Exception('Insufficient funds');
            }
            $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?")->execute([MoneyService::fromCents($newBalanceCents), $userId]);

            $pdo->prepare("INSERT INTO transactions (user_id, product_id, provider, amount, status, coupon_id) VALUES (?, ?, 'wallet', ?, 'paid', ?)")
                ->execute([$userId, $productId, MoneyService::decimalStringFromCents($priceCents), $couponId]);
            $orderId = (int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO wallet_logs (user_id, amount, type, reference_id, description) VALUES (?, ?, ?, ?, ?)")
                ->execute([$userId, MoneyService::decimalStringFromCents(-$priceCents), 'purchase', $orderId, "Bought: " . $product['title']]);
            if ($couponId) {
                $couponUpdate = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ? AND used_count < max_uses");
                $couponUpdate->execute([$couponId]);
                if ($couponUpdate->rowCount() !== 1) {
                    throw new \Exception('Coupon usage limit reached.');
                }
            }

            $pdo->commit();
            \Src\Core\Event::fire('order.paid', ['id' => $orderId, 'user_id' => $userId, 'product_id' => $productId, 'amount' => MoneyService::fromCents($priceCents)]);
            
            $this->redirect('/payment/success?order_id='.$orderId);
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->redirect('/product/'.$productId, $e->getMessage() ?: 'Insufficient funds or error.');
        }
    }

    // DEPOSIT
    public function deposit() {
        $this->requireAuth();
        $this->verifyCsrf();
        
        // Fix: Use generic getter if key missing
        $amount = (float)($_POST['amount'] ?? 0);
        $provider = strtolower(trim((string)($_POST['provider'] ?? 'yoomoney')));
        
        if (MoneyService::toCents($amount) <= 0) $this->redirect('/wallet', 'Invalid Amount');

        $service = $this->paymentService();
        // Product ID 0 = Deposit
        $link = $service->createPayment($provider, $this->currentUserId(), 0, $amount);
        
        if($link) {
            header("Location: " . $link);
            exit;
        }
        $this->redirect('/wallet', 'Deposit Init Failed');
    }

    public function success() { 
        $free = isset($_GET['free']);
        $id = $_GET['id'] ?? 0;
        $this->view('success', ['is_free' => $free, 'product_name' => 'Digital Asset', 'key' => 'Check your Profile']); 
    }
    
    public function webhook($provider) { 
        if (!$this->isSecureWebhookRequest()) {
            http_response_code(426);
            exit('HTTPS required');
        }
        $rawBody = (string)file_get_contents('php://input');
        $payload = $_POST;
        if (empty($payload) && $rawBody !== '') {
            $json = json_decode($rawBody, true);
            if (is_array($json)) {
                $payload = $json;
            }
        }
        $this->paymentService()->handleWebhook($provider, $payload, $rawBody); 
    }

    private function isSecureWebhookRequest(): bool {
        $appEnv = strtolower(trim((string)getenv('APP_ENV')));
        if (in_array($appEnv, ['local', 'dev', 'development', 'test', 'testing'], true)) {
            return true;
        }
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        $proto = strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        return $proto === 'https';
    }

    public function yoomoney_form() {
        if (file_exists(VIEW_PATH . '/payment/yoomoney_form.php')) include VIEW_PATH . '/payment/yoomoney_form.php';
    }

    private function currentProductPrice(array $product): float {
        $regular = (float)($product['price'] ?? 0);
        $salePrice = $product['sale_price'] !== null ? (float)$product['sale_price'] : null;
        $saleEnd = (string)($product['sale_end'] ?? '');
        if ($salePrice !== null && $salePrice > 0 && $salePrice < $regular && $saleEnd !== '' && strtotime($saleEnd) > time()) {
            return $salePrice;
        }
        return $regular;
    }
}
