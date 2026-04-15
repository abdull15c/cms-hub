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
    .article-shell{position:relative;z-index:1}
    .article-card,.article-side,.article-back{background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));border:1px solid var(--surface-border);border-radius:28px;box-shadow:0 18px 42px rgba(0,0,0,.22)}
    .article-back{display:inline-flex;align-items:center;gap:10px;padding:12px 16px;color:var(--muted-text);text-decoration:none;margin-bottom:18px}
    .article-back:hover{color:#fff}
    .article-card{overflow:hidden}
    .article-head{padding:36px;background:radial-gradient(circle at top right,var(--primary-soft),transparent 30%),radial-gradient(circle at bottom left,var(--secondary-soft),transparent 36%),rgba(9,17,29,.92);border-bottom:1px solid var(--surface-border)}
    .article-head h1{color:#fff;font-size:clamp(2rem,4vw,3.2rem);line-height:1.04;letter-spacing:-.045em;margin:0 0 14px}
    .article-meta{display:flex;flex-wrap:wrap;gap:16px;color:var(--muted-text);font-size:.92rem}
    .article-body{padding:36px}
    .article-content{color:var(--text-main);font-size:1.05rem;line-height:1.85}
    .article-content h2,.article-content h3,.article-content h4{color:#fff;margin:1.8rem 0 .8rem}
    .article-content p{margin-bottom:1.2rem}
    .article-content ul,.article-content ol{padding-left:1.25rem}
    .article-content a{color:var(--primary-neon)}
    .article-side{padding:24px}
    .article-side h3{color:#fff;font-size:1.05rem;margin-bottom:16px}
    .article-side-item + .article-side-item{margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,.06)}
    .article-side-item a{color:#fff;text-decoration:none;font-weight:600}
    .article-side-item p{color:var(--muted-text);line-height:1.65;font-size:.92rem;margin:.5rem 0 0}
    @media (max-width:991.98px){.article-body,.article-head,.article-side{padding:24px}}
</style>

<div class="container py-4 py-lg-5 article-shell">
    <a href="<?= htmlspecialchars($publicUrl('/blog')) ?>" class="article-back">
        <?= $iconSvg('fa-arrow-left') ?>
        <span><?= htmlspecialchars($isRu ? 'Назад в блог' : 'Back to blog') ?></span>
    </a>

    <div class="row g-4">
        <div class="col-lg-8">
            <article class="article-card">
                <header class="article-head">
                    <h1><?= htmlspecialchars((string)$post['title']) ?></h1>
                    <div class="article-meta">
                        <span><?= $iconSvg('fa-calendar', 'me-1') ?><?= htmlspecialchars(date('F d, Y', strtotime((string)$post['created_at']))) ?></span>
                        <span><?= $iconSvg('fa-robot', 'me-1') ?><?= htmlspecialchars($isRu ? 'Материал витрины' : 'Storefront article') ?></span>
                    </div>
                </header>
                <div class="article-body">
                    <div class="article-content">
                        <?= \Src\Services\Security::cleanHtml((string)$post['content']) ?>
                    </div>
                </div>
            </article>
        </div>

        <div class="col-lg-4">
            <aside class="article-side">
                <h3><?= htmlspecialchars($isRu ? 'Ещё статьи' : 'More articles') ?></h3>
                <?php if (empty($related_posts)): ?>
                    <p class="text-secondary mb-0"><?= htmlspecialchars($isRu ? 'Скоро здесь появятся новые публикации.' : 'More posts will appear here soon.') ?></p>
                <?php else: ?>
                    <?php foreach ($related_posts as $related): ?>
                        <div class="article-side-item">
                            <a href="<?= htmlspecialchars($publicUrl('/blog/' . (int)$related['id'])) ?>"><?= htmlspecialchars((string)$related['title']) ?></a>
                            <p><?= htmlspecialchars(mb_strimwidth(strip_tags((string)$related['content']), 0, 110, '...')) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</div>
