<?php
namespace Src\Controllers;
use Config\Database;
use Src\Services\SessionService;

class ReviewController extends Controller {
    public function store($id) {
        $this->requireAuth();
        $this->verifyCsrf();
        $userId = $this->currentUserId();
        $rating = intval($_POST['rating']);
        $comment = substr($_POST['comment'], 0, 500); 
        $pdo = Database::connect();
        $lic = $pdo->prepare("SELECT id FROM licenses WHERE user_id = ? AND product_id = ?"); $lic->execute([$userId, $id]);
        if (!$lic->fetch()) $this->redirect('/product/'.$id, 'You must buy this product first.');
        $dup = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?"); $dup->execute([$userId, $id]);
        if ($dup->fetch()) $this->redirect('/product/'.$id, 'Already reviewed.');
        $pdo->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)")->execute([$userId, $id, $rating, $comment]);
        $this->redirect('/product/' . $id, null, 'Review submitted for approval.');
    }

    public function delete($id) {
        $this->requireAdmin();
        $this->verifyCsrf(); // Ensure POST
        
        $pdo = Database::connect();
        // Get product ID for redirect before delete
        $stmt = $pdo->prepare("SELECT product_id FROM reviews WHERE id = ?");
        $stmt->execute([$id]);
        $review = $stmt->fetch();
        
        $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$id]);
        
        // FORTRESS: Redirect to Product or Dashboard
        if ($review) $this->redirect('/product/' . $review['product_id']);
        else $this->redirect('/admin/dashboard');
    }
}
