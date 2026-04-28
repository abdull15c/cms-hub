<?php
namespace Src\Controllers\Admin;
use Config\Database;
use Src\Services\AuditService;
use Src\Services\Gate;

class UserController extends BaseAdminController {
    public function index() {
        $this->checkAuth();
        Gate::authorize('dashboard.view');
        $page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
        $perPage = 20; $offset = ($page - 1) * $perPage;
        
        $pdo = Database::connect();
        $users = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT $perPage OFFSET $offset")->fetchAll();
        $total = $pdo->query("SELECT count(*) FROM users")->fetchColumn();
        
        $this->view('admin/users', ['users'=>$users, 'page'=>$page, 'total'=>$total, 'perPage'=>$perPage]);
    }
    
    public function ban($id) { 
        $this->checkAuth(); 
        Gate::authorize('dashboard.view');
        $this->verifyCsrf();
        if((int)$id === $this->currentUserId()) $this->redirect('/admin/users', 'Cannot ban self.');
        Database::connect()->prepare("UPDATE users SET is_banned = NOT is_banned, api_token = NULL WHERE id = ?")->execute([$id]); 
        AuditService::log('user', 'ban_toggle', $id);
        $this->redirect('/admin/users', null, 'User status updated.'); 
    }
}
