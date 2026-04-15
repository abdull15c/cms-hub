<?php
use Src\Core\Event;
use Src\Services\QueueService;
use Src\Services\AuditService;

// User Registered -> Send Welcome Email via Queue
Event::listen('user.registered', function($user) {
    QueueService::push('Src\Jobs\SendEmailJob', [
        'to' => $user['email'],
        'subject' => 'Welcome to CMS-HUB',
        'template' => 'verify_account', 
        'vars' => ['link' => BASE_URL.'/verify/'.$user['token']]
    ]);
    AuditService::log('user', 'register', $user['id']);
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