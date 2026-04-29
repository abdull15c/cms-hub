<?php
$currentLanguage = (string)($langCode ?? 'ru');
$isRu = $currentLanguage === 'ru';
$publicUrl = static function (string $path = '/', array $params = []) use ($currentLanguage) {
    $normalizedPath = $path === '/' ? '' : $path;
    $queryString = http_build_query(array_merge(['lang' => $currentLanguage], $params));
    return BASE_URL . $normalizedPath . ($queryString !== '' ? '?' . $queryString : '');
};
?>

<style>
    .content-shell{position:relative;z-index:1}
    .content-hero,.post-card{background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));border:1px solid var(--surface-border);border-radius:28px;box-shadow:0 18px 42px rgba(0,0,0,.22)}
    .content-hero{padding:34px;background:radial-gradient(circle at top right,var(--primary-soft),transparent 30%),radial-gradient(circle at bottom left,var(--secondary-soft),transparent 36%),linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015))}
    .content-kicker{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:.45rem .85rem;background:var(--badge-bg);color:var(--badge-text);font-size:.8rem;text-transform:uppercase;letter-spacing:.08em}
    .content-title{color:#fff;font-size:clamp(2rem,4vw,3.4rem);line-height:1.03;letter-spacing:-.045em;margin:18px 0 14px}
    .content-subtitle,.post-card p{color:var(--muted-text);line-height:1.75}
    .post-card{padding:24px;height:100%;display:flex;flex-direction:column}
    .post-meta{display:flex;flex-wrap:wrap;gap:14px;color:var(--muted-text);font-size:.88rem;margin-bottom:14px}
    .post-card h3{color:#fff;font-size:1.2rem;line-height:1.35;margin-bottom:12px}
    .post-link{margin-top:auto;display:inline-flex;align-items:center;gap:8px;color:var(--button-text);background:var(--primary-neon);border-radius:999px;padding:10px 14px;text-decoration:none;font-weight:700}
    .empty-card{padding:34px;text-align:center;border:1px dashed var(--surface-border);border-radius:26px;background:rgba(255,255,255,.02)}
    @media (max-width:767.98px){.content-hero,.post-card{border-radius:22px}.content-hero{padding:24px}}
</style>

<div class="container py-4 py-lg-5 content-shell">
    <section class="content-hero mb-5">
        <span class="content-kicker"><?= $iconSvg('fa-newspaper') ?> <?= htmlspecialchars($isRu ? 'Блог' : 'Blog') ?></span>
        <h1 class="content-title"><?= htmlspecialchars($isRu ? 'Обновления, обзоры и статьи для digital storefront' : 'Updates, reviews and articles for a digital storefront') ?></h1>
        <p class="content-subtitle mb-0"><?= htmlspecialchars($isRu ? 'Подборка материалов о готовых сайтах, скриптах, шаблонах, запуске магазинов и развитии digital products.' : 'A curated stream of posts about ready-made sites, scripts, templates, store launches and digital products.') ?></p>
    </section>

    <div class="row g-4">
        <?php if (empty($posts)): ?>
            <div class="col-12">
                <div class="empty-card">
                    <h3 class="text-white"><?= htmlspecialchars($isRu ? 'Статей пока нет' : 'No posts yet') ?></h3>
                    <p class="text-secondary mb-0"><?= htmlspecialchars($isRu ? 'Когда появятся первые публикации, они будут показаны здесь.' : 'When the first posts are published, they will appear here.') ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
            <div class="col-md-6 col-xl-4">
                <article class="post-card">
                    <div class="post-meta">
                        <span><?= $iconSvg('fa-calendar', 'me-1') ?><?= htmlspecialchars(date('M d, Y', strtotime((string)$post['created_at']))) ?></span>
                        <span><?= $iconSvg('fa-sparkles', 'me-1') ?><?= htmlspecialchars($isRu ? 'Материал витрины' : 'Storefront article') ?></span>
                    </div>
                    <h3><?= htmlspecialchars((string)$post['title']) ?></h3>
                    <p><?= htmlspecialchars(mb_strimwidth(strip_tags((string)$post['content']), 0, 180, '...')) ?></p>
                    <a href="<?= htmlspecialchars($publicUrl('/blog/' . (int)$post['id'])) ?>" class="post-link">
                        <?= htmlspecialchars($isRu ? 'Читать статью' : 'Read article') ?>
                        <?= $iconSvg('fa-arrow-right') ?>
                    </a>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
</div>
