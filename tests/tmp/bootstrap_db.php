<?php
declare(strict_types=1);

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
putenv('INSTALLER_TOKEN=local-bootstrap-token');
$_ENV['INSTALLER_TOKEN'] = 'local-bootstrap-token';
$_POST = [
    'setup_token' => 'local-bootstrap-token',
    'db_host' => 'localhost',
    'db_name' => 'dle_market_db',
    'db_user' => 'root',
    'db_pass' => '',
    'admin_email' => 'admin@market.test',
    'admin_pass' => 'Admin123!',
    'app_url' => 'https://market.test',
];

require dirname(__DIR__, 2) . '/public/install/process.php';
