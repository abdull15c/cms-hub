<?php
namespace Src\Controllers;
use Src\Services\QueueService;
use Src\Services\SessionService;

class WorkerController extends Controller {
    
    public function run() {
        // Security: Only Admins can trigger this via web
        // But for "Autopilot" via AJAX, we check session
        SessionService::start();
        
        header('Content-Type: application/json');
        
        if (SessionService::get('role') !== 'admin') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'msg' => 'Auth required']);
            exit;
        }

        try {
            // Process one job
            $worked = QueueService::processNext();
            echo json_encode(['status' => 'ok', 'worked' => $worked]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }
}
