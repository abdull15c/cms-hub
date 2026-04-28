<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$authController = file_get_contents($root . '/src/Controllers/AuthController.php');
$apiController = file_get_contents($root . '/src/Controllers/ApiController.php');

if ($authController === false || $apiController === false) {
    fwrite(STDERR, "[SMOKE-FAIL] Cannot read auth security target files.\n");
    exit(1);
}

$checks = [
    [$authController, "sha256:' . hash('sha256', \$token)", 'API tokens are stored hashed'],
    [$apiController, "sha256:' . hash('sha256', \$token)", 'Bearer tokens are matched by hash'],
    [$authController, "if (!empty(\$user['totp_secret']))", 'Social login checks TOTP state'],
    [$authController, "verify2FA(\$this->currentUserId(), \$code)", 'Disabling 2FA requires a current TOTP code'],
];

foreach ($checks as [$code, $needle, $name]) {
    if (strpos($code, $needle) === false) {
        fwrite(STDERR, "[SMOKE-FAIL] Auth security invariant missing: {$name}\n");
        exit(1);
    }
}

echo "[SMOKE-OK] Auth security invariants are present.\n";
exit(0);
