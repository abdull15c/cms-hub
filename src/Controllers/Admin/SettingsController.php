<?php
namespace Src\Controllers\Admin;
use Config\Database;
use Src\Services\AuditService;
use Src\Services\Gate;
use Src\Services\MailService;
use Src\Services\Security;

class SettingsController extends BaseAdminController {
    
    public function index() { 
        $this->checkAuth(); 
        Gate::authorize('dashboard.view');
        $stmt = Database::connect()->query("SELECT * FROM settings"); 
        $s=[]; foreach($stmt->fetchAll() as $r) $s[$r['setting_key']]=$r['setting_value']; 
        $this->view('admin/settings', ['s'=>$s]); 
    }

    public function save() { 
        $this->checkAuth(); 
        Gate::authorize('dashboard.view');
        $this->verifyCsrf(); 
        $pdo = Database::connect(); 
        
        // Allowed Keys (Full List)
        $allowed = [
            'site_title', 
            'hero_title', 'hero_subtitle', 'hero_primary_cta', 'hero_secondary_cta',
            'footer_text', 'contact_email', 'telegram_url', 'discord_url', 'youtube_url',
            // Payments
            'yoomoney_enabled', 'yoomoney_wallet', 'yoomoney_secret', 
            'freekassa_enabled', 'fk_merchant_id', 'fk_secret_1', 'fk_secret_2', 
            'payeer_enabled', 'payeer_merchant_id', 'payeer_secret_key',
            'crypto_enabled', 'crypto_shop_id', 'crypto_api_key',
            // Social
            'google_client_id', 'google_client_secret', 
            'github_client_id', 'github_client_secret', 
            // AI
            'ai_provider', 'openai_key', 'openai_model', 'gemini_key', 'gemini_model', 
            // System
            'maintenance_mode'
        ];

        // 1. Handle Checkboxes (If unchecked, they are not in $_POST, so we force 0)
        $toggles = ['maintenance_mode', 'yoomoney_enabled', 'freekassa_enabled', 'payeer_enabled', 'crypto_enabled'];
        foreach($toggles as $t) {
            if (!isset($_POST[$t])) $_POST[$t] = '0';
        }

        // 2. Save Text Settings
        foreach($_POST as $k => $v){ 
            if(!in_array($k, $allowed)) continue;
            $v = trim($v);
            $pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$k,$v,$v]); 
        } 

        // 3. Handle File Uploads
        $this->handleUpload('site_logo', ['png', 'jpg', 'jpeg', 'webp'], ['image/png', 'image/jpeg', 'image/webp'], 2 * 1024 * 1024);
        $this->handleUpload('site_favicon', ['ico', 'png'], ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon', 'application/octet-stream'], 1024 * 1024);

        AuditService::log('settings', 'update', null, 'Configuration Updated');
        $this->redirect('/admin/settings', null, 'Settings Saved Successfully.'); 
    }

    private function handleUpload($key, array $exts, array $mimeTypes, int $maxBytes) {
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $exts, true)) {
                $this->redirect('/admin/settings', 'Unsupported branding file type.');
            }

            $tmpPath = (string)($_FILES[$key]['tmp_name'] ?? '');
            $size = (int)($_FILES[$key]['size'] ?? 0);
            if ($tmpPath === '' || !is_uploaded_file($tmpPath) || $size <= 0 || $size > $maxBytes) {
                $this->redirect('/admin/settings', 'Branding upload is invalid or too large.');
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? (string)finfo_file($finfo, $tmpPath) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
            if (!in_array($mime, $mimeTypes, true)) {
                $this->redirect('/admin/settings', 'Branding upload failed file validation.');
            }

            if ($ext !== 'ico' && @getimagesize($tmpPath) === false) {
                $this->redirect('/admin/settings', 'Uploaded branding asset must be a valid image.');
            }

            $name = $key . '_' . time() . '_' . Security::generateToken(6) . '.' . $ext;
            $path = ROOT_PATH . '/public/uploads/branding/' . $name;
            if(!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);

            if (!move_uploaded_file($tmpPath, $path)) {
                $this->redirect('/admin/settings', 'Failed to save uploaded branding file.');
            }

            Database::connect()->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?")
                ->execute([$key, $name, $name]);
        }
    }

    public function testEmail() {
        $this->checkAuth(); $this->verifyCsrf();
        Gate::authorize('dashboard.view');
        $to = $_POST['test_to'];
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) $this->redirect('/admin/settings', 'Invalid Email');
        try {
            $res = (new MailService())->send($to, 'SMTP Test', 'It works!');
            $this->redirect('/admin/settings', null, $res ? 'Sent!' : 'Failed (Check Logs)');
        } catch (\Exception $e) { $this->redirect('/admin/settings', 'Error: '.$e->getMessage()); }
    }
    
    public function messages() {
        $this->checkAuth();
        Gate::authorize('dashboard.view');
        $msgs = Database::connect()->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 50")->fetchAll();
        $this->view('admin/messages', ['messages'=>$msgs]);
    }
}
