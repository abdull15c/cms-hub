<?php
// HYBRID QUEUE WORKER (Web & AJAX)
require_once dirname(__DIR__) . '/src/app_bootstrap.php';

use Src\Core\Env;
use Src\Services\SessionService;

SessionService::start();

$isAdmin = (SessionService::get('role') === 'admin');
$cronToken = (string)Env::get('CRON_TOKEN', '');
$providedToken = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$hasToken = ($cronToken !== '' && $providedToken !== '' && hash_equals($cronToken, $providedToken));

if (!$isAdmin && !$hasToken) {
    http_response_code(403);
    exit('Access Denied');
}

// Check for AJAX/Silent request
$isAjax = (isset($_GET['ajax']) && $_GET['ajax'] == 1) || (isset($_SERVER['HTTP_ACCEPT']) && strpos((string)$_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

$respondJson = static function (array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
};

if ($isAjax) {
    try {
        // Run one job
        $worked = \Src\Services\QueueService::processNext();
        $respondJson(['status' => 'ok', 'worked' => $worked]);
    } catch (Exception $e) {
        $respondJson(['status' => 'error', 'msg' => $e->getMessage()], 500);
    }
}

if (!$isAdmin) {
    try {
        $worked = \Src\Services\QueueService::processNext();
        header('Content-Type: text/plain; charset=utf-8');
        echo $worked ? "OK\n" : "IDLE\n";
    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "ERROR\n";
    }
    exit;
}

echo "<h1>Queue Processor</h1>";
echo "<p>Checking for pending jobs...</p>";
echo "<pre>";
$worked = \Src\Services\QueueService::processNext();
if ($worked) {
    echo "\n[SUCCESS] Job Processed successfully!";
} else {
    echo "\n[INFO] No pending jobs found.";
}
echo "</pre>";
echo '<br><a href="'.BASE_URL.'/admin/dashboard">Back to Dashboard</a>';
