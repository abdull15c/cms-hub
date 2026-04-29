<?php
namespace Src\Services;
use Config\Database;
use Src\Services\Logger;
use Src\Services\MoneyService;
use Src\Services\RateLimiter;
use Src\Core\Event;

class PaymentService {
    private $pdo;
    private string $webhookProvider = '';
    private string $webhookRawBody = '';
    private array $webhookPayload = [];
    private const WEBHOOK_ALERT_REASONS = ['Sign Error', 'Currency mismatch'];
    private const WEBHOOK_ALERT_THRESHOLD = 10;
    private const WEBHOOK_ALERT_WINDOW_SECONDS = 600;
    public function __construct() { $this->pdo = Database::connect(); }
    private function getSetting($key) { return \Src\Services\SettingsService::get($key); }
    private const PROVIDERS = ['yoomoney', 'payeer', 'yookassa', 'cryptomus', 'lemonsqueezy', 'stripe'];

    private function linkYooMoney($orderId, $amount) { $w = $this->getSetting('yoomoney_wallet'); return defined('BASE_URL') ? BASE_URL . "/payment/yoomoney_form?id=$orderId&sum=$amount&wallet=$w&label=$orderId" : '#'; }
    private function linkPayeer($orderId, $amount) {
        $merchantId = $this->getSetting('payeer_merchant_id');
        $secret = $this->getSetting('payeer_secret_key');
        if ($merchantId === '' || $secret === '') {
            return null;
        }
        $desc = base64_encode("Order #{$orderId}");
        $parts = [$merchantId, $orderId, $amount, 'USD', $desc, $secret];
        $sign = strtoupper(hash('sha256', implode(':', $parts)));
        return 'https://payeer.com/merchant/?' . http_build_query([
            'm_shop' => $merchantId,
            'm_orderid' => $orderId,
            'm_amount' => $amount,
            'm_curr' => 'USD',
            'm_desc' => $desc,
            'm_sign' => $sign,
        ]);
    }
    public function createPayment($provider, $user_id, $product_id, ?float $depositAmount = null, string $couponCode = '') {
        $provider = $this->normalizeProvider($provider);
        if (!in_array($provider, self::PROVIDERS, true)) {
            return null;
        }
        if (!$this->isGatewayEnabled($provider)) {
            return null;
        }
        if (!$this->gatewayConfigured($provider)) {
            Logger::warning('Payment gateway is enabled but not configured', ['provider' => $provider]);
            return null;
        }

        $orderId = null;
        try {
            $this->pdo->beginTransaction();
            if ($product_id == 0) {
                $amountCents = MoneyService::toCents((float)$depositAmount);
                if ($amountCents <= 0) {
                    throw new \Exception('Invalid deposit amount.');
                }
                $isDeposit = true;
            } else {
                $product = $this->loadPurchasableProduct((int)$product_id);
                $amountCents = MoneyService::toCents(self::currentProductPriceValue($product));
                $isDeposit = false;
            }

            $couponId = null;
            if (!$isDeposit && $couponCode) {
                // LOCK Coupon Row
                $cStmt = $this->pdo->prepare("SELECT * FROM coupons WHERE code = ? FOR UPDATE");
                $cStmt->execute([$couponCode]);
                $coupon = $cStmt->fetch();
                if ($coupon && $coupon['used_count'] < $coupon['max_uses']) {
                    $amountCents = MoneyService::applyPercentDiscountCents($amountCents, (float)$coupon['discount_percent']);
                    $couponId = $coupon['id'];
                }
            }
            
            $finalAmount = MoneyService::decimalStringFromCents($amountCents);
            if (!$isDeposit && $amountCents <= 0) {
                $this->pdo->commit();
                return $this->processFreeOrder($user_id, $product_id, $couponId);
            }

            $stmt = $this->pdo->prepare("INSERT INTO transactions (user_id, product_id, provider, amount, status, coupon_id) VALUES (?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([$user_id, $product_id, $provider, $finalAmount, $couponId]);
            $orderId = (int)$this->pdo->lastInsertId();
            $this->pdo->commit();

            $link = null;
            if ($provider === 'yoomoney') $link = $this->linkYooMoney($orderId, $finalAmount);
            elseif ($provider === 'payeer') $link = $this->linkPayeer($orderId, $finalAmount);
            elseif ($provider === 'yookassa') $link = $this->createYooKassaPayment((int)$orderId, $finalAmount);
            elseif ($provider === 'cryptomus') $link = $this->createCryptomusPayment((int)$orderId, $finalAmount);
            elseif ($provider === 'lemonsqueezy') $link = $this->createLemonSqueezyPayment((int)$orderId, $finalAmount);
            elseif ($provider === 'stripe') $link = $this->createStripePayment((int)$orderId, $finalAmount);

            if ($link === null || $link === '#') {
                $this->pdo->prepare("UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE id = ? AND status = 'pending'")
                    ->execute([$orderId]);
                return null;
            }
            return $link;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            if (!empty($orderId)) {
                $this->pdo->prepare("UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE id = ? AND status = 'pending'")
                    ->execute([$orderId]);
            }
            Logger::error('Payment init failed: ' . $e->getMessage(), ['provider' => $provider]);
            return null;
        }
    }

    private function processFreeOrder($user_id, $product_id, $couponId = null) {
        $this->pdo->beginTransaction();
        $stmt = $this->pdo->prepare("INSERT INTO transactions (user_id, product_id, provider, amount, status, coupon_id) VALUES (?, ?, 'free', 0.00, 'paid', ?)");
        $stmt->execute([$user_id, $product_id, $couponId]);
        $trxId = (int)$this->pdo->lastInsertId();
        if (!empty($couponId)) {
            $couponUpdate = $this->pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ? AND used_count < max_uses");
            $couponUpdate->execute([$couponId]);
            if ($couponUpdate->rowCount() !== 1) {
                $this->pdo->rollBack();
                throw new \Exception('Coupon usage limit reached.');
            }
        }
        $this->pdo->commit();
        Event::fire('order.paid', ['id' => $trxId, 'user_id' => $user_id, 'product_id' => $product_id, 'amount' => 0.00]);
        return defined('BASE_URL') ? BASE_URL . "/payment/success?order_id=" . $trxId : '/';
    }
    
    public function handleWebhook($provider, $data, $rawBody = '') {
        $provider = $this->normalizeProvider($provider);
        $this->webhookProvider = $provider;
        $this->webhookRawBody = (string)$rawBody;
        $this->webhookPayload = is_array($data) ? $data : [];
        if ($provider === 'yoomoney') $this->verifyYooMoney($data);
        elseif ($provider === 'payeer') $this->verifyPayeer($data);
        elseif ($provider === 'yookassa') $this->verifyYooKassa($data);
        elseif ($provider === 'cryptomus') $this->verifyCryptomus($data, $rawBody);
        elseif ($provider === 'lemonsqueezy') $this->verifyLemonSqueezy($data, $rawBody);
        elseif ($provider === 'stripe') $this->verifyStripe($data, $rawBody);
        else $this->rejectWebhook('Unknown provider', 400);
    }

    private function rejectWebhook(string $message, int $status = 403): void {
        $this->recordWebhookFailure($message, $status);
        http_response_code($status);
        exit($message);
    }

    private function recordWebhookFailure(string $reason, int $status): void {
        try {
            $payloadSource = $this->webhookRawBody !== ''
                ? $this->webhookRawBody
                : json_encode($this->webhookPayload, JSON_UNESCAPED_UNICODE);
            $payloadHash = hash('sha256', (string)$payloadSource);
            $ip = RateLimiter::getIp();
            $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
            $requestId = substr((string)($_SERVER['REQUEST_ID'] ?? ''), 0, 64);
            $provider = $this->webhookProvider !== '' ? $this->webhookProvider : 'unknown';
            $stmt = $this->pdo->prepare("
                INSERT INTO webhook_failures (provider, reason, status_code, ip_address, payload_hash, request_id, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$provider, $reason, $status, $ip, $payloadHash, $requestId, $ua]);
            $this->triggerWebhookAlertIfNeeded($provider, $reason);
        } catch (\Throwable $e) {
            Logger::warning('Failed to persist webhook failure log', ['reason' => $reason, 'error' => $e->getMessage()]);
        }
    }

    private function triggerWebhookAlertIfNeeded(string $provider, string $reason): void {
        if (!in_array($reason, self::WEBHOOK_ALERT_REASONS, true)) {
            return;
        }
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM webhook_failures
            WHERE provider = ? AND reason = ? AND created_at >= (NOW() - INTERVAL ? SECOND)
        ");
        $stmt->execute([$provider, $reason, self::WEBHOOK_ALERT_WINDOW_SECONDS]);
        $count = (int)$stmt->fetchColumn();
        if ($count >= self::WEBHOOK_ALERT_THRESHOLD) {
            Logger::warning('Webhook failure spike detected', [
                'provider' => $provider,
                'reason' => $reason,
                'count' => $count,
                'window_seconds' => self::WEBHOOK_ALERT_WINDOW_SECONDS,
            ]);
        }
    }

    private function verifyYooMoney($data) {
        $this->requireWebhookFields($data, ['notification_type', 'operation_id', 'amount', 'currency', 'datetime', 'sender', 'codepro', 'label', 'sha1_hash']);
        $currency = strtoupper((string)$data['currency']);
        if (!in_array($currency, ['643', 'RUB', 'RUR'], true)) {
            $this->rejectWebhook('Currency mismatch');
        }
        $secret = $this->getSetting('yoomoney_secret');
        if (trim((string)$secret) === '') {
            $this->rejectWebhook('Webhook secret missing');
        }
        $str = $data['notification_type'].'&'.$data['operation_id'].'&'.$data['amount'].'&'.$data['currency'].'&'.$data['datetime'].'&'.$data['sender'].'&'.$data['codepro'].'&'.$secret.'&'.$data['label'];
        if (!hash_equals(sha1($str), $data['sha1_hash'] ?? '')) { $this->rejectWebhook('Hash Error'); }
        if (!$this->processSuccess((int)$data['label'], (float)$data['amount'], 'yoomoney')) {
            $this->rejectWebhook('Processing failed', 500);
        }
        echo "OK"; exit;
    }

    private function verifyPayeer($data) {
        $this->requireWebhookFields($data, ['m_operation_id', 'm_operation_ps', 'm_operation_date', 'm_operation_pay_date', 'm_shop', 'm_orderid', 'm_amount', 'm_curr', 'm_desc', 'm_status', 'm_sign']);
        if (strtoupper((string)$data['m_curr']) !== 'USD') {
            $this->rejectWebhook('Currency mismatch');
        }
        $m_key = $this->getSetting('payeer_secret_key');
        if (trim((string)$m_key) === '') {
            $this->rejectWebhook('Webhook secret missing');
        }
        $arHash = [
            $data['m_operation_id'], $data['m_operation_ps'], $data['m_operation_date'],
            $data['m_operation_pay_date'], $data['m_shop'], $data['m_orderid'],
            $data['m_amount'], $data['m_curr'], $data['m_desc'], $data['m_status']
        ];
        if (isset($data['m_params'])) $arHash[] = $data['m_params'];
        $arHash[] = $m_key;
        $sign_hash = strtoupper(hash('sha256', implode(':', $arHash)));
        if (hash_equals($sign_hash, strtoupper($data['m_sign'])) && $data['m_status'] == 'success') {
            if (!$this->processSuccess((int)$data['m_orderid'], (float)$data['m_amount'], 'payeer')) {
                $this->rejectWebhook('Processing failed', 500);
            }
            echo $data['m_orderid'] . '|success'; exit;
        }
        $this->rejectWebhook('Sign Error');
    }

    private function verifyYooKassa(array $data): void {
        $object = is_array($data['object'] ?? null) ? $data['object'] : [];
        $paymentId = (string)($object['id'] ?? '');
        if ($paymentId === '') {
            $this->rejectWebhook('Bad Request', 400);
        }

        $verified = $this->fetchYooKassaPayment($paymentId);
        $metadata = is_array($verified['metadata'] ?? null) ? $verified['metadata'] : [];
        $amount = is_array($verified['amount'] ?? null) ? $verified['amount'] : [];
        $orderId = (int)($metadata['order_id'] ?? 0);
        $currency = strtoupper((string)($amount['currency'] ?? ''));
        $value = (string)($amount['value'] ?? '');

        if (($verified['status'] ?? '') !== 'succeeded' || empty($verified['paid']) || $orderId <= 0 || $value === '') {
            $this->rejectWebhook('Payment is not completed');
        }
        if ($currency !== $this->gatewayCurrency('yookassa', 'RUB')) {
            $this->rejectWebhook('Currency mismatch');
        }

        if (!$this->processSuccess($orderId, (float)$value, 'yookassa', $paymentId)) {
            $this->rejectWebhook('Processing failed', 500);
        }
        echo "OK";
        exit;
    }

    private function verifyCryptomus(array $data, string $rawBody = ''): void {
        $sign = (string)($data['sign'] ?? '');
        if ($sign === '') {
            $this->rejectWebhook('Bad Request', 400);
        }
        $payload = $data;
        unset($payload['sign']);
        $secret = $this->getSetting('cryptomus_payment_key');
        if ($secret === '') {
            $this->rejectWebhook('Webhook secret missing');
        }
        $expected = md5(base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE)) . $secret);
        if (!hash_equals($expected, $sign)) {
            $this->rejectWebhook('Sign Error');
        }

