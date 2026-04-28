<?php
namespace Src\Services;
use Config\Database;

class HealthService {
    
    public static function checkDatabase() {
        $start = microtime(true);
        try {
            $pdo = Database::connect();
            $pdo->query("SELECT 1");
            $duration = (microtime(true) - $start) * 1000;
            return ['status' => true, 'ms' => round($duration, 2)];
        } catch (\Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    public static function checkDisk() {
        $path = ROOT_PATH; // Check project drive
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;
        return [
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'percent' => round(($used / $total) * 100)
        ];
    }

    public static function getErrorCount() {
        $logFile = STORAGE_PATH . '/logs/app-' . date('Y-m-d') . '.log';
        if (!file_exists($logFile)) return 0;
        
        // Count JSON log entries with ERROR level.
        $lines = file($logFile);
        $count = 0;
        foreach ($lines as $line) {
            if (strpos($line, '"level":"ERROR"') !== false) $count++;
        }
        return $count;
    }

    public static function checkCron() {
        return self::heartbeatStatus(STORAGE_PATH . '/logs/cron-heartbeat', 600, STORAGE_PATH . '/logs/heartbeat');
    }

    public static function checkWorker() {
        return self::heartbeatStatus(STORAGE_PATH . '/logs/worker-heartbeat', 120);
    }

    public static function checkQueue() {
        try {
            $pdo = Database::connect();
            $stmt = $pdo->query("
                SELECT status, COUNT(*) AS cnt
                FROM jobs
                GROUP BY status
            ");

            $counts = [
                'pending' => 0,
                'retry' => 0,
                'processing' => 0,
                'dead' => 0,
                'completed' => 0,
            ];

            foreach ($stmt->fetchAll() as $row) {
                $status = (string)($row['status'] ?? '');
                if (array_key_exists($status, $counts)) {
                    $counts[$status] = (int)$row['cnt'];
                }
            }

            $oldestStmt = $pdo->query("
                SELECT created_at
                FROM jobs
                WHERE status IN ('pending', 'retry', 'processing')
                ORDER BY created_at ASC
                LIMIT 1
            ");
            $oldest = $oldestStmt->fetchColumn();
            $oldestAge = 'Queue empty';

            if (is_string($oldest) && $oldest !== '') {
                $timestamp = strtotime($oldest);
                $oldestAge = $timestamp !== false ? self::humanTiming($timestamp) . ' ago' : 'Unknown';
            }

            return [
                'status' => true,
                'pending' => $counts['pending'],
                'retry' => $counts['retry'],
                'processing' => $counts['processing'],
                'dead' => $counts['dead'],
                'completed' => $counts['completed'],
                'backlog' => $counts['pending'] + $counts['retry'],
                'oldest_age' => $oldestAge,
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'error' => $e->getMessage(),
                'pending' => 0,
                'retry' => 0,
                'processing' => 0,
                'dead' => 0,
                'completed' => 0,
                'backlog' => 0,
                'oldest_age' => 'Unavailable',
            ];
        }
    }

    private static function heartbeatStatus(string $primaryFile, int $freshSeconds, ?string $legacyFile = null): array
    {
        $beatFile = $primaryFile;
        if (!file_exists($beatFile) && $legacyFile !== null && file_exists($legacyFile)) {
            $beatFile = $legacyFile;
        }

        if (!file_exists($beatFile)) {
            return ['status' => false, 'ago' => 'Never run'];
        }

        $lastRun = filemtime($beatFile);
        $diff = time() - $lastRun;

        return [
            'status' => $diff < $freshSeconds,
            'ago' => self::humanTiming($lastRun) . ' ago',
        ];
    }
    
    private static function humanTiming($time) {
        $time = time() - $time;
        $tokens = [31536000=>'year', 2592000=>'month', 604800=>'week', 86400=>'day', 3600=>'hour', 60=>'min', 1=>'sec'];
        foreach ($tokens as $unit => $text) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
        }
        return 'just now';
    }
}
