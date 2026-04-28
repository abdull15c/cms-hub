<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$trxController = $root . '/src/Controllers/Admin/TransactionController.php';
$paymentService = $root . '/src/Services/PaymentService.php';

$controllerCode = file_get_contents($trxController);
$paymentCode = file_get_contents($paymentService);

if ($controllerCode === false || $paymentCode === false) {
    fwrite(STDERR, "[SMOKE-FAIL] Cannot read idempotency target files.\n");
    exit(1);
}

if (strpos($controllerCode, 'approveManually') === false) {
    fwrite(STDERR, "[SMOKE-FAIL] Admin approve flow is not delegated to PaymentService.\n");
    exit(1);
}

if (strpos($paymentCode, "if (!\$trx || \$trx['status'] === 'paid')") === false) {
    fwrite(STDERR, "[SMOKE-FAIL] PaymentService idempotency guard is missing.\n");
    exit(1);
}

echo "[SMOKE-OK] Idempotency guards are present.\n";
exit(0);
