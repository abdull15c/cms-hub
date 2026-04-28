<?php
namespace Src\Controllers\Admin;
use Config\Database;
use Src\Services\AuditService;
use Src\Services\Gate;
use Src\Services\PaymentService;

class TransactionController extends BaseAdminController {
    
    public function index() {
        $this->checkAuth();
        Gate::authorize('dashboard.view');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20; $offset = ($page-1)*$perPage;
        
        $pdo = Database::connect();
        $sql = "SELECT t.*, u.email, p.title as product_title 
                FROM transactions t 
                LEFT JOIN users u ON t.user_id = u.id 
                LEFT JOIN products p ON t.product_id = p.id 
                ORDER BY t.created_at DESC LIMIT $perPage OFFSET $offset";
        
        $trans = $pdo->query($sql)->fetchAll();
        $total = $pdo->query("SELECT count(*) FROM transactions")->fetchColumn();
        
        $this->view('admin/transactions', ['trans'=>$trans, 'page'=>$page, 'total'=>$total, 'perPage'=>$perPage]);
    }

    public function approve($id) {
        $this->checkAuth(); $this->verifyCsrf();
        Gate::authorize('dashboard.view');
        $this->service('payment', function () {
            return new PaymentService();
        })->approveManually((int)$id);

        AuditService::log('payment', 'manual_approve', $id);
        $this->redirect('/admin/transactions', null, 'Order Approved Manually.');
    }

    public function cancel($id) {
        $this->checkAuth(); $this->verifyCsrf();
        Gate::authorize('dashboard.view');
        Database::connect()->prepare("UPDATE transactions SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        AuditService::log('payment', 'manual_cancel', $id);
        $this->redirect('/admin/transactions', null, 'Order Cancelled.');
    }
}
