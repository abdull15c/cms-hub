<?php
// CRON HANDLER (For Shared Hosting)
// Run this every minute: * * * * * php /path/to/public/cron.php

require_once dirname(__DIR__) . '/src/app_bootstrap.php';

// 3. Security Check (CLI or Token)
$required = \Src\Core\Env::get('CRON_TOKEN');
$isCli = php_sapi_name() === 'cli';
$authHeader = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? '');
$bearer = preg_match('/Bearer\s+(\S+)/i', $authHeader, $m) ? $m[1] : '';
$provided = (string)($_POST['token'] ?? ($_SERVER['HTTP_X_CRON_TOKEN'] ?? $bearer));
$tokenMatch = $required !== '' && $provided !== '' && hash_equals($required, $provided);

if (!$isCli && !$tokenMatch) {
    http_response_code(403); 
    exit('Access Denied');
}

// 4. PROCESS JOBS
echo "[CRON] Started at " . date('Y-m-d H:i:s') . "\n";

// Create logs dir if missing
$logDir = ROOT_PATH . '/storage/logs';
if(!is_dir($logDir)) mkdir($logDir, 0755, true);

try {
    // Attempt to process up to 10 jobs per run to prevent timeouts
    $jobsProcessed = 0;
    $limit = 10; 
    
    for ($i = 0; $i < $limit; $i++) {
        $worked = \Src\Services\QueueService::processNext();
        if ($worked) {
            $jobsProcessed++;
        } else {
            // Queue empty
            break;
        }
    }
    
    $pdo = \Config\Database::connect();
    $deadCount = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'dead'")->fetchColumn();
    $retryCount = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'retry'")->fetchColumn();
    file_put_contents($logDir . '/cron-heartbeat', date('Y-m-d H:i:s'));
    file_put_contents($logDir . '/heartbeat', date('Y-m-d H:i:s'));
    echo "[CRON] Processed $jobsProcessed jobs. retry=$retryCount dead=$deadCount Done.\n";

} catch (Throwable $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    error_log("[CRON ERROR] " . $e->getMessage(), 3, $logDir . '/error.log');
}
