<?php
namespace Src\Controllers;
use Config\Database;
use Src\Repositories\ProductRepository;
use Src\Services\SettingsService;
use Src\Services\SessionService;

class ProductController extends Controller {
    public function show($id) {
        $pdo = Database::connect();
        $repo = new ProductRepository();
        $lang = SessionService::get('lang', 'ru');
        $isRu = $lang === 'ru';
        $localizedColumns = $repo->localizedColumns($lang, 'p', 'pt');
        $translationJoin = $repo->translationJoin($lang, 'p', 'pt');
        $allowDraft = SessionService::get('role') === 'admin';
        $statusSql = $allowDraft ? '' : " AND p.status = 'published'";
        $stmt=$pdo->prepare("SELECT p.*, c.name AS category_name, {$localizedColumns} FROM products p {$translationJoin} LEFT JOIN categories c ON c.id = p.category_id WHERE p.id=?{$statusSql}"); $stmt->execute([$id]); $product=$stmt->fetch(); if(!$product)$this->abort(404, 'Product not found.');
        
        $images=$pdo->prepare("SELECT * FROM product_images WHERE product_id=? ORDER BY is_main DESC"); $images->execute([$id]); $images=$images->fetchAll();
        $reviews=$pdo->prepare("SELECT r.*,u.email FROM reviews r JOIN users u ON r.user_id=u.id WHERE r.product_id=? AND r.is_approved=1 ORDER BY r.created_at DESC"); $reviews->execute([$id]); $reviews=$reviews->fetchAll();
        
        $avg=0; if(count($reviews)>0){ $s=0; foreach($reviews as $r)$s+=$r['rating']; $avg=round($s/count($reviews),1); }

        $canReview=false; $chatMessages=[];
        SessionService::start();
        
        if (SessionService::get('user_id')) {
            $uid = $this->currentUserId();
            $chk=$pdo->prepare("SELECT id FROM licenses WHERE user_id=? AND product_id=?"); $chk->execute([$uid,$id]); 
            $rev=$pdo->prepare("SELECT id FROM reviews WHERE user_id=? AND product_id=?"); $rev->execute([$uid,$id]);
            if($chk->fetch() && !$rev->fetch()) $canReview=true;

            // FETCH CHAT HISTORY
            $th = $pdo->prepare("SELECT id FROM chat_threads WHERE user_id=? AND product_id=?"); $th->execute([$uid,$id]);
            $thread = $th->fetch();
            if($thread) {
                $msgs = $pdo->prepare("SELECT * FROM chat_messages WHERE thread_id=? ORDER BY created_at ASC");
                $msgs->execute([$thread['id']]);
                $chatMessages = $msgs->fetchAll();
            }
        }

        $relatedProducts = [];
        $relatedSql = "SELECT p.id, p.price, p.sale_price, p.sale_end, c.name AS category_name, {$localizedColumns} FROM products p {$translationJoin} LEFT JOIN categories c ON c.id = p.category_id WHERE p.id <> ? AND p.status = 'published'";
        $relatedParams = [$id];
        if (!empty($product['category_id'])) {
            $relatedSql .= " AND p.category_id = ?";
            $relatedParams[] = $product['category_id'];
        }
        $relatedSql .= " ORDER BY p.id DESC LIMIT 3";
        $relatedStmt = $pdo->prepare($relatedSql);
        $relatedStmt->execute($relatedParams);
        $relatedProducts = $relatedStmt->fetchAll();

        if (empty($relatedProducts) && !empty($product['category_id'])) {
            $fallbackStmt = $pdo->prepare("SELECT p.id, p.price, p.sale_price, p.sale_end, c.name AS category_name, {$localizedColumns} FROM products p {$translationJoin} LEFT JOIN categories c ON c.id = p.category_id WHERE p.id <> ? AND p.status = 'published' ORDER BY p.id DESC LIMIT 3");
            $fallbackStmt->execute([$id]);
            $relatedProducts = $fallbackStmt->fetchAll();
        }

        if (!empty($relatedProducts)) {
            $relatedIds = array_column($relatedProducts, 'id');
            $inQuery = implode(',', array_map('intval', $relatedIds));
            $imgStmt = $pdo->query("SELECT product_id, image_path FROM product_images WHERE product_id IN ($inQuery) AND is_main = 1");
            $relatedImages = $imgStmt->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE);
            foreach ($relatedProducts as &$relatedProduct) {
                $relatedProduct['thumbnail'] = $relatedImages[$relatedProduct['id']]['image_path'] ?? null;
            }
            unset($relatedProduct);
        }

