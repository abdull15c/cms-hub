<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/Core/Env.php';

\Src\Core\Env::load();

$host = (string)\Src\Core\Env::get('DB_HOST', 'localhost');
$dbName = (string)\Src\Core\Env::get('DB_NAME', 'dle_market_db');
$user = (string)\Src\Core\Env::get('DB_USER', 'root');
$pass = (string)\Src\Core\Env::get('DB_PASS', '');

$pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$stmt = $pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote($dbName));
$exists = (bool)$stmt->fetchColumn();
if (!$exists) {
    $pdo->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "[RECOVER] Created missing database {$dbName}\n";
}

$pdo->exec("USE `{$dbName}`");

$installFile = dirname(__DIR__) . '/public/install/process.php';
$source = file_get_contents($installFile);
if ($source === false) {
    throw new RuntimeException('Cannot read installer process file.');
}

if (!preg_match('/\\$sql\\s*=\\s*"(.*?)";\\s*\\$pdo->exec\\(\\$sql\\);/s', $source, $m)) {
    throw new RuntimeException('Failed to extract schema SQL from installer.');
}

$schemaSql = $m[1];
$pdo->exec($schemaSql);
echo "[RECOVER] Core schema applied.\n";

$adminEmail = trim((string)\Src\Core\Env::get('RECOVER_ADMIN_EMAIL', 'admin@market.test'));
$adminPass = trim((string)\Src\Core\Env::get('RECOVER_ADMIN_PASSWORD', 'Admin123!'));
if ($adminEmail === '') {
    $adminEmail = 'admin@market.test';
}
if ($adminPass === '') {
    $adminPass = 'Admin123!';
}
$checkAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
if ((int)$checkAdmin === 0) {
    $hash = password_hash($adminPass, PASSWORD_ARGON2ID);
    $ins = $pdo->prepare("INSERT INTO users (email, password, role, name, email_verified_at) VALUES (?, ?, 'admin', 'Administrator', NOW())");
    $ins->execute([$adminEmail, $hash]);
    echo "[RECOVER] Default admin created: {$adminEmail}\n";
}

echo "[RECOVER] Done.\n";
