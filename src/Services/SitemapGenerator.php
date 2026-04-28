<?php
namespace Src\Services;

require_once CONFIG_PATH . '/Database.php';

use Config\Database;

class SitemapGenerator
{
    public function generate(): void
    {
        $pdo = Database::connect();
        $baseUrl = rtrim($this->baseUrl(), '/');

        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';

        $publicPages = [
            ['path' => '/', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['path' => '/faq', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['path' => '/blog', 'changefreq' => 'daily', 'priority' => '0.8'],
            ['path' => '/page/terms', 'changefreq' => 'monthly', 'priority' => '0.4'],
            ['path' => '/page/privacy', 'changefreq' => 'monthly', 'priority' => '0.4'],
            ['path' => '/page/contact', 'changefreq' => 'monthly', 'priority' => '0.5'],
        ];

        foreach ($publicPages as $page) {
            foreach (['ru', 'en'] as $language) {
                $xml[] = $this->urlEntry(
                    $this->buildUrl($baseUrl, $page['path'], $language),
                    $page['changefreq'],
                    $page['priority'],
                    null,
                    $this->alternateLinks($baseUrl, $page['path'])
                );
            }
        }

        $stmt = $pdo->query("SELECT id, created_at FROM products WHERE status = 'published' ORDER BY id DESC");
        while ($row = $stmt->fetch()) {
            $path = '/product/' . (int)$row['id'];
            $lastmod = !empty($row['created_at']) ? gmdate('c', strtotime((string)$row['created_at'])) : null;

            foreach (['ru', 'en'] as $language) {
                $xml[] = $this->urlEntry(
                    $this->buildUrl($baseUrl, $path, $language),
                    'weekly',
                    '0.9',
                    $lastmod,
                    $this->alternateLinks($baseUrl, $path)
                );
            }
        }

        $postStmt = $pdo->query("SELECT id, created_at FROM posts ORDER BY created_at DESC");
        while ($post = $postStmt->fetch()) {
            $path = '/blog/' . (int)$post['id'];
            $lastmod = !empty($post['created_at']) ? gmdate('c', strtotime((string)$post['created_at'])) : null;

            foreach (['ru', 'en'] as $language) {
                $xml[] = $this->urlEntry(
                    $this->buildUrl($baseUrl, $path, $language),
                    'monthly',
                    '0.7',
                    $lastmod,
                    $this->alternateLinks($baseUrl, $path)
                );
            }
        }

        $xml[] = '</urlset>';
        file_put_contents(ROOT_PATH . '/public/sitemap.xml', implode('', $xml));
    }

    private function baseUrl(): string
    {
        if (defined('BASE_URL') && BASE_URL !== '') {
            return (string)BASE_URL;
        }

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        return $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    private function buildUrl(string $baseUrl, string $path, string $language): string
    {
        $normalizedPath = $path === '/' ? '' : $path;
        return $baseUrl . $normalizedPath . '?' . http_build_query(['lang' => $language]);
    }

    private function alternateLinks(string $baseUrl, string $path): array
    {
        return [
            'ru' => $this->buildUrl($baseUrl, $path, 'ru'),
            'en' => $this->buildUrl($baseUrl, $path, 'en'),
        ];
    }

    private function urlEntry(string $loc, string $changefreq, string $priority, ?string $lastmod = null, array $alternates = []): string
    {
        $entry = '<url>';
        $entry .= '<loc>' . $this->xml($loc) . '</loc>';

        foreach ($alternates as $language => $url) {
            $entry .= '<xhtml:link rel="alternate" hreflang="' . $this->xml($language) . '" href="' . $this->xml($url) . '" />';
        }

        if ($lastmod !== null) {
            $entry .= '<lastmod>' . $this->xml($lastmod) . '</lastmod>';
        }

        $entry .= '<changefreq>' . $this->xml($changefreq) . '</changefreq>';
        $entry .= '<priority>' . $this->xml($priority) . '</priority>';
        $entry .= '</url>';
        return $entry;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
