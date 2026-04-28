<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/src');
define('CONFIG_PATH', ROOT_PATH . '/config');

spl_autoload_register(function ($class) {
    $prefixes = [
        'Src\\' => APP_PATH . '/',
        'Config\\' => CONFIG_PATH . '/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

require_once ROOT_PATH . '/src/Core/Env.php';
\Src\Core\Env::load();

use Src\Core\Env;

function scalar(\PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function tz(): \DateTimeZone
{
    $raw = trim((string)Env::get('APP_TIMEZONE', 'UTC'));
    if ($raw === '') {
        $raw = 'UTC';
    }
    try {
        return new \DateTimeZone($raw);
    } catch (\Throwable $e) {
        return new \DateTimeZone('UTC');
    }
}

function todayRangeUtc(): array
{
    $timezone = tz();
    $now = new \DateTimeImmutable('now', $timezone);
    $start = $now->setTime(0, 0, 0);
    $end = $start->modify('+1 day');
    return [
        $start->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
        $end->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
    ];
}

try {
    $pdo = \Config\Database::connect();
    [$dayStart, $dayEnd] = todayRangeUtc();

    $usersTotal = scalar($pdo, "SELECT COUNT(*) FROM users");
    $analyticsTotal = scalar($pdo, "SELECT COUNT(*) FROM analytics_registrations");
    $missingInAnalytics = scalar($pdo, "SELECT COUNT(*) FROM users u LEFT JOIN analytics_registrations ar ON ar.user_id = u.id WHERE ar.user_id IS NULL");
    $orphanAnalytics = scalar($pdo, "SELECT COUNT(*) FROM analytics_registrations ar LEFT JOIN users u ON u.id = ar.user_id WHERE u.id IS NULL");

    $usersToday = scalar($pdo, "SELECT COUNT(*) FROM users WHERE created_at >= ? AND created_at < ?", [$dayStart, $dayEnd]);
    $analyticsToday = scalar($pdo, "SELECT COUNT(*) FROM analytics_registrations WHERE created_at >= ? AND created_at < ?", [$dayStart, $dayEnd]);
    $usersTodayLocal = scalar($pdo, "SELECT COUNT(*) FROM users WHERE created_at >= ? AND created_at < ? AND (oauth_provider IS NULL OR oauth_provider = '')", [$dayStart, $dayEnd]);
    $usersTodaySocial = scalar($pdo, "SELECT COUNT(*) FROM users WHERE created_at >= ? AND created_at < ? AND (oauth_provider IS NOT NULL AND oauth_provider <> '')", [$dayStart, $dayEnd]);
    $usersTodayGoogle = scalar($pdo, "SELECT COUNT(*) FROM users WHERE created_at >= ? AND created_at < ? AND oauth_provider = 'google'", [$dayStart, $dayEnd]);
    $usersTodayGithub = scalar($pdo, "SELECT COUNT(*) FROM users WHERE created_at >= ? AND created_at < ? AND oauth_provider = 'github'", [$dayStart, $dayEnd]);

    $analyticsTodayLocal = scalar($pdo, "SELECT COUNT(*) FROM analytics_registrations WHERE created_at >= ? AND created_at < ? AND source = 'local'", [$dayStart, $dayEnd]);
    $analyticsTodaySocial = scalar($pdo, "SELECT COUNT(*) FROM analytics_registrations WHERE created_at >= ? AND created_at < ? AND source = 'social'", [$dayStart, $dayEnd]);
    $analyticsTodayGoogle = scalar($pdo, "SELECT COUNT(*) FROM analytics_registrations WHERE created_at >= ? AND created_at < ? AND provider = 'google'", [$dayStart, $dayEnd]);
    $analyticsTodayGithub = scalar($pdo, "SELECT COUNT(*) FROM analytics_registrations WHERE created_at >= ? AND created_at < ? AND provider = 'github'", [$dayStart, $dayEnd]);

    $sampleMissing = $pdo->query(
        "SELECT u.id, u.email, u.created_at
         FROM users u
         LEFT JOIN analytics_registrations ar ON ar.user_id = u.id
         WHERE ar.user_id IS NULL
         ORDER BY u.id ASC
         LIMIT 10"
    )->fetchAll() ?: [];

    $sampleOrphan = $pdo->query(
        "SELECT ar.user_id, ar.source, ar.provider, ar.created_at
         FROM analytics_registrations ar
         LEFT JOIN users u ON u.id = ar.user_id
         WHERE u.id IS NULL
         ORDER BY ar.user_id ASC
         LIMIT 10"
    )->fetchAll() ?: [];

    $lines = [
        '=== Analytics Registration Accuracy Check ===',
        'Timezone: ' . tz()->getName(),
        'Today UTC range: [' . $dayStart . ', ' . $dayEnd . ')',
        '',
        '[TOTAL]',
        'users=' . $usersTotal . ' analytics_registrations=' . $analyticsTotal . ' diff=' . ($usersTotal - $analyticsTotal),
        '',
        '[INTEGRITY]',
        'missing_in_analytics=' . $missingInAnalytics,
        'orphan_in_analytics=' . $orphanAnalytics,
        '',
        '[TODAY]',
        'users=' . $usersToday . ' analytics=' . $analyticsToday . ' diff=' . ($usersToday - $analyticsToday),
        'users_local=' . $usersTodayLocal . ' analytics_local=' . $analyticsTodayLocal . ' diff=' . ($usersTodayLocal - $analyticsTodayLocal),
        'users_social=' . $usersTodaySocial . ' analytics_social=' . $analyticsTodaySocial . ' diff=' . ($usersTodaySocial - $analyticsTodaySocial),
        'users_google=' . $usersTodayGoogle . ' analytics_google=' . $analyticsTodayGoogle . ' diff=' . ($usersTodayGoogle - $analyticsTodayGoogle),
        'users_github=' . $usersTodayGithub . ' analytics_github=' . $analyticsTodayGithub . ' diff=' . ($usersTodayGithub - $analyticsTodayGithub),
    ];

    fwrite(STDOUT, implode(PHP_EOL, $lines) . PHP_EOL);

    if (!empty($sampleMissing)) {
        fwrite(STDOUT, PHP_EOL . '[SAMPLE missing in analytics]' . PHP_EOL);
        foreach ($sampleMissing as $row) {
            fwrite(STDOUT, 'user_id=' . (int)$row['id'] . ' email=' . (string)$row['email'] . ' created_at=' . (string)$row['created_at'] . PHP_EOL);
        }
    }

    if (!empty($sampleOrphan)) {
        fwrite(STDOUT, PHP_EOL . '[SAMPLE orphan in analytics]' . PHP_EOL);
        foreach ($sampleOrphan as $row) {
            fwrite(STDOUT, 'user_id=' . (int)$row['user_id'] . ' source=' . (string)$row['source'] . ' provider=' . (string)($row['provider'] ?? '') . ' created_at=' . (string)$row['created_at'] . PHP_EOL);
        }
    }

    $hasFailure = $missingInAnalytics > 0 || $orphanAnalytics > 0 || $usersToday !== $analyticsToday;
    exit($hasFailure ? 1 : 0);
} catch (\Throwable $e) {
    fwrite(STDERR, '[FAIL] Analytics registration check failed: ' . $e->getMessage() . PHP_EOL);
    exit(2);
}

