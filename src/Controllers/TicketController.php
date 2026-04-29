<?php
namespace Src\Controllers;
use Config\Database;
use Src\Services\AuditService;

class TicketController extends Controller {
    
    public function index() {
        $this->requireAuth();
        $pdo = Database::connect();
        $tickets = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY updated_at DESC");
        $tickets->execute([$this->currentUserId()]);
        $this->view('tickets/index', ['tickets' => $tickets->fetchAll()]);
    }

    public function create() {
        $this->requireAuth();
        // Get paid orders for dropdown
        $pdo = Database::connect();
        $orders = $pdo->prepare("SELECT t.id, p.title FROM transactions t JOIN products p ON t.product_id = p.id WHERE t.user_id = ? AND t.status = 'paid'");
        $orders->execute([$this->currentUserId()]);
        $this->view('tickets/create', ['orders' => $orders->fetchAll()]);
    }

    public function store() {
        $this->requireAuth();
        $this->verifyCsrf();
        
        $uid = $this->currentUserId();
        $subject = trim(mb_substr(strip_tags((string)($_POST['subject'] ?? '')), 0, 255));
        $dept = (string)($_POST['department'] ?? 'general');
        $prio = (string)($_POST['priority'] ?? 'normal');
        $trxId = !empty($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : null;
        $msg = trim(strip_tags((string)($_POST['message'] ?? '')));
        $allowedDepartments = ['general', 'billing', 'technical', 'support'];
        $allowedPriorities = ['low', 'normal', 'high'];
        if ($subject === '' || $msg === '') {
            $this->redirect('/tickets/new', 'Subject and message are required.');
        }
        if (!in_array($dept, $allowedDepartments, true)) {
            $dept = 'general';
        }
        if (!in_array($prio, $allowedPriorities, true)) {
            $prio = 'normal';
        }

        $pdo = Database::connect();
        if ($trxId !== null) {
            $check = $pdo->prepare("SELECT id FROM transactions WHERE id = ? AND user_id = ? AND status = 'paid'");
            $check->execute([$trxId, $uid]);
            if (!$check->fetch()) {
                $this->redirect('/tickets/new', 'Selected order is unavailable.');
            }
        }
        $pdo->prepare("INSERT INTO tickets (user_id, transaction_id, subject, department, priority) VALUES (?, ?, ?, ?, ?)")
            ->execute([$uid, $trxId, $subject, $dept, $prio]);
        $tid = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message) VALUES (?, ?, 0, ?)")
            ->execute([$tid, $uid, $msg]);

        AuditService::log('ticket', 'create', $tid);
        $this->redirect('/tickets/view/' . $tid);
    }

    public function show($id) {
        $this->requireAuth();
        $pdo = Database::connect();
        
        $tStmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND user_id = ?");
        $tStmt->execute([$id, $this->currentUserId()]);
        $ticket = $tStmt->fetch();

        if (!$ticket) $this->redirect('/tickets', 'Ticket not found');

        $mStmt = $pdo->prepare("SELECT tm.*, u.email FROM ticket_messages tm LEFT JOIN users u ON tm.user_id = u.id WHERE ticket_id = ? ORDER BY created_at ASC");
        $mStmt->execute([$id]);
        
        $this->view('tickets/show', ['ticket' => $ticket, 'messages' => $mStmt->fetchAll()]);
    }

    public function reply($id) {
        $this->requireAuth();
        $this->verifyCsrf();
        $msg = trim($_POST['message']);
        
        if ($msg) {
            $pdo = Database::connect();
            // Verify ownership
            $check = $pdo->prepare("SELECT id FROM tickets WHERE id = ? AND user_id = ?");
            $userId = $this->currentUserId();
            $check->execute([$id, $userId]);
            if (!$check->fetch()) $this->abort(403, 'Access denied.');

            $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message) VALUES (?, ?, 0, ?)")
                ->execute([$id, $userId, $msg]);
            
            $pdo->prepare("UPDATE tickets SET status = 'customer_reply', updated_at = NOW() WHERE id = ?")->execute([$id]);
        }
        $this->redirect('/tickets/view/' . $id);
    }
}
