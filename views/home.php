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
?>

<style>
    .storefront-shell {
        position: relative;
        z-index: 1;
    }

    .hero-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
        gap: 28px;
        align-items: stretch;
    }

    .hero-panel {
        background: linear-gradient(145deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01));
        border: 1px solid var(--card-border);
        border-radius: 32px;
        padding: 38px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 24px 50px rgba(0, 0, 0, 0.25);
    }

    .hero-panel::before {
        content: '';
        position: absolute;
        inset: auto auto -120px -80px;
        width: 280px;
        height: 280px;
        background: radial-gradient(circle, var(--secondary-soft) 0%, transparent 70%);
        pointer-events: none;
    }

    .hero-panel::after {
        content: '';
        position: absolute;
        inset: -120px -80px auto auto;
        width: 320px;
        height: 320px;
        background: radial-gradient(circle, var(--primary-soft) 0%, transparent 72%);
        pointer-events: none;
    }

    .hero-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.5rem 0.9rem;
        border-radius: 999px;
        background: var(--badge-bg);
        color: var(--badge-text);
        font-size: 0.8rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .hero-title {
        font-size: clamp(2.4rem, 5vw, 4.2rem);
        line-height: 1.02;
        letter-spacing: -0.04em;
        margin: 22px 0 18px;
        color: #fff;
    }

    .hero-title span {
        background: linear-gradient(90deg, var(--primary-neon), var(--secondary-neon));
        -webkit-background-clip: text;
        color: transparent;
    }

    .hero-subtitle {
        font-size: 1.05rem;
        line-height: 1.75;
        color: var(--muted-text);
        max-width: 760px;
        margin-bottom: 28px;
    }

    .hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 26px;
    }

    .hero-cta-primary {
        background: linear-gradient(90deg, var(--primary-neon), var(--secondary-neon));
        color: var(--button-text);
        border: none;
        border-radius: 18px;
        padding: 14px 22px;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 18px 34px var(--primary-soft);
    }

    .hero-cta-secondary {
        border: 1px solid var(--surface-border);
        color: var(--text-main);
        border-radius: 18px;
        padding: 14px 22px;
        text-decoration: none;
        background: rgba(255,255,255,0.02);
    }

    .trust-row {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 22px;
    }

    .trust-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        padding: 10px 14px;
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--card-border);
        color: var(--text-main);
        font-size: 0.92rem;
    }

    .hero-search {
        display: flex;
        gap: 10px;
        align-items: stretch;
        margin-top: 8px;
    }

    .hero-search input {
        flex: 1;
        min-height: 56px;
        background: var(--surface-bg);
        border: 1px solid var(--surface-border);
        color: var(--text-main);
        border-radius: 18px;
        padding: 0 18px;
    }

    .hero-search button {
        min-width: 64px;
        border-radius: 18px;
        border: none;
        background: var(--primary-neon);
        color: var(--button-text);
        font-size: 1.1rem;
    }

    .hero-side {
        display: grid;
        gap: 18px;
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
        min-height: 260px;
        padding: 20px;
        display: grid;
        background:
            linear-gradient(160deg, rgba(255,255,255,0.05), rgba(255,255,255,0.01)),
            radial-gradient(circle at top right, var(--primary-soft), transparent 38%),
            radial-gradient(circle at bottom left, var(--secondary-soft), transparent 42%);
    }

    .preview-browser {
        background: rgba(11, 15, 25, 0.82);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 22px;
        padding: 16px;
        display: grid;
        gap: 16px;
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
        gap: 12px;
    }

    .preview-line {
        height: 12px;
        border-radius: 999px;
        background: rgba(255,255,255,0.12);
    }

    .preview-line.short {
        width: 58%;
    }

    .preview-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .preview-block {
        border-radius: 18px;
        min-height: 92px;
        background: rgba(255,255,255,0.06);
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
        padding: 22px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }

    .stat-tile {
        padding: 16px;
        border-radius: 20px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.04);
    }

    .stat-value {
        color: #fff;
        font-size: 1.35rem;
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
        margin: 54px 0 24px;
    }

    .section-head h2 {
        color: #fff;
        font-size: clamp(1.8rem, 3vw, 2.5rem);
        margin: 0;
    }

    .section-head p {
        margin: 8px 0 0;
        color: var(--muted-text);
        max-width: 720px;
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
        border: 1px solid var(--surface-border);
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
        background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.015));
        border: 1px solid var(--surface-border);
        border-radius: 28px;
        overflow: hidden;
        height: 100%;
        transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
    }

    .product-card:hover {
        transform: translateY(-6px);
        border-color: var(--primary-neon);
        box-shadow: 0 20px 40px rgba(0,0,0,0.24), 0 0 26px var(--primary-soft);
    }

    .product-cover {
        position: relative;
        min-height: 220px;
        background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01));
        border-bottom: 1px solid var(--card-border);
    }

    .product-cover img {
        width: 100%;
        height: 220px;
        object-fit: cover;
        display: block;
    }

    .product-cover-fallback {
        height: 220px;
        display: grid;
        place-items: center;
        color: var(--muted-text);
        background:
            radial-gradient(circle at top right, var(--primary-soft), transparent 32%),
            radial-gradient(circle at bottom left, var(--secondary-soft), transparent 35%),
            rgba(255,255,255,0.02);
    }

    .price-chip,
    .category-chip {
        position: absolute;
        border-radius: 999px;
        padding: 8px 12px;
        font-size: 0.8rem;
        backdrop-filter: blur(10px);
    }

    .price-chip {
        right: 14px;
        bottom: 14px;
        background: rgba(10, 15, 24, 0.78);
        border: 1px solid rgba(255,255,255,0.12);
        color: #fff;
        font-weight: 700;
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
        padding: 22px;
        display: flex;
        flex-direction: column;
        height: calc(100% - 220px);
    }

    .product-title {
        font-size: 1.08rem;
        line-height: 1.35;
        margin-bottom: 10px;
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
    }

    .product-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-top: 18px;
        padding-top: 16px;
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
        padding: 6px 9px;
        border-radius: 999px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.05);
        color: var(--muted-text);
        font-size: 0.74rem;
    }

    .price-stack {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 2px;
    }

    .price-old {
        color: var(--muted-text);
        font-size: 0.74rem;
        text-decoration: line-through;
        opacity: 0.8;
    }

    .product-link {
        text-decoration: none;
        color: var(--button-text);
        background: var(--primary-neon);
        border-radius: 999px;
        padding: 10px 14px;
        font-weight: 700;
        white-space: nowrap;
    }

    .value-grid,
    .use-case-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
    }

    .value-card,
    .use-case-card,
    .cta-card,
    .faq-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--card-border);
        border-radius: 26px;
        padding: 24px;
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
    .use-case-card h3 {
        color: #fff;
        font-size: 1.08rem;
        margin-bottom: 10px;
    }

    .value-card p,
    .use-case-card p,
    .cta-card p {
        color: var(--muted-text);
        line-height: 1.7;
        margin-bottom: 0;
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

    .empty-state {
        padding: 40px 20px;
        text-align: center;
        border: 1px dashed var(--card-border);
        border-radius: 28px;
        background: rgba(255,255,255,0.02);
    }

    @media (max-width: 991.98px) {
        .hero-grid,
        .value-grid,
        .use-case-grid {
            grid-template-columns: 1fr;
        }

        .hero-panel {
            padding: 28px;
        }
    }

    @media (max-width: 767.98px) {
        .hero-panel,
        .stats-card,
        .value-card,
        .use-case-card,
        .cta-card,
        .faq-card {
            border-radius: 22px;
        }

        .hero-search {
            flex-direction: column;
        }

        .hero-search button {
            min-height: 52px;
        }

        .stats-grid {
            grid-template-columns: 1fr 1fr;
        }

        .product-meta {
            flex-direction: column;
            align-items: stretch;
        }

        .product-link {
            text-align: center;
        }
    }
</style>

<div class="container py-4 py-lg-5 storefront-shell">
    <?php if ($plain_listing): ?>
        <section class="hero-grid mb-5" data-aos="fade-up">
            <div class="hero-panel">
                <span class="hero-eyebrow"><?= $iconSvg('fa-satellite-dish') ?> <?= htmlspecialchars($hero['eyebrow'] ?? 'Digital Storefront') ?></span>
                <h1 class="hero-title"><?= htmlspecialchars($hero['title'] ?? '') ?> <span>Dark Tech</span></h1>
                <p class="hero-subtitle"><?= htmlspecialchars($hero['subtitle'] ?? '') ?></p>

                <div class="hero-actions">
                    <a href="#product-grid" class="hero-cta-primary"><?= htmlspecialchars($hero['primary_cta'] ?? 'Browse catalog') ?></a>
                    <a href="#why-dark-tech" class="hero-cta-secondary"><?= htmlspecialchars($hero['secondary_cta'] ?? 'Why Dark Tech') ?></a>
                </div>

                <div class="trust-row">
                    <?php foreach (($trust_badges ?? []) as $badge): ?>
                        <span class="trust-pill"><?= $iconSvg('fa-check') ?> <?= htmlspecialchars($badge) ?></span>
                    <?php endforeach; ?>
                </div>

                <form action="<?= BASE_URL ?>/" method="GET" class="hero-search">
                    <input type="hidden" name="lang" value="<?= htmlspecialchars($langCode ?? 'ru') ?>">
                    <input type="text" name="q" placeholder="<?= htmlspecialchars($t('search_placeholder', 'Search for scripts, themes...')) ?>" value="<?= htmlspecialchars($currentQ ?? '') ?>">
                    <button type="submit" aria-label="Search"><?= $iconSvg('fa-search') ?></button>
                </form>
            </div>

            <div class="hero-side">
                <div class="preview-card">
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
                </div>

                <div class="stats-card">
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
                        <span class="price-chip">
                            <span class="price-stack">
                                <span><?= \Src\Services\CurrencyService::format($displayPrice) ?></span>
                                <?php if ($cardIsOnSale): ?><span class="price-old"><?= \Src\Services\CurrencyService::format((float)$p['price']) ?></span><?php endif; ?>
                            </span>
                        </span>
                    </div>

                    <div class="product-body">
                        <h3 class="product-title">
                            <a href="<?= htmlspecialchars($productUrl) ?>">
                                <?= htmlspecialchars($p['title']) ?>
                            </a>
                        </h3>
                        <p class="product-desc"><?= htmlspecialchars(mb_strimwidth(strip_tags($p['description'] ?? ''), 0, 150, '...')) ?></p>

                        <div class="product-meta">
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
        <section id="why-dark-tech" class="section-head" data-aos="fade-up">
            <div>
                <h2><?= htmlspecialchars(($langCode ?? 'ru') === 'ru' ? 'Почему Dark Tech подходит для продажи digital products' : 'Why Dark Tech works for digital products') ?></h2>
                <p><?= htmlspecialchars(($langCode ?? 'ru') === 'ru' ? 'Тема проектируется вокруг сильной витрины, понятного пути к покупке и хорошей структуры под SEO товара.' : 'The storefront is shaped around a strong buying flow, a cleaner product page and better product-oriented SEO structure.') ?></p>
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

        <section class="section-head" data-aos="fade-up">
            <div>
                <h2><?= htmlspecialchars(($langCode ?? 'ru') === 'ru' ? 'Кому подойдёт эта витрина' : 'Who this storefront fits best') ?></h2>
                <p><?= htmlspecialchars(($langCode ?? 'ru') === 'ru' ? 'Тема ориентирована на владельцев digital storefronts, агентства и тех, кто продаёт готовые решения.' : 'The theme is aimed at digital storefront owners, agencies and sellers of ready-made solutions.') ?></p>
            </div>
        </section>

        <section class="use-case-grid mb-5">
            <?php foreach (($use_cases ?? []) as $item): ?>
                <article class="use-case-card" data-aos="fade-up">
                    <h3><?= htmlspecialchars($item['title'] ?? '') ?></h3>
                    <p><?= htmlspecialchars($item['text'] ?? '') ?></p>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="section-head" data-aos="fade-up">
            <div>
                <h2>FAQ</h2>
                <p><?= htmlspecialchars(($langCode ?? 'ru') === 'ru' ? 'Короткие ответы для посетителя и дополнительная полезная структура для поисковиков.' : 'Short answers for visitors and an additional helpful structure for search engines.') ?></p>
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
                <h3><?= htmlspecialchars(($langCode ?? 'ru') === 'ru' ? 'Dark Tech уже заточен под продажу digital products' : 'Dark Tech is already shaped for digital product sales') ?></h3>
                <p><?= htmlspecialchars(($langCode ?? 'ru') === 'ru' ? 'Главная, каталог и карточка товара работают как единая витрина для готовых сайтов, скриптов, шаблонов и других цифровых решений с RU/EN потоком и SEO-основой.' : 'Home, catalog and product pages now work as a single storefront for ready-made sites, scripts, templates and other digital solutions with RU/EN flow and SEO groundwork.') ?></p>
            </div>
            <div class="d-flex flex-wrap gap-3">
                <a href="#product-grid" class="hero-cta-primary"><?= htmlspecialchars(($langCode ?? 'ru') === 'ru' ? 'Смотреть товары' : 'View products') ?></a>
                <a href="<?= htmlspecialchars($publicUrl('/faq')) ?>" class="hero-cta-secondary">FAQ</a>
            </div>
        </section>
    <?php endif; ?>
</div>
