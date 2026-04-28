<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$authController = $root . '/src/Controllers/AuthController.php';
$code = file_get_contents($authController);

if ($code === false) {
    fwrite(STDERR, "[SMOKE-FAIL] Cannot read AuthController.php\n");
    exit(1);
}

$checks = [
    "verifyAndConsumeState(\$state)" => 'OAuth state verification',
    "unset(\$_SESSION['oauth_state'])" => 'OAuth state one-time cleanup',
];

foreach ($checks as $needle => $name) {
    if (strpos($code, $needle) === false) {
        fwrite(STDERR, "[SMOKE-FAIL] Missing OAuth state control: {$name}\n");
        exit(1);
    }
}

echo "[SMOKE-OK] OAuth state controls are present.\n";
exit(0);
