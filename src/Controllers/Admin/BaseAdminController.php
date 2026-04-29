<?php
namespace Src\Controllers\Admin;
use Src\Controllers\Controller;

class BaseAdminController extends Controller {
    public function __construct() {
        $this->requireAdmin();
    }

    protected function checkAuth() {
        $this->requireAdmin();
    }
}
