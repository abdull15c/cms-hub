<?php
namespace Src\Controllers;
use Config\Database;
use Src\Services\SessionService;

class ChatController extends Controller {
    public function send($productId) {
        SessionService::start();
        
        if (!SessionService::get('user_id')) { 
            SessionService::set('redirect_after_login', '/product/' . $productId);
            $this->redirect('/login');
        }
        
        $this->verifyCsrf();
        $userId = $this->currentUserId();
        $message = trim($_POST['message']);
        
        if (empty($message)) {
            $this->redirect('/product/' . $productId);
        }

        $pdo = Database::connect();
        $productCheck = $pdo->prepare("SELECT id FROM products WHERE id = ? AND status = 'published'");
        $productCheck->execute([$productId]);
        if (!$productCheck->fetch()) {
            $this->abort(404, 'Product not found.');
        }
        // Check thread exists
        $stmt = $pdo->prepare("SELECT id FROM chat_threads WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $thread = $stmt->fetch();

        if ($thread) {
            $pdo->prepare("UPDATE chat_threads SET updated_at = NOW() WHERE id = ?")->execute([$thread['id']]);
            $threadId = $thread['id'];
        } else {
            $pdo->prepare("INSERT INTO chat_threads (user_id, product_id) VALUES (?, ?)")->execute([$userId, $productId]);
            $threadId = $pdo->lastInsertId();
        }

        $pdo->prepare("INSERT INTO chat_messages (thread_id, sender_type, message) VALUES (?, 'user', ?)")->execute([$threadId, $message]);
        
        // POLISH: Use helper
        $this->redirect('/product/' . $productId . '?chat_open=1');
    }
}