        $facts = $isRu
            ? [
                ['label' => 'Категория', 'value' => $product['category_name'] ?: 'Digital Product'],
                ['label' => 'Доставка', 'value' => 'Цифровая выдача после оплаты'],
                ['label' => 'Языки', 'value' => 'RU / EN'],
                ['label' => 'Лицензия', 'value' => !empty($product['has_license']) ? 'С ключом лицензии' : 'Без отдельного license key'],
            ]
            : [
                ['label' => 'Category', 'value' => $product['category_name'] ?: 'Digital Product'],
                ['label' => 'Delivery', 'value' => 'Digital access after payment'],
                ['label' => 'Languages', 'value' => 'RU / EN'],
                ['label' => 'License', 'value' => !empty($product['has_license']) ? 'Includes license key' : 'No separate license key'],
            ];

        $perks = $isRu
            ? [
                ['icon' => 'fa-bolt', 'title' => 'Быстрый доступ', 'text' => 'После успешной оплаты пользователь сразу получает цифровой доступ к заказу.'],
                ['icon' => 'fa-shield-halved', 'title' => 'Витрина для digital products', 'text' => 'Карточка товара строится вокруг пользы, состава, цены и понятного пути к покупке.'],
                ['icon' => 'fa-language', 'title' => 'Локализация товара', 'text' => 'Товарная страница может работать на RU и EN с отдельными SEO-полями и контентом.'],
            ]
            : [
                ['icon' => 'fa-bolt', 'title' => 'Fast access', 'text' => 'Customers receive digital order access right after a successful payment.'],
                ['icon' => 'fa-shield-halved', 'title' => 'Built for digital products', 'text' => 'The product page is structured around value, contents, pricing and a clean purchase path.'],
                ['icon' => 'fa-language', 'title' => 'Localized product copy', 'text' => 'Product pages can work in RU and EN with separate SEO fields and localized content.'],
            ];

        $faqItems = $isRu
            ? [
                ['question' => 'Что получает покупатель после оплаты?', 'answer' => 'Покупатель получает цифровой доступ к заказу и может работать с продуктом через свой аккаунт и выдачу файлов.'],
                ['question' => 'Подойдёт ли этот товар для коммерческого использования?', 'answer' => !empty($product['has_license']) ? 'Да, у товара предусмотрена лицензируемая модель, что удобно для коммерческого использования и контроля выдачи.' : 'Уточните сценарий использования по описанию товара и комплекту поставки.'],
                ['question' => 'Есть ли у страницы товара SEO-подготовка?', 'answer' => 'Да. Структура страницы строится вокруг локализованных мета-полей, Product schema, FAQ и внутренней перелинковки.'],
            ]
            : [
                ['question' => 'What does the buyer get after payment?', 'answer' => 'The buyer receives digital order access and can work with the product through the account delivery flow.'],
                ['question' => 'Can this be used commercially?', 'answer' => !empty($product['has_license']) ? 'Yes. This product is prepared for a licensed distribution model, which is useful for commercial usage and controlled delivery.' : 'Review the product scope and package details before using it commercially.'],
                ['question' => 'Is the product page SEO-ready?', 'answer' => 'Yes. The layout is being built around localized meta fields, Product schema, FAQ content and stronger internal linking.'],
            ];

