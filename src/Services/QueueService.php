<?php
namespace Src\Services;
use Config\Database;

class QueueService {
    private const MAX_ATTEMPTS = 3;
    private const BASE_BACKOFF_SECONDS = 30;

    public static function push($handler, $data) {
        if (!is_array($data)) {
            $data = ['payload' => $data];
        }
        if (!isset($data['__attempt'])) {
            $data['__attempt'] = 0;
        }
        $payload = json_encode($data);
        Database::connect()->prepare("INSERT INTO jobs (handler, payload) VALUES (?, ?)")
            ->execute([$handler, $payload]);
    }
    
    public static function processNext() {
        $pdo = Database::connect();
        // Lock one job
        $pdo->beginTransaction();
        $stmt = $pdo->query("SELECT * FROM jobs WHERE status IN ('pending','retry') AND created_at <= NOW() ORDER BY id ASC LIMIT 1 FOR UPDATE");
        $job = $stmt->fetch();
        
        if (!$job) {
            $pdo->commit();
            return false;
        }
        
        // Mark processing
        $pdo->prepare("UPDATE jobs SET status = 'processing' WHERE id = ?")->execute([$job['id']]);
        $pdo->commit();
        
        try {
            $handler = $job['handler'];
            $payload = json_decode($job['payload'], true) ?: [];
            // Dynamic call: Src\Jobs\SendEmail::handle($data)
            if (class_exists($handler)) {
                $instance = new $handler();
                $instance->handle($payload);
                
                $pdo->prepare("UPDATE jobs SET status = 'completed' WHERE id = ?")->execute([$job['id']]);
                echo "[OK] Job #{$job['id']} processed.\n";
            } else {
                throw new \Exception("Handler $handler not found");
            }
        } catch (\Exception $e) {
            $payload = json_decode($job['payload'], true) ?: [];
            $attempt = (int)($payload['__attempt'] ?? 0) + 1;
            $payload['__attempt'] = $attempt;
            $errorMessage = mb_substr($e->getMessage(), 0, 1000);
            if ($attempt < self::MAX_ATTEMPTS) {
                $delay = self::BASE_BACKOFF_SECONDS * (2 ** ($attempt - 1));
                $pdo->prepare("UPDATE jobs SET status = 'retry', payload = ?, created_at = DATE_ADD(NOW(), INTERVAL ? SECOND), last_error = ? WHERE id = ?")
                    ->execute([json_encode($payload), $delay, $errorMessage, $job['id']]);
                Logger::warning("Queue job retry scheduled", ['job_id' => (int)$job['id'], 'attempt' => $attempt, 'delay_seconds' => $delay, 'error' => $e->getMessage()]);
            } else {
                $pdo->prepare("UPDATE jobs SET status = 'dead', payload = ?, last_error = ? WHERE id = ?")
                    ->execute([json_encode($payload), $errorMessage, $job['id']]);
                Logger::error("Queue job moved to DLQ", ['job_id' => (int)$job['id'], 'attempt' => $attempt, 'error' => $e->getMessage()]);
            }
        }
        return true;
    }
}