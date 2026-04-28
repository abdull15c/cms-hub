<?php
namespace Src\Controllers\Admin;
use Config\Database;
use Src\Services\AnalyticsService;
use Src\Services\MoneyService;

class DashboardController extends BaseAdminController {
    public function index() { 
        $this->checkAuth(); 
        $pdo = Database::connect(); 

        /** @var AnalyticsService $analytics */
        $analytics = $this->service('analytics', function () {
            return new AnalyticsService();
        });
        $summary = $analytics->getDashboardSummary(7);
        
        $products = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 5")->fetchAll(); 
        $sales = $pdo->query("SELECT t.*, u.email FROM transactions t LEFT JOIN users u ON t.user_id=u.id WHERE t.status='paid' ORDER BY t.created_at DESC LIMIT 5")->fetchAll(); 
        $revenueRaw = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE status = 'paid'")->fetchColumn();
        $revenueTotal = MoneyService::fromCents(MoneyService::toCents($revenueRaw));
        
        $this->view('admin/dashboard', [
            'products' => $products, 
            'sales' => $sales,
            'prod_count' => $summary['products_total'],
            'registered_count' => $summary['registered_users_total'],
            'visitors_7d' => $summary['unique_visitors_period'],
            'page_views_7d' => $summary['page_views_period'],
            'registrations_today' => $summary['registrations_today'],
            'registrations_today_local' => $summary['registrations_today_local'] ?? 0,
            'registrations_today_social' => $summary['registrations_today_social'] ?? 0,
            'registrations_today_google' => $summary['registrations_today_google'] ?? 0,
            'registrations_today_github' => $summary['registrations_today_github'] ?? 0,
            'logins_today' => $summary['logins_today'],
            'top_page' => $summary['top_page'],
            'chart' => $summary['chart'],
            'revenue_total' => $revenueTotal,
        ]); 
    }
}
