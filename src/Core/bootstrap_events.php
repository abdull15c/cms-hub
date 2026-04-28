<?php
use Src\Core\Container;
use Src\Core\Event;
use Src\Services\QueueService;
use Src\Services\AuditService;

// User Registered -> Send Welcome Email via Queue
Event::listen('user.registered', function($user) {
    $registrationSource = (string)($user['registration_source'] ?? 'local');
    $verifyToken = (string)($user['token'] ?? '');
    if ($verifyToken !== '') {
        QueueService::push('Src\Jobs\SendEmailJob', [
            'to' => $user['email'],
            'subject' => 'Welcome to CMS-HUB',
            'template' => 'verify_account',
            'vars' => ['link' => BASE_URL.'/verify/'.$verifyToken]
        ]);
    } else {
        QueueService::push('Src\Jobs\SendEmailJob', [
            'to' => $user['email'],
            'subject' => 'Welcome to CMS-HUB',
            'template' => 'welcome',
            'vars' => ['source' => $registrationSource]
        ]);
    }
    AuditService::log('user', 'register', $user['id']);
    try {
        $analytics = Container::has('analytics') ? Container::get('analytics') : new \Src\Services\AnalyticsService();
        $analytics->trackRegistration($user);
    } catch (\Throwable $e) {
        \Src\Services\Logger::warning('Analytics registration listener skipped', ['error' => $e->getMessage()]);
    }
});

// Order Paid -> Send Receipt
Event::listen('order.paid', function($order) {
    QueueService::push('Src\Jobs\SendEmailJob', [
        'to' => $order['email'],
        'subject' => 'Payment Receipt',
        'template' => 'receipt',
        'vars' => [
            'product_name' => $order['title'],
            'price' => $order['amount'],
            'order_id' => $order['id'],
            'license_key' => $order['key'] ?? 'N/A',
            'download_link' => BASE_URL.'/download/'.$order['product_id']
        ]
    ]);
});