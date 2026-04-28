<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$target = $root . '/src/Services/LicenseService.php';
$code = file_get_contents($target);

if ($code === false) {
    fwrite(STDERR, "[SMOKE-FAIL] Cannot read LicenseService.php\n");
    exit(1);
}

$checks = [
    'SELECT * FROM licenses WHERE license_key = ? FOR UPDATE' => 'License row lock on validation',
    "WHERE id = ? AND (domain IS NULL OR domain = '')" => 'Atomic first activation update guard',
];

foreach ($checks as $needle => $name) {
    if (strpos($code, $needle) === false) {
        fwrite(STDERR, "[SMOKE-FAIL] Missing license security guard: {$name}\n");
        exit(1);
    }
}

echo "[SMOKE-OK] License security guards are present.\n";
exit(0);
