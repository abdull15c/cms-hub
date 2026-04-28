<?php
namespace Src\Controllers\Admin;
use Src\Services\HealthService;
use Src\Services\Gate;

class PulseController extends BaseAdminController {
    public function index() {
        $this->checkAuth();
        // RBAC: Only Admin/Manager usually check health
        if (method_exists(Gate::class, 'authorize')) {
            Gate::authorize('dashboard.view'); 
        }

        $db = HealthService::checkDatabase();
        $disk = HealthService::checkDisk();
        $errors = HealthService::getErrorCount();
        $cron = HealthService::checkCron();
        $worker = HealthService::checkWorker();
        $queue = HealthService::checkQueue();
        
        $serverInfo = [
            'php' => phpversion(),
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'memory_limit' => ini_get('memory_limit')
        ];

        $this->view('admin/pulse', compact('db', 'disk', 'errors', 'cron', 'worker', 'queue', 'serverInfo'));
    }
}
