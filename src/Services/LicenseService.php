<?php
namespace Src\Services;

use Config\Database;

class LicenseService {
    
    public function generateKey($productId, $userId = null) {
        // BRANDING: Use CMSHUB prefix
        $prefix = 'CMSHUB';
        
        // Secure Random Bytes for Pro Key
        $part1 = strtoupper(bin2hex(random_bytes(2)));
        $part2 = strtoupper(bin2hex(random_bytes(2)));
        $part3 = strtoupper(bin2hex(random_bytes(2)));
        $part4 = strtoupper(bin2hex(random_bytes(2)));
        
        $key = "$prefix-$part1-$part2-$part3-$part4";
        
        $pdo = Database::connect();
        $stmt = $pdo->prepare("INSERT INTO licenses (license_key, product_id, user_id, is_active) VALUES (?, ?, ?, 1)");
        $stmt->execute([$key, $productId, $userId]);
        
        return $key;
    }

    public function validateLicense($key, $domain) {
        $key = trim((string)$key);
        $normalizedDomain = self::normalizeDomain((string)$domain);
        $pdo = Database::connect();

        // Standard Response Structure
        $response = [
            'status' => 'error',
            'message' => 'Invalid License',
            'authority' => 'cms-hub.ru', // BRANDING
            'timestamp' => time()
        ];

        if ($normalizedDomain === null) {
            $response['message'] = 'Invalid domain';
            return $response;
        }

        try {
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }
            $stmt = $pdo->prepare("SELECT * FROM licenses WHERE license_key = ? FOR UPDATE");
            $stmt->execute([$key]);
            $license = $stmt->fetch();
            if (!$license) {
                $pdo->commit();
                return $response;
            }
            if (!(bool)$license['is_active']) {
                $pdo->commit();
                $response['message'] = 'License Suspended by Administrator';
                return $response;
            }

            $currentDomain = trim((string)($license['domain'] ?? ''));
            if ($currentDomain === '') {
                $update = $pdo->prepare("
                    UPDATE licenses
                    SET domain = ?, activated_at = NOW()
                    WHERE id = ? AND (domain IS NULL OR domain = '')
                ");
                $update->execute([$normalizedDomain, $license['id']]);
                if ($update->rowCount() === 1) {
                    $pdo->commit();
                    $response['status'] = 'success';
                    $response['message'] = "Activated for $normalizedDomain";
                    return $response;
                }

                // Domain was bound by concurrent request while we were processing.
                $refetch = $pdo->prepare("SELECT domain FROM licenses WHERE id = ? FOR UPDATE");
                $refetch->execute([$license['id']]);
                $boundDomain = trim((string)$refetch->fetchColumn());
                $pdo->commit();
                if ($boundDomain === $normalizedDomain) {
                    $response['status'] = 'success';
                    $response['message'] = 'License Valid';
                    return $response;
                }
                $response['message'] = 'Domain mismatch';
                return $response;
            }

            $pdo->commit();
            if ($currentDomain !== $normalizedDomain) {
                $response['message'] = 'Domain mismatch';
                return $response;
            }
            $response['status'] = 'success';
            $response['message'] = 'License Valid';
            return $response;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return $response;
        }
    }

    public static function normalizeDomain(string $domain): ?string
    {
        $candidate = trim($domain);
        if ($candidate === '') {
            return null;
        }

        if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $candidate)) {
            $candidate = 'http://' . $candidate;
        }

        $host = parse_url($candidate, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }

        $host = strtolower(rtrim($host, '.'));
        if ($host === 'localhost') {
            return $host;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $host;
        }
        if (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return $host;
        }
        return null;
    }
}
