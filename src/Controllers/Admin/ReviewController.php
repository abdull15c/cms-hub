<?php
namespace Src\Controllers\Admin;
use Config\Database;

class ReviewController extends BaseAdminController {
    public function index() {
        $this->checkAuth();
        $pdo = Database::connect();
        $reviews = $pdo->query("SELECT r.*, u.email, p.title as product_title 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            JOIN products p ON r.product_id = p.id 
            ORDER BY r.is_approved ASC, r.created_at DESC")->fetchAll();
        $this->view('admin/reviews', ['reviews' => $reviews]);
    }

    public function approve($id) {
        $this->checkAuth();
        $this->verifyCsrf();
        Database::connect()->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ?")->execute([$id]);
        $this->redirect('/admin/reviews');
    }

    public function delete($id) {
        $this->checkAuth();
        $this->verifyCsrf();
        Database::connect()->prepare("DELETE FROM reviews WHERE id = ?")->execute([$id]);
        $this->redirect('/admin/reviews');
    }

    public function reply($id) {
        $this->checkAuth();
        $this->verifyCsrf();
        $reply = trim($_POST['reply']);
        Database::connect()->prepare("UPDATE reviews SET reply = ?, is_approved = 1 WHERE id = ?")->execute([$reply, $id]);
        $this->redirect('/admin/reviews');
    }
}