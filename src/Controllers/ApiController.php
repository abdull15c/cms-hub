<?php
namespace Src\Controllers;
use Config\Database;
use Src\Repositories\ProductRepository;
use Src\Services\SessionService;

class ApiController {
    
    private function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
        header('Pragma: no-cache');
        header('Vary: Authorization');
        echo json_encode($data);
        exit;
    }

    private function error($code, $message, $status = 400) {
        $this->json([
            'status' => 'error',
            'error' => ['code' => $code, 'message' => $message]
        ], $status);
    }

    private function auth() {
        $token = '';
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $token = (string)($headers['Authorization'] ?? $headers['authorization'] ?? '');
        }
        if ($token === '') {
            $token = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        }
        if (preg_match('/Bearer\s+(\S+)/i', $token, $matches)) {
            $token = $matches[1];
        }
        
        if(!$token) $this->error('missing_token', 'No token provided', 401);
        if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
            $this->error('invalid_token', 'Invalid token', 401);
        }
        
        $pdo = Database::connect();
        $tokenHash = 'sha256:' . hash('sha256', $token);
        $stmt = $pdo->prepare("SELECT id, email, balance, role, is_banned FROM users WHERE api_token = ?");
        $stmt->execute([$tokenHash]);
        $user = $stmt->fetch();
        
        if(!$user) $this->error('invalid_token', 'Invalid token', 401);
        if (!empty($user['is_banned'])) $this->error('account_unavailable', 'Account is unavailable', 403);
        return $user;
    }

    // GET /api/products
    public function products() {
        $pdo = Database::connect();
        $repo = new ProductRepository();
        $lang = SessionService::get('lang', 'ru');
        $localizedColumns = $repo->localizedColumns($lang, 'p', 'pt');
        $translationJoin = $repo->translationJoin($lang, 'p', 'pt');
        $products = $pdo->query("SELECT p.id, p.price, p.sale_price, {$localizedColumns} FROM products p {$translationJoin} WHERE p.status = 'published' ORDER BY p.id DESC LIMIT 20")->fetchAll();
        $this->json(['status' => 'success', 'data' => $products]);
    }

    // GET /api/me (Profile)
    public function me() {
        $user = $this->auth();
        $this->json([
            'id' => $user['id'],
            'email' => $user['email'],
            'balance' => $user['balance'],
            'role' => $user['role']
        ]);
    }
}
