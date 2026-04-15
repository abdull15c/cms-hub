<?php
$isRu = ($langCode ?? 'ru') === 'ru';
$items = $isRu
    ? [
        ['title' => '1. Общие положения', 'text' => 'Покупая продукты магазина, вы соглашаетесь с правилами использования, лицензирования и цифровой выдачи файлов.'],
        ['title' => '2. Лицензия', 'text' => 'Если не указано иное, продукты продаются как цифровые товары для использования в рамках заявленного сценария. Перепродажа, публичное распространение и передача исходников третьим лицам запрещены.'],
        ['title' => '3. Возвраты', 'text' => 'Из-за цифрового характера товаров возврат возможен только в случае подтверждённой неработоспособности продукта, которую нельзя устранить поддержкой.'],
        ['title' => '4. Поддержка', 'text' => 'Поддержка покрывает базовые вопросы по запуску и обнаруженные баги. Индивидуальная кастомизация и доработка под заказ не входят автоматически.'],
    ]
    : [
        ['title' => '1. General terms', 'text' => 'By purchasing products from the store, you agree to the rules for usage, licensing and digital file delivery.'],
        ['title' => '2. License', 'text' => 'Unless stated otherwise, products are sold as digital goods for the intended usage scope. Resale, public redistribution and sharing source files with third parties are prohibited.'],
        ['title' => '3. Refunds', 'text' => 'Because these are digital products, refunds are only available if the product is proven defective and support cannot resolve the issue.'],
        ['title' => '4. Support', 'text' => 'Support covers launch-related questions and confirmed bugs. Custom implementation or bespoke adaptation is not automatically included.'],
    ];
?>

<style>
    .legal-shell,.legal-card,.legal-item{position:relative}
    .legal-card,.legal-item{background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));border:1px solid var(--surface-border);border-radius:28px;box-shadow:0 18px 42px rgba(0,0,0,.22)}
    .legal-card{padding:34px;background:radial-gradient(circle at top right,var(--primary-soft),transparent 30%),radial-gradient(circle at bottom left,var(--secondary-soft),transparent 36%),linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015))}
    .legal-kicker{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:.45rem .85rem;background:var(--badge-bg);color:var(--badge-text);font-size:.8rem;text-transform:uppercase;letter-spacing:.08em}
    .legal-title{color:#fff;font-size:clamp(2rem,4vw,3.2rem);line-height:1.04;letter-spacing:-.045em;margin:18px 0 14px}
    .legal-subtitle,.legal-item p,.legal-updated{color:var(--muted-text);line-height:1.75}
    .legal-grid{display:grid;gap:16px;margin-top:24px}
    .legal-item{padding:22px}
    .legal-item h2{color:#fff;font-size:1.1rem;margin-bottom:10px}
    @media (max-width:767.98px){.legal-card,.legal-item{border-radius:22px}.legal-card,.legal-item{padding:24px}}
</style>

<div class="container py-4 py-lg-5 legal-shell">
    <section class="legal-card">
        <span class="legal-kicker"><?= $iconSvg('fa-scale-balanced') ?> <?= htmlspecialchars($isRu ? 'Условия' : 'Terms') ?></span>
        <h1 class="legal-title"><?= htmlspecialchars($isRu ? 'Условия использования магазина' : 'Terms of service') ?></h1>
        <p class="legal-subtitle mb-0"><?= htmlspecialchars($isRu ? 'Краткие правила по лицензиям, возвратам, поддержке и работе с digital products на витрине.' : 'A concise overview of licensing, refunds, support and the use of digital products on the storefront.') ?></p>
    </section>

    <div class="legal-grid">
        <?php foreach ($items as $item): ?>
            <article class="legal-item">
                <h2><?= htmlspecialchars($item['title']) ?></h2>
                <p class="mb-0"><?= htmlspecialchars($item['text']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="legal-updated mt-4"><?= htmlspecialchars($isRu ? 'Обновлено:' : 'Last updated:') ?> <?= date('Y-m-d') ?></div>
</div>
