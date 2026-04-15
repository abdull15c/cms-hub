<?php
namespace Src\Controllers;

use Config\Database;
use Src\Services\SessionService;
use Src\Services\SettingsService;

class PageController extends Controller
{
    public function terms()
    {
        $lang = SessionService::get('lang', 'ru');
        $isRu = $lang === 'ru';
        $siteTitle = SettingsService::get('site_title') ?: 'CMS-HUB';

        $this->view('pages/terms', [
            'page_meta' => [
                'title' => ($isRu ? 'Условия использования' : 'Terms of Service') . ' | ' . $siteTitle,
                'description' => $isRu
                    ? 'Условия использования магазина, лицензии, возвраты и правила работы с digital products.'
                    : 'Store terms of service, licensing, refunds and rules for working with digital products.',
                'keywords' => $this->seoKeywords([
                    $isRu ? 'условия использования' : 'terms of service',
                    $isRu ? 'лицензии и возвраты' : 'licenses and refunds',
                    'digital products',
                ]),
                'canonical' => $this->currentUrl(['lang' => $lang], ['preview_theme']),
                'alternates' => [
                    'ru' => $this->currentUrl(['lang' => 'ru'], ['preview_theme']),
                    'en' => $this->currentUrl(['lang' => 'en'], ['preview_theme']),
                ],
            ],
        ]);
    }

    public function privacy()
    {
        $lang = SessionService::get('lang', 'ru');
        $isRu = $lang === 'ru';
        $siteTitle = SettingsService::get('site_title') ?: 'CMS-HUB';

        $this->view('pages/privacy', [
            'page_meta' => [
                'title' => ($isRu ? 'Политика конфиденциальности' : 'Privacy Policy') . ' | ' . $siteTitle,
                'description' => $isRu
                    ? 'Как магазин обрабатывает данные пользователей, email, cookies и платёжную информацию.'
                    : 'How the store handles user data, email, cookies and payment-related information.',
                'keywords' => $this->seoKeywords([
                    $isRu ? 'политика конфиденциальности' : 'privacy policy',
                    $isRu ? 'обработка данных' : 'data handling',
                    'cookies',
                ]),
                'canonical' => $this->currentUrl(['lang' => $lang], ['preview_theme']),
                'alternates' => [
                    'ru' => $this->currentUrl(['lang' => 'ru'], ['preview_theme']),
                    'en' => $this->currentUrl(['lang' => 'en'], ['preview_theme']),
                ],
            ],
        ]);
    }

    public function contact()
    {
        $this->renderContactPage([
            'captcha_q' => $this->nextCaptchaQuestion(),
        ]);
    }

    public function sendMessage()
    {
        $this->verifyCsrf();
        $lang = SessionService::get('lang', 'ru');
        $ans = intval($_POST['captcha'] ?? -1);

        if ($ans !== (int)SessionService::get('captcha_res', -1)) {
            $this->renderContactPage([
                'error' => 'Incorrect Captcha',
                'captcha_q' => $this->nextCaptchaQuestion(),
            ]);
            return;
        }

        $name = htmlspecialchars((string)($_POST['name'] ?? ''));
        $email = htmlspecialchars((string)($_POST['email'] ?? ''));
        $msg = htmlspecialchars((string)($_POST['message'] ?? ''));

        Database::connect()->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)")
            ->execute([$name, $email, $msg]);

        $this->redirect('/page/contact?sent=1&lang=' . rawurlencode($lang));
    }

    private function renderContactPage(array $data = []): void
    {
        $lang = SessionService::get('lang', 'ru');
        $isRu = $lang === 'ru';
        $siteTitle = SettingsService::get('site_title') ?: 'CMS-HUB';
        $contactEmail = SettingsService::get('contact_email');
        $canonical = $this->currentUrl(['lang' => $lang], ['preview_theme', 'sent']);

        $pageMeta = [
            'title' => ($isRu ? 'Связаться с нами' : 'Contact us') . ' | ' . $siteTitle,
            'description' => $isRu
                ? 'Свяжитесь с магазином по вопросам покупки, лицензий, поддержки и работы с digital products.'
                : 'Contact the store about purchases, licenses, support and digital products.',
            'keywords' => $this->seoKeywords([
                $isRu ? 'контакты магазина' : 'store contact',
                $isRu ? 'поддержка и лицензии' : 'support and licenses',
                'digital products',
            ]),
            'canonical' => $canonical,
            'alternates' => [
                'ru' => $this->currentUrl(['lang' => 'ru'], ['preview_theme', 'sent']),
                'en' => $this->currentUrl(['lang' => 'en'], ['preview_theme', 'sent']),
            ],
            'structured_data' => [[
                '@context' => 'https://schema.org',
                '@type' => 'ContactPage',
                'name' => $isRu ? 'Контакты' : 'Contact',
                'url' => $canonical,
                'inLanguage' => $lang,
            ]],
        ];

        if ($contactEmail !== '') {
            $pageMeta['structured_data'][0]['mainEntity'] = [
                '@type' => 'Organization',
                'name' => $siteTitle,
                'email' => $contactEmail,
            ];
        }

        $this->view('pages/contact', array_merge($data, [
            'contact_email' => $contactEmail,
            'page_meta' => $pageMeta,
        ]));
    }

    private function nextCaptchaQuestion(): string
    {
        $a = rand(1, 10);
        $b = rand(1, 10);
        SessionService::set('captcha_res', $a + $b);
        return $a . ' + ' . $b;
    }
}