        $siteTitle = SettingsService::get('site_title') ?: ($isRu ? 'Market' : 'Market');
        $summary = $this->seoDescription((string)($product['meta_desc'] ?: $product['description']), 170);
        $pageTitle = trim((string)($product['meta_title'] ?: ($product['title'] . ' | ' . $siteTitle)));
        $pageKeywords = trim((string)($product['meta_keywords'] ?: $this->seoKeywords([
            $product['title'],
            $product['category_name'] ?? '',
            $isRu ? 'готовый сайт' : 'ready-made site',
            $isRu ? 'скрипт' : 'script',
            $isRu ? 'шаблон сайта' : 'website template',
        ])));
        $imageUrls = [];
        foreach ($images as $image) {
            if (!empty($image['image_path'])) {
                $imageUrls[] = rtrim((string)BASE_URL, '/') . '/uploads/images/' . rawurlencode($image['image_path']);
            }
        }
        $mainImage = $imageUrls[0] ?? $this->defaultOgImage();
        $productUrl = $this->currentUrl(['lang' => $lang], ['preview_theme']);
        $draftRobots = ($product['status'] ?? 'published') === 'draft' ? 'noindex,nofollow' : 'index,follow';

        $baseUrl = rtrim((string)BASE_URL, '/');
        $homeUrl = $baseUrl . '/?lang=' . rawurlencode($lang);
        $catalogUrl = !empty($product['category_id'])
            ? $baseUrl . '/?' . http_build_query(['cat' => (int)$product['category_id'], 'lang' => $lang])
            : $homeUrl;

        $productSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['title'],
            'description' => $summary,
            'sku' => 'product-' . (int)$product['id'],
            'category' => $product['category_name'] ?: 'Digital Product',
            'brand' => [
                '@type' => 'Brand',
                'name' => $siteTitle,
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $productUrl,
                'priceCurrency' => 'RUB',
                'price' => number_format((float)($product['sale_price'] && strtotime((string)$product['sale_end']) > time() ? $product['sale_price'] : $product['price']), 2, '.', ''),
                'availability' => 'https://schema.org/InStock',
            ],
        ];

        if (!empty($imageUrls)) {
            $productSchema['image'] = $imageUrls;
        } elseif ($mainImage !== null) {
            $productSchema['image'] = [$mainImage];
        }

        if (!empty($product['sale_end']) && strtotime((string)$product['sale_end']) > time()) {
            $productSchema['offers']['priceValidUntil'] = date('Y-m-d', strtotime((string)$product['sale_end']));
        }

        $breadcrumbItems = [[
            '@type' => 'ListItem',
            'position' => 1,
            'name' => $isRu ? 'Главная' : 'Home',
            'item' => $homeUrl,
        ]];

        if (!empty($product['category_id'])) {
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $product['category_name'] ?: ($isRu ? 'Каталог' : 'Catalog'),
                'item' => $catalogUrl,
            ];
        }

        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => count($breadcrumbItems) + 1,
            'name' => $product['title'],
            'item' => $productUrl,
        ];

        $structuredData = [$productSchema, [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbItems,
        ]];

        if (count($reviews) > 0 && $avg > 0) {
            $structuredData[0]['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format((float)$avg, 1, '.', ''),
                'reviewCount' => count($reviews),
            ];
        }

        if (!empty($faqItems)) {
            $structuredData[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map(static function (array $item): array {
                    return [
                        '@type' => 'Question',
                        'name' => (string)($item['question'] ?? ''),
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => (string)($item['answer'] ?? ''),
                        ],
                    ];
                }, $faqItems),
            ];
        }

        $this->view('product', [
            'product' => $product, 'images' => $images, 'reviews' => $reviews, 'avg_rating' => $avg, 'can_review' => $canReview,
            'chat_messages' => $chatMessages,
            'related_products' => $relatedProducts,
            'product_facts' => $facts,
            'product_perks' => $perks,
            'product_faq_items' => $faqItems,
            'product_summary' => $summary,
            'page_meta' => [
                'title' => $pageTitle,
                'description' => $summary,
                'keywords' => $pageKeywords,
                'robots' => $draftRobots,
                'canonical' => $productUrl,
                'alternates' => [
                    'ru' => $this->currentUrl(['lang' => 'ru'], ['preview_theme']),
                    'en' => $this->currentUrl(['lang' => 'en'], ['preview_theme']),
                ],
                'og_type' => 'product',
                'og_image' => $mainImage,
                'locale' => $this->localeForLanguage($lang),
                'structured_data' => $structuredData,
            ],
        ]);
    }
}
