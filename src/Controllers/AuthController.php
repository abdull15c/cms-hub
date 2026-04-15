<?php
namespace Src\Controllers;

use Src\Services\AuthService;
use Src\Services\SessionService;
use Src\Services\TotpService;
use Src\Services\SocialAuthService;
use Config\Database;

class AuthController extends Controller {
    
    private $auth;

    public function __construct() {
        $this->auth = $this->service('auth', function () {
            return new AuthService();
        });
    }

    // --- LOGIN ---
    public function loginForm() { 
        if (SessionService::get('2fa_pending_uid')) { 
            $this->view('auth/2fa_verify'); 
            return; 
        }
        $this->view('auth/login'); 
    }

    public function login() {
        if (isset($_POST['totp_code'])) { $this->verify2faLogin(); return; }
        
        $this->verifyCsrf();
        $email = $_POST['email']; 
        $pass = $_POST['password'];

        try {
            $user = $this->auth->attempt($email, $pass);
            
            if (!$user) {
                $this->redirect('/login', "Invalid credentials.");
            }

            // 2FA Check
            if (!empty($user['totp_secret'])) {
                SessionService::set('2fa_pending_uid', $user['id']);
                SessionService::set('2fa_pending_email', $user['email']);
                SessionService::set('2fa_pending_role', $user['role']);
                $this->redirect('/login'); // Will render 2fa_verify
                return;
            }

            // Success
            $this->auth->loginUser($user);
            $this->redirect($user['role'] === 'admin' ? '/admin/dashboard' : '/profile', null, "Welcome back!");

        } catch (\Exception $e) {
            $this->redirect('/login', $e->getMessage());
        }
    }

    private function verify2faLogin() {
        $this->verifyCsrf();
        $code = $_POST['totp_code'];
        $uid = SessionService::get('2fa_pending_uid');

        if ($this->auth->verify2FA($uid, $code)) {
            $user = [
                'id' => $uid,
                'email' => SessionService::get('2fa_pending_email'),
                'role' => SessionService::get('2fa_pending_role')
            ];
            
            // Cleanup Temp Session
            SessionService::forget('2fa_pending_uid');
            SessionService::forget('2fa_pending_email');
            SessionService::forget('2fa_pending_role');
            
            $this->auth->loginUser($user);
            $this->redirect($user['role'] === 'admin' ? '/admin/dashboard' : '/profile', null, "2FA Verified.");
        } else {
            $this->redirect('/login', "Invalid 2FA Code.");
        }
    }

    // --- REGISTER ---
    public function registerForm() { $this->view('auth/register'); }
    
    public function register() {
        $this->verifyCsrf();
        $email = trim($_POST['email']); 
        $pass = $_POST['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect('/register', "Invalid email format.");
        }
        if(strlen($pass) < 8) {
            $this->redirect('/register', "Password must be 8+ chars.");
        }

        try {
            $user = $this->auth->register($email, $pass);
            $this->auth->loginUser($user); // Auto login
            $this->redirect('/profile', null, "Account created! Please verify email.");
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'Duplicate') !== false) $msg = "Email already registered.";
            $this->redirect('/register', $msg);
        }
    }

    public function logout() {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/');
        }
        SessionService::logout();
        $this->redirect('/', null, "You have logged out."); 
    }
    
    // --- 2FA SETTINGS ---
    public function setup2fa() {
        if (!SessionService::get('user_id')) $this->redirect('/login');
        $secret = (new TotpService())->generateSecret();
        SessionService::set('2fa_new_secret', $secret);
        $qrUrl = (new TotpService())->getQrUrl(SessionService::get('user_email'), $secret);
        $this->view('auth/2fa_setup', ['secret' => $secret, 'qrUrl' => $qrUrl]);
    }

    public function enable2fa() {
        $this->verifyCsrf();
        if (!SessionService::get('user_id')) $this->redirect('/login');
        $code = $_POST['code'];
        $secret = SessionService::get('2fa_new_secret');
        if ((new TotpService())->verify($secret, $code)) {
            Database::connect()->prepare("UPDATE users SET totp_secret = ? WHERE id = ?")->execute([$secret, SessionService::get('user_id')]);
            SessionService::forget('2fa_new_secret');
            $this->redirect('/profile', null, '2FA Enabled Successfully');
        } else {
            $this->redirect('/auth/2fa/setup', 'Invalid Code');
        }
    }
    
    public function disable2fa() {
        $this->verifyCsrf();
        if (!SessionService::get('user_id')) $this->redirect('/login');
        Database::connect()->prepare("UPDATE users SET totp_secret = NULL WHERE id = ?")->execute([SessionService::get('user_id')]);
        $this->redirect('/profile', null, '2FA Disabled');
    }

    // --- API TOKEN (WAS MISSING) ---
    public function generateToken() {
        if (!SessionService::get('user_id')) $this->redirect('/login');
        $this->verifyCsrf();
        
        $token = bin2hex(random_bytes(32));
        $uid = SessionService::get('user_id');
        
        Database::connect()->prepare("UPDATE users SET api_token = ? WHERE id = ?")->execute([$token, $uid]);
        $this->redirect('/profile/api', null, 'New API Token Generated');
    }

    // --- SOCIAL AUTH (WAS MISSING) ---
    public function loginGoogle() {
        $service = $this->service('social_auth', function () {
            return new SocialAuthService();
        });
        header("Location: " . $service->getAuthUrl('google'));
        exit;
    }

    public function loginGithub() {
        $service = $this->service('social_auth', function () {
            return new SocialAuthService();
        });
        header("Location: " . $service->getAuthUrl('github'));
        exit;
    }

    public function callback($provider) {
        $service = $this->service('social_auth', function () {
            return new SocialAuthService();
        });
        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        if (!$code) $this->redirect('/login', 'Auth Failed');
        // unset($_SESSION['oauth_state']) is handled inside SocialAuthService::verifyAndConsumeState($state)
        if (!$service->verifyAndConsumeState($state)) {
            $this->redirect('/login', 'Invalid OAuth state');
        }

        $data = $service->handleCallback($provider, $code);
        if (!$data) $this->redirect('/login', 'Provider Error');

        try {
            $user = $this->auth->findOrCreateSocialUser($provider, $data);
            $this->auth->loginUser($user);
            $this->redirect('/profile', null, ucfirst($provider) . ' login complete.');
        } catch (\Exception $e) {
            $this->redirect('/login', $e->getMessage());
        }
    }
    
    public function forgotForm() { $this->view('auth/forgot'); }
    public function sendResetLink() {
        $this->verifyCsrf();
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->auth->createPasswordReset($email);
        }
        $this->redirect('/forgot', null, 'If the email exists, a reset link has been sent.');
    }
    public function resetForm($token) {
        if (!$this->auth->validateResetToken((string)$token)) {
            $this->redirect('/forgot', 'Reset link is invalid or expired.');
        }
        $this->view('auth/reset', ['token'=>$token]);
    }
    public function resetPassword() {
        $this->verifyCsrf();
        $token = (string)($_POST['token'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        if (strlen($password) < 8) {
            $this->redirect('/reset/' . $token, 'Password must be 8+ chars.');
        }
        if (!$this->auth->resetPassword($token, $password)) {
            $this->redirect('/forgot', 'Reset link is invalid or expired.');
        }
        $this->redirect('/login', null, 'Password changed. You can now sign in.');
    }
}
