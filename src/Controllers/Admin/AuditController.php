<?php
namespace Src\Controllers\Admin;
use Config\Database;
use Src\Services\Gate;

class AuditController extends BaseAdminController {
    public function index() {
        $this->checkAuth();
        Gate::authorize('dashboard.view'); // Managers can see logs

        $pdo = Database::connect();
        
        // Pagination
        $page = $_GET['page'] ?? 1;
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        // Filter Logic
        $where = "WHERE 1=1";
        $params = [];
        if (!empty($_GET['type'])) { $where .= " AND event_type = ?"; $params[] = $_GET['type']; }
        if (!empty($_GET['uid']))  { $where .= " AND user_id = ?"; $params[] = $_GET['uid']; }

        // Fetch Logs with User Emails
        $sql = "SELECT a.*, u.email as user_email 
                FROM audit_logs a 
                LEFT JOIN users u ON a.user_id = u.id 
                $where 
                ORDER BY a.id DESC 
                LIMIT $perPage OFFSET $offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Count for pagination
        $countStmt = $pdo->prepare("SELECT count(*) FROM audit_logs $where");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $this->view('admin/audit_logs', ['logs' => $logs, 'page' => $page, 'total' => $total, 'perPage' => $perPage]);
    }
}