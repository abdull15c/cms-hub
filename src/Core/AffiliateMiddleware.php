<?php
// Affiliate Tracker with Secure Cookies
if (isset($_GET['ref'])) {
    $refId = intval($_GET['ref']);
    
    // Modern Cookie Settings
    $options = [
        'expires' => time() + (86400 * 30), // 30 days
        'path' => '/',
        'domain' => '', // Current domain
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // Only on HTTPS
        'httponly' => true, // No JS access
        'samesite' => 'Lax' // CSRF protection
    ];
    
    // PHP 7.3+ supports array options
    if (PHP_VERSION_ID >= 70300) {
        setcookie('ref_id', $refId, $options);
    } else {
        // Fallback for older PHP
        setcookie('ref_id', $refId, time() + (86400 * 30), '/', '', isset($_SERVER['HTTPS']), true);
    }
}