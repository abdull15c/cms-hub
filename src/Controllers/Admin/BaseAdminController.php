<?php
namespace Src\Controllers\Admin;
use Src\Controllers\Controller;

class BaseAdminController extends Controller {
    protected function checkAuth() {
        $this->requireAdmin();
    }
}
