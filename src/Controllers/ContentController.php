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
        if (empty($faqs)) {
            $faqs = $isRu
                ? [
                    [
                        'question' => 'Как проходит покупка цифрового товара?',
                        'answer' => 'Вы выбираете товар, оплачиваете удобным способом и получаете доступ к файлам/ключу после подтверждения платежа. Для некоторых способов оплаты подтверждение может занять немного времени.',
                    ],
                    [
                        'question' => 'Когда я получу доступ к скачиванию?',
                        'answer' => 'Обычно сразу после успешной оплаты. Если доступ не появился, проверьте email и страницу профиля, затем свяжитесь с поддержкой, указав номер заказа.',
                    ],
                    [
                        'question' => 'Что делать, если письмо с доступом не пришло?',
                        'answer' => 'Проверьте папки Спам/Промо, корректность email в аккаунте и статус заказа. Если проблема остается, напишите в поддержку: мы выдадим доступ вручную после проверки оплаты.',
                    ],
                    [
                        'question' => 'Можно ли использовать продукт на нескольких сайтах?',
                        'answer' => 'Это зависит от типа лицензии конкретного товара. По умолчанию лицензия ограниченная и действует в рамках указанного сценария. Подробности всегда смотрите в карточке товара.',
                    ],
                    [
                        'question' => 'Разрешена ли перепродажа или передача файлов третьим лицам?',
                        'answer' => 'Нет. Перепродажа, публичное распространение, публикация исходников и передача лицензии/файлов третьим лицам без письменного разрешения правообладателя запрещены.',
                    ],
                    [
                        'question' => 'Есть ли возврат для digital products?',
                        'answer' => 'Из-за цифрового характера товара возвраты обычно недоступны после выдачи доступа. Исключения возможны при подтвержденной критической неисправности, которую нельзя исправить в разумный срок, а также в случаях, требуемых законом.',
                    ],
                    [
                        'question' => 'Что входит в поддержку?',
                        'answer' => 'Поддержка включает базовую помощь по установке, активации и исправлению подтвержденных багов. Индивидуальные доработки, интеграции и кастомные работы выполняются отдельно по договоренности.',
                    ],
                    [
                        'question' => 'Как работает раздел Demo на странице товара?',
                        'answer' => 'Если продавец включил демо, вы увидите ссылку на демо-сайт и, при необходимости, тестовые учетные данные (логин/пароль). Демо предназначено для ознакомления и может иметь ограниченный функционал.',
                    ],
                    [
                        'question' => 'Безопасно ли оплачивать на сайте?',
                        'answer' => 'Платежи обрабатываются подключенными провайдерами. Данные банковских карт не хранятся в системе магазина. Рекомендуем использовать только официальный домен и защищенное соединение HTTPS.',
                    ],
                    [
                        'question' => 'Какие данные сохраняются в аккаунте?',
                        'answer' => 'Сохраняются данные, необходимые для работы сервиса: email, история заказов, технические логи безопасности и настройки сессии (язык/валюта). Подробности описаны в Политике конфиденциальности.',
                    ],
                    [
                        'question' => 'Можно ли удалить аккаунт и персональные данные?',
                        'answer' => 'Да, вы можете отправить запрос через поддержку. Часть данных может храниться дольше, если это требуется законом (например, бухгалтерские или антифрод-обязательства).',
                    ],
                    [
                        'question' => 'Есть ли API для разработчиков?',
                        'answer' => 'Да. В профиле доступен раздел API с токеном и примерами запросов. Не передавайте токен третьим лицам и соблюдайте требования Terms/Privacy при обработке данных через интеграции.',
                    ],
                    [
                        'question' => 'Что делать, если токен API был скомпрометирован?',
                        'answer' => 'Сразу перевыпустите токен в профиле (Revoke & Regenerate), обновите его во всех интеграциях и проверьте логи запросов на подозрительную активность.',
                    ],
                    [
                        'question' => 'Куда обращаться по юридическим вопросам и жалобам?',
                        'answer' => 'Используйте страницу контактов и укажите тему обращения (платеж, лицензия, персональные данные, авторские права), номер заказа и email аккаунта для ускорения проверки.',
                    ],
                ]
                : [
                    [
                        'question' => 'How does purchasing a digital product work?',
                        'answer' => 'Choose a product, complete checkout, and receive file/key access after payment confirmation. Some payment methods may take additional confirmation time.',
                    ],
                    [
                        'question' => 'When do I get download access?',
                        'answer' => 'Usually immediately after successful payment. If access is missing, check your email and profile orders page, then contact support with your order ID.',
                    ],
                    [
                        'question' => 'What if I did not receive the delivery email?',
                        'answer' => 'Check Spam/Promotions folders, verify your account email, and confirm order status. If still missing, contact support and access can be reissued after payment verification.',
                    ],
                    [
                        'question' => 'Can I use a product on multiple websites?',
                        'answer' => 'It depends on the specific license type for that product. By default, licenses are limited to the declared use case. Always check product card details.',
                    ],
                    [
                        'question' => 'Can I resell or redistribute purchased files?',
                        'answer' => 'No. Resale, redistribution, public source sharing, and transferring license/files to third parties without written permission are prohibited.',
                    ],
                    [
                        'question' => 'Are refunds available for digital products?',
                        'answer' => 'Because delivery is digital, refunds are generally unavailable after access is granted. Exceptions may apply for proven critical defects that cannot be fixed in a reasonable timeframe, or where required by law.',
                    ],
                    [
                        'question' => 'What is included in support?',
                        'answer' => 'Support covers installation/activation guidance and confirmed bugs. Custom development, integrations, and bespoke work are outside default support unless separately agreed.',
                    ],
                    [
                        'question' => 'How does the Demo section on product pages work?',
                        'answer' => 'If enabled by the seller, product pages show a demo URL and optional test credentials. Demos are for evaluation and may have limited functionality.',
                    ],
                    [
                        'question' => 'Is payment secure on this store?',
                        'answer' => 'Payments are handled by connected providers. Card details are not stored by the store itself. Use only official domains and HTTPS connections.',
                    ],
                    [
                        'question' => 'What account data is stored?',
                        'answer' => 'We store data necessary for service operation: email, order history, security logs, and session preferences (language/currency). See Privacy Policy for full details.',
                    ],
                    [
                        'question' => 'Can I request account and personal data deletion?',
                        'answer' => 'Yes, submit a request via support. Some records may be retained longer where required by law (e.g., accounting or anti-fraud obligations).',
                    ],
                    [
                        'question' => 'Is there an API for developers?',
                        'answer' => 'Yes. Your profile includes API token management and endpoint examples. Keep tokens private and ensure lawful processing under Terms and Privacy obligations.',
                    ],
                    [
                        'question' => 'What should I do if my API token is leaked?',
                        'answer' => 'Immediately revoke/regenerate the token in your profile, rotate credentials in all integrations, and review request logs for suspicious activity.',
                    ],
                    [
                        'question' => 'Where can I send legal or compliance requests?',
                        'answer' => 'Use the contact page and specify request type (payment, license, privacy rights, copyright), your order ID, and account email for faster resolution.',
                    ],
                ];
        }

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
