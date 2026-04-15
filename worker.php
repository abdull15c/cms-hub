<?php
declare(strict_types=1);

require_once __DIR__ . '/src/app_bootstrap.php';

$logDir = ROOT_PATH . '/storage/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$heartbeatFile = trim((string)($_ENV['WORKER_HEARTBEAT_FILE'] ?? ($logDir . '/worker-heartbeat')));
if ($heartbeatFile === '') {
    $heartbeatFile = $logDir . '/worker-heartbeat';
}

$envNumber = static function (string $key, string $default): int {
    $value = trim((string)($_ENV[$key] ?? ''));
    if ($value === '') {
        $value = $default;
    }
    return (int)$value;
};

$sleepSeconds = max(1, $envNumber('WORKER_SLEEP_SECONDS', '3'));
$maxJobs = max(0, $envNumber('WORKER_MAX_JOBS', '500'));
$maxRuntime = max(0, $envNumber('WORKER_MAX_RUNTIME', '3600'));

$startedAt = time();
$processedJobs = 0;

$writeHeartbeat = static function () use ($heartbeatFile): void {
    @file_put_contents($heartbeatFile, date('Y-m-d H:i:s'));
    @touch($heartbeatFile);
};

$writeHeartbeat();

echo "[WORKER] Started at " . date('Y-m-d H:i:s') . " | Site URL: " . BASE_URL . PHP_EOL;
echo "[WORKER] Config: sleep={$sleepSeconds}s, max_jobs=" . ($maxJobs > 0 ? $maxJobs : 'unlimited')
    . ", max_runtime=" . ($maxRuntime > 0 ? ($maxRuntime . 's') : 'unlimited') . PHP_EOL;
echo "[WORKER] Waiting for jobs... (Press Ctrl+C to stop)" . PHP_EOL;

while (true) {
    try {
        $writeHeartbeat();
        $worked = \Src\Services\QueueService::processNext();

        if ($worked) {
            $processedJobs++;
            $writeHeartbeat();

            if ($maxJobs > 0 && $processedJobs >= $maxJobs) {
                echo "[WORKER] Max jobs reached ({$processedJobs}). Exiting for supervised restart." . PHP_EOL;
                break;
            }
        } else {
            if ($maxRuntime > 0 && (time() - $startedAt) >= $maxRuntime) {
                echo "[WORKER] Max runtime reached. Exiting for supervised restart." . PHP_EOL;
                break;
            }

            sleep($sleepSeconds);
        }
    } catch (Throwable $e) {
        $writeHeartbeat();
        echo "[ERROR] " . $e->getMessage() . PHP_EOL;
        sleep(max(3, $sleepSeconds));
    }

    if ($maxRuntime > 0 && (time() - $startedAt) >= $maxRuntime) {
        echo "[WORKER] Max runtime reached. Exiting for supervised restart." . PHP_EOL;
        break;
    }
}

$writeHeartbeat();
echo "[WORKER] Exiting cleanly." . PHP_EOL;
