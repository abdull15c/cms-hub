<?php
namespace Src\Controllers;
use Config\Database;

class WishlistController extends Controller {
    private function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
    
    public function toggle($productId) { $this->verifyCsrf();
        if (!$this->currentUserId()) $this->json(['error' => 'Login required'], 401);
        
        $uid = $this->currentUserId();
        $pdo = Database::connect();
        
        $check = $pdo->prepare("SELECT id FROM wishlists WHERE user_id=? AND product_id=?");
        $check->execute([$uid, $productId]);
        
        if($check->fetch()) {
            $pdo->prepare("DELETE FROM wishlists WHERE user_id=? AND product_id=?")->execute([$uid, $productId]);
            $action = 'removed';
        } else {
            $pdo->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?,?)")->execute([$uid, $productId]);
            $action = 'added';
        }
        
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => $action]);
            exit;
        }
        
        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    public function index() {
        $this->requireAuth();
        $uid = $this->currentUserId();
        
        $sql = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_main DESC LIMIT 1) as thumbnail 
                FROM wishlists w 
                JOIN products p ON w.product_id = p.id 
                WHERE w.user_id = ? ORDER BY w.created_at DESC";
        
        $products = Database::connect()->prepare($sql);
        $products->execute([$uid]);
        
        $this->view('auth/wishlist', ['products' => $products->fetchAll()]);
    }
}
