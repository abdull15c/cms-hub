<?php
$currentLanguage = (string)($langCode ?? 'ru');
$publicUrl = static function (string $path = '/', array $params = []) use ($currentLanguage) {
    $normalizedPath = $path === '/' ? '' : $path;
    $queryString = http_build_query(array_merge(['lang' => $currentLanguage], $params));
    return BASE_URL . $normalizedPath . ($queryString !== '' ? '?' . $queryString : '');
};
$isRu = $currentLanguage === 'ru';
$instantLabel = $isRu ? 'Мгновенно' : 'Instant';
$digitalLabel = $isRu ? 'Digital' : 'Digital';
$licensedLabel = $isRu ? 'Лицензия' : 'Licensed';
$saleLabel = $isRu ? 'Скидка' : 'Sale';
$themeSlug = (string)($theme['slug'] ?? 'dark-tech');
$homeVariant = (string)($theme['home_variant'] ?? 'tech-grid');
$homeVariantClass = preg_replace('/[^a-z0-9-]+/', '-', strtolower($homeVariant)) ?: 'tech-grid';
$storefrontShellClass = 'storefront-shell storefront-shell--' . $homeVariantClass;
$statsCaption = $isRu ? 'Кратко о витрине' : 'Storefront snapshot';
$statsLead = $isRu ? 'Фокус на быстрый запуск и готовые решения' : 'Built around ready-made solutions and fast launch';
$previewTitle = '';
$previewText = '';
$previewRibbon = '';
$previewItems = [];

if ($homeVariant === 'luxe-editorial') {
    $statsCaption = $isRu ? 'Premium-витрина' : 'Premium storefront';
    $statsLead = $isRu ? 'Для дорогих офферов, услуг и готовых решений под клиента' : 'Built for high-ticket offers, services and client-ready solutions';
    $previewTitle = $isRu ? 'Кураторская подача для premium digital-продуктов' : 'Curated presentation for premium digital products';
    $previewText = $isRu ? 'Подходит для готовых сайтов, агентских пакетов, брендированной кастомизации и high-ticket продажи.' : 'Fits ready-made sites, agency packs, branded customization and high-ticket delivery.';
    $previewRibbon = $isRu ? 'Подача с premium-акцентом' : 'Premium-first positioning';
    $previewItems = [
        ['title' => $isRu ? 'Готовые сайты' : 'Ready websites', 'text' => $isRu ? 'Запуск под клиента' : 'Client launch'],
        ['title' => $isRu ? 'Custom build' : 'Custom build', 'text' => $isRu ? 'Доработка под бренд' : 'Brand refinement'],
        ['title' => $isRu ? 'Сопровождение' : 'Support layer', 'text' => $isRu ? 'Настройка и запуск' : 'Setup and delivery'],
    ];
} elseif ($homeVariant === 'ocean-catalog') {
    $statsCaption = $isRu ? 'Спокойный каталог' : 'Calm catalog';
    $statsLead = $isRu ? 'Для маркетов, подписок, сервисов и широких витрин' : 'Built for markets, memberships, services and wide storefronts';
    $previewTitle = $isRu ? 'Мягкая и понятная витрина для длинного каталога' : 'A softer storefront for wide catalogs and long-term offers';
    $previewText = $isRu ? 'Хорошо работает там, где важны навигация, доверие, услуги и понятный каталог без перегруза.' : 'Works well where navigation, trust, services and calm catalog browsing matter most.';
    $previewRibbon = $isRu ? 'Фокус на навигацию и каталог' : 'Focused on navigation and browsing';
    $previewItems = [
        ['title' => $isRu ? 'Каталог' : 'Catalog', 'text' => $isRu ? 'Удобный просмотр' : 'Easy browsing'],
        ['title' => $isRu ? 'Услуги' : 'Services', 'text' => $isRu ? 'Подключение и помощь' : 'Setup and support'],
        ['title' => $isRu ? 'Подписки' : 'Retention', 'text' => $isRu ? 'Апдейты и допы' : 'Updates and add-ons'],
    ];
}
?>

