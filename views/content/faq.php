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
    .faq-shell{position:relative;z-index:1}
    .faq-hero,.faq-item,.faq-cta{background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));border:1px solid var(--surface-border);border-radius:28px;box-shadow:0 18px 42px rgba(0,0,0,.22)}
    .faq-hero,.faq-cta{padding:32px}
    .faq-hero{background:radial-gradient(circle at top right,var(--primary-soft),transparent 30%),radial-gradient(circle at bottom left,var(--secondary-soft),transparent 36%),linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015))}
    .faq-kicker{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:.45rem .85rem;background:var(--badge-bg);color:var(--badge-text);font-size:.8rem;text-transform:uppercase;letter-spacing:.08em}
    .faq-title{color:#fff;font-size:clamp(2rem,4vw,3.2rem);line-height:1.04;letter-spacing:-.045em;margin:18px 0 14px}
    .faq-subtitle,.faq-cta p{color:var(--muted-text);line-height:1.75}
    .faq-item{padding:22px}
    .faq-item .accordion-button{background:transparent;color:#fff;box-shadow:none;padding:0;font-weight:700}
    .faq-item .accordion-button:not(.collapsed){color:var(--primary-neon)}
    .faq-item .accordion-button::after{filter:invert(1)}
    .faq-item .accordion-body{padding:14px 0 0;color:var(--muted-text);line-height:1.75}
    .faq-cta a{display:inline-flex;align-items:center;gap:8px;text-decoration:none;background:var(--primary-neon);color:var(--button-text);border-radius:999px;padding:12px 16px;font-weight:700}
    @media (max-width:767.98px){.faq-hero,.faq-item,.faq-cta{border-radius:22px;padding:24px}}
</style>

<div class="container py-4 py-lg-5 faq-shell">
    <section class="faq-hero mb-5" data-aos="fade-up">
        <span class="faq-kicker"><i class="fa-solid fa-circle-question"></i> FAQ</span>
        <h1 class="faq-title"><?= htmlspecialchars($isRu ? 'Частые вопросы по магазину и digital products' : 'Frequently asked questions about the store and digital products') ?></h1>
        <p class="faq-subtitle mb-0"><?= htmlspecialchars($isRu ? 'Ответы о покупке, лицензиях, доступе к файлам, поддержке и работе с готовыми сайтами, скриптами и шаблонами.' : 'Answers about buying, licenses, file delivery, support and working with ready-made sites, scripts and templates.') ?></p>
    </section>

    <div class="accordion" id="faqAccordion">
        <?php foreach ($faqs as $index => $faq): ?>
            <div class="faq-item accordion-item border-0 mb-3" data-aos="fade-up">
                <h2 class="accordion-header">
                    <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= (int)$index ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>">
                        <?= htmlspecialchars((string)$faq['question']) ?>
                    </button>
                </h2>
                <div id="faq<?= (int)$index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <?= nl2br(htmlspecialchars((string)$faq['answer'])) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <section class="faq-cta mt-4" data-aos="zoom-in">
        <h3 class="text-white mb-2"><?= htmlspecialchars($isRu ? 'Остались вопросы?' : 'Still have questions?') ?></h3>
        <p class="mb-3"><?= htmlspecialchars($isRu ? 'Если ответа не нашлось, напишите через форму контактов или используйте ссылки в футере.' : 'If you did not find the answer here, reach out through the contact page or the links in the footer.') ?></p>
        <a href="<?= htmlspecialchars($publicUrl('/page/contact')) ?>">
            <?= htmlspecialchars($isRu ? 'Связаться с поддержкой' : 'Contact support') ?>
            <i class="fa-solid fa-arrow-right"></i>
        </a>
    </section>
</div>
