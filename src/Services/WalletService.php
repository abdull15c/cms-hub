<?php
namespace Src\Services;
use Config\Database;
use Src\Services\MoneyService;

class WalletService {
    
    // ATOMIC TRANSACTION with PRECISION MATH
    public static function changeBalance($userId, $amount, $type, $refId = null, $desc = '') {
        $pdo = Database::connect();
        try {
            $pdo->beginTransaction();
            
            // Lock user row
            $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $currentCents = MoneyService::toCents((float)$stmt->fetchColumn());
            $deltaCents = MoneyService::toCents((float)$amount);
            $newBalanceCents = $currentCents + $deltaCents;
            if ($newBalanceCents < 0) throw new \Exception("Insufficient funds");
            $newBalance = MoneyService::fromCents($newBalanceCents);

            // Update Balance
            $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?")->execute([$newBalance, $userId]);
            
            // Log It
            $pdo->prepare("INSERT INTO wallet_logs (user_id, amount, type, reference_id, description) VALUES (?, ?, ?, ?, ?)")
                ->execute([$userId, MoneyService::decimalStringFromCents($deltaCents), $type, $refId, $desc]);

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