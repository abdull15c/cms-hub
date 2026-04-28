<?php
namespace Src\Controllers\Admin;
use Src\Services\Gate;

class LogViewerController extends BaseAdminController {
    public function index() {
        $this->checkAuth();
        Gate::authorize('dashboard.view');

        $logDir = dirname(__DIR__, 3) . '/storage/logs';
        $files = array_values(array_filter(glob($logDir . '/app-*.log') ?: [], 'is_file'));
        rsort($files); // Newest first
        $fileMap = [];
        foreach ($files as $file) {
            $fileMap[basename($file)] = $file;
        }

        $requestedFile = basename((string)($_GET['file'] ?? ''));
        $currentFile = ($requestedFile !== '' && isset($fileMap[$requestedFile]))
            ? $fileMap[$requestedFile]
            : ($files[0] ?? null);
        $logs = [];

        if ($currentFile && file_exists($currentFile)) {
            $content = file_get_contents($currentFile);
            // Parse simplistic log format
            $lines = explode(PHP_EOL, $content);
            foreach($lines as $line) {
                if(trim($line)) $logs[] = $line;
            }
            $logs = array_reverse($logs); // Show newest lines at top
        }

        $this->view('admin/logs', [
            'files' => array_keys($fileMap),
            'current' => $currentFile ? basename($currentFile) : null,
            'logs' => $logs
        ]);
    }

    public function clear() {
        $this->checkAuth();
        $this->verifyCsrf();
        $logDir = dirname(__DIR__, 3) . '/storage/logs';
        $files = glob($logDir . '/*.log');
        foreach($files as $f) unlink($f);
        $this->redirect('/admin/logs', null, 'Logs cleared.');
    }
}
