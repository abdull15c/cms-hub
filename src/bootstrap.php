<?php
use Src\Core\Container;
use Src\Core\Event;
use Src\Services\QueueService;
use Src\Services\AuditService;

// Register Listeners
Event::listen('order.paid', 'Src\Listeners\ProductDeliveryListener@handle');
Event::listen('order.paid', 'Src\Listeners\AffiliateListener@handle');
Event::listen('user.registered', function($user) {
    QueueService::push('Src\Jobs\SendEmailJob', [
        'to' => $user['email'],
        'subject' => 'Welcome to CMS-HUB',
        'template' => 'verify_account',
        'vars' => ['link' => BASE_URL . '/verify/' . $user['token']]
    ]);
    AuditService::log('user', 'register', $user['id']);
});

Event::listen('user.login', function($user) {
    try {
        $analytics = Container::has('analytics') ? Container::get('analytics') : new \Src\Services\AnalyticsService();
        $analytics->trackLogin($user);
    } catch (\Throwable $e) {
        \Src\Services\Logger::warning('Analytics login listener skipped', ['error' => $e->getMessage()]);
    }
});

// Example: Audit Log listener
Event::listen('order.paid', function($order) {
    \Src\Services\AuditService::log('payment', 'complete', $order['id'], "Amount: {$order['amount']}");
});
