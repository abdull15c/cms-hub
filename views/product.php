<?php
$isRu = ($langCode ?? 'ru') === 'ru';
$currentLanguage = (string)($langCode ?? 'ru');
$homeUrl = BASE_URL . '/?lang=' . urlencode($currentLanguage);
$categoryUrl = !empty($product['category_id'])
    ? BASE_URL . '/?' . http_build_query(['cat' => (int)$product['category_id'], 'lang' => $currentLanguage])
    : $homeUrl;
$categoryName = trim((string)($product['category_name'] ?? '')) ?: ($isRu ? 'Каталог' : 'Catalog');
$summary = trim((string)($product_summary ?? ''));
$isOnSale = !empty($product['sale_price']) && !empty($product['sale_end']) && strtotime((string)$product['sale_end']) > time();
$regularPrice = (float)($product['price'] ?? 0);
$displayPrice = $isOnSale ? (float)$product['sale_price'] : $regularPrice;
$reviewCount = count($reviews ?? []);
$averageRating = (float)($avg_rating ?? 0);
$mainImage = !empty($images[0]['image_path']) ? BASE_URL . '/uploads/images/' . rawurlencode((string)$images[0]['image_path']) : '';
$heroBadges = [
    $isRu ? 'Мгновенная выдача' : 'Instant delivery',
    $isRu ? 'Безопасная оплата' : 'Secure checkout',
    !empty($product['has_license']) ? ($isRu ? 'Лицензия включена' : 'License included') : ($isRu ? 'Цифровой товар' : 'Digital product'),
];
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css">

