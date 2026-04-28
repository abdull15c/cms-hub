<?php
// QUEUE WORKER ENTRYPOINT (CLI or signed token only)
require_once dirname(__DIR__) . '/src/app_bootstrap.php';

use Src\Core\Env;
$isCli = (PHP_SAPI === 'cli');
$cronToken = (string)Env::get('CRON_TOKEN', '');
$authHeader = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? '');
$bearer = preg_match('/Bearer\s+(\S+)/i', $authHeader, $m) ? $m[1] : '';
$providedToken = (string)($_POST['token'] ?? ($_SERVER['HTTP_X_CRON_TOKEN'] ?? $bearer));
$hasToken = ($cronToken !== '' && $providedToken !== '' && hash_equals($cronToken, $providedToken));

if (!$isCli && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

if (!$isCli && !$hasToken) {
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

if ($isAjax || !$isCli) {
    try {
        $worked = \Src\Services\QueueService::processNext();
        $respondJson(['status' => 'ok', 'worked' => $worked]);
    } catch (Throwable $e) {
        $respondJson(['status' => 'error', 'msg' => $e->getMessage()], 500);
    }
}

try {
    $worked = \Src\Services\QueueService::processNext();
    header('Content-Type: text/plain; charset=utf-8');
    echo $worked ? "OK\n" : "IDLE\n";
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "ERROR\n";
}
