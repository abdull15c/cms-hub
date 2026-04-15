<?php
namespace Src\Controllers;

use Config\Database;
use Src\Services\SessionService;
use Src\Services\SettingsService;

class ContentController extends Controller
{
    public function blog()
    {
        $pdo = Database::connect();
        $lang = SessionService::get('lang', 'ru');
        $isRu = $lang === 'ru';
        $posts = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC")->fetchAll();
        $siteTitle = SettingsService::get('site_title') ?: 'CMS-HUB';

        $pageTitle = ($isRu ? 'Блог и обновления' : 'Blog and updates') . ' | ' . $siteTitle;
        $pageDescription = $isRu
            ? 'Новости, обзоры и обновления о готовых сайтах, скриптах, шаблонах и digital products.'
            : 'News, reviews and updates about ready-made sites, scripts, templates and digital products.';

        $structuredData = [[
            '@context' => 'https://schema.org',
            '@type' => 'Blog',
            'name' => $isRu ? 'Блог' : 'Blog',
            'description' => $pageDescription,
            'url' => $this->currentUrl(['lang' => $lang], ['preview_theme']),
            'inLanguage' => $lang,
        ]];

        if (!empty($posts)) {
            $itemList = [
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'name' => $isRu ? 'Последние статьи' : 'Latest posts',
                'itemListElement' => [],
            ];

            foreach ($posts as $index => $post) {
                $itemList['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => rtrim((string)BASE_URL, '/') . '/blog/' . (int)$post['id'] . '?lang=' . rawurlencode($lang),
                    'name' => (string)$post['title'],
                ];
            }

            $structuredData[] = $itemList;
        }

        $this->view('content/blog_index', [
            'posts' => $posts,
            'page_meta' => [
                'title' => $pageTitle,
                'description' => $pageDescription,
                'keywords' => $this->seoKeywords([
                    $isRu ? 'блог digital products' : 'digital products blog',
                    $isRu ? 'готовые сайты' : 'ready-made sites',
                    $isRu ? 'скрипты и шаблоны' : 'scripts and templates',
                ]),
                'canonical' => $this->currentUrl(['lang' => $lang], ['preview_theme']),
                'alternates' => [
                    'ru' => $this->currentUrl(['lang' => 'ru'], ['preview_theme']),
                    'en' => $this->currentUrl(['lang' => 'en'], ['preview_theme']),
                ],
                'og_type' => 'article',
                'structured_data' => $structuredData,
            ],
        ]);
    }

    public function post($id)
    {
        $pdo = Database::connect();
        $lang = SessionService::get('lang', 'ru');
        $isRu = $lang === 'ru';
        $siteTitle = SettingsService::get('site_title') ?: 'CMS-HUB';

        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        $post = $stmt->fetch();

        if (!$post) {
            $this->redirect('/blog?lang=' . rawurlencode($lang));
        }

        $relatedStmt = $pdo->prepare("SELECT id, title, content, created_at FROM posts WHERE id <> ? ORDER BY created_at DESC LIMIT 3");
        $relatedStmt->execute([$id]);
        $relatedPosts = $relatedStmt->fetchAll();

        $summary = $this->seoDescription((string)$post['content'], 170);
        $postUrl = $this->currentUrl(['lang' => $lang], ['preview_theme']);

        $structuredData = [[
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post['title'],
            'description' => $summary,
            'datePublished' => !empty($post['created_at']) ? date('c', strtotime((string)$post['created_at'])) : null,
            'mainEntityOfPage' => $postUrl,
            'url' => $postUrl,
            'author' => [
                '@type' => 'Organization',
                'name' => $siteTitle,
            ],
        ], [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => $isRu ? 'Главная' : 'Home',
                    'item' => rtrim((string)BASE_URL, '/') . '/?lang=' . rawurlencode($lang),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $isRu ? 'Блог' : 'Blog',
                    'item' => rtrim((string)BASE_URL, '/') . '/blog?lang=' . rawurlencode($lang),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $post['title'],
                    'item' => $postUrl,
                ],
            ],
        ]];

        $this->view('content/blog_show', [
            'post' => $post,
            'related_posts' => $relatedPosts,
            'page_meta' => [
                'title' => trim((string)$post['title']) . ' | ' . $siteTitle,
                'description' => $summary,
                'keywords' => $this->seoKeywords([
                    $post['title'],
                    $isRu ? 'обзор digital products' : 'digital products review',
                    $isRu ? 'готовые сайты и скрипты' : 'ready-made sites and scripts',
                ]),
                'canonical' => $postUrl,
                'alternates' => [
                    'ru' => $this->currentUrl(['lang' => 'ru'], ['preview_theme']),
                    'en' => $this->currentUrl(['lang' => 'en'], ['preview_theme']),
                ],
                'og_type' => 'article',
                'structured_data' => array_map(static function (array $schema): array {
                    return array_filter($schema, static fn($value) => $value !== null);
                }, $structuredData),
            ],
        ]);
    }

    public function faq()
    {
        $pdo = Database::connect();
        $lang = SessionService::get('lang', 'ru');
        $isRu = $lang === 'ru';
        $siteTitle = SettingsService::get('site_title') ?: 'CMS-HUB';
        $faqs = $pdo->query("SELECT * FROM faqs ORDER BY sort_order ASC")->fetchAll();

        $structuredData = [[
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static function (array $faq): array {
                return [
                    '@type' => 'Question',
                    'name' => (string)$faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => (string)$faq['answer'],
                    ],
                ];
            }, $faqs),
        ]];

        $this->view('content/faq', [
            'faqs' => $faqs,
            'page_meta' => [
                'title' => ($isRu ? 'FAQ и ответы' : 'FAQ and answers') . ' | ' . $siteTitle,
                'description' => $isRu
                    ? 'Ответы на частые вопросы о покупке, доставке, лицензиях и работе с digital products.'
                    : 'Answers to common questions about buying, delivery, licenses and working with digital products.',
                'keywords' => $this->seoKeywords([
                    'FAQ',
                    $isRu ? 'вопросы и ответы' : 'questions and answers',
                    $isRu ? 'лицензии и доставка' : 'licenses and delivery',
                ]),
                'canonical' => $this->currentUrl(['lang' => $lang], ['preview_theme']),
                'alternates' => [
                    'ru' => $this->currentUrl(['lang' => 'ru'], ['preview_theme']),
                    'en' => $this->currentUrl(['lang' => 'en'], ['preview_theme']),
                ],
                'structured_data' => $structuredData,
            ],
        ]);
    }
}
