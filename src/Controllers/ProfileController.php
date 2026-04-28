<?php
namespace Src\Controllers;
use Config\Database;
use Src\Services\AuditService;
use Src\Services\SessionService;
use Src\Services\Security;

class ProfileController extends Controller {
    
    public function index() {
        $this->requireAuth();
        $userId = $this->currentUserId();
        $pdo = Database::connect();
        
        $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $user = $stmtUser->fetch();
        if(!$user) { SessionService::logout(); $this->redirect('/login'); }
        
        // Purchases
        $sql = "SELECT p.title, p.file_path, p.id as product_id, p.price, 
                (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_main DESC LIMIT 1) as thumbnail,
                l.license_key, l.domain, t.created_at
                FROM transactions t
                JOIN products p ON t.product_id = p.id
                LEFT JOIN licenses l ON (l.user_id = t.user_id AND l.product_id = p.id)
                WHERE t.user_id = ? AND t.status = 'paid'
                ORDER BY t.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $purchases = $stmt->fetchAll();

        // Stats
        $stmtSpent = $pdo->prepare("SELECT sum(amount) FROM transactions WHERE user_id = ? AND status = 'paid'");
        $stmtSpent->execute([$userId]);
        $spent = $stmtSpent->fetchColumn() ?: 0;
        $stmtTickets = $pdo->prepare("SELECT count(*) FROM tickets WHERE user_id = ? AND status != 'closed'");
        $stmtTickets->execute([$userId]);
        $tickets = $stmtTickets->fetchColumn();

        $this->view('auth/profile', [
            'user' => $user,
            'purchases' => $purchases, 
            'stats' => ['spent' => $spent, 'tickets' => $tickets, 'count' => count($purchases)],
            'is2faEnabled' => !empty($user['totp_secret']),
            'isVerified' => !empty($user['email_verified_at'])
        ]);
    }

    public function edit() {
        $this->requireAuth();
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$this->currentUserId()]);
        
        // Load default avatars
        $defaults = glob(ROOT_PATH . '/public/uploads/avatars/defaults/*.png');
        $defaults = array_map('basename', $defaults);
        
        $this->view('auth/settings', ['user' => $stmt->fetch(), 'default_avatars' => $defaults]);
    }

    public function apiPage() {
        $this->requireAuth();
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT api_token FROM users WHERE id = ?");
        $stmt->execute([$this->currentUserId()]);
        $this->view('auth/api', ['user' => $stmt->fetch()]);
    }

    public function update() {
        $this->requireAuth();
        $this->verifyCsrf();
        $uid = $this->currentUserId();
        $pdo = Database::connect();
        $stmtUser = $pdo->prepare("SELECT password, oauth_provider FROM users WHERE id = ?");
        $stmtUser->execute([$uid]);
        $currentUser = $stmtUser->fetch();
        if (!$currentUser) {
            SessionService::logout();
            $this->redirect('/login');
        }
        
        $name = strip_tags(trim($_POST['name']));
        if($name) $pdo->prepare("UPDATE users SET name = ? WHERE id = ?")->execute([$name, $uid]);

        if (!empty($_POST['password'])) {
            $newPassword = (string)$_POST['password'];
            if (strlen($newPassword) < 8) {
                $this->redirect('/profile/settings', 'Password must be 8+ chars.');
            }

            $requiresCurrentPassword = empty($currentUser['oauth_provider']);
            $currentPassword = (string)($_POST['current_password'] ?? '');
            if ($requiresCurrentPassword && !password_verify($currentPassword, (string)$currentUser['password'])) {
                $this->redirect('/profile/settings', 'Current password is incorrect.');
            }

            $hash = password_hash($newPassword, PASSWORD_ARGON2ID);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $uid]);
            AuditService::log('user', 'password_change', $uid);
        }

        // 1. Check Default Avatar Selection
        if (!empty($_POST['default_avatar'])) {
            $def = basename($_POST['default_avatar']);
            // Verify it exists
            if (file_exists(ROOT_PATH . '/public/uploads/avatars/defaults/' . $def)) {
                $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute(['defaults/' . $def, $uid]);
            }
        }

        // 2. Check File Upload (Overrides default selection)
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $tmpPath = (string)($_FILES['avatar']['tmp_name'] ?? '');
            $size = (int)($_FILES['avatar']['size'] ?? 0);
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $this->redirect('/profile/settings', 'Avatar must be JPG, PNG or WebP.');
            }
            if ($tmpPath === '' || !is_uploaded_file($tmpPath) || $size <= 0 || $size > 3 * 1024 * 1024) {
                $this->redirect('/profile/settings', 'Avatar upload is invalid or too large.');
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? (string)finfo_file($finfo, $tmpPath) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || @getimagesize($tmpPath) === false) {
                $this->redirect('/profile/settings', 'Uploaded avatar must be a valid image.');
            }

            $filename = 'u' . $uid . '_' . time() . '_' . Security::generateToken(6) . '.' . $ext;
            $path = ROOT_PATH . '/public/uploads/avatars/' . $filename;
            if(!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
            if (!move_uploaded_file($tmpPath, $path)) {
                $this->redirect('/profile/settings', 'Failed to save avatar.');
            }
            $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$filename, $uid]);
        }
        $this->redirect('/profile', null, 'Profile updated');
    }
}
