<?php
namespace Src\Services;
use Config\Database;

class AccessManager {
    public static function canDownload($userId, $productId) {
        $pdo = Database::connect();
        
        // 1. Check Paid Transaction
        $stmt = $pdo->prepare("SELECT id FROM transactions WHERE user_id = ? AND product_id = ? AND status = 'paid'");
        $stmt->execute([$userId, $productId]);
        if ($stmt->fetch()) return true;

        // 2. Check Active License (Manual Gift or Legacy)
        $stmt2 = $pdo->prepare("SELECT id FROM licenses WHERE user_id = ? AND product_id = ? AND is_active = 1");
        $stmt2->execute([$userId, $productId]);
        if ($stmt2->fetch()) return true;

        return false;
    }

    public static function canReview($userId, $productId) {
        // Can review if they have access AND haven't reviewed yet
        if (!self::canDownload($userId, $productId)) return false;
        
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        return !$stmt->fetch();
    }
}