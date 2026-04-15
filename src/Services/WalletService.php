<?php
namespace Src\Services;
use Config\Database;

class WalletService {
    
    // ATOMIC TRANSACTION with PRECISION MATH
    public static function changeBalance($userId, $amount, $type, $refId = null, $desc = '') {
        $pdo = Database::connect();
        try {
            $pdo->beginTransaction();
            
            // Lock user row
            $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $current = $stmt->fetchColumn();
            
            // FIXED: Use BCMath for precision if available, else strict rounding
            if (function_exists('bcadd')) {
                $newBalance = bcadd((string)$current, (string)$amount, 2);
                $comp = bccomp($newBalance, '0.00', 2); // check if < 0
            } else {
                $newBalance = round($current + $amount, 2);
                $comp = ($newBalance < 0) ? -1 : 1;
            }

            if ($comp < 0) throw new \Exception("Insufficient funds");

            // Update Balance
            $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?")->execute([$newBalance, $userId]);
            
            // Log It
            $pdo->prepare("INSERT INTO wallet_logs (user_id, amount, type, reference_id, description) VALUES (?, ?, ?, ?, ?)")
                ->execute([$userId, $amount, $type, $refId, $desc]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function getBalance($userId) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return (float) $stmt->fetchColumn();
    }
}