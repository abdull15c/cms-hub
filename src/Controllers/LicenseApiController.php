<?php
namespace Src\Controllers;

use Src\Services\LicenseService;

class LicenseApiController {
    private function respond($payload, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }

    public function check() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $this->respond([
                'status' => 'error',
                'error' => ['code' => 'invalid_json', 'message' => 'Request body must be valid JSON']
            ], 400);
            return;
        }
        
        $key = trim((string)($input['key'] ?? ''));
        $domain = LicenseService::normalizeDomain((string)($input['domain'] ?? ''));
        
        if ($key === '' || $domain === null) {
            $this->respond([
                'status' => 'error',
                'error' => ['code' => 'missing_params', 'message' => 'Missing parameters: key, domain']
            ], 422);
            return;
        }
        if (!preg_match('/^[A-Z0-9-]{8,100}$/i', $key)) {
            $this->respond([
                'status' => 'error',
                'error' => ['code' => 'invalid_key', 'message' => 'License key format is invalid']
            ], 422);
            return;
        }

        $service = new LicenseService();
        $result = $service->validateLicense($key, $domain);
        $this->respond($result, 200);
    }
}
