<?php
namespace Src\Controllers;
use Config\Database;
use Src\Services\AccessManager;
use Src\Services\Logger;
use Src\Services\SessionService;

class DownloadController extends Controller {
    public function download($id) {
        SessionService::start();
        
        // 1. Auth Check
        if (!SessionService::get('user_id')) {
            $this->abort(403, 'Access denied: login required.');
        }

        // 2. Data Fetch
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?"); 
        $stmt->execute([$id]); 
        $product = $stmt->fetch();
        
        if (!$product) { $this->abort(404, 'Product not found'); }

        // 3. Ownership Check (Strict)
        $userId = $this->currentUserId();
        $canDownload = SessionService::get('role') === 'admin';
        if (!$canDownload) {
            $canDownload = AccessManager::canDownload($userId, $id);
        }

        if (!$canDownload) {
            Logger::warning("Unauthorized download attempt: User {$userId} -> Product $id");
            $this->abort(403, 'Access denied: you do not own this product.');
        }

        // 4. File Path Check
        $fileName = basename($product['file_path']);
        if(empty($fileName) || $fileName == '.' || $fileName == '..') $this->abort(400, 'Invalid file path.');
        $downloadName = preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName);

        $file = STORAGE_PATH . '/secure_uploads/' . $fileName;
        
        if (!file_exists($file)) { 
            Logger::error("Missing file on disk: $file");
            $this->abort(404, 'File missing on server. Contact support.'); 
        }

        // 5. Serving the File
        $filesize = filesize($file);
        
        // Clear buffers to prevent corruption
        if (ob_get_level()) ob_end_clean();

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $filesize);

        // PRODUCTION OPTIMIZATION:
        // Uncomment the line below if using Nginx (Fastest)
        // header("X-Accel-Redirect: /protected_files/" . $fileName); exit;
        
        // Uncomment if using Apache with mod_xsendfile
        // header("X-Sendfile: " . $file); exit;

        // Fallback: PHP Stream (Memory Efficient)
        $handle = fopen($file, 'rb');
        while (!feof($handle)) {
            echo fread($handle, 8192); // 8KB chunks
            flush();
        }
        fclose($handle);
        exit;
    }
}