<style>
    .storefront-shell {
        position: relative;
        z-index: 1;
        display: grid;
        gap: 12px;
    }

    .hero-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.24fr) minmax(320px, 0.76fr);
        gap: 24px;
        align-items: stretch;
    }

    .hero-panel {
        background:
            linear-gradient(160deg, rgba(255,255,255,0.05), rgba(255,255,255,0.015)),
            linear-gradient(180deg, rgba(0,242,234,0.03), transparent 35%);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 32px;
        padding: 42px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 28px 60px rgba(0, 0, 0, 0.25);
        display: flex;
        min-height: 100%;
    }

    .hero-panel::before {
        content: '';
        position: absolute;
        inset: auto auto -140px -100px;
        width: 340px;
        height: 340px;
        background: radial-gradient(circle, rgba(255,0,80,0.24) 0%, transparent 68%);
        pointer-events: none;
    }

    .hero-panel::after {
        content: '';
        position: absolute;
        inset: -150px -90px auto auto;
        width: 360px;
        height: 360px;
        background: radial-gradient(circle, rgba(0,242,234,0.18) 0%, transparent 72%);
        pointer-events: none;
    }

    .hero-panel-inner {
        position: relative;
        z-index: 1;
        display: grid;
        gap: 28px;
        width: 100%;
    }

    .hero-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.55rem 0.95rem;
        border-radius: 999px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.08);
        color: var(--badge-text);
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .hero-title {
        font-size: clamp(2.65rem, 5.3vw, 4.75rem);
        line-height: 0.98;
        letter-spacing: -0.04em;
        max-width: 11ch;
        margin: 20px 0 18px;
        color: #fff;
        text-wrap: balance;
    }

    .hero-title span {
        background: linear-gradient(90deg, var(--primary-neon), var(--secondary-neon));
        -webkit-background-clip: text;
        color: transparent;
    }

    .hero-subtitle {
        font-size: 1.02rem;
        line-height: 1.82;
        color: var(--muted-text);
        max-width: 640px;
        margin: 0;
    }

    .hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .hero-cta-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(90deg, var(--primary-neon), var(--secondary-neon));
        color: var(--button-text);
        border: none;
        border-radius: 18px;
        min-height: 54px;
        padding: 14px 22px;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 18px 34px var(--primary-soft);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .hero-cta-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 54px;
        border: 1px solid rgba(255,255,255,0.08);
        color: var(--text-main);
        border-radius: 18px;
        padding: 14px 22px;
        text-decoration: none;
        background: rgba(255,255,255,0.03);
        transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
    }

    .hero-cta-primary:hover,
    .hero-cta-secondary:hover {
        transform: translateY(-1px);
        text-decoration: none;
    }

    .hero-cta-primary:hover {
        box-shadow: 0 22px 38px var(--primary-soft);
    }

    .hero-cta-secondary:hover {
        border-color: rgba(255,255,255,0.14);
        background: rgba(255,255,255,0.05);
    }

    .trust-row {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .trust-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        padding: 10px 14px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        color: var(--text-main);
        font-size: 0.86rem;
    }

    .hero-foot {
        display: grid;
        gap: 18px;
    }

    .hero-search {
        display: flex;
        gap: 12px;
        align-items: stretch;
        padding: 12px;
        border-radius: 24px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
    }

    .hero-search input {
        flex: 1;
        min-height: 56px;
        background: rgba(9,17,29,0.86);
        border: 1px solid rgba(255,255,255,0.06);
        color: var(--text-main);
        border-radius: 18px;
        padding: 0 18px;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.02);
    }

    .hero-search button {
        min-width: 142px;
        border-radius: 18px;
        border: none;
        padding: 0 20px;
        font-weight: 800;
        background: linear-gradient(135deg, var(--primary-neon), #8ffcf6);
        color: var(--button-text);
        font-size: 0.96rem;
        box-shadow: 0 16px 28px var(--primary-soft);
    }

    .hero-side {
        display: grid;
        gap: 18px;
        grid-template-rows: minmax(0, 1fr) auto;
    }

    .preview-card,
    .stats-card,
    .section-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--card-border);
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 18px 40px rgba(0,0,0,0.18);
    }

    .preview-card {
        min-height: 286px;
        padding: 22px;
        display: grid;
        background:
            linear-gradient(160deg, rgba(255,255,255,0.05), rgba(255,255,255,0.01)),
            radial-gradient(circle at top right, var(--primary-soft), transparent 38%),
            radial-gradient(circle at bottom left, var(--secondary-soft), transparent 42%);
    }

    .preview-showcase {
        display: grid;
        gap: 18px;
        height: 100%;
        align-content: start;
        padding: 8px;
    }

    .preview-showcase-title {
        color: #fff;
        font-size: clamp(1.35rem, 2vw, 1.85rem);
        line-height: 1.15;
        letter-spacing: -0.03em;
        margin: 0;
    }

    .preview-showcase-text {
        color: var(--muted-text);
        line-height: 1.72;
        margin: 0;
    }

    .preview-stack-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .preview-stack-card {
        padding: 14px;
        border-radius: 18px;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.04);
        min-height: 118px;
        display: grid;
        align-content: start;
        gap: 8px;
    }

    .preview-stack-card strong {
        color: #fff;
        font-size: 0.96rem;
        line-height: 1.2;
    }

    .preview-stack-card span {
        color: var(--muted-text);
        font-size: 0.84rem;
        line-height: 1.55;
    }

    .preview-ribbon {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        justify-self: start;
        padding: 10px 14px;
        border-radius: 999px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.08);
        color: var(--text-main);
        font-size: 0.84rem;
        font-weight: 700;
    }

    .preview-browser {
        background: rgba(11, 15, 25, 0.82);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 22px;
        padding: 18px;
        display: grid;
        gap: 18px;
    }

    .preview-top {
        display: flex;
        gap: 8px;
    }

    .preview-top span {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: rgba(255,255,255,0.45);
    }

    .preview-hero {
        display: grid;
        gap: 14px;
    }

    .preview-line {
        height: 12px;
        border-radius: 999px;
        background: rgba(255,255,255,0.12);
    }

    .preview-line.short {
        width: 54%;
    }

    .preview-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .preview-block {
        border-radius: 18px;
        min-height: 104px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.05);
    }

    .preview-block.primary {
        background: linear-gradient(135deg, var(--primary-neon), rgba(255,255,255,0.12));
        opacity: 0.85;
    }

    .preview-block.secondary {
        background: linear-gradient(135deg, var(--secondary-neon), rgba(255,255,255,0.08));
        opacity: 0.78;
    }

    .stats-card {
        padding: 24px;
        display: grid;
        gap: 18px;
    }

    .stats-lead {
        color: #fff;
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    .stats-caption {
        color: var(--muted-text);
        font-size: 0.8rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }

    .stat-tile {
        padding: 18px;
        border-radius: 20px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.02);
    }

    .stat-value {
        color: #fff;
        font-size: 1.45rem;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .stat-label {
        margin-top: 6px;
        font-size: 0.85rem;
        color: var(--muted-text);
        line-height: 1.5;
    }

    .section-head {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: end;
        gap: 16px;
        margin: 48px 0 22px;
    }

    .hero-grid + .section-head,
    .filter-state + .section-head {
        margin-top: 6px;
    }

    .section-head h2 {
        color: #fff;
        font-size: clamp(1.9rem, 3vw, 2.7rem);
        letter-spacing: -0.035em;
        margin: 0;
    }

    .section-head p {
        margin: 8px 0 0;
        color: var(--muted-text);
        max-width: 680px;
        line-height: 1.72;
    }

    .catalog-pills {
        display: flex;
        flex-wrap: nowrap;
        gap: 12px;
        overflow: auto;
        padding-bottom: 8px;
    }

    .filter-state {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 24px;
        padding: 18px 20px;
        border-radius: 24px;
        border: 1px solid var(--surface-border);
        background: rgba(255,255,255,0.03);
    }

    .filter-state-label {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: #fff;
        font-weight: 600;
    }

    .filter-state-note {
        color: var(--muted-text);
        font-size: 0.92rem;
    }

    .catalog-pill {
        white-space: nowrap;
        text-decoration: none;
        border-radius: 999px;
        padding: 10px 18px;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.03);
        color: var(--muted-text);
        transition: 0.2s ease;
    }

    .catalog-pill:hover,
    .catalog-pill.active {
        background: var(--primary-neon);
        border-color: var(--primary-neon);
        color: var(--button-text);
        box-shadow: 0 10px 24px var(--primary-soft);
    }

    .product-card {
        position: relative;
        display: flex;
        flex-direction: column;
        background:
            linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.015)),
            linear-gradient(180deg, rgba(0,242,234,0.02), transparent 38%);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 30px;
        overflow: hidden;
        height: 100%;
        transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
    }

    .product-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto;
        height: 1px;
        background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,0.18), rgba(255,255,255,0));
        pointer-events: none;
    }

    .product-card:hover {
        transform: translateY(-6px);
        border-color: var(--primary-neon);
        box-shadow: 0 24px 44px rgba(0,0,0,0.24), 0 0 26px var(--primary-soft);
    }

    .product-cover {
        position: relative;
        min-height: 238px;
        background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01));
        border-bottom: 1px solid var(--card-border);
    }

    .product-cover a {
        display: block;
        overflow: hidden;
    }

    .product-cover img {
        width: 100%;
        height: 238px;
        object-fit: cover;
        display: block;
        transition: transform 0.35s ease;
    }

    .product-card:hover .product-cover img {
        transform: scale(1.04);
    }

    .product-cover-fallback {
        height: 238px;
        display: grid;
        place-items: center;
        color: var(--muted-text);
        background:
            radial-gradient(circle at top right, var(--primary-soft), transparent 32%),
            radial-gradient(circle at bottom left, var(--secondary-soft), transparent 35%),
            rgba(255,255,255,0.02);
    }

    .category-chip {
        position: absolute;
        border-radius: 999px;
        padding: 8px 12px;
        font-size: 0.8rem;
        backdrop-filter: blur(10px);
    }

    .sale-chip {
        position: absolute;
        right: 14px;
        top: 14px;
        border-radius: 999px;
        padding: 7px 11px;
        background: rgba(255, 0, 80, 0.16);
        border: 1px solid rgba(255, 0, 80, 0.30);
        color: #fff;
        font-size: 0.76rem;
        font-weight: 700;
    }

    .category-chip {
        left: 14px;
        top: 14px;
        background: var(--badge-bg);
        color: var(--badge-text);
        border: 1px solid transparent;
    }

    .product-body {
        padding: 22px 22px 24px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        flex: 1;
    }

    .product-price-row {
        display: flex;
        align-items: baseline;
        gap: 10px;
        flex-wrap: wrap;
    }

    .product-price-main {
        color: #fff;
        font-size: 1.45rem;
        font-weight: 800;
        letter-spacing: -0.04em;
    }

    .product-title {
        font-size: 1.12rem;
        line-height: 1.34;
        margin: 0;
    }

    .product-title a {
        color: #fff;
        text-decoration: none;
    }

    .product-desc {
        color: var(--muted-text);
        font-size: 0.92rem;
        line-height: 1.7;
        flex-grow: 1;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .product-footer {
        display: flex;
        justify-content: space-between;
        align-items: end;
        gap: 12px;
        margin-top: auto;
        padding-top: 18px;
        border-top: 1px solid var(--card-border);
    }

    .product-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .mini-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.06);
        color: var(--muted-text);
        font-size: 0.74rem;
    }

    .price-old {
        color: var(--muted-text);
        font-size: 0.86rem;
        text-decoration: line-through;
        opacity: 0.8;
    }

    .product-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 46px;
        text-decoration: none;
        color: var(--button-text);
        background: linear-gradient(135deg, var(--primary-neon), #86fbf4);
        border-radius: 999px;
        padding: 10px 16px;
        font-weight: 800;
        white-space: nowrap;
        box-shadow: 0 14px 24px var(--primary-soft);
    }

    .catalog-group-grid,
    .value-grid,
    .service-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
    }

    .catalog-group-card,
    .value-card,
    .service-card,
    .cta-card,
    .faq-card,
    .about-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--card-border);
        border-radius: 26px;
        padding: 24px;
        box-shadow: 0 18px 34px rgba(0,0,0,0.16);
    }

    .card-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--badge-text);
    }

    .value-icon {
        width: 52px;
        height: 52px;
        border-radius: 18px;
        display: grid;
        place-items: center;
        font-size: 1.1rem;
        margin-bottom: 16px;
        background: var(--badge-bg);
        color: var(--badge-text);
    }

    .value-card h3,
    .catalog-group-card h3,
    .service-card h3 {
        color: #fff;
        font-size: 1.08rem;
        margin-bottom: 10px;
    }

    .catalog-group-card p,
    .value-card p,
    .service-card p,
    .cta-card p {
        color: var(--muted-text);
        line-height: 1.7;
        margin-bottom: 0;
    }

    .about-card {
        display: grid;
        gap: 14px;
        background:
            radial-gradient(circle at top right, var(--primary-soft), transparent 30%),
            rgba(255,255,255,0.03);
    }

    .about-card h3 {
        color: #fff;
        font-size: clamp(1.5rem, 2.4vw, 2rem);
        margin: 0;
    }

    .about-card p {
        color: var(--muted-text);
        line-height: 1.82;
        margin: 0;
        max-width: 820px;
    }

    .faq-wrap {
        display: grid;
        gap: 14px;
    }

    .faq-card .accordion-button {
        background: transparent;
        color: #fff;
        box-shadow: none;
        padding: 0;
        font-weight: 700;
    }

    .faq-card .accordion-button:not(.collapsed) {
        color: var(--primary-neon);
    }

    .faq-card .accordion-button::after {
        filter: invert(1);
    }

    .faq-card .accordion-body {
        color: var(--muted-text);
        padding: 14px 0 0;
        line-height: 1.75;
    }

    .cta-card {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        background:
            radial-gradient(circle at top right, var(--primary-soft), transparent 32%),
            radial-gradient(circle at bottom left, var(--secondary-soft), transparent 35%),
            rgba(255,255,255,0.03);
    }

    .cta-card h3 {
        color: #fff;
        font-size: clamp(1.6rem, 3vw, 2.2rem);
        margin-bottom: 10px;
    }

    .storefront-shell--luxe-editorial .hero-grid {
        grid-template-columns: minmax(0, 1.06fr) minmax(360px, 0.94fr);
    }

    .storefront-shell--luxe-editorial .hero-panel {
        padding: 52px;
        border-radius: 38px;
        background:
            linear-gradient(160deg, rgba(255,255,255,0.07), rgba(255,255,255,0.02)),
            radial-gradient(circle at top left, rgba(224,166,74,0.18), transparent 34%),
            linear-gradient(180deg, rgba(249,115,22,0.05), transparent 48%);
    }

    .storefront-shell--luxe-editorial .hero-panel::before {
        background: radial-gradient(circle, rgba(249,115,22,0.20) 0%, transparent 68%);
    }

    .storefront-shell--luxe-editorial .hero-panel::after {
        background: radial-gradient(circle, rgba(224,166,74,0.22) 0%, transparent 72%);
    }

    .storefront-shell--luxe-editorial .hero-title {
        max-width: 12ch;
        font-size: clamp(3rem, 5.6vw, 5.3rem);
    }

    .storefront-shell--luxe-editorial .preview-card,
    .storefront-shell--luxe-editorial .stats-card,
    .storefront-shell--luxe-editorial .catalog-group-card,
    .storefront-shell--luxe-editorial .value-card,
    .storefront-shell--luxe-editorial .service-card,
    .storefront-shell--luxe-editorial .faq-card,
    .storefront-shell--luxe-editorial .about-card,
    .storefront-shell--luxe-editorial .cta-card {
        border-color: rgba(224,166,74,0.14);
        box-shadow: 0 24px 44px rgba(0,0,0,0.22);
    }

    .storefront-shell--luxe-editorial .product-card {
        background:
            linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0.018)),
            linear-gradient(180deg, rgba(224,166,74,0.06), transparent 42%);
        border-color: rgba(224,166,74,0.14);
    }

    .storefront-shell--luxe-editorial .product-card:hover {
        border-color: var(--primary-neon);
        box-shadow: 0 26px 46px rgba(0,0,0,0.26), 0 0 28px var(--primary-soft);
    }

    .storefront-shell--luxe-editorial .preview-showcase {
        gap: 20px;
    }

    .storefront-shell--luxe-editorial .preview-stack-card {
        background: rgba(35, 29, 24, 0.72);
        border-color: rgba(224,166,74,0.14);
    }

    .storefront-shell--luxe-editorial .preview-ribbon {
        background: rgba(224,166,74,0.10);
        border-color: rgba(224,166,74,0.16);
    }

    .storefront-shell--ocean-catalog .hero-grid {
        grid-template-columns: minmax(0, 1.12fr) minmax(340px, 0.88fr);
    }

    .storefront-shell--ocean-catalog .hero-panel {
        background:
            linear-gradient(160deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02)),
            radial-gradient(circle at top right, rgba(56,189,248,0.16), transparent 34%),
            linear-gradient(180deg, rgba(45,212,191,0.05), transparent 46%);
    }

    .storefront-shell--ocean-catalog .hero-panel::before {
        background: radial-gradient(circle, rgba(45,212,191,0.22) 0%, transparent 68%);
    }

    .storefront-shell--ocean-catalog .hero-panel::after {
        background: radial-gradient(circle, rgba(56,189,248,0.20) 0%, transparent 72%);
    }

    .storefront-shell--ocean-catalog .preview-card,
    .storefront-shell--ocean-catalog .stats-card,
    .storefront-shell--ocean-catalog .catalog-group-card,
    .storefront-shell--ocean-catalog .value-card,
    .storefront-shell--ocean-catalog .service-card,
    .storefront-shell--ocean-catalog .faq-card,
    .storefront-shell--ocean-catalog .about-card,
    .storefront-shell--ocean-catalog .cta-card {
        background: rgba(8,30,46,0.56);
    }

    .storefront-shell--ocean-catalog .product-card {
        background:
            linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.018)),
            linear-gradient(180deg, rgba(56,189,248,0.05), transparent 40%);
        border-color: rgba(56,189,248,0.12);
    }

    .storefront-shell--ocean-catalog .preview-stack-card {
        background: rgba(12, 37, 53, 0.72);
        border-color: rgba(56,189,248,0.14);
    }

    .storefront-shell--ocean-catalog .preview-ribbon {
        background: rgba(56,189,248,0.10);
        border-color: rgba(56,189,248,0.14);
    }

    .empty-state {
        padding: 48px 24px;
        text-align: center;
        border: 1px dashed var(--card-border);
        border-radius: 28px;
        background: rgba(255,255,255,0.02);
    }

    @media (max-width: 991.98px) {
        .hero-grid,
        .catalog-group-grid,
        .value-grid,
        .service-grid {
            grid-template-columns: 1fr;
        }

        .preview-stack-grid {
            grid-template-columns: 1fr;
        }

        .hero-panel {
            padding: 32px 28px;
        }

        .hero-title {
            max-width: none;
        }

        .hero-side {
            grid-template-rows: auto;
        }
    }

    @media (max-width: 767.98px) {
        .hero-panel,
        .preview-card,
        .stats-card,
        .catalog-group-card,
        .value-card,
        .service-card,
        .cta-card,
        .faq-card,
        .about-card {
            border-radius: 22px;
        }

        .hero-search {
            flex-direction: column;
        }

        .hero-search button {
            min-height: 52px;
            width: 100%;
        }

        .stats-grid {
            grid-template-columns: 1fr 1fr;
        }

        .product-footer {
            flex-direction: column;
            align-items: stretch;
        }

        .product-link {
            text-align: center;
        }

        .product-body {
            padding: 20px;
        }

        .section-head {
            margin: 36px 0 18px;
        }
    }