<style>
    .dt-wrap{position:relative;z-index:1}
    .dt-breadcrumbs{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:22px;color:var(--muted-text);font-size:.92rem}
    .dt-breadcrumbs a{color:var(--muted-text);text-decoration:none}
    .dt-breadcrumbs a:hover,.dt-link:hover{color:#fff}
    .dt-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(320px,.9fr);gap:24px;align-items:start}
    .dt-card{background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));border:1px solid var(--surface-border);border-radius:28px;box-shadow:0 18px 42px rgba(0,0,0,.22)}
    .dt-gallery{position:relative;overflow:hidden}
    .dt-main{min-height:500px;display:grid;place-items:center;background:radial-gradient(circle at top right,var(--primary-soft),transparent 28%),radial-gradient(circle at bottom left,var(--secondary-soft),transparent 32%),rgba(9,17,29,.92)}
    .dt-main img{width:100%;height:500px;object-fit:contain;display:block}
    .dt-sale{position:absolute;top:18px;right:18px;border-radius:999px;padding:10px 14px;background:rgba(255,0,80,.16);border:1px solid rgba(255,0,80,.35);color:#fff;font-size:.84rem;font-weight:700}
    .dt-empty{padding:40px 20px;text-align:center;color:var(--muted-text)}
    .dt-thumbs{display:grid;grid-template-columns:repeat(auto-fit,minmax(90px,1fr));gap:10px;margin-top:14px}
    .dt-thumb{border-radius:18px;overflow:hidden;border:1px solid var(--surface-border);cursor:pointer;background:rgba(255,255,255,.03);transition:.2s}
    .dt-thumb:hover,.dt-thumb.is-active{transform:translateY(-2px);border-color:var(--primary-neon);box-shadow:0 0 0 1px var(--primary-soft)}
    .dt-thumb img{width:100%;height:82px;object-fit:cover;display:block}
    .dt-stack{display:grid;gap:18px}
    .dt-pad{padding:28px}
    .dt-kicker,.dt-badge{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:.45rem .85rem}
    .dt-kicker{background:var(--badge-bg);color:var(--badge-text);font-size:.8rem;text-transform:uppercase;letter-spacing:.08em}
    .dt-title{color:#fff;font-size:clamp(2rem,4vw,3rem);line-height:1.03;letter-spacing:-.045em;margin:18px 0 14px}
    .dt-summary,.dt-muted,.dt-description,.dt-faq .accordion-body,.dt-review p,.dt-related p,.dt-merchant p{color:var(--muted-text);line-height:1.75}
    .dt-stars{display:inline-flex;gap:4px;color:#fbbf24}
    .dt-badges{display:flex;flex-wrap:wrap;gap:10px;margin:18px 0}
    .dt-badge{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.05);color:var(--text-main);font-size:.86rem}
    .dt-price{display:flex;flex-wrap:wrap;align-items:end;gap:14px;margin:18px 0 20px}
    .dt-price-main{color:#fff;font-size:clamp(2rem,4vw,3rem);font-weight:800;letter-spacing:-.05em;line-height:1}
    .dt-price-old{color:var(--muted-text);text-decoration:line-through;font-size:1.05rem;padding-bottom:.35rem}
    .dt-facts,.dt-perks,.dt-related{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
    .dt-fact,.dt-perk,.dt-related-item,.dt-review,.dt-buyfact{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.05);border-radius:22px}
    .dt-fact,.dt-perk{padding:20px}
    .dt-fact small,.dt-buyfact small{display:block;color:var(--muted-text);font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px}
    .dt-fact strong,.dt-buyfact strong,.dt-section h2,.dt-perk h3,.dt-related-item h3{color:#fff}
    .dt-section{margin-top:18px}
    .dt-section h2{font-size:clamp(1.5rem,3vw,2rem);margin-bottom:10px}
    .dt-description h2,.dt-description h3,.dt-description h4{color:#fff;margin:1.6rem 0 .75rem}
    .dt-description ul,.dt-description ol{padding-left:1.25rem}
    .dt-perkicon{width:50px;height:50px;display:grid;place-items:center;border-radius:16px;background:var(--badge-bg);color:var(--badge-text);margin-bottom:14px}
    .dt-buy{position:sticky;top:106px}
    .dt-buyfacts{display:grid;gap:10px;margin:18px 0}
    .dt-buyfact{padding:12px 14px;display:flex;justify-content:space-between;gap:14px}
    .dt-buyfact strong{text-align:right}
    .dt-buybtn{width:100%;border:none;border-radius:20px;padding:16px 18px;background:linear-gradient(90deg,var(--primary-neon),var(--secondary-neon));color:var(--button-text);font-weight:800;box-shadow:0 18px 34px var(--primary-soft)}
    .dt-paygrid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:14px}
    .dt-trust{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:18px;padding-top:18px;border-top:1px solid rgba(255,255,255,.08)}
    .dt-trust div{display:flex;align-items:center;gap:8px;color:var(--muted-text);font-size:.9rem}
    .dt-merchant{display:flex;align-items:center;gap:14px;padding:18px 20px}
    .dt-mark{width:48px;height:48px;border-radius:16px;display:grid;place-items:center;background:linear-gradient(135deg,var(--primary-neon),var(--secondary-neon));color:var(--button-text);font-weight:800}
    .dt-faq .accordion-button{background:transparent;box-shadow:none;color:#fff;padding:0;font-weight:700}
    .dt-faq .accordion-button:not(.collapsed){color:var(--primary-neon)}
    .dt-faq .accordion-button::after{filter:invert(1)}
    .dt-faq-item{padding:22px}
    .dt-reviewform{padding:22px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:24px}
    .dt-review{padding:22px;margin-top:14px}
    .dt-reviewhead{display:flex;justify-content:space-between;gap:12px;margin-bottom:12px}
    .dt-author{display:flex;align-items:center;gap:12px}
    .dt-avatar{width:40px;height:40px;border-radius:999px;display:grid;place-items:center;background:rgba(255,255,255,.05);color:#fff;font-weight:700}
    .dt-related-item{overflow:hidden}
    .dt-related-item img{width:100%;height:170px;object-fit:cover;display:block;border-bottom:1px solid var(--surface-border)}
    .dt-related-body{padding:18px}
    .dt-related-meta{display:flex;justify-content:space-between;gap:12px;align-items:center;padding-top:14px;margin-top:14px;border-top:1px solid rgba(255,255,255,.06)}
    .dt-link{color:var(--primary-neon);text-decoration:none;font-weight:700}
    @media (max-width:1199.98px){.dt-grid,.dt-facts,.dt-perks,.dt-related{grid-template-columns:1fr}.dt-buy{position:static}}
    @media (max-width:767.98px){.dt-main,.dt-main img{min-height:320px;height:320px}.dt-card{border-radius:22px}.dt-pad,.dt-reviewform,.dt-review,.dt-faq-item,.dt-merchant{padding:22px}.dt-paygrid,.dt-trust{grid-template-columns:1fr}.dt-reviewhead,.dt-related-meta{flex-direction:column;align-items:flex-start}}
</style>

<div class="container py-4 py-lg-5 dt-wrap">
    <nav class="dt-breadcrumbs" aria-label="Breadcrumb">
        <a href="<?= htmlspecialchars($homeUrl) ?>"><?= $iconSvg('fa-house', 'me-1') ?><?= htmlspecialchars($t('product_home', 'Home')) ?></a>
        <span>/</span>
        <a href="<?= htmlspecialchars($categoryUrl) ?>"><?= htmlspecialchars($categoryName) ?></a>
        <span>/</span>
        <span><?= htmlspecialchars($product['title']) ?></span>
    </nav>

    <div class="dt-grid">
        <div class="dt-stack">
            <div class="dt-card dt-gallery">
                <div class="dt-main">
                    <?php if ($mainImage !== ''): ?>
                        <a data-fancybox="gallery" href="<?= htmlspecialchars($mainImage) ?>" class="w-100 h-100">
                            <img id="mainImage" src="<?= htmlspecialchars($mainImage) ?>" alt="<?= htmlspecialchars($product['title']) ?>">
                        </a>
                    <?php else: ?>
                        <div class="dt-empty">
                            <?= $iconSvg('fa-image', 'fa-4x mb-3') ?>
                            <div><?= htmlspecialchars($t('product_no_preview', 'No Preview')) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($isOnSale): ?><div class="dt-sale"><?= $iconSvg('fa-fire', 'me-1') ?><?= htmlspecialchars($t('product_flash_sale', 'FLASH SALE')) ?></div><?php endif; ?>
            </div>

            <?php if (count($images) > 1): ?>
                <div class="dt-thumbs">
                    <?php foreach ($images as $index => $image): ?>
                        <?php $imageUrl = BASE_URL . '/uploads/images/' . rawurlencode((string)$image['image_path']); ?>
                        <div class="dt-thumb <?= $index === 0 ? 'is-active' : '' ?>" onclick="updateMainImage(this, '<?= htmlspecialchars($imageUrl, ENT_QUOTES) ?>')">
                            <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars($product['title']) ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($product_facts)): ?>
                <div class="dt-facts">
                    <?php foreach ($product_facts as $fact): ?>
                        <div class="dt-fact">
                            <small><?= htmlspecialchars((string)($fact['label'] ?? '')) ?></small>
                            <strong><?= htmlspecialchars((string)($fact['value'] ?? '')) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section class="dt-card dt-pad dt-section">
                <span class="dt-kicker"><?= $iconSvg('fa-code') ?> <?= htmlspecialchars($categoryName) ?></span>
                <h1 class="dt-title d-lg-none"><?= htmlspecialchars($product['title']) ?></h1>
                <?php if ($summary !== ''): ?><p class="dt-summary"><?= htmlspecialchars($summary) ?></p><?php endif; ?>
                <h2><?= htmlspecialchars($t('product_overview', 'Overview')) ?></h2>
                <div class="dt-description">
                    <?= $product['description'] ?: '<p>' . htmlspecialchars($summary !== '' ? $summary : ($isRu ? 'Описание товара появится здесь.' : 'The product description will appear here.')) . '</p>' ?>
                </div>
            </section>

            <?php if (!empty($product_perks)): ?>
                <section class="dt-card dt-pad dt-section">
                    <h2><?= htmlspecialchars($isRu ? 'Почему эта карточка продаёт лучше' : 'Why this product page converts better') ?></h2>
                    <div class="dt-perks">
                        <?php foreach ($product_perks as $perk): ?>
                            <div class="dt-perk">
                                <div class="dt-perkicon"><?= $iconSvg((string)($perk['icon'] ?? 'fa-bolt')) ?></div>
                                <h3 class="fs-5 mb-2"><?= htmlspecialchars((string)($perk['title'] ?? '')) ?></h3>
                                <p class="mb-0"><?= htmlspecialchars((string)($perk['text'] ?? '')) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($product_faq_items)): ?>
                <section class="dt-card dt-pad dt-section dt-faq">
                    <h2>FAQ</h2>
                    <div class="accordion" id="productFaq">
                        <?php foreach ($product_faq_items as $index => $item): ?>
                            <div class="dt-faq-item accordion-item border-0 bg-transparent">
                                <h3 class="accordion-header" id="productFaqHeading<?= (int)$index ?>">
                                    <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#productFaqCollapse<?= (int)$index ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>">
                                        <?= htmlspecialchars((string)($item['question'] ?? '')) ?>
                                    </button>
                                </h3>
                                <div id="productFaqCollapse<?= (int)$index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#productFaq">
                                    <div class="accordion-body px-0 pb-0"><?= htmlspecialchars((string)($item['answer'] ?? '')) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="dt-card dt-pad dt-section">
                <h2><?= htmlspecialchars($t('product_reviews', 'Reviews')) ?><?php if ($reviewCount > 0): ?> <span class="text-secondary fs-6">(<?= (int)$reviewCount ?>)</span><?php endif; ?></h2>
                <?php if (!empty($can_review)): ?>
                    <div class="dt-reviewform">
                        <h3 class="text-white fs-5 mb-3"><?= htmlspecialchars($t('product_write_review', 'Write a review')) ?></h3>
                        <form action="<?= BASE_URL ?>/review/store/<?= (int)$product['id'] ?>" method="POST">
                            <?= \Src\Core\Csrf::field() ?>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <select name="rating" class="form-select bg-dark text-white border-secondary">
                                        <option value="5"><?= htmlspecialchars($t('product_rating_5', '★★★★★ Excellent')) ?></option>
                                        <option value="4"><?= htmlspecialchars($t('product_rating_4', '★★★★☆ Good')) ?></option>
                                        <option value="3"><?= htmlspecialchars($t('product_rating_3', '★★★☆☆ Average')) ?></option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <textarea name="comment" class="form-control bg-dark text-white border-secondary" rows="4" placeholder="<?= htmlspecialchars($t('product_review_placeholder', 'Share your experience...')) ?>"></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-outline-info rounded-pill px-4"><?= htmlspecialchars($t('product_submit_review', 'Submit Review')) ?></button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($reviewCount === 0): ?>
                    <div class="dt-review"><p class="mb-0"><?= htmlspecialchars($t('product_no_reviews', 'No reviews yet. Be the first!')) ?></p></div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <article class="dt-review">
                            <div class="dt-reviewhead">
                                <div class="dt-author">
                                    <div class="dt-avatar"><?= htmlspecialchars(strtoupper(substr((string)$review['email'], 0, 1))) ?></div>
                                    <div>
                                        <div class="text-white fw-semibold"><?= htmlspecialchars(explode('@', (string)$review['email'])[0]) ?></div>
                                        <div class="text-secondary small"><?= htmlspecialchars(date('M j, Y', strtotime((string)$review['created_at']))) ?></div>
                                    </div>
                                </div>
                                <div class="dt-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?><?= $iconSvg('fa-star', (int)$review['rating'] >= $i ? '' : 'opacity-25') ?><?php endfor; ?>
                                </div>
                            </div>
                            <p class="mb-0"><?= nl2br(htmlspecialchars((string)$review['comment'])) ?></p>
                            <?php if (!empty($review['reply'])): ?>
                                <div class="mt-3 pt-3 border-top border-secondary border-opacity-10">
                                    <div class="text-info small mb-2"><?= htmlspecialchars($t('product_author_reply', 'Author Reply:')) ?></div>
                                    <p class="mb-0 text-white"><?= nl2br(htmlspecialchars((string)$review['reply'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <?php if (!empty($related_products)): ?>
                <section class="dt-card dt-pad dt-section">
                    <h2><?= htmlspecialchars($isRu ? 'Похожие товары' : 'Related products') ?></h2>
                    <div class="dt-related">
                        <?php foreach ($related_products as $related): ?>
                            <?php
                            $relatedUrl = BASE_URL . '/product/' . (int)$related['id'] . '?lang=' . urlencode($currentLanguage);
                            $relatedPrice = (!empty($related['sale_price']) && !empty($related['sale_end']) && strtotime((string)$related['sale_end']) > time())
                                ? (float)$related['sale_price']
                                : (float)($related['price'] ?? 0);
                            ?>
                            <article class="dt-related-item">
                                <?php if (!empty($related['thumbnail'])): ?>
                                    <a href="<?= htmlspecialchars($relatedUrl) ?>"><img src="<?= BASE_URL ?>/uploads/images/<?= htmlspecialchars((string)$related['thumbnail']) ?>" alt="<?= htmlspecialchars((string)$related['title']) ?>" loading="lazy"></a>
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center text-secondary" style="min-height:170px;"><?= $iconSvg('fa-image', 'fa-2x') ?></div>
                                <?php endif; ?>
                                <div class="dt-related-body">
                                    <span class="dt-kicker mb-3"><?= $iconSvg('fa-layer-group') ?> <?= htmlspecialchars((string)($related['category_name'] ?? $categoryName)) ?></span>
                                    <h3 class="fs-5 mb-2"><?= htmlspecialchars((string)$related['title']) ?></h3>
                                    <p class="mb-0"><?= htmlspecialchars(mb_strimwidth(strip_tags((string)($related['description'] ?? '')), 0, 140, '...')) ?></p>
                                    <div class="dt-related-meta">
                                        <div class="text-white fw-bold"><?= \Src\Services\CurrencyService::format($relatedPrice) ?></div>
                                        <a class="dt-link" href="<?= htmlspecialchars($relatedUrl) ?>"><?= htmlspecialchars($isRu ? 'Открыть' : 'Open') ?> <?= $iconSvg('fa-arrow-right', 'ms-1') ?></a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>

        <div class="dt-stack">
            <aside class="dt-card dt-pad dt-buy">
                <span class="dt-kicker"><?= $iconSvg('fa-microchip') ?> <?= htmlspecialchars($categoryName) ?></span>
                <h1 class="dt-title d-none d-lg-block"><?= htmlspecialchars($product['title']) ?></h1>
                <?php if ($summary !== ''): ?><p class="dt-summary"><?= htmlspecialchars($summary) ?></p><?php endif; ?>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dt-stars"><?php for ($i = 1; $i <= 5; $i++): ?><?= $iconSvg('fa-star', $averageRating >= $i || $reviewCount === 0 ? '' : 'opacity-25') ?><?php endfor; ?></div>
                    <span class="text-secondary small"><?= $reviewCount > 0 ? (int)$reviewCount . ' ' . htmlspecialchars($isRu ? 'отзывов' : 'reviews') : htmlspecialchars($isRu ? 'новый релиз' : 'new release') ?></span>
                </div>

                <div class="dt-badges">
                    <?php foreach ($heroBadges as $badge): ?><span class="dt-badge"><?= $iconSvg('fa-check') ?> <?= htmlspecialchars($badge) ?></span><?php endforeach; ?>
                </div>

                <div class="dt-price">
                    <div class="dt-price-main"><?= \Src\Services\CurrencyService::format($displayPrice) ?></div>
                    <?php if ($isOnSale): ?><div class="dt-price-old"><?= \Src\Services\CurrencyService::format($regularPrice) ?></div><?php endif; ?>
                </div>

                <?php if (!empty($product_facts)): ?>
                    <div class="dt-buyfacts">
                        <?php foreach ($product_facts as $fact): ?>
                            <div class="dt-buyfact">
                                <div><small><?= htmlspecialchars((string)($fact['label'] ?? '')) ?></small></div>
                                <strong><?= htmlspecialchars((string)($fact['value'] ?? '')) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/checkout/<?= (int)$product['id'] ?>" method="POST">
                    <?= \Src\Core\Csrf::field() ?>
                    <?php if ($displayPrice > 0): ?>
                        <div class="mb-3">
                            <a href="#" class="dt-link" onclick="document.getElementById('couponArea').classList.toggle('d-none'); return false;"><?= $iconSvg('fa-tag', 'me-1') ?><?= htmlspecialchars($t('product_have_coupon', 'Have a promo code?')) ?></a>
                            <div id="couponArea" class="d-none mt-3">
                                <div class="d-flex gap-2">
                                    <input type="text" id="couponInput" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="CODE">
                                    <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="applyCoupon()"><?= htmlspecialchars($t('product_apply', 'Apply')) ?></button>
                                </div>
                                <div id="couponMsg" class="small mt-2"></div>
                                <input type="hidden" name="coupon_code" id="hiddenCoupon">
                            </div>
                        </div>
                        <button type="submit" name="provider" value="wallet" class="dt-buybtn"><?= $iconSvg('fa-cart-shopping', 'me-2') ?><?= htmlspecialchars($t('product_buy_now', 'Buy Now')) ?></button>
                        <div class="dt-paygrid">
                            <?php if (\Src\Services\SettingsService::get('yoomoney_enabled') != '0'): ?><button type="submit" name="provider" value="yoomoney" class="btn btn-dark border-secondary text-secondary"><?= $iconSvg('fa-ruble-sign', 'me-1') ?>YooMoney</button><?php endif; ?>
                            <?php if (\Src\Services\SettingsService::get('crypto_enabled')): ?><button type="submit" name="provider" value="crypto" class="btn btn-dark border-secondary text-secondary"><?= $iconSvg('fa-bitcoin', 'me-1') ?>Crypto</button><?php endif; ?>
                        </div>
                    <?php else: ?>
                        <button type="submit" name="provider" value="free" class="btn btn-success w-100 btn-lg rounded-4"><?= $iconSvg('fa-download', 'me-2') ?><?= htmlspecialchars($t('product_free_download', 'Free Download')) ?></button>
                    <?php endif; ?>
                </form>

                <div class="dt-trust">
                    <div><?= $iconSvg('fa-shield-halved', 'text-success') ?><span><?= htmlspecialchars($t('product_secure', 'Secure')) ?></span></div>
                    <div><?= $iconSvg('fa-bolt', 'text-warning') ?><span><?= htmlspecialchars($t('product_instant', 'Instant')) ?></span></div>
                    <div><?= $iconSvg('fa-language', 'text-info') ?><span>RU / EN</span></div>
                    <div role="button" data-bs-toggle="modal" data-bs-target="#chatModal"><?= $iconSvg('fa-comments', 'text-info') ?><span><?= htmlspecialchars($t('product_chat', 'Chat')) ?></span></div>
                </div>
            </aside>

            <div class="dt-card dt-merchant">
                <div class="dt-mark">DT</div>
                <div>
                    <div class="text-white fw-semibold"><?= htmlspecialchars($t('product_verified_seller', 'Verified Seller')) ?></div>
                    <p class="mb-0"><?= htmlspecialchars($isRu ? 'Витрина рассчитана на продажу готовых сайтов, скриптов и других digital products.' : 'This storefront is optimized for ready-made sites, scripts and other digital products.') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="chatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-secondary" style="background:#131a29;border-radius:24px;">
            <div class="modal-header border-secondary border-opacity-10">
                <h5 class="modal-title text-white"><?= htmlspecialchars($t('product_presale_chat', 'Pre-sale Chat')) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 text-secondary text-center py-4"><?= htmlspecialchars($t('product_chat_active', 'Chat feature is active.')) ?></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script>
    Fancybox.bind("[data-fancybox='gallery']", { Thumbs: { autoStart: false } });

    function updateMainImage(thumb, src) {
        const mainImage = document.getElementById('mainImage');
        if (!mainImage) return;
        mainImage.src = src;
        if (mainImage.parentElement) mainImage.parentElement.href = src;
        document.querySelectorAll('.dt-thumb').forEach((item) => item.classList.remove('is-active'));
        thumb.classList.add('is-active');
    }

    function applyCoupon() {
        const code = document.getElementById('couponInput').value;
        const msg = document.getElementById('couponMsg');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

        fetch('<?= BASE_URL ?>/api/check_coupon', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({code: code, csrf_token: csrf})
        })
        .then((res) => res.json())
        .then((data) => {
            if (data.valid) {
                msg.innerHTML = '<span class="text-success fw-bold">✓ ' + data.msg + '</span>';
                document.getElementById('hiddenCoupon').value = code;
            } else {
                msg.innerHTML = '<span class="text-danger">✕ ' + data.msg + '</span>';
                document.getElementById('hiddenCoupon').value = '';
            }
        });
    }
</script>
