<?php
namespace Src\Controllers\Admin;
use Src\Services\AuditService;

class UpdaterController extends BaseAdminController {
    
    public function index() {
        $this->checkAuth();
        // Visual warning
        $this->view('admin/updater', ['disabled' => true]);
    }

    public function run() {
        $this->checkAuth();
        $this->verifyCsrf();
        
        // SECURITY LOCK: Prevent RCE via Web
        // Updates should be done via GIT or FTP/SSH only in production
        AuditService::log('security', 'attempted_web_update_blocked');
        $this->redirect('/admin/update', 'Web Updater is disabled for security reasons. Please update via Git/FTP.');
    }
}