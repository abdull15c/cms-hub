<?php
namespace Src\Controllers;
use Config\Database;
use Src\Services\SessionService;

class AdminChatController extends Controller {
    
    public function __construct() { SessionService::start(); }
    private function checkAuth() {
        if (!SessionService::get('user_id') || SessionService::get('role') !== 'admin') {
            $this->redirect('/login');
        }
    }

    public function inbox() {
        $this->checkAuth();
        $pdo = Database::connect();
        
        $sql = "
            SELECT t.id, t.updated_at, p.title as product_title, u.email,
            (SELECT message FROM chat_messages WHERE thread_id = t.id ORDER BY id DESC LIMIT 1) as last_msg
            FROM chat_threads t
            JOIN products p ON t.product_id = p.id
            JOIN users u ON t.user_id = u.id
            ORDER BY t.updated_at DESC
        ";
        $threads = $pdo->query($sql)->fetchAll();
        
        $this->view('admin/chat/inbox', ['threads' => $threads]);
    }

    public function viewThread($id) {
        $this->checkAuth();
        $pdo = Database::connect();
        
        // AUDIT FIX: Use Prepared Statements instead of direct interpolation
        $threadStmt = $pdo->prepare("
            SELECT t.*, p.title, u.email 
            FROM chat_threads t 
            JOIN products p ON t.product_id = p.id 
            JOIN users u ON t.user_id = u.id 
            WHERE t.id = ?
        ");
        $threadStmt->execute([$id]);
        $thread = $threadStmt->fetch();

        if (!$thread) $this->redirect('/admin/chat', 'Thread not found');

        $msgStmt = $pdo->prepare("SELECT * FROM chat_messages WHERE thread_id = ? ORDER BY created_at ASC");
        $msgStmt->execute([$id]);
        $messages = $msgStmt->fetchAll();
        
        $this->view('admin/chat/conversation', ['thread' => $thread, 'messages' => $messages]);
    }

    public function reply($id) {
        $this->checkAuth();
        $this->verifyCsrf();
        $msg = trim($_POST['message']);
        if($msg) {
            $pdo = Database::connect();
            $pdo->prepare("INSERT INTO chat_messages (thread_id, sender_type, message) VALUES (?, 'admin', ?)")->execute([$id, $msg]);
            $pdo->prepare("UPDATE chat_threads SET updated_at = NOW() WHERE id = ?")->execute([$id]);
        }
        $this->redirect('/admin/chat/' . $id);
    }
}