        $status = (string)($payload['status'] ?? '');
        $orderId = (int)($payload['order_id'] ?? 0);
        $amount = (string)($payload['amount'] ?? '');
        $currency = strtoupper((string)($payload['currency'] ?? ''));
        $paymentId = (string)($payload['uuid'] ?? ($payload['payment_uuid'] ?? ''));
        if (!in_array($status, ['paid', 'paid_over'], true) || $orderId <= 0 || $amount === '') {
            $this->rejectWebhook('Payment is not completed');
        }
        if ($currency !== $this->gatewayCurrency('cryptomus', 'USD')) {
            $this->rejectWebhook('Currency mismatch');
        }

        if (!$this->processSuccess($orderId, (float)$amount, 'cryptomus', $paymentId !== '' ? $paymentId : null)) {
            $this->rejectWebhook('Processing failed', 500);
        }
        echo "OK";
        exit;
    }

    private function verifyLemonSqueezy(array $data, string $rawBody = ''): void {
        $secret = trim((string)$this->getSetting('lemonsqueezy_webhook_secret'));
        $signature = (string)($_SERVER['HTTP_X_SIGNATURE'] ?? '');
        if ($secret === '' || $signature === '') {
            $this->rejectWebhook('Sign Error');
        }
        $expected = hash_hmac('sha256', $rawBody !== '' ? $rawBody : json_encode($data, JSON_UNESCAPED_UNICODE), $secret);
        if (!hash_equals($expected, $signature)) {
            $this->rejectWebhook('Sign Error');
        }

        $eventName = (string)($data['meta']['event_name'] ?? $data['event_name'] ?? '');
        if (!in_array($eventName, ['order_created', 'subscription_payment_success'], true)) {
            http_response_code(200);
            echo "IGNORED";
            exit;
        }

        $attrs = is_array($data['data']['attributes'] ?? null) ? $data['data']['attributes'] : [];
        $orderId = (int)(
            $attrs['custom_data']['order_id']
            ?? $data['meta']['custom_data']['order_id']
            ?? 0
        );
        if ($orderId <= 0) {
            $this->rejectWebhook('Missing order id', 400);
        }

        $currency = strtoupper((string)($attrs['currency'] ?? $attrs['currency_code'] ?? ''));
        $expectedCurrency = $this->gatewayCurrency('lemonsqueezy', 'USD');
        if ($currency !== '' && $currency !== $expectedCurrency) {
            $this->rejectWebhook('Currency mismatch');
        }

        $amountValue = 0.0;
        if (isset($attrs['total'])) {
            $amountValue = ((float)$attrs['total']) / 100;
        } elseif (isset($attrs['subtotal'])) {
            $amountValue = ((float)$attrs['subtotal']) / 100;
        }
        $providerPaymentId = (string)($data['data']['id'] ?? '');
        if (!$this->processSuccess($orderId, $amountValue > 0 ? $amountValue : null, 'lemonsqueezy', $providerPaymentId !== '' ? $providerPaymentId : null)) {
            $this->rejectWebhook('Processing failed', 500);
        }
        echo "OK";
        exit;
    }

    private function verifyStripe(array $data, string $rawBody = ''): void {
        $secret = trim((string)$this->getSetting('stripe_webhook_secret'));
        $sigHeader = (string)($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
        if ($secret === '' || $sigHeader === '' || $rawBody === '') {
            $this->rejectWebhook('Sign Error');
        }
        $parts = [];
        foreach (explode(',', $sigHeader) as $part) {
            $entry = explode('=', trim($part), 2);
            if (count($entry) === 2) {
                $parts[$entry[0]] = $entry[1];
            }
        }
        $timestamp = (string)($parts['t'] ?? '');
        $signature = (string)($parts['v1'] ?? '');
        if ($timestamp === '' || $signature === '') {
            $this->rejectWebhook('Sign Error');
        }
        if (!ctype_digit($timestamp) || abs(time() - (int)$timestamp) > 300) {
            $this->rejectWebhook('Stale signature');
        }
        $expected = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
        if (!hash_equals($expected, $signature)) {
            $this->rejectWebhook('Sign Error');
        }

        $eventType = (string)($data['type'] ?? '');
        if (!in_array($eventType, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            http_response_code(200);
            echo "IGNORED";
            exit;
        }

        $object = is_array($data['data']['object'] ?? null) ? $data['data']['object'] : [];
        $orderId = (int)($object['metadata']['order_id'] ?? 0);
        if ($orderId <= 0) {
            $this->rejectWebhook('Missing order id', 400);
        }

        $currency = strtoupper((string)($object['currency'] ?? ''));
        if ($currency !== '' && $currency !== $this->gatewayCurrency('stripe', 'USD')) {
            $this->rejectWebhook('Currency mismatch');
        }
        $amount = isset($object['amount_total']) ? ((float)$object['amount_total']) / 100 : null;
        $paymentId = (string)($object['id'] ?? $object['payment_intent'] ?? '');
        if (!$this->processSuccess($orderId, $amount, 'stripe', $paymentId !== '' ? $paymentId : null)) {
            $this->rejectWebhook('Processing failed', 500);
        }
        echo "OK";
        exit;
    }

    public function approveManually($orderId) {
        return $this->processSuccess((int)$orderId, null);
    }

    // *** CRITICAL: ATOMIC PROCESSING ***
    private function processSuccess($orderId, $paidAmount = null, ?string $provider = null, ?string $providerPaymentId = null): bool {
        try {
            $pdo = Database::connect();
            if (!$pdo->inTransaction()) $pdo->beginTransaction();
            
            // LOCK ROW FOR UPDATE
            $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? FOR UPDATE"); 
            $stmt->execute([$orderId]); 
            $trx = $stmt->fetch();
            
            // Idempotency check
            if (!$trx || $trx['status'] === 'paid') { 
                $pdo->commit(); 
                return true; 
            }
            if ($trx['status'] !== 'pending') {
                $pdo->commit();
                Logger::warning("Payment ignored for non-pending order #$orderId", ['status' => $trx['status']]);
                return true;
            }
            if ($provider !== null && !hash_equals((string)$trx['provider'], $provider)) {
                $pdo->prepare("UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE id = ?")->execute([$orderId]);
                $pdo->commit();
                Logger::warning("Payment provider mismatch for Order #$orderId", ['expected' => $trx['provider'], 'received' => $provider]);
                return false;
            }
            $storedProviderPaymentId = (string)($trx['provider_payment_id'] ?? '');
            if ($providerPaymentId !== null && $storedProviderPaymentId !== '' && !hash_equals($storedProviderPaymentId, $providerPaymentId)) {
                $pdo->prepare("UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE id = ?")->execute([$orderId]);
                $pdo->commit();
                Logger::warning("Payment id mismatch for Order #$orderId", ['expected' => $trx['provider_payment_id'], 'received' => $providerPaymentId]);
                return false;
            }
            if ($paidAmount !== null) {
                $expected = (float)$trx['amount'];
                // Keep explicit decimal tolerance check for legacy smoke guard compatibility.
                if (abs($expected - (float)$paidAmount) > 0.01 || !MoneyService::nearlyEqual($expected, (float)$paidAmount)) {
                    $pdo->prepare("UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE id = ?")->execute([$orderId]);
                    $pdo->commit();
                    Logger::warning("Payment amount mismatch for Order #$orderId", ['expected' => $expected, 'received' => (float)$paidAmount]);
                    return false;
                }
            }

            if (!empty($trx['coupon_id'])) {
                $couponUpdate = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ? AND used_count < max_uses");
                $couponUpdate->execute([$trx['coupon_id']]);
                if ($couponUpdate->rowCount() !== 1) {
                    $pdo->prepare("UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE id = ?")->execute([$orderId]);
                    $pdo->commit();
                    Logger::warning("Payment coupon limit reached for Order #$orderId", ['coupon_id' => $trx['coupon_id']]);
                    return false;
                }
            }

            if ($providerPaymentId !== null && $storedProviderPaymentId === '') {
                $pdo->prepare("UPDATE transactions SET provider_payment_id = ? WHERE id = ?")->execute([$providerPaymentId, $orderId]);
            }
            $pdo->prepare("UPDATE transactions SET status = 'paid', updated_at = NOW() WHERE id = ?")->execute([$orderId]);
            
            if ($trx['product_id'] == 0) {
                $uStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
                $uStmt->execute([$trx['user_id']]);
                $currentBalanceCents = MoneyService::toCents((float)$uStmt->fetchColumn());
                $depositCents = MoneyService::toCents((float)$trx['amount']);
                $newBalance = MoneyService::fromCents($currentBalanceCents + $depositCents);
                $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?")->execute([$newBalance, $trx['user_id']]);
                $pdo->prepare("INSERT INTO wallet_logs (user_id, amount, type, reference_id, description) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$trx['user_id'], MoneyService::decimalStringFromCents($depositCents), 'deposit', $orderId, 'Gateway Deposit']);
            }
            
            $pdo->commit();
            Event::fire('order.paid', $trx);
            Logger::info("Payment processed securely for Order #$orderId");
            return true;
        } catch (\Throwable $e) {
            if(isset($pdo) && $pdo->inTransaction()) $pdo->rollBack(); 
            Logger::error("Payment Error: " . $e->getMessage());
            return false;
        }
    }

    private function loadPurchasableProduct(int $productId): array {
        $stmt = $this->pdo->prepare("SELECT id, price, sale_price, sale_end, status FROM products WHERE id = ? FOR UPDATE");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product || ($product['status'] ?? '') !== 'published') {
            throw new \Exception('Product is not available for purchase.');
        }
        return $product;
    }

    public static function currentProductPriceValue(array $product): float {
        $regular = (float)($product['price'] ?? 0);
        $salePrice = $product['sale_price'] !== null ? (float)$product['sale_price'] : null;
        $saleEnd = (string)($product['sale_end'] ?? '');
        if ($salePrice !== null && $salePrice > 0 && $salePrice < $regular && $saleEnd !== '' && strtotime($saleEnd) > time()) {
            return $salePrice;
        }
        return $regular;
    }

    private function requireWebhookFields(array $data, array $fields): void {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data) || (string)$data[$field] === '') {
                $this->rejectWebhook('Bad Request', 400);
            }
        }
    }

    private function normalizeProvider($provider): string {
        $provider = strtolower(trim((string)$provider));
        return $provider === 'crypto' ? 'cryptomus' : $provider;
    }

    private function isGatewayEnabled(string $provider): bool {
        return $this->getSetting($provider . '_enabled') === '1';
    }

    private function gatewayConfigured(string $provider): bool {
        $required = [
            'yoomoney' => ['yoomoney_wallet', 'yoomoney_secret'],
            'payeer' => ['payeer_merchant_id', 'payeer_secret_key'],
            'yookassa' => ['yookassa_shop_id', 'yookassa_secret_key'],
            'cryptomus' => ['cryptomus_merchant_uuid', 'cryptomus_payment_key'],
            'lemonsqueezy' => ['lemonsqueezy_api_key', 'lemonsqueezy_store_id', 'lemonsqueezy_variant_id', 'lemonsqueezy_webhook_secret'],
            'stripe' => ['stripe_secret_key', 'stripe_webhook_secret'],
        ];
        foreach ($required[$provider] ?? [] as $key) {
            if (trim((string)$this->getSetting($key)) === '') {
                return false;
            }
        }
        return true;
    }

    private function gatewayCurrency(string $provider, string $default): string {
        $currency = strtoupper(trim((string)$this->getSetting($provider . '_currency')));
        return $currency !== '' ? $currency : $default;
    }

    private function createYooKassaPayment(int $orderId, string $amount): ?string {
        $shopId = $this->getSetting('yookassa_shop_id');
        $secret = $this->getSetting('yookassa_secret_key');
        if ($shopId === '' || $secret === '') {
            return null;
        }

        $payload = [
            'amount' => [
                'value' => $amount,
                'currency' => $this->gatewayCurrency('yookassa', 'RUB'),
            ],
            'capture' => true,
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => (defined('BASE_URL') ? BASE_URL : '') . '/payment/success?order_id=' . $orderId,
            ],
            'description' => 'Order #' . $orderId,
            'metadata' => ['order_id' => (string)$orderId],
        ];

        $response = $this->httpJson('https://api.yookassa.ru/v3/payments', $payload, [
            'Authorization: Basic ' . base64_encode($shopId . ':' . $secret),
            'Idempotence-Key: cms-hub-order-' . $orderId,
        ]);
        $paymentId = (string)($response['id'] ?? '');
        $url = (string)($response['confirmation']['confirmation_url'] ?? '');
        if ($paymentId === '' || $url === '') {
            throw new \RuntimeException('YooKassa did not return a payment URL.');
        }

        $this->storeProviderPaymentId($orderId, $paymentId);
        return $url;
    }

    private function fetchYooKassaPayment(string $paymentId): array {
        $shopId = $this->getSetting('yookassa_shop_id');
        $secret = $this->getSetting('yookassa_secret_key');
        if ($shopId === '' || $secret === '') {
            $this->rejectWebhook('YooKassa credentials missing');
        }

        return $this->httpJson('https://api.yookassa.ru/v3/payments/' . rawurlencode($paymentId), null, [
            'Authorization: Basic ' . base64_encode($shopId . ':' . $secret),
        ]);
    }

    private function createCryptomusPayment(int $orderId, string $amount): ?string {
        $merchant = $this->getSetting('cryptomus_merchant_uuid');
        $key = $this->getSetting('cryptomus_payment_key');
        if ($merchant === '' || $key === '') {
            return null;
        }

        $payload = [
            'amount' => $amount,
            'currency' => $this->gatewayCurrency('cryptomus', 'USD'),
            'order_id' => (string)$orderId,
            'url_callback' => (defined('BASE_URL') ? BASE_URL : '') . '/payment/webhook/cryptomus',
            'url_return' => (defined('BASE_URL') ? BASE_URL : '') . '/payment/success?order_id=' . $orderId,
        ];
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $response = $this->httpJson('https://api.cryptomus.com/v1/payment', $payload, [
            'merchant: ' . $merchant,
            'sign: ' . md5(base64_encode((string)$body) . $key),
        ]);

        $result = is_array($response['result'] ?? null) ? $response['result'] : [];
        $paymentId = (string)($result['uuid'] ?? '');
        $url = (string)($result['url'] ?? '');
        if ($url === '') {
            throw new \RuntimeException('Cryptomus did not return a payment URL.');
        }
        if ($paymentId !== '') {
            $this->storeProviderPaymentId($orderId, $paymentId);
        }

        return $url;
    }

    private function createLemonSqueezyPayment(int $orderId, string $amount): ?string {
        $apiKey = trim((string)$this->getSetting('lemonsqueezy_api_key'));
        $storeId = trim((string)$this->getSetting('lemonsqueezy_store_id'));
        $variantId = trim((string)$this->getSetting('lemonsqueezy_variant_id'));
        if ($apiKey === '' || $storeId === '' || $variantId === '') {
            return null;
        }

        $payload = [
            'data' => [
                'type' => 'checkouts',
                'attributes' => [
                    'checkout_data' => [
                        'custom' => ['order_id' => (string)$orderId],
                    ],
                    'checkout_options' => [
                        'embed' => false,
                    ],
                    'product_options' => [
                        'enabled_variants' => [(int)$variantId],
                    ],
                ],
                'relationships' => [
                    'store' => ['data' => ['type' => 'stores', 'id' => (string)$storeId]],
                    'variant' => ['data' => ['type' => 'variants', 'id' => (string)$variantId]],
                ],
            ],
        ];
        $response = $this->httpJson(
            'https://api.lemonsqueezy.com/v1/checkouts',
            $payload,
            [
                'Authorization: Bearer ' . $apiKey,
                'Accept: application/vnd.api+json',
                'Content-Type: application/vnd.api+json',
            ],
            false
        );

        $url = (string)($response['data']['attributes']['url'] ?? '');
        if ($url === '') {
            throw new \RuntimeException('Lemon Squeezy did not return checkout URL.');
        }
        return $url;
    }

    private function createStripePayment(int $orderId, string $amount): ?string {
        $secretKey = trim((string)$this->getSetting('stripe_secret_key'));
        if ($secretKey === '') {
            return null;
        }
        $currency = strtolower($this->gatewayCurrency('stripe', 'USD'));
        $amountCents = MoneyService::toCents((float)$amount);
        if ($amountCents <= 0) {
            throw new \RuntimeException('Stripe amount must be positive.');
        }

        $payload = [
            'mode' => 'payment',
            'success_url' => (defined('BASE_URL') ? BASE_URL : '') . '/payment/success?order_id=' . $orderId,
            'cancel_url' => (defined('BASE_URL') ? BASE_URL : '') . '/',
            'metadata[order_id]' => (string)$orderId,
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][product_data][name]' => 'Order #' . $orderId,
            'line_items[0][price_data][unit_amount]' => (string)$amountCents,
            'line_items[0][quantity]' => '1',
        ];
        $response = $this->httpForm('https://api.stripe.com/v1/checkout/sessions', $payload, [
            'Authorization: Bearer ' . $secretKey,
        ]);
        $sessionId = (string)($response['id'] ?? '');
        $url = (string)($response['url'] ?? '');
        if ($sessionId !== '') {
            $this->storeProviderPaymentId($orderId, $sessionId);
        }
        if ($url === '') {
            throw new \RuntimeException('Stripe did not return checkout URL.');
        }
        return $url;
    }

    private function storeProviderPaymentId(int $orderId, string $paymentId): void {
        $this->pdo->prepare("UPDATE transactions SET provider_payment_id = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$paymentId, $orderId]);
    }

    private function httpJson(string $url, ?array $payload = null, array $headers = [], bool $jsonContentType = true): array {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL extension is required for payment gateways.');
        }
        $ch = curl_init($url);
        $baseHeaders = ['Accept: application/json'];
        if ($payload !== null) {
            if ($jsonContentType) {
                $baseHeaders[] = 'Content-Type: application/json';
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($baseHeaders, $headers));

        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $status < 200 || $status >= 300) {
            throw new \RuntimeException('Payment API request failed: ' . ($error ?: ('HTTP ' . $status)));
        }
        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Payment API returned invalid JSON.');
        }
        return $decoded;
    }

    private function httpForm(string $url, array $payload, array $headers = []): array {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL extension is required for payment gateways.');
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(['Accept: application/json'], $headers));

        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($raw === false || $status < 200 || $status >= 300) {
            throw new \RuntimeException('Payment API request failed: ' . ($error ?: ('HTTP ' . $status)));
        }
        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Payment API returned invalid JSON.');
        }
        return $decoded;
    }
}
