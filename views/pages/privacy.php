<?php
$isRu = ($langCode ?? 'ru') === 'ru';
$items = $isRu
    ? [
        ['title' => '1. Какие данные мы храним', 'text' => 'Магазин хранит email, историю покупок и служебные данные, необходимые для выдачи цифровых товаров и доступа к аккаунту.'],
        ['title' => '2. Cookies и сессии', 'text' => 'Cookies используются для авторизации, языка интерфейса, валюты и стабильной работы пользовательской сессии.'],
        ['title' => '3. Платёжные провайдеры', 'text' => 'Для завершения оплаты отдельные данные передаются подключённым платёжным сервисам. Платёжные карты в системе магазина не хранятся.'],
        ['title' => '4. Безопасность', 'text' => 'Пароли хранятся в защищённом виде, а доступ к цифровым товарам и административной части ограничивается внутренними проверками и настройками безопасности.'],
    ]
    : [
        ['title' => '1. What data we store', 'text' => 'The store keeps email, purchase history and service data required for digital delivery and account access.'],
        ['title' => '2. Cookies and sessions', 'text' => 'Cookies are used for authentication, interface language, currency selection and overall session stability.'],
        ['title' => '3. Payment providers', 'text' => 'Some data is shared with connected payment providers to complete transactions. Payment card details are not stored inside the store itself.'],
        ['title' => '4. Security', 'text' => 'Passwords are stored securely and access to digital products and the admin area is restricted through internal checks and security settings.'],
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
        <span class="privacy-kicker"><i class="fa-solid fa-user-shield"></i> <?= htmlspecialchars($isRu ? 'Приватность' : 'Privacy') ?></span>
        <h1 class="privacy-title"><?= htmlspecialchars($isRu ? 'Политика конфиденциальности' : 'Privacy policy') ?></h1>
        <p class="privacy-subtitle mb-0"><?= htmlspecialchars($isRu ? 'Как витрина работает с данными аккаунта, cookies и платёжной информацией.' : 'How the storefront handles account data, cookies and payment-related information.') ?></p>
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
