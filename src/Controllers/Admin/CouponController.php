<?php
namespace Src\Controllers\Admin;
use Config\Database;
use Src\Services\AuditLogger;

class CouponController extends BaseAdminController {
    public function index() {
        $this->checkAuth();
        $pdo = Database::connect();
        $coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
        $this->view('admin/coupons', ['coupons' => $coupons]);
    }

    public function store() {
        $this->checkAuth();
        $this->verifyCsrf();
        $code = strtoupper(trim(preg_replace('/[^A-Za-z0-9]/', '', $_POST['code'])));
        $percent = intval($_POST['percent']);
        if ($percent < 1 || $percent > 100) $this->redirect('/admin/coupons', 'Invalid percent');
        
        $pdo = Database::connect();
        try {
            $pdo->prepare("INSERT INTO coupons (code, discount_percent) VALUES (?, ?)")->execute([$code, $percent]);
            AuditLogger::log('coupon_create', $code);
            $this->redirect('/admin/coupons', null, 'Coupon created!');
        } catch (\Exception $e) {
            $this->redirect('/admin/coupons', 'Code already exists');
        }
    }

    public function delete($id) {
        $this->checkAuth();
        $this->verifyCsrf();
        Database::connect()->prepare("DELETE FROM coupons WHERE id = ?")->execute([$id]);
        AuditLogger::log('coupon_delete', "ID: $id");
        $this->redirect('/admin/coupons', null, 'Coupon deleted.');
    }
}