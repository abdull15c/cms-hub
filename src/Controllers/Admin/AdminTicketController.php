<?php
namespace Src\Controllers\Admin;
use Config\Database;
use Src\Services\AuditLogger;
use Src\Services\Gate;

class AdminTicketController extends BaseAdminController {
    public function index() {
        $this->checkAuth();
        Gate::authorize('dashboard.view');
        $pdo = Database::connect();
        $status = $_GET['status'] ?? 'open';
        $sql = "SELECT t.*, u.email FROM tickets t JOIN users u ON t.user_id = u.id ";
        if ($status !== 'all') $sql .= "WHERE t.status != 'closed' ";
        $sql .= "ORDER BY FIELD(t.status, 'customer_reply', 'open', 'answered'), t.updated_at DESC";
        $this->view('admin/tickets/index', ['tickets' => $pdo->query($sql)->fetchAll()]);
    }

    public function show($id) {
        $this->checkAuth();
        Gate::authorize('dashboard.view');
        $pdo = Database::connect();
        // FIXED: PREPARED STATEMENT
        $stmt = $pdo->prepare("SELECT t.*, u.email, tr.amount, p.title as product_title 
            FROM tickets t 
            JOIN users u ON t.user_id = u.id 
            LEFT JOIN transactions tr ON t.transaction_id = tr.id
            LEFT JOIN products p ON tr.product_id = p.id
            WHERE t.id = ?");
        $stmt->execute([$id]);
        $ticket = $stmt->fetch();
            
        if (!$ticket) $this->redirect('/admin/tickets');

        $mStmt = $pdo->prepare("SELECT tm.*, u.email FROM ticket_messages tm LEFT JOIN users u ON tm.user_id = u.id WHERE ticket_id = ? ORDER BY created_at ASC");
        $mStmt->execute([$id]);
        
        $this->view('admin/tickets/show', ['ticket' => $ticket, 'messages' => $mStmt->fetchAll()]);
    }

    public function reply($id) {
        $this->checkAuth();
        Gate::authorize('dashboard.view');
        $this->verifyCsrf();
        $msg = trim($_POST['message']);
        $status = in_array($_POST['status'] ?? 'answered', ['open', 'answered', 'customer_reply', 'closed'], true)
            ? $_POST['status']
            : 'answered';
        $pdo = Database::connect();
        if ($msg) {
            $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message) VALUES (?, ?, 1, ?)")->execute([$id, $this->currentUserId(), $msg]);
        }
        $pdo->prepare("UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$status, $id]);
        
        // NOTIFY HOOK
        $tInfo = $pdo->query("SELECT user_id, subject FROM tickets WHERE id=".intval($id))->fetch();
        if($tInfo) \Src\Services\NotificationService::send($tInfo["user_id"], "Support replied: " . substr($tInfo["subject"],0,20)."...", "info", BASE_URL."/tickets/view/".$id);

        AuditLogger::log('ticket_reply', "ID: $id, Status: $status");
        $this->redirect('/admin/tickets/view/' . $id);
    }

    public function ajaxAiReply() {
        $this->checkAuth();
        Gate::authorize('dashboard.view');
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $subject = $input['subject'] ?? '';
        $msg = $input['message'] ?? '';
        $user = $input['user'] ?? 'Customer';

        if (!$msg) { echo json_encode(['error' => 'Message required']); exit; }

        try {
            $ai = new \Src\Services\AiService();
            $reply = $ai->generateSupportReply($subject, $msg, $user);
            if (!$reply) throw new \Exception("AI returned empty result");
            echo json_encode(['status' => 'success', 'reply' => $reply]);
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}
