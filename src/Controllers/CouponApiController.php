<?php
namespace Src\Controllers;
use Config\Database;
use Src\Core\Csrf;

class CouponApiController {
    public function check() {
        header('Content-Type: application/json');
        
        // CSRF PROTECTION
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['csrf_token'] ?? '';
        
        if (!Csrf::verify($token)) {
            http_response_code(403);
            echo json_encode(['valid' => false, 'msg' => 'Security Error (CSRF)']);
            return;
        }

        $code = strtoupper($input['code'] ?? '');
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ?");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch();

        if (!$coupon) { echo json_encode(['valid' => false, 'msg' => 'Invalid code']); return; }
        if ($coupon['used_count'] >= $coupon['max_uses']) { echo json_encode(['valid' => false, 'msg' => 'Limit reached']); return; }

        echo json_encode([
            'valid' => true, 
            'percent' => $coupon['discount_percent'],
            'msg' => "Coupon applied: -{$coupon['discount_percent']}%"
        ]);
    }
}