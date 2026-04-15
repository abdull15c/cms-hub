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
    .contact-shell{position:relative;z-index:1}
    .contact-card,.contact-side{background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));border:1px solid var(--surface-border);border-radius:28px;box-shadow:0 18px 42px rgba(0,0,0,.22)}
    .contact-card{padding:34px;background:radial-gradient(circle at top right,var(--primary-soft),transparent 30%),radial-gradient(circle at bottom left,var(--secondary-soft),transparent 36%),linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015))}
    .contact-kicker{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:.45rem .85rem;background:var(--badge-bg);color:var(--badge-text);font-size:.8rem;text-transform:uppercase;letter-spacing:.08em}
    .contact-title{color:#fff;font-size:clamp(2rem,4vw,3.1rem);line-height:1.04;letter-spacing:-.045em;margin:18px 0 14px}
    .contact-subtitle,.contact-side p{color:var(--muted-text);line-height:1.75}
    .contact-side{padding:24px;height:100%}
    .contact-side h3{color:#fff;font-size:1.05rem;margin-bottom:12px}
    .contact-note{display:flex;align-items:flex-start;gap:10px;color:var(--muted-text);margin-top:16px}
    @media (max-width:767.98px){.contact-card,.contact-side{border-radius:22px;padding:24px}}
</style>

<div class="container py-4 py-lg-5 contact-shell">
    <div class="row g-4">
        <div class="col-lg-8">
            <section class="contact-card">
                <span class="contact-kicker"><i class="fa-regular fa-envelope"></i> <?= htmlspecialchars($t('contact_title', 'Contact Support')) ?></span>
                <h1 class="contact-title"><?= htmlspecialchars($isRu ? 'Связаться с магазином' : 'Get in touch with the store') ?></h1>
                <p class="contact-subtitle"><?= htmlspecialchars($isRu ? 'Напишите по вопросам покупки, лицензий, доступа к файлам, багов или сотрудничества.' : 'Reach out about purchases, licenses, file delivery, bugs or partnership questions.') ?></p>

                <?php if (isset($_GET['sent'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($t('contact_sent', 'Message sent!')) ?></div>
                <?php endif; ?>
                <?php if (!empty($error ?? '')): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars((string)$error) ?></div>
                <?php endif; ?>

                <form action="<?= htmlspecialchars($publicUrl('/page/contact/send')) ?>" method="POST">
                    <?= \Src\Core\Csrf::field() ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-secondary"><?= htmlspecialchars($t('contact_name', 'Name')) ?></label>
                            <input type="text" name="name" class="form-control bg-dark text-light border-secondary" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-secondary"><?= htmlspecialchars($t('auth_email', 'Email')) ?></label>
                            <input type="email" name="email" class="form-control bg-dark text-light border-secondary" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-secondary"><?= htmlspecialchars($t('contact_message', 'Message')) ?></label>
                        <textarea name="message" rows="6" class="form-control bg-dark text-light border-secondary" required></textarea>
                    </div>
                    <div class="mb-4" style="max-width:260px;">
                        <label class="text-warning"><?= htmlspecialchars($t('contact_solve', 'Solve')) ?>: <?= htmlspecialchars((string)$captcha_q) ?> = ?</label>
                        <input type="number" name="captcha" class="form-control bg-dark text-light border-warning" required>
                    </div>
                    <button type="submit" class="btn btn-cyber px-5"><?= htmlspecialchars($t('contact_send', 'Send Message')) ?></button>
                </form>
            </section>
        </div>

        <div class="col-lg-4">
            <aside class="contact-side">
                <h3><?= htmlspecialchars($isRu ? 'Что можно написать' : 'What you can ask about') ?></h3>
                <p><?= htmlspecialchars($isRu ? 'Покупка товара, проблемы с выдачей, лицензии, вопросы по поддержке, предзаказ и партнёрство.' : 'Product purchases, delivery issues, licenses, support questions, pre-sale inquiries and partnerships.') ?></p>

                <?php if (!empty($contact_email ?? '')): ?>
                    <div class="contact-note">
                        <i class="fa-regular fa-envelope mt-1"></i>
                        <div>
                            <div class="text-white fw-semibold"><?= htmlspecialchars($isRu ? 'Контактный email' : 'Contact email') ?></div>
                            <a href="mailto:<?= htmlspecialchars((string)$contact_email) ?>" class="text-info text-decoration-none"><?= htmlspecialchars((string)$contact_email) ?></a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="contact-note">
                    <i class="fa-solid fa-shield-halved mt-1"></i>
                    <div><?= htmlspecialchars($isRu ? 'Форма защищена CSRF-проверкой и простой captcha-проверкой.' : 'The form is protected by CSRF validation and a simple captcha check.') ?></div>
                </div>
            </aside>
        </div>
    </div>
</div>
