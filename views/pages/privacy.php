<?php
$isRu = ($langCode ?? 'ru') === 'ru';
$items = $isRu
    ? [
        ['title' => '1. Роли и определения', 'text' => 'Оператор сервиса выступает контролёром/оператором персональных данных в объёме, предусмотренном применимым законодательством. Пользователь — субъект данных, использующий сайт, аккаунт и цифровые товары.'],
        ['title' => '2. Какие данные собираются', 'text' => 'Мы можем обрабатывать email, имя/ник, данные аккаунта, историю заказов и платежей, технические данные (IP, user-agent, сессия), логи безопасности, обращения в поддержку, настройки языка/валюты и данные взаимодействия с сайтом.'],
        ['title' => '3. Цели обработки', 'text' => 'Данные используются для регистрации и аутентификации, исполнения договора купли цифровых товаров, доставки файлов/ключей, предотвращения мошенничества, поддержки пользователей, аналитики качества сервиса, а также соблюдения обязательств по закону.'],
        ['title' => '4. Правовые основания', 'text' => 'Основания обработки включают исполнение договора, законные интересы оператора (безопасность, стабильность, предотвращение злоупотреблений), согласие пользователя (где требуется), а также соблюдение правовых обязательств.'],
        ['title' => '5. Cookies и сессии', 'text' => 'Cookies и подобные технологии применяются для входа в аккаунт, защиты сессии, запоминания языка/валюты, работы корзины/чекаута и технической аналитики. Отключение cookies может повлиять на функциональность сайта.'],
        ['title' => '6. Платёжные сервисы и внешние обработчики', 'text' => 'Для оплаты и выполнения транзакций часть данных передаётся внешним платёжным провайдерам и инфраструктурным сервисам. Данные банковских карт не хранятся в системе магазина, если иное явно не указано.'],
        ['title' => '7. Сроки хранения', 'text' => 'Данные хранятся только на период, необходимый для целей обработки: активный аккаунт, исполнение обязательств, соблюдение бухгалтерских/налоговых требований, урегулирование споров и обеспечение безопасности.'],
        ['title' => '8. Передача и трансграничная обработка', 'text' => 'При использовании внешних сервисов обработка может выполняться в разных юрисдикциях. Мы применяем разумные меры для выбора надёжных поставщиков и защиты данных при передаче.'],
        ['title' => '9. Меры безопасности', 'text' => 'Используются организационные и технические меры: хэширование паролей, контроль доступа, валидация запросов, журналирование критических действий, ограничение попыток, TLS/HTTPS и иные меры в рамках текущей архитектуры.'],
        ['title' => '10. Права пользователя', 'text' => 'Пользователь может запрашивать доступ, уточнение, исправление, удаление данных (где применимо), ограничение обработки, отзыв согласия и иные права, предусмотренные применимым законодательством.'],
        ['title' => '11. Дети и возрастные ограничения', 'text' => 'Сервис не предназначен для лиц, не достигших возраста, разрешённого местным законодательством для самостоятельного заключения цифровых сделок и согласия на обработку данных.'],
        ['title' => '12. Обращения по данным', 'text' => 'По вопросам обработки персональных данных, безопасности и реализации прав пользователя обращайтесь через страницу контактов или официальный контактный email, указанный на сайте.'],
        ['title' => '13. Обновления политики', 'text' => 'Политика может обновляться при изменении функционала, законодательства или процессов обработки. Актуальная версия действует с момента публикации на сайте.'],
    ]
    : [
        ['title' => '1. Roles and definitions', 'text' => 'The service operator acts as a data controller/operator within the scope required by applicable law. The user is the data subject using the website, account, and digital products.'],
        ['title' => '2. Data we collect', 'text' => 'We may process email, display name, account data, order/payment history, technical identifiers (IP, user-agent, session), security logs, support requests, language/currency preferences, and usage analytics.'],
        ['title' => '3. Processing purposes', 'text' => 'Data is processed for account access, contract performance (digital product delivery), fraud prevention, service support, quality analytics, and compliance with legal obligations.'],
        ['title' => '4. Legal bases', 'text' => 'Processing may rely on contract performance, legitimate interests (security and abuse prevention), consent where required, and compliance with statutory obligations.'],
        ['title' => '5. Cookies and session technologies', 'text' => 'Cookies and similar technologies are used for login/session security, language/currency preferences, checkout flow, and technical analytics. Disabling cookies may limit functionality.'],
        ['title' => '6. Payment processors and third parties', 'text' => 'Certain data is shared with payment processors and infrastructure providers to complete transactions and operate the platform. Payment card details are not stored by the store unless explicitly stated otherwise.'],
        ['title' => '7. Data retention', 'text' => 'Data is retained only as long as necessary for stated purposes: active account operation, legal/accounting obligations, dispute resolution, and security controls.'],
        ['title' => '8. International transfers', 'text' => 'When external services are used, processing may occur across jurisdictions. We apply reasonable safeguards when selecting providers and transferring data.'],
        ['title' => '9. Security measures', 'text' => 'Reasonable technical and organizational controls are implemented, including password hashing, access controls, request validation, logging of critical actions, rate limiting, and encrypted transport where available.'],
        ['title' => '10. Your rights', 'text' => 'Depending on applicable law, you may request access, correction, deletion, restriction, objection, withdrawal of consent, and other legally granted rights.'],
        ['title' => '11. Children and age limits', 'text' => 'The service is not intended for users below the legal age required in their jurisdiction to enter digital transactions or provide valid consent for data processing.'],
        ['title' => '12. Data contact requests', 'text' => 'For privacy/security requests and data rights inquiries, contact us via the support contact page or the official contact email listed on the website.'],
        ['title' => '13. Policy updates', 'text' => 'This policy may be updated due to feature changes, legal requirements, or processing updates. The latest published version on the website is the effective one.'],
    ];
