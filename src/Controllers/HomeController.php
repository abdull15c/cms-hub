<?php
namespace Src\Controllers;

use Config\Database;
use Src\Repositories\ProductRepository;
use Src\Services\SessionService;
use Src\Services\SettingsService;

class HomeController extends Controller {
    public function index() {
        $pdo = Database::connect();
        $repo = new ProductRepository();
        $lang = SessionService::get('lang', 'ru');
        $isRu = $lang === 'ru';
        $translationJoin = $repo->translationJoin($lang, 'p', 'pt');
        $localizedColumns = $repo->localizedColumns($lang, 'p', 'pt');

        // OPTIMIZED: Select only ID and Name for categories, not everything.
        $cats = $pdo->query("SELECT id, name FROM categories")->fetchAll();

        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 9;
        $offset = ($page - 1) * $perPage;

        $where = "WHERE p.status = 'published'";
        $params = [];

        if (!empty($_GET['cat'])) {
            $where .= " AND p.category_id = ?";
            $params[] = $_GET['cat'];
        }

        if (!empty($_GET['q'])) {
            $where .= " AND (COALESCE(pt.title, p.title) LIKE ? OR COALESCE(pt.description, p.description) LIKE ?)";
            $term = '%' . $_GET['q'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $countStmt = $pdo->prepare("SELECT count(*) FROM products p {$translationJoin} $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = (int)ceil($total / $perPage);

        $sql = "SELECT p.id, p.price, p.sale_price, p.sale_end, p.has_license, p.created_at, c.name AS category_name, {$localizedColumns}
                FROM products p
                {$translationJoin}
                LEFT JOIN categories c ON c.id = p.category_id
                $where
                ORDER BY p.id DESC
                LIMIT $perPage OFFSET $offset";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        if (!empty($products)) {
            $ids = array_column($products, 'id');
            $inQuery = implode(',', array_map('intval', $ids));
            $imgStmt = $pdo->query("SELECT product_id, image_path FROM product_images WHERE product_id IN ($inQuery) AND is_main = 1");
            $images = $imgStmt->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_UNIQUE);

            foreach ($products as &$product) {
                $product['thumbnail'] = $images[$product['id']]['image_path'] ?? null;
            }
            unset($product);
        }

        $currentCatId = (int)($_GET['cat'] ?? 0);
        $currentQ = trim((string)($_GET['q'] ?? ''));
        $categoryMap = [];
        foreach ($cats as $category) {
            $categoryMap[(int)$category['id']] = $category['name'];
        }

        $currentCategoryName = $currentCatId > 0 ? ($categoryMap[$currentCatId] ?? '') : '';
        $isFilteredListing = $currentCatId > 0 || $currentQ !== '' || $page > 1;
        $plainListing = !$isFilteredListing;
        $siteTitle = SettingsService::get('site_title') ?: ($isRu ? 'Market' : 'Market');

        $defaultHero = $isRu
            ? [
                'eyebrow' => 'WordPress · DLE · Standalone',
                'title' => 'Готовые сайты, скрипты, шаблоны и плагины для быстрого запуска',
                'subtitle' => 'Решения для WordPress, DLE и standalone-проектов — для запуска, кастомизации и внедрения без долгой сборки с нуля.',
                'primary_cta' => 'Смотреть каталог',
                'secondary_cta' => 'Что доступно',
            ]
            : [
                'eyebrow' => 'WordPress · DLE · Standalone',
                'title' => 'Ready-made websites, scripts, templates and plugins for fast launch',
                'subtitle' => 'Solutions for WordPress, DLE and standalone projects — built for launch, customization and deployment without a long build-from-scratch process.',
                'primary_cta' => 'Browse catalog',
                'secondary_cta' => 'What is inside',
            ];

        $hero = $defaultHero;
        $heroTitle = trim((string)SettingsService::get('hero_title'));
        $heroSubtitle = trim((string)SettingsService::get('hero_subtitle'));
        $heroPrimaryCta = trim((string)SettingsService::get('hero_primary_cta'));
        $heroSecondaryCta = trim((string)SettingsService::get('hero_secondary_cta'));

        if ($heroTitle !== '') {
            $hero['title'] = $heroTitle;
        }
        if ($heroSubtitle !== '') {
            $hero['subtitle'] = $heroSubtitle;
        }
        if ($heroPrimaryCta !== '') {
            $hero['primary_cta'] = $heroPrimaryCta;
        }
        if ($heroSecondaryCta !== '') {
            $hero['secondary_cta'] = $heroSecondaryCta;
        }

        $trustBadges = $isRu
            ? ['Быстрый запуск', 'WordPress · DLE · Standalone', 'Готово для кастомизации', 'Установка и доработка']
            : ['Fast launch', 'WordPress · DLE · Standalone', 'Customization-ready', 'Setup and refinement'];

        $stats = [
            ['value' => (string)$total, 'label' => $isRu ? 'готовых решений в каталоге' : 'ready-made products in catalog'],
            ['value' => (string)count($cats), 'label' => $isRu ? 'категорий и направлений' : 'categories and directions'],
            ['value' => 'WP / DLE / SA', 'label' => $isRu ? 'основные платформы' : 'primary platforms'],
            ['value' => 'Custom', 'label' => $isRu ? 'установка и адаптация' : 'setup and adaptation'],
        ];

        $catalogGroups = $isRu
            ? [
                ['icon' => 'fa-globe', 'title' => 'Готовые сайты', 'text' => 'Готовые проекты для быстрого запуска под продажу, запуск под себя или клиентскую работу.'],
                ['icon' => 'fa-terminal', 'title' => 'Standalone-скрипты', 'text' => 'Самостоятельные скрипты и инструменты, которые можно быстро внедрить без лишней сборки.'],
                ['icon' => 'fa-wordpress', 'title' => 'WordPress шаблоны', 'text' => 'Темы и заготовки для быстрого старта, адаптации под бренд и запуска клиентских сайтов.'],
                ['icon' => 'fa-puzzle-piece', 'title' => 'WordPress плагины', 'text' => 'Плагины и расширения для автоматизации, новых возможностей и практических задач.'],
                ['icon' => 'fa-layer-group', 'title' => 'DLE шаблоны', 'text' => 'Готовые шаблоны для DataLife Engine с упором на удобную доработку и быстрый запуск.'],
                ['icon' => 'fa-plug-circle-bolt', 'title' => 'DLE плагины', 'text' => 'Модули и плагины для DLE-проектов, которые ускоряют внедрение нужного функционала.'],
                ['icon' => 'fa-screwdriver-wrench', 'title' => 'Услуги', 'text' => 'Установка, настройка, адаптация под проект и доработка под конкретную задачу.'],
            ]
            : [
                ['icon' => 'fa-globe', 'title' => 'Ready websites', 'text' => 'Ready-made projects for fast launch, resale or client work.'],
                ['icon' => 'fa-terminal', 'title' => 'Standalone scripts', 'text' => 'Standalone scripts and tools that can be deployed without a long setup flow.'],
                ['icon' => 'fa-wordpress', 'title' => 'WordPress themes', 'text' => 'Themes and starter kits for fast launch, branding and client delivery.'],
                ['icon' => 'fa-puzzle-piece', 'title' => 'WordPress plugins', 'text' => 'Plugins and extensions for automation, feature growth and custom needs.'],
                ['icon' => 'fa-layer-group', 'title' => 'DLE templates', 'text' => 'Templates for DataLife Engine focused on fast launch and flexible customization.'],
                ['icon' => 'fa-plug-circle-bolt', 'title' => 'DLE plugins', 'text' => 'Modules and plugins for DLE projects that speed up implementation.'],
                ['icon' => 'fa-screwdriver-wrench', 'title' => 'Services', 'text' => 'Setup, adaptation, refinement and custom work around your project.'],
            ];

        $whyItems = $isRu
            ? [
                ['icon' => 'fa-bolt', 'title' => 'Готовые решения для быстрого старта', 'text' => 'Каталог собран вокруг продуктов, которые можно запускать без долгой подготовки и лишней ручной сборки.'],
                ['icon' => 'fa-code-branch', 'title' => 'WordPress, DLE и standalone', 'text' => 'Витрина сразу показывает, с какими платформами и типами решений ты реально работаешь.'],
                ['icon' => 'fa-sliders', 'title' => 'Удобная база для кастомизации', 'text' => 'Шаблоны, плагины, сайты и скрипты подаются как практичная основа, которую удобно адаптировать под задачу.'],
                ['icon' => 'fa-briefcase', 'title' => 'Подходит под запуск и коммерцию', 'text' => 'Оффер одинаково хорошо работает для своих проектов, клиентских внедрений и продажи готовых digital-решений.'],
            ]
            : [
                ['icon' => 'fa-bolt', 'title' => 'Ready-made solutions for fast launch', 'text' => 'The catalog is shaped around products that can be launched without a long setup cycle.'],
                ['icon' => 'fa-code-branch', 'title' => 'WordPress, DLE and standalone', 'text' => 'The storefront clearly states the platforms and solution types behind the offer.'],
                ['icon' => 'fa-sliders', 'title' => 'Built for customization', 'text' => 'Themes, plugins, sites and scripts are presented as a practical base for adaptation and refinement.'],
                ['icon' => 'fa-briefcase', 'title' => 'Useful for launch and resale', 'text' => 'The offer works for personal projects, client delivery and commercial-ready digital products.'],
            ];

        $serviceItems = $isRu
            ? [
                ['icon' => 'fa-download', 'title' => 'Установка и настройка', 'text' => 'Помощь с запуском, подключением и базовой настройкой купленного решения.'],
                ['icon' => 'fa-wand-magic-sparkles', 'title' => 'Адаптация под проект', 'text' => 'Подгонка структуры, блоков и функционала под задачу, нишу или клиента.'],
                ['icon' => 'fa-screwdriver-wrench', 'title' => 'Доработка шаблона или скрипта', 'text' => 'Точечные правки, новые секции, интеграции и улучшения поверх готовой базы.'],
                ['icon' => 'fa-pen-ruler', 'title' => 'Custom build и branding', 'text' => 'Индивидуальная доработка, визуальная адаптация и правки под ваш стиль и сценарий запуска.'],
            ]
            : [
                ['icon' => 'fa-download', 'title' => 'Setup and installation', 'text' => 'Help with launch, basic setup and initial configuration of the product.'],
                ['icon' => 'fa-wand-magic-sparkles', 'title' => 'Project adaptation', 'text' => 'Adjustments for your niche, client flow or business requirements.'],
                ['icon' => 'fa-screwdriver-wrench', 'title' => 'Theme or script refinement', 'text' => 'Targeted improvements, extra sections, integrations and functional upgrades.'],
                ['icon' => 'fa-pen-ruler', 'title' => 'Custom build and branding', 'text' => 'Individual refinement, branding work and design changes for your launch scenario.'],
            ];

        $aboutBlock = $isRu
            ? [
                'title' => 'О магазине',
                'text' => 'Здесь собраны мои готовые digital-продукты: сайты, скрипты, шаблоны и плагины для WordPress, DLE и standalone-проектов. Основной упор — на быстрый запуск, удобную кастомизацию и практичные решения, которые можно использовать в реальной работе.',
            ]
            : [
                'title' => 'About the store',
                'text' => 'This store brings together my ready-made digital products: websites, scripts, templates and plugins for WordPress, DLE and standalone projects. The main focus is fast launch, flexible customization and practical solutions built for real use.',
            ];

        $ctaBlock = $isRu
            ? [
                'title' => 'Нужна установка, адаптация или доработка под проект?',
                'text' => 'Кроме готовых решений, доступны услуги по запуску, настройке, кастомизации и custom build для WordPress, DLE и standalone-проектов.',
                'primary' => 'Смотреть каталог',
                'secondary' => 'Связаться',
            ]
            : [
                'title' => 'Need setup, adaptation or refinement for your project?',
                'text' => 'Alongside ready-made products, you can also order setup, customization and custom build work for WordPress, DLE and standalone projects.',
                'primary' => 'View catalog',
                'secondary' => 'Contact',
            ];

        $faqItems = $isRu
            ? [
                ['question' => 'Что можно найти в каталоге?', 'answer' => 'Готовые сайты, standalone-скрипты, WordPress шаблоны и плагины, DLE шаблоны и плагины, а также решения для кастомизации и быстрого запуска.'],
                ['question' => 'Можно ли заказать установку или доработку?', 'answer' => 'Да. Помимо готовых продуктов доступны услуги по установке, настройке, адаптации под проект и дополнительной разработке.'],
                ['question' => 'Подходит ли это для WordPress, DLE и самостоятельных проектов?', 'answer' => 'Да. Именно на эти направления и сделан основной акцент: WordPress, DLE и standalone-решения для быстрого старта и внедрения.'],
                ['question' => 'Можно ли использовать продукты как базу под свой проект?', 'answer' => 'Да. Один из главных акцентов магазина — готовые решения, которые удобно дорабатывать, адаптировать и использовать в реальной работе.'],
            ]
            : [
                ['question' => 'What can I find in the catalog?', 'answer' => 'Ready-made websites, standalone scripts, WordPress themes and plugins, DLE templates and plugins, plus solutions for customization and fast launch.'],
                ['question' => 'Can I order installation or refinement?', 'answer' => 'Yes. Alongside ready-made products, setup, adaptation and extra development services are available.'],
                ['question' => 'Is the store focused on WordPress, DLE and standalone projects?', 'answer' => 'Yes. Those are the core directions behind the store and the positioning of the offer.'],
                ['question' => 'Can I use the products as a base for my own project?', 'answer' => 'Yes. A big part of the offer is practical products that are easy to adapt, extend and use in real work.'],
            ];

        $filterLabel = '';
        if ($currentQ !== '') {
            $filterLabel = $isRu ? ('Поиск: ' . $currentQ) : ('Search: ' . $currentQ);
        } elseif ($currentCategoryName !== '') {
            $filterLabel = $isRu ? ('Категория: ' . $currentCategoryName) : ('Category: ' . $currentCategoryName);
        } elseif ($page > 1) {
            $filterLabel = $isRu ? ('Страница ' . $page) : ('Page ' . $page);
        }

        $defaultHeading = $isRu ? 'Каталог готовых решений' : 'Catalog of ready-made solutions';
        $defaultSubheading = $isRu
            ? 'Сайты, скрипты, шаблоны, плагины и другие digital-продукты для быстрого запуска и кастомизации.'
            : 'Websites, scripts, templates, plugins and other digital products for fast launch and customization.';
        $sectionHeading = $defaultHeading;
        $sectionSubheading = $defaultSubheading;

        if ($currentQ !== '') {
            $sectionHeading = $isRu ? 'Результаты поиска' : 'Search results';
            $sectionSubheading = $isRu
                ? ('Подборка решений по запросу: ' . $currentQ)
                : ('Matching solutions for: ' . $currentQ);
        } elseif ($currentCategoryName !== '') {
            $sectionHeading = $currentCategoryName;
            $sectionSubheading = $isRu
                ? ('Подборка товаров из категории "' . $currentCategoryName . '".')
                : ('Solutions from the "' . $currentCategoryName . '" category.');
        } elseif ($page > 1) {
            $sectionHeading = $isRu ? ('Каталог, страница ' . $page) : ('Catalog, page ' . $page);
        }

        $defaultTitle = $isRu
            ? 'Готовые сайты, скрипты, шаблоны и плагины'
            : 'Ready-made websites, scripts, templates and plugins';
        $metaTitle = $isFilteredListing
            ? (($currentQ !== ''
                ? ($isRu ? ('Поиск: ' . $currentQ) : ('Search: ' . $currentQ))
                : ($currentCategoryName !== ''
                    ? ($currentCategoryName . ' | ' . ($isRu ? 'Каталог готовых решений' : 'Catalog of ready-made solutions'))
                    : ($isRu ? 'Каталог готовых решений' : 'Catalog of ready-made solutions')))
                . ' | ' . $siteTitle)
            : ($defaultTitle . ' | ' . $siteTitle);

        $metaDescription = $isFilteredListing
            ? ($isRu
                ? 'Каталог готовых решений: сайты, скрипты, шаблоны и плагины для WordPress, DLE и standalone-проектов.'
                : 'Browse ready-made solutions: websites, scripts, templates and plugins for WordPress, DLE and standalone projects.')
            : ($isRu
                ? 'Готовые сайты, скрипты, шаблоны и плагины для WordPress, DLE и standalone-проектов. Быстрый запуск, кастомизация и практичные digital-решения без долгой сборки с нуля.'
                : 'Ready-made websites, scripts, templates and plugins for WordPress, DLE and standalone projects. Fast launch, customization and practical digital solutions without a long build-from-scratch process.');

        $metaKeywords = $this->seoKeywords($isRu
            ? ['готовый сайт', 'wordpress шаблон', 'wordpress плагин', 'dle шаблон', 'dle плагин', 'standalone скрипт', 'digital решения']
            : ['ready-made website', 'wordpress theme', 'wordpress plugin', 'dle template', 'dle plugin', 'standalone script', 'digital solutions']);

        $canonical = $plainListing
            ? $this->currentUrl(['lang' => $lang], ['preview_theme', 'cat', 'q', 'page'])
            : $this->currentUrl(['lang' => $lang], ['preview_theme']);
        $alternates = [
            'ru' => $plainListing ? $this->currentUrl(['lang' => 'ru'], ['preview_theme', 'cat', 'q', 'page']) : $this->currentUrl(['lang' => 'ru'], ['preview_theme']),
            'en' => $plainListing ? $this->currentUrl(['lang' => 'en'], ['preview_theme', 'cat', 'q', 'page']) : $this->currentUrl(['lang' => 'en'], ['preview_theme']),
        ];

        $listingUrl = $this->currentUrl(['lang' => $lang], ['preview_theme']);
        $structuredData = [[
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteTitle,
            'url' => $this->currentUrl(['lang' => $lang], ['preview_theme', 'cat', 'q', 'page']),
            'description' => $metaDescription,
            'inLanguage' => $lang,
        ], [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $sectionHeading,
            'description' => $metaDescription,
            'url' => $listingUrl,
            'inLanguage' => $lang,
        ]];

        if (!empty($products)) {
            $itemList = [
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'name' => $sectionHeading,
                'numberOfItems' => count($products),
                'itemListElement' => [],
            ];

            foreach ($products as $index => $product) {
                $itemList['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => $offset + $index + 1,
                    'url' => rtrim((string)BASE_URL, '/') . '/product/' . (int)$product['id'] . '?lang=' . rawurlencode($lang),
                    'name' => (string)$product['title'],
                ];
            }

            $structuredData[] = $itemList;
        }

        $this->view('home', [
            'products' => $products,
            'categories' => $cats,
            'page' => $page,
            'totalPages' => $totalPages,
            'currentCat' => $currentCatId,
            'currentQ' => $currentQ,
            'current_category_name' => $currentCategoryName,
            'filter_label' => $filterLabel,
            'clear_filters_url' => $this->currentUrl(['lang' => $lang], ['preview_theme', 'cat', 'q', 'page']),
            'is_filtered_listing' => $isFilteredListing,
            'plain_listing' => $plainListing,
            'hero' => $hero,
            'trust_badges' => $trustBadges,
            'stats' => $stats,
            'catalog_groups' => $catalogGroups,
            'why_items' => $whyItems,
            'service_items' => $serviceItems,
            'about_block' => $aboutBlock,
            'cta_block' => $ctaBlock,
            'faq_items' => $faqItems,
            'section_heading' => $sectionHeading,
            'section_subheading' => $sectionSubheading,
            'page_meta' => [
                'title' => $metaTitle,
                'description' => $metaDescription,
                'keywords' => $metaKeywords,
                'robots' => $isFilteredListing ? 'noindex,follow' : 'index,follow',
                'canonical' => $canonical,
                'alternates' => $alternates,
                'og_type' => 'website',
                'structured_data' => $structuredData,
            ],
        ]);
    }
}
