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
        
        // OPTIMIZED: Select only ID and Name for categories, not everything
        $cats = $pdo->query("SELECT id, name FROM categories")->fetchAll();
        
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 9;
        $offset = ($page - 1) * $perPage;

        $where = "WHERE p.status = 'published'"; 
        $params = [];
        
        if (!empty($_GET['cat'])) { $where .= " AND p.category_id = ?"; $params[] = $_GET['cat']; }
        if (!empty($_GET['q'])) {
            $where .= " AND (COALESCE(pt.title, p.title) LIKE ? OR COALESCE(pt.description, p.description) LIKE ?)";
            $term = '%' . $_GET['q'] . '%';
            $params[] = $term; $params[] = $term;
        }

        $countStmt = $pdo->prepare("SELECT count(*) FROM products p {$translationJoin} $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = (int)ceil($total / $perPage);

        $sql = "SELECT p.id, p.price, p.sale_price, p.sale_end, p.has_license, p.created_at, c.name AS category_name, {$localizedColumns} FROM products p {$translationJoin} LEFT JOIN categories c ON c.id = p.category_id $where ORDER BY p.id DESC LIMIT $perPage OFFSET $offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        if (!empty($products)) {
            $ids = array_column($products, 'id');
            $inQuery = implode(',', array_map('intval', $ids));
            $imgStmt = $pdo->query("SELECT product_id, image_path FROM product_images WHERE product_id IN ($inQuery) AND is_main = 1");
            $images = $imgStmt->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE);
            
            foreach ($products as &$p) {
                $p['thumbnail'] = $images[$p['id']]['image_path'] ?? null;
            }
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
                'eyebrow' => 'Digital Storefront',
                'title' => 'Готовые сайты, скрипты и шаблоны для запуска без долгой сборки',
                'subtitle' => 'Подберите готовый продукт для продажи, внедрения или быстрой кастомизации. Витрина заточена под digital assets, developer kits и готовые storefront solutions.',
                'primary_cta' => 'Смотреть каталог',
                'secondary_cta' => 'Почему Dark Tech',
            ]
            : [
                'eyebrow' => 'Digital Storefront',
                'title' => 'Ready-made sites, scripts and templates you can launch fast',
                'subtitle' => 'Pick a polished digital product for resale, deployment or rapid customization. The storefront is designed for digital assets, developer kits and ready-made solutions.',
                'primary_cta' => 'Browse catalog',
                'secondary_cta' => 'Why Dark Tech',
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
            ? ['Мгновенная выдача', 'RU / EN контент', 'Безопасная оплата', 'Файлы для скачивания сразу после оплаты']
            : ['Instant delivery', 'RU / EN content', 'Secure checkout', 'Download files right after payment'];

        $stats = [
            ['value' => (string)$total, 'label' => $isRu ? 'активных товаров' : 'active products'],
            ['value' => (string)count($cats), 'label' => $isRu ? 'категорий' : 'categories'],
            ['value' => 'RU / EN', 'label' => $isRu ? 'языка витрины' : 'storefront languages'],
            ['value' => '24/7', 'label' => $isRu ? 'выдача цифровых товаров' : 'digital delivery flow'],
        ];

        $whyItems = $isRu
            ? [
                ['icon' => 'fa-shield-halved', 'title' => 'Чистая витрина для digital products', 'text' => 'Тема собрана вокруг продажи готовых сайтов, скриптов, SaaS-китов и шаблонов без лишнего визуального мусора.'],
                ['icon' => 'fa-bolt', 'title' => 'Быстрый путь к покупке', 'text' => 'Главная, каталог и карточка товара выстраиваются вокруг поиска, доверия, цены и CTA, а не только вокруг красивого фона.'],
                ['icon' => 'fa-language', 'title' => 'Нормальная работа RU / EN', 'text' => 'Контент товара и SEO поля уже могут жить на двух языках, что удобно и для клиентов, и для дальнейшего роста по поиску.'],
                ['icon' => 'fa-chart-line', 'title' => 'Подготовка под SEO', 'text' => 'Структура темы учитывает мета-теги, schema, FAQ и внутреннюю перелинковку, чтобы товары было легче продвигать в поисковиках.'],
            ]
            : [
                ['icon' => 'fa-shield-halved', 'title' => 'Built for digital products', 'text' => 'The layout focuses on selling ready-made sites, scripts, SaaS kits and templates without decorative noise.'],
                ['icon' => 'fa-bolt', 'title' => 'Fast path to conversion', 'text' => 'Home, catalog and product pages are structured around search intent, trust, pricing and CTA instead of just visual effects.'],
                ['icon' => 'fa-language', 'title' => 'RU / EN ready', 'text' => 'Product content and SEO fields can already live in two languages, which helps both customers and long-term search visibility.'],
                ['icon' => 'fa-chart-line', 'title' => 'SEO-friendly layout', 'text' => 'The theme structure is prepared for meta tags, schema, FAQ blocks and internal linking so products can rank better over time.'],
            ];

        $useCases = $isRu
            ? [
                ['title' => 'Для владельца магазина', 'text' => 'Быстро публиковать готовые продукты, собирать витрину и продавать цифровые товары без лишней кастомной сборки.'],
                ['title' => 'Для агентства или фрилансера', 'text' => 'Брать готовую основу, докручивать бренд и быстрее отдавать клиентам готовые решения.'],
                ['title' => 'Для реселла и пакетов услуг', 'text' => 'Использовать тему как аккуратную витрину для шаблонов, скриптов, dev-kits и bundled offers.'],
            ]
            : [
                ['title' => 'For store owners', 'text' => 'Publish ready-made products fast and run a focused digital storefront without a heavy custom build.'],
                ['title' => 'For agencies and freelancers', 'text' => 'Start from a strong base, adapt the brand layer and ship finished solutions to clients faster.'],
                ['title' => 'For resale bundles', 'text' => 'Use the theme as a clean storefront for templates, scripts, dev kits and bundled offers.'],
            ];

        $faqItems = $isRu
            ? [
                ['question' => 'Подходит ли тема именно для продажи готовых сайтов и скриптов?', 'answer' => 'Да. Визуальная подача и структура блоков заточены под developer products, digital assets и storefront товары, а не под блог или корпоративный сайт.'],
                ['question' => 'Можно ли продавать и шаблоны, и готовые сайты, и просто скрипты?', 'answer' => 'Да. Dark Tech делается как универсальная тема для разных digital product типов: шаблоны, готовые storefront сайты, PHP-скрипты, панели управления и AI-ready решения.'],
                ['question' => 'Будет ли тема нормально работать на телефонах?', 'answer' => 'Да. Вся структура должна оставаться удобной на мобильных: крупные CTA, нормальные карточки, чистый sticky buy flow и читаемая типографика.'],
                ['question' => 'Насколько это готово под SEO?', 'answer' => 'Тема строится сразу с прицелом на SEO товаров: локализованные мета-поля, schema, canonical, hreflang, FAQ и сильную структуру страницы товара.'],
            ]
            : [
                ['question' => 'Is this theme really meant for selling ready-made sites and scripts?', 'answer' => 'Yes. The layout and content hierarchy are designed around developer products, digital assets and storefront-ready items rather than a generic blog or corporate website.'],
                ['question' => 'Can I sell templates, complete sites and standalone scripts in the same store?', 'answer' => 'Yes. Dark Tech is meant to work across multiple digital product types: templates, ready-made storefront sites, PHP scripts, admin panels and AI-ready assets.'],
                ['question' => 'Will it work well on mobile?', 'answer' => 'Yes. The theme is being shaped around large CTA buttons, clean cards, a usable sticky purchase flow and readable mobile typography.'],
                ['question' => 'How SEO-ready is it?', 'answer' => 'The theme is being built with product SEO in mind: localized meta fields, schema, canonical URLs, hreflang support, FAQ blocks and stronger content structure.'],
            ];

        $filterLabel = '';
        if ($currentQ !== '') {
            $filterLabel = $isRu ? ('Поиск: ' . $currentQ) : ('Search: ' . $currentQ);
        } elseif ($currentCategoryName !== '') {
            $filterLabel = $isRu ? ('Категория: ' . $currentCategoryName) : ('Category: ' . $currentCategoryName);
        } elseif ($page > 1) {
            $filterLabel = $isRu ? ('Страница ' . $page) : ('Page ' . $page);
        }

        $defaultHeading = $isRu ? 'Актуальные digital products' : 'Featured digital products';
        $defaultSubheading = $isRu
            ? 'Витрина для готовых сайтов, скриптов, шаблонов и storefront решений.'
            : 'A curated storefront for ready-made sites, scripts, templates and developer products.';
        $sectionHeading = $defaultHeading;
        $sectionSubheading = $defaultSubheading;

        if ($currentQ !== '') {
            $sectionHeading = $isRu ? 'Результаты поиска' : 'Search results';
            $sectionSubheading = $isRu
                ? ('Подборка товаров по запросу: ' . $currentQ)
                : ('Matching products for: ' . $currentQ);
        } elseif ($currentCategoryName !== '') {
            $sectionHeading = $currentCategoryName;
            $sectionSubheading = $isRu
                ? ('Каталог товаров из категории "' . $currentCategoryName . '".')
                : ('Products from the "' . $currentCategoryName . '" category.');
        } elseif ($page > 1) {
            $sectionHeading = $isRu ? ('Каталог, страница ' . $page) : ('Catalog, page ' . $page);
        }

        $defaultTitle = $isRu
            ? 'Готовые сайты, скрипты и шаблоны'
            : 'Ready-made sites, scripts and templates';
        $metaTitle = $isFilteredListing
            ? (($currentQ !== ''
                ? ($isRu ? ('Поиск: ' . $currentQ) : ('Search: ' . $currentQ))
                : ($currentCategoryName !== ''
                    ? ($currentCategoryName . ' | ' . ($isRu ? 'Каталог digital products' : 'Digital products catalog'))
                    : ($isRu ? 'Каталог digital products' : 'Digital products catalog')))
                . ' | ' . $siteTitle)
            : ($defaultTitle . ' | ' . $siteTitle);
        $metaDescription = $isFilteredListing
            ? ($isRu
                ? 'Каталог цифровых товаров: готовые сайты, скрипты и шаблоны с быстрым доступом к товарам и структурой под продажу.'
                : 'Browse digital products, ready-made sites, scripts and templates with a storefront focused on clarity and conversion.')
            : ($isRu
                ? 'Витрина готовых сайтов, скриптов и шаблонов с акцентом на скорость, понятную карточку товара и SEO-friendly структуру для digital products.'
                : 'A storefront for ready-made sites, scripts and templates with a strong product layout, faster buying flow and SEO-friendly structure for digital products.');
        $metaKeywords = $this->seoKeywords($isRu
            ? ['готовый сайт', 'скрипт для продажи', 'шаблон сайта', 'digital products', 'php marketplace', 'готовый магазин']
            : ['ready-made site', 'digital product script', 'website template', 'developer marketplace', 'php marketplace', 'ready storefront']);

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
            'why_items' => $whyItems,
            'use_cases' => $useCases,
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