?>

<style>
    .privacy-shell,.privacy-card,.privacy-item{position:relative}
    .privacy-card,.privacy-item{background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));border:1px solid var(--surface-border);border-radius:28px;box-shadow:0 18px 42px rgba(0,0,0,.22)}
    .privacy-card{padding:34px;background:radial-gradient(circle at top right,var(--primary-soft),transparent 30%),radial-gradient(circle at bottom left,var(--secondary-soft),transparent 36%),linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015))}
    .privacy-kicker{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:.45rem .85rem;background:var(--badge-bg);color:var(--badge-text);font-size:.8rem;text-transform:uppercase;letter-spacing:.08em}
    .privacy-title{color:#fff;font-size:clamp(2rem,4vw,3.2rem);line-height:1.04;letter-spacing:-.045em;margin:18px 0 14px}
    .privacy-subtitle,.privacy-item p{color:var(--muted-text);line-height:1.75}
    .privacy-grid{display:grid;gap:16px;margin-top:24px}
    .privacy-item{padding:22px}
    .privacy-item h2{color:#fff;font-size:1.1rem;margin-bottom:10px}
    @media (max-width:767.98px){.privacy-card,.privacy-item{border-radius:22px}.privacy-card,.privacy-item{padding:24px}}
</style>

<div class="container py-4 py-lg-5 privacy-shell">
    <section class="privacy-card">
        <span class="privacy-kicker"><?= $iconSvg('fa-user-shield') ?> <?= htmlspecialchars($isRu ? 'Приватность' : 'Privacy') ?></span>
        <h1 class="privacy-title"><?= htmlspecialchars($isRu ? 'Политика конфиденциальности' : 'Privacy policy') ?></h1>
        <p class="privacy-subtitle mb-0"><?= htmlspecialchars($isRu ? 'Полная политика обработки данных: состав данных, цели, правовые основания, сроки хранения, безопасность и права пользователя.' : 'Comprehensive privacy coverage: data categories, purposes, legal basis, retention, security, and user rights.') ?></p>
    </section>

    <div class="privacy-grid">
        <?php foreach ($items as $item): ?>
            <article class="privacy-item">
                <h2><?= htmlspecialchars($item['title']) ?></h2>
                <p class="mb-0"><?= htmlspecialchars($item['text']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</div>
