<?php
namespace Src\Services;

class RobotsGenerator
{
    public function generate(): void
    {
        $baseUrl = rtrim($this->baseUrl(), '/');
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin/',
            'Disallow: /profile',
            'Disallow: /download/',
            'Sitemap: ' . $baseUrl . '/sitemap.xml',
            '',
        ];

        file_put_contents(ROOT_PATH . '/public/robots.txt', implode("\n", $lines));
    }

    private function baseUrl(): string
    {
        if (defined('BASE_URL') && BASE_URL !== '') {
            return (string)BASE_URL;
        }

        $appUrl = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'http://localhost/market/public';
        return rtrim((string)$appUrl, '/');
    }
}
