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
        
        $stmt = $pdo->prepare("SELECT * FROM licenses WHERE license_key = ?");
        $stmt->execute([$key]);
        $license = $stmt->fetch();

        // Standard Response Structure
        $response = [
            'status' => 'error',
            'message' => 'Invalid License',
            'authority' => 'cms-hub.ru', // BRANDING
            'timestamp' => time()
        ];

        if (!$license) return $response;

        if ($normalizedDomain === null) {
            $response['message'] = 'Invalid domain';
            return $response;
        }

        if (!$license['is_active']) {
            $response['message'] = 'License Suspended by Administrator';
            return $response;
        }

        // Domain Logic
        if (empty($license['domain'])) {
            $update = $pdo->prepare("UPDATE licenses SET domain = ?, activated_at = NOW() WHERE id = ?");
            $update->execute([$normalizedDomain, $license['id']]);
            
            $response['status'] = 'success';
            $response['message'] = "Activated for $normalizedDomain";
            return $response;
        }

        if ($license['domain'] !== $normalizedDomain) {
            $response['message'] = "Domain mismatch. Key bound to: " . $license['domain'];
            return $response;
        }

        $response['status'] = 'success';
        $response['message'] = 'License Valid';
        return $response;
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
