<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$routes = file_get_contents($root . '/public/index.php');

if ($routes === false) {
    fwrite(STDERR, "[SMOKE-FAIL] Cannot read public/index.php\n");
    exit(1);
}

$checks = [
    "'/admin/login', 'AuthController', 'loginForm'" => 'Admin login route points to real auth form',
    "'/api/license/check', 'LicenseApiController', 'check'" => 'License API route uses dedicated controller',
];

foreach ($checks as $needle => $name) {
    if (strpos($routes, $needle) === false) {
        fwrite(STDERR, "[SMOKE-FAIL] Route security invariant missing: {$name}\n");
        exit(1);
    }
}

if (strpos($routes, "'/admin/login', 'Admin\\BaseAdminController', 'login'") !== false) {
    fwrite(STDERR, "[SMOKE-FAIL] Broken admin login route returned.\n");
    exit(1);
}

echo "[SMOKE-OK] Route security invariants are present.\n";
exit(0);
