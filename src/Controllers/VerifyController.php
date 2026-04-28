<?php
namespace Src\Controllers;
use Config\Database;
use Src\Services\AuditService;
use Src\Services\AuthService;

class VerifyController extends Controller {
    public function verify($token) {
        if (!$token) $this->redirect('/');
        
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE verify_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $pdo->prepare("UPDATE users SET email_verified_at = NOW(), verify_token = NULL WHERE id = ?")->execute([$user['id']]);
            AuditService::log('user', 'verified', $user['id']);
            
            // Auto login if not logged in? Or just redirect to login
            $this->redirect('/login', null, 'Email Verified! Please login.');
        } else {
            $this->redirect('/', 'Invalid or expired verification link.');
        }
    }
    
    public function resend() {
          $this->requireAuth();
          $this->verifyCsrf();

          $auth = $this->service('auth', function () {
             return new AuthService();
         });
         $sent = $auth->resendVerification($this->currentUserId());
         $this->redirect('/profile', $sent ? null : 'Email is already verified or unavailable.', $sent ? 'Verification email sent.' : null);
    }
}
