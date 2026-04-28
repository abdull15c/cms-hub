<?php
$isRu = ($langCode ?? 'ru') === 'ru';
$items = $isRu
    ? [
        ['title' => '1. Принятие условий', 'text' => 'Используя сайт, создавая аккаунт и/или оформляя заказ, вы подтверждаете согласие с настоящими условиями. Если вы не согласны с условиями, пожалуйста, не используйте сервис.'],
        ['title' => '2. Предмет сервиса', 'text' => 'Сайт предоставляет доступ к цифровым товарам (шаблоны, скрипты, плагины, исходники, сопутствующие материалы) и связанным услугам по настройке/поддержке, если это явно указано в карточке товара или отдельном соглашении.'],
        ['title' => '3. Регистрация и безопасность аккаунта', 'text' => 'Пользователь обязан предоставлять актуальные данные, хранить доступ к аккаунту в безопасности и незамедлительно уведомлять о подозрительной активности. Ответственность за действия под своей учётной записью несёт владелец аккаунта.'],
        ['title' => '4. Лицензия и права использования', 'text' => 'Если не указано иное, предоставляется ограниченная, неисключительная, непередаваемая лицензия на использование цифрового товара в рамках разрешённого сценария. Запрещены перепродажа, публичное распространение, перепаковка, публикация исходников и передача лицензии третьим лицам без письменного разрешения правообладателя.'],
        ['title' => '5. Интеллектуальная собственность', 'text' => 'Все права на контент, код, дизайн, базу данных и иные объекты ИС принадлежат правообладателям. Товарные знаки, логотипы и названия сохраняют права их владельцев.'],
        ['title' => '6. Платежи и налоги', 'text' => 'Оплата обрабатывается подключёнными платёжными провайдерами. Итоговая стоимость, валюта и применённые скидки отображаются при оформлении. Пользователь самостоятельно несёт обязательства по локальным налогам и сборам, если они применимы.'],
        ['title' => '7. Доставка цифрового товара', 'text' => 'Доставка осуществляется в электронном виде (доступ к загрузке, ключ, активационные данные и т.п.) после подтверждения оплаты. Срок и формат зависят от типа товара и платёжного провайдера.'],
        ['title' => '8. Возвраты и отмены', 'text' => 'С учётом цифровой природы товаров возврат обычно не применяется после предоставления доступа к файлам/ключам. Исключения: подтверждённая критическая неисправность, которую невозможно устранить в разумный срок, либо иные случаи, прямо установленные обязательным законодательством.'],
        ['title' => '9. Поддержка и SLA', 'text' => 'Базовая поддержка включает вопросы по установке, активации и подтверждённым дефектам товара. Доработка под индивидуальные требования, интеграции и консалтинг выполняются только по отдельной договорённости.'],
        ['title' => '10. Запрещённое использование', 'text' => 'Запрещены попытки взлома, обхода лицензий, парсинг/скрейпинг без разрешения, загрузка вредоносного контента, злоупотребление API, DDoS-активность, а также любое использование, нарушающее закон или права третьих лиц.'],
        ['title' => '11. Ограничение ответственности', 'text' => 'Сервис и цифровые товары предоставляются «как есть», если иное не предусмотрено обязательным правом. В максимально допустимой законом степени администрация не отвечает за косвенные убытки, упущенную выгоду, простои бизнеса, потерю данных или несовместимость с инфраструктурой пользователя.'],
        ['title' => '12. Приостановка доступа', 'text' => 'Администрация вправе ограничить или прекратить доступ к аккаунту при нарушении условий, злоупотреблении платёжными механизмами, риске безопасности либо по требованию закона.'],
        ['title' => '13. Изменение условий', 'text' => 'Условия могут обновляться. Новая версия вступает в силу с момента публикации на сайте, если не указано иное. Продолжение использования сервиса после обновления означает согласие с новой редакцией.'],
        ['title' => '14. Применимое право и споры', 'text' => 'К отношениям сторон применяется право, определённое обязательными нормами и юрисдикцией правообладателя/оператора сервиса. Споры решаются путём переговоров, а при недостижении соглашения — в компетентном суде по правилам применимого права.'],
    ]
    : [
        ['title' => '1. Acceptance of terms', 'text' => 'By using the website, creating an account, and/or placing an order, you agree to these terms. If you do not agree, you must stop using the service.'],
        ['title' => '2. Service scope', 'text' => 'The website provides access to digital goods (themes, scripts, plugins, source code, related assets) and optional support/setup services where explicitly stated in product pages or separate agreements.'],
        ['title' => '3. Account and security', 'text' => 'You must provide accurate account information, keep credentials secure, and promptly report suspicious access. You are responsible for activity performed under your account.'],
        ['title' => '4. License and permitted use', 'text' => 'Unless otherwise stated, you receive a limited, non-exclusive, non-transferable license for the intended use case. Resale, redistribution, repackaging, public code sharing, or transferring the license to third parties without written permission is prohibited.'],
        ['title' => '5. Intellectual property', 'text' => 'All rights to content, code, design, and databases remain with their respective owners. Trademarks, logos, and brand names remain the property of their holders.'],
        ['title' => '6. Payments and taxes', 'text' => 'Payments are processed by connected payment providers. Final price, currency, and discounts are shown during checkout. You are responsible for local taxes or fees where applicable.'],
        ['title' => '7. Digital delivery', 'text' => 'Delivery is electronic (download access, keys, activation data, etc.) after payment confirmation. Timing and format depend on product type and payment provider flow.'],
        ['title' => '8. Refunds and cancellations', 'text' => 'Due to the nature of digital goods, refunds are generally not available after delivery/access is granted, except for proven critical defects that cannot be resolved in a reasonable timeframe, or where required by mandatory law.'],
        ['title' => '9. Support scope', 'text' => 'Standard support includes installation/activation questions and confirmed product defects. Custom development, integrations, and consultancy are outside default support unless separately agreed.'],
        ['title' => '10. Prohibited conduct', 'text' => 'You may not attempt unauthorized access, bypass licensing, scrape without permission, upload malicious content, abuse APIs, perform DoS attacks, or violate laws/third-party rights.'],
        ['title' => '11. Limitation of liability', 'text' => 'The service and digital goods are provided “as is” unless mandatory law states otherwise. To the maximum extent permitted by law, the operator is not liable for indirect damages, lost profits, business interruption, data loss, or infrastructure incompatibility.'],
        ['title' => '12. Suspension or termination', 'text' => 'Access may be restricted or terminated for policy violations, payment abuse, security risks, or legal requirements.'],
        ['title' => '13. Changes to terms', 'text' => 'These terms may be updated. The current version becomes effective upon publication unless otherwise stated. Continued use after updates means acceptance of the revised terms.'],
        ['title' => '14. Governing law and disputes', 'text' => 'The relationship is governed by applicable mandatory law and the operator’s jurisdiction framework. Disputes should be attempted in good faith first, then resolved by the competent court where required.'],
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
        <p class="legal-subtitle mb-0"><?= htmlspecialchars($isRu ? 'Полная редакция условий: лицензии, оплата, возвраты, ответственность, ограничения и правовые положения для цифрового маркетплейса.' : 'Full terms covering licensing, payments, refunds, liability, restrictions, and legal provisions for a digital marketplace.') ?></p>
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
