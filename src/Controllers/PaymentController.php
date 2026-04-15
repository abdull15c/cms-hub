<?php
namespace Src\Controllers;
use Src\Services\PaymentService;
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
        
        $provider = $_POST['provider'] ?? 'yoomoney';
        $userId = $this->currentUserId();
        
        // WALLET PAYMENT
        if ($provider === 'wallet') {
            $this->processWalletPayment($userId, $id, $_POST['coupon_code'] ?? '');
            return;
        }
        
        // GATEWAY PAYMENT
        $service = $this->paymentService();
        $link = $service->createPayment($provider, $userId, $id);
        
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
            $stmt = $pdo->prepare("SELECT price, title FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            if (!$product) {
                throw new \Exception('Product not found.');
            }
            $price = (float)$product['price'];
            $couponId = null;

            if ($couponCode) {
                $cStmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? FOR UPDATE");
                $cStmt->execute([$couponCode]);
                $coupon = $cStmt->fetch();
                if ($coupon && $coupon['used_count'] < $coupon['max_uses']) {
                    $price -= $price * ((float)$coupon['discount_percent'] / 100);
                    $couponId = (int)$coupon['id'];
                }
            }
            $uStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
            $uStmt->execute([$userId]);
            $currentBalance = (float)$uStmt->fetchColumn();
            $newBalance = round($currentBalance - $price, 2);
            if ($newBalance < 0) {
                throw new \Exception('Insufficient funds');
            }
            $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?")->execute([$newBalance, $userId]);

            $pdo->prepare("INSERT INTO transactions (user_id, product_id, provider, amount, status, coupon_id) VALUES (?, ?, 'wallet', ?, 'paid', ?)")
                ->execute([$userId, $productId, $price, $couponId]);
            $orderId = (int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO wallet_logs (user_id, amount, type, reference_id, description) VALUES (?, ?, ?, ?, ?)")
                ->execute([$userId, -$price, 'purchase', $orderId, "Bought: " . $product['title']]);
            if ($couponId) {
                $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$couponId]);
            }

            $pdo->commit();
            \Src\Core\Event::fire('order.paid', ['id' => $orderId, 'user_id' => $userId, 'product_id' => $productId, 'amount' => $price]);
            
            $this->redirect('/payment/success?id='.$productId);
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
        $amount = floatval($_POST['amount'] ?? 0);
        $provider = $_POST['provider'] ?? 'yoomoney'; 
        
        if ($amount <= 0) $this->redirect('/wallet', 'Invalid Amount');

        $service = $this->paymentService();
        // Product ID 0 = Deposit
        $link = $service->createPayment($provider, $this->currentUserId(), 0);
        
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

    public function yoomoney_form() {
        if (file_exists(VIEW_PATH . '/payment/yoomoney_form.php')) include VIEW_PATH . '/payment/yoomoney_form.php';
    }
}