</style>

<div class="container py-4 py-lg-5 <?= htmlspecialchars($storefrontShellClass) ?>">
    <?php if ($plain_listing): ?>
        <section class="hero-grid mb-5" data-aos="fade-up">
            <div class="hero-panel">
                <div class="hero-panel-inner">
                    <div>
                        <span class="hero-eyebrow"><?= $iconSvg('fa-satellite-dish') ?> <?= htmlspecialchars($hero['eyebrow'] ?? 'Digital Storefront') ?></span>
                        <h1 class="hero-title"><?= htmlspecialchars($hero['title'] ?? '') ?></h1>
                        <p class="hero-subtitle"><?= htmlspecialchars($hero['subtitle'] ?? '') ?></p>
                    </div>

                    <div class="hero-foot">
                        <div class="hero-actions">
                            <a href="#product-grid" class="hero-cta-primary"><?= htmlspecialchars($hero['primary_cta'] ?? 'Browse catalog') ?></a>
                            <a href="#catalog-groups" class="hero-cta-secondary"><?= htmlspecialchars($hero['secondary_cta'] ?? 'What is inside') ?></a>
                        </div>

                        <div class="trust-row">
                            <?php foreach (($trust_badges ?? []) as $badge): ?>
                                <span class="trust-pill"><?= $iconSvg('fa-check') ?> <?= htmlspecialchars($badge) ?></span>
                            <?php endforeach; ?>
                        </div>

                        <form action="<?= BASE_URL ?>/" method="GET" class="hero-search">
                            <input type="hidden" name="lang" value="<?= htmlspecialchars($langCode ?? 'ru') ?>">
                            <input type="text" name="q" placeholder="<?= htmlspecialchars($t('search_placeholder', 'Search for scripts, themes...')) ?>" value="<?= htmlspecialchars($currentQ ?? '') ?>">
                            <button type="submit" aria-label="Search"><?= $iconSvg('fa-search', 'me-2') ?><?= htmlspecialchars($t('nav_search_submit', 'Search catalog')) ?></button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="hero-side">
                <div class="preview-card">
                    <?php if ($homeVariant === 'tech-grid'): ?>
                        <div class="preview-browser">
                            <div class="preview-top"><span></span><span></span><span></span></div>
                            <div class="preview-hero">
                                <div class="preview-line"></div>
                                <div class="preview-line short"></div>
                            </div>
                            <div class="preview-grid">
                                <div class="preview-block primary"></div>
                                <div class="preview-block"></div>
                                <div class="preview-block"></div>
                                <div class="preview-block secondary"></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="preview-showcase preview-showcase--<?= htmlspecialchars($homeVariantClass) ?>">
                            <span class="hero-eyebrow"><?= $iconSvg($homeVariant === 'luxe-editorial' ? 'fa-star' : 'fa-layer-group') ?> <?= htmlspecialchars($theme['name'] ?? 'Storefront Theme') ?></span>
                            <h3 class="preview-showcase-title"><?= htmlspecialchars($previewTitle) ?></h3>
                            <p class="preview-showcase-text"><?= htmlspecialchars($previewText) ?></p>
                            <div class="preview-stack-grid">
                                <?php foreach ($previewItems as $item): ?>
                                    <div class="preview-stack-card">
                                        <strong><?= htmlspecialchars($item['title'] ?? '') ?></strong>
                                        <span><?= htmlspecialchars($item['text'] ?? '') ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="preview-ribbon"><?= $iconSvg($homeVariant === 'luxe-editorial' ? 'fa-tag' : 'fa-chart-line') ?> <?= htmlspecialchars($previewRibbon) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="stats-card">
                    <div>
                        <div class="stats-caption"><?= htmlspecialchars($statsCaption) ?></div>
                        <div class="stats-lead"><?= htmlspecialchars($statsLead) ?></div>
                    </div>
                    <div class="stats-grid">
                        <?php foreach (($stats ?? []) as $stat): ?>
                            <div class="stat-tile">
                                <div class="stat-value"><?= htmlspecialchars($stat['value'] ?? '') ?></div>
                                <div class="stat-label"><?= htmlspecialchars($stat['label'] ?? '') ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($is_filtered_listing) && !empty($filter_label)): ?>
        <div class="filter-state" data-aos="fade-up">
            <div>
                <div class="filter-state-label">
                    <?= $iconSvg('fa-sliders') ?>
                    <span><?= htmlspecialchars($filter_label) ?></span>
                </div>
                <div class="filter-state-note">
                    <?= htmlspecialchars($isRu ? 'Каталог обновлён под выбранный запрос, категорию или страницу.' : 'The catalog has been updated for the selected query, category or page.') ?>
                </div>
            </div>
            <a href="<?= htmlspecialchars($clear_filters_url ?? $publicUrl('/')) ?>" class="hero-cta-secondary"><?= htmlspecialchars($isRu ? 'Сбросить фильтры' : 'Clear filters') ?></a>
        </div>
    <?php endif; ?>

    <?php if ($plain_listing): ?>
        <section id="catalog-groups" class="section-head" data-aos="fade-up">
            <div>
                <h2><?= htmlspecialchars($isRu ? 'Что доступно в каталоге' : 'What is available in the catalog') ?></h2>
                <p><?= htmlspecialchars($isRu ? 'Готовые сайты, standalone-скрипты, WordPress и DLE решения, а также услуги для быстрого запуска и кастомизации.' : 'Ready websites, standalone scripts, WordPress and DLE solutions, plus services for fast launch and customization.') ?></p>
            </div>
        </section>

        <section class="catalog-group-grid mb-5">
            <?php foreach (($catalog_groups ?? []) as $group): ?>
                <article class="catalog-group-card" data-aos="fade-up">
                    <div class="value-icon"><?= $iconSvg((string)($group['icon'] ?? 'fa-layer-group')) ?></div>
                    <div class="card-kicker"><?= htmlspecialchars($isRu ? 'Категория' : 'Category') ?></div>
                    <h3><?= htmlspecialchars($group['title'] ?? '') ?></h3>
                    <p><?= htmlspecialchars($group['text'] ?? '') ?></p>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <section class="section-head" data-aos="fade-up">
        <div>
            <h2><?= htmlspecialchars($section_heading ?? $t('home_explore', 'Explore')) ?></h2>
            <p><?= htmlspecialchars($section_subheading ?? $t('home_subtitle', 'Discover premium scripts & templates')) ?></p>
        </div>
    </section>

    <div class="catalog-pills mb-4" data-aos="fade-up" data-aos-delay="80">
        <a href="<?= htmlspecialchars($publicUrl('/')) ?>" class="catalog-pill <?= empty($currentCat) ? 'active' : '' ?>">
            <?= htmlspecialchars($t('cat_all', 'All')) ?>
        </a>
        <?php foreach ($categories as $c): ?>
            <a href="<?= htmlspecialchars($publicUrl('/', ['cat' => $c['id']])) ?>" class="catalog-pill <?= ((int)($currentCat ?? 0) === (int)$c['id']) ? 'active' : '' ?>">
                <?= htmlspecialchars($c['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <section id="product-grid" class="row g-4">
        <?php if (empty($products)): ?>
            <div class="col-12">
                <div class="empty-state">
                    <?= $iconSvg('fa-ghost', 'fa-3x text-secondary opacity-50 mb-3') ?>
                    <h3 class="text-white"><?= htmlspecialchars($t('empty_title', 'Nothing here yet')) ?></h3>
                    <p class="text-secondary mb-0"><?= htmlspecialchars($t('empty_desc', 'Try adjusting your filters.')) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php foreach ($products as $p): ?>
            <div class="col-md-6 col-xl-4" data-aos="fade-up">
                <?php
                    $productUrl = $publicUrl('/product/' . (int)$p['id']);
                    $cardIsOnSale = !empty($p['sale_price']) && !empty($p['sale_end']) && strtotime((string)$p['sale_end']) > time();
                    $displayPrice = $cardIsOnSale ? (float)$p['sale_price'] : (float)$p['price'];
                ?>
                <article class="product-card">
                    <div class="product-cover">
                        <?php if (!empty($p['thumbnail'])): ?>
                            <a href="<?= htmlspecialchars($productUrl) ?>">
                                <img src="<?= BASE_URL ?>/uploads/images/<?= htmlspecialchars($p['thumbnail']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy">
                            </a>
                        <?php else: ?>
                            <div class="product-cover-fallback">
                                <div class="text-center">
                                    <?= $iconSvg('fa-image', 'fa-2x mb-2') ?>
                                    <div><?= htmlspecialchars($t('product_no_preview', 'No Preview')) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <span class="category-chip"><?= htmlspecialchars($p['category_name'] ?? $t('home_category_code', 'CODE')) ?></span>
                        <?php if ($cardIsOnSale): ?>
                            <span class="sale-chip"><?= htmlspecialchars($saleLabel) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="product-body">
                        <div class="product-price-row">
                            <span class="product-price-main"><?= \Src\Services\CurrencyService::format($displayPrice) ?></span>
                            <?php if ($cardIsOnSale): ?><span class="price-old"><?= \Src\Services\CurrencyService::format((float)$p['price']) ?></span><?php endif; ?>
                        </div>
                        <h3 class="product-title">
                            <a href="<?= htmlspecialchars($productUrl) ?>">
                                <?= htmlspecialchars($p['title']) ?>
                            </a>
                        </h3>
                        <p class="product-desc"><?= htmlspecialchars(mb_strimwidth(strip_tags($p['description'] ?? ''), 0, 150, '...')) ?></p>

                        <div class="product-footer">
                            <div class="product-badges">
                                <span class="mini-badge"><?= $iconSvg('fa-bolt') ?> <?= htmlspecialchars($instantLabel) ?></span>
                                <span class="mini-badge"><?= $iconSvg('fa-code') ?> <?= htmlspecialchars($digitalLabel) ?></span>
                                <?php if (!empty($p['has_license'])): ?><span class="mini-badge"><?= $iconSvg('fa-key') ?> <?= htmlspecialchars($licensedLabel) ?></span><?php endif; ?>
                            </div>
                            <a class="product-link" href="<?= htmlspecialchars($productUrl) ?>">
                                <?= htmlspecialchars($t('btn_get', 'Get')) ?> <?= $iconSvg('fa-arrow-right', 'ms-1') ?>
                            </a>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </section>

    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="mt-5 d-flex justify-content-center">
            <nav>
                <ul class="pagination pagination-sm bg-transparent">
                    <?php
                        $page = $page ?? 1;
                        $qs = $_GET;
                        $prev = $page - 1;
                        $next = $page + 1;
                        $qsPrev = array_merge($qs, ['page' => $prev]);
                        $qsNext = array_merge($qs, ['page' => $next]);
                    ?>
                    <?php if ($prev > 0): ?>
                        <li class="page-item"><a class="page-link bg-transparent border-secondary text-white rounded-start-pill px-3" href="?<?= http_build_query($qsPrev) ?>"><?= htmlspecialchars($t('pagination_prev', 'Prev')) ?></a></li>
                    <?php endif; ?>
                    <li class="page-item disabled"><span class="page-link bg-transparent border-secondary text-secondary"><?= (int)$page ?></span></li>
                    <?php if ($next <= $totalPages): ?>
                        <li class="page-item"><a class="page-link bg-transparent border-secondary text-white rounded-end-pill px-3" href="?<?= http_build_query($qsNext) ?>"><?= htmlspecialchars($t('pagination_next', 'Next')) ?></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>

    <?php if ($plain_listing): ?>
        <section id="why-storefront" class="section-head" data-aos="fade-up">
            <div>
                <h2><?= htmlspecialchars($isRu ? 'Почему этот магазин удобен' : 'Why this store is easy to work with') ?></h2>
                <p><?= htmlspecialchars($isRu ? 'Главная страница и каталог теперь подают витрину как магазин готовых решений для запуска, продажи и кастомизации.' : 'The homepage and catalog now present the storefront as a ready-made solutions store built for launch, resale and customization.') ?></p>
            </div>
        </section>

        <section class="value-grid mb-5">
            <?php foreach (($why_items ?? []) as $item): ?>
                <article class="value-card" data-aos="fade-up">
                    <div class="value-icon"><?= $iconSvg((string)($item['icon'] ?? 'fa-bolt')) ?></div>
                    <h3><?= htmlspecialchars($item['title'] ?? '') ?></h3>
                    <p><?= htmlspecialchars($item['text'] ?? '') ?></p>
                </article>
            <?php endforeach; ?>
        </section>

        <section id="services" class="section-head" data-aos="fade-up">
            <div>
                <h2><?= htmlspecialchars($isRu ? 'Дополнительные услуги' : 'Additional services') ?></h2>
                <p><?= htmlspecialchars($isRu ? 'Помимо готовых продуктов, можно сразу предложить установку, адаптацию, доработку и кастомную сборку под задачу клиента или под свой запуск.' : 'Alongside ready-made products, the storefront can also present setup, adaptation, refinement and custom build services for client work or your own launch.') ?></p>
            </div>
        </section>

        <section class="service-grid mb-5">
            <?php foreach (($service_items ?? []) as $item): ?>
                <article class="service-card" data-aos="fade-up">
                    <div class="value-icon"><?= $iconSvg((string)($item['icon'] ?? 'fa-screwdriver-wrench')) ?></div>
                    <div class="card-kicker"><?= htmlspecialchars($isRu ? 'Услуга' : 'Service') ?></div>
                    <h3><?= htmlspecialchars($item['title'] ?? '') ?></h3>
                    <p><?= htmlspecialchars($item['text'] ?? '') ?></p>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="section-head" data-aos="fade-up">
            <div>
                <h2><?= htmlspecialchars($about_block['title'] ?? ($isRu ? 'О магазине' : 'About the store')) ?></h2>
                <p><?= htmlspecialchars($isRu ? 'Позиционирование смещено от простой витрины файлов к магазину практичных digital-решений для реальной работы.' : 'The positioning moves away from a plain file showcase toward a store of practical digital solutions for real work.') ?></p>
            </div>
        </section>

        <section class="about-card mb-5" data-aos="fade-up">
            <div class="card-kicker"><?= htmlspecialchars($isRu ? 'Позиционирование' : 'Positioning') ?></div>
            <h3><?= htmlspecialchars($about_block['title'] ?? '') ?></h3>
            <p><?= htmlspecialchars($about_block['text'] ?? '') ?></p>
        </section>

        <section class="section-head" data-aos="fade-up">
            <div>
                <h2>FAQ</h2>
                <p><?= htmlspecialchars($isRu ? 'Короткие ответы о типах продуктов, услугах и том, как эти решения можно использовать в работе.' : 'Short answers about product types, services and how these solutions can be used in real work.') ?></p>
            </div>
        </section>

        <section class="faq-wrap mb-5 accordion" id="homeFaq">
            <?php foreach (($faq_items ?? []) as $index => $item): ?>
                <div class="faq-card accordion-item border-0" data-aos="fade-up">
                    <h3 class="accordion-header" id="faqHeading<?= (int)$index ?>">
                        <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse<?= (int)$index ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="faqCollapse<?= (int)$index ?>">
                            <?= htmlspecialchars($item['question'] ?? '') ?>
                        </button>
                    </h3>
                    <div id="faqCollapse<?= (int)$index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="faqHeading<?= (int)$index ?>" data-bs-parent="#homeFaq">
                        <div class="accordion-body">
                            <?= htmlspecialchars($item['answer'] ?? '') ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="cta-card mb-4" data-aos="zoom-in">
            <div>
                <h3><?= htmlspecialchars($cta_block['title'] ?? ($isRu ? 'Нужна помощь с запуском?' : 'Need help with launch?')) ?></h3>
                <p><?= htmlspecialchars($cta_block['text'] ?? '') ?></p>
            </div>
            <div class="d-flex flex-wrap gap-3">
                <a href="#product-grid" class="hero-cta-primary"><?= htmlspecialchars($cta_block['primary'] ?? ($isRu ? 'Смотреть каталог' : 'View catalog')) ?></a>
                <a href="<?= htmlspecialchars($publicUrl('/page/contact')) ?>" class="hero-cta-secondary"><?= htmlspecialchars($cta_block['secondary'] ?? ($isRu ? 'Связаться' : 'Contact')) ?></a>
            </div>
        </section>
    <?php endif; ?>
</div>
