<style>
    .footer-nebula { background: transparent; border-top: 1px solid var(--footer-border); position: relative; margin-top: auto; padding-top: 80px; padding-bottom: 30px; overflow: hidden; }
    .footer-glow { position: absolute; bottom: -100px; left: 50%; transform: translateX(-50%); width: 600px; height: 300px; background: radial-gradient(circle, var(--footer-glow) 0%, transparent 70%); pointer-events: none; z-index: 0; }
    .footer-brand { font-family: var(--font-sans); font-weight: 800; font-size: 1.8rem; background: linear-gradient(90deg, #fff, var(--muted-text)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 20px; display: inline-block; text-decoration: none; }
    .footer-link { color: var(--muted-text); text-decoration: none; margin-bottom: 12px; display: block; transition: 0.3s; font-size: 0.95rem; }
    .footer-link:hover { color: #fff; transform: translateX(5px); text-shadow: 0 0 10px rgba(255,255,255,0.5); }
    .social-btn { width: 40px; height: 40px; border-radius: 12px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; color: #fff; transition: 0.3s; border: 1px solid rgba(255,255,255,0.05); text-decoration: none; }
    .social-btn:hover { background: var(--primary-neon); border-color: var(--primary-neon); transform: translateY(-3px); box-shadow: 0 5px 15px var(--primary-soft); color: var(--button-text); }
    .newsletter-input { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; padding: 12px 15px; }
    .newsletter-input:focus { background: rgba(255,255,255,0.1); outline: none; border-color: var(--primary-neon); }
    .payment-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 48px; padding: 6px 10px; border-radius: 999px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); color: var(--muted-text); font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
</style>

<?php
$currentLanguage = (string)($langCode ?? 'ru');
$publicUrl = static function (string $path = '/', array $params = []) use ($currentLanguage) {
    $normalizedPath = $path === '/' ? '' : $path;
    $queryString = http_build_query(array_merge(['lang' => $currentLanguage], $params));
    return (defined('BASE_URL') ? BASE_URL : '') . $normalizedPath . ($queryString !== '' ? '?' . $queryString : '');
};
$footerText = \Src\Services\SettingsService::get('footer_text') ?: $t('footer_text', 'Premium digital marketplace.');
$contactEmail = \Src\Services\SettingsService::get('contact_email');
$usesStorefrontLiteCss = (bool)($usesStorefrontLiteCss ?? false);
$storefrontJsPath = ROOT_PATH . '/public/assets/storefront.js';
$storefrontJsVersion = file_exists($storefrontJsPath) ? (string)filemtime($storefrontJsPath) : '1';
$socialLinks = array_filter([
    ['url' => \Src\Services\SettingsService::get('telegram_url'), 'icon' => 'fa-telegram', 'label' => 'Telegram'],
    ['url' => \Src\Services\SettingsService::get('discord_url'), 'icon' => 'fa-discord', 'label' => 'Discord'],
    ['url' => \Src\Services\SettingsService::get('youtube_url'), 'icon' => 'fa-youtube', 'label' => 'YouTube'],
], static fn(array $item): bool => trim((string)$item['url']) !== '');
?>

    </main>
<footer class="footer-nebula" aria-label="<?= htmlspecialchars($t('footer_label', 'Footer')) ?>">
    <div class="footer-glow"></div>
    <div class="container position-relative z-1">
        <div class="row g-5">
            <div class="col-lg-4">
                <a href="<?= htmlspecialchars($publicUrl('/')) ?>" class="footer-brand"><?= htmlspecialchars(\Src\Services\SettingsService::get('site_title') ?: $t('site_title', 'CMS-HUB')) ?></a>
                <p class="text-secondary small mb-4" style="line-height: 1.6; max-width: 300px;"><?= htmlspecialchars($footerText) ?></p>
                <?php if (!empty($socialLinks)): ?>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php foreach ($socialLinks as $social): ?>
                            <a href="<?= htmlspecialchars((string)$social['url']) ?>" class="social-btn" target="_blank" rel="noopener noreferrer" aria-label="<?= htmlspecialchars((string)$social['label']) ?>">
                                <?= $iconSvg((string)$social['icon'], '', (string)$social['label']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($contactEmail !== ''): ?>
                    <div class="text-secondary small mt-3">
                        <?= $iconSvg('fa-envelope', 'me-2') ?><a href="mailto:<?= htmlspecialchars($contactEmail) ?>" class="footer-link d-inline mb-0"><?= htmlspecialchars($contactEmail) ?></a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-6 col-lg-2">
                <p class="text-white fw-bold mb-4"><?= htmlspecialchars($t('col_platform', 'Platform')) ?></p>
                <a href="<?= htmlspecialchars($publicUrl('/')) ?>" class="footer-link"><?= htmlspecialchars($t('site_title', 'Marketplace')) ?></a>
                <a href="<?= htmlspecialchars($publicUrl('/blog')) ?>" class="footer-link"><?= htmlspecialchars($t('nav_blog', 'Blog')) ?></a>
                <a href="<?= htmlspecialchars($publicUrl('/register')) ?>" class="footer-link"><?= htmlspecialchars($t('nav_register', 'Join')) ?></a>
                <a href="<?= htmlspecialchars($publicUrl('/faq')) ?>" class="footer-link"><?= htmlspecialchars($t('nav_faq', 'FAQ')) ?></a>
            </div>
            <div class="col-6 col-lg-2">
                <p class="text-white fw-bold mb-4"><?= htmlspecialchars($t('col_legal', 'Legal')) ?></p>
                <a href="<?= htmlspecialchars($publicUrl('/page/terms')) ?>" class="footer-link"><?= htmlspecialchars($t('link_terms', 'Terms')) ?></a>
                <a href="<?= htmlspecialchars($publicUrl('/page/privacy')) ?>" class="footer-link"><?= htmlspecialchars($t('link_privacy', 'Privacy')) ?></a>
                <a href="<?= htmlspecialchars($publicUrl('/page/contact')) ?>" class="footer-link"><?= htmlspecialchars($t('link_contact', 'Contact')) ?></a>
                <a href="<?= BASE_URL ?>/api/products" class="footer-link"><?= htmlspecialchars($t('link_api', 'API')) ?></a>
            </div>
            <div class="col-lg-4">
                <div class="p-4 rounded-4" style="background: var(--badge-bg); border: 1px solid var(--footer-border);">
                    <p class="text-white fw-bold mb-2"><?= htmlspecialchars($t('col_subscribe', 'Stay Updated')) ?></p>
                    <p class="text-secondary small mb-3"><?= htmlspecialchars($t('sub_text', 'Subscribe for updates.')) ?></p>
                    <form action="#" class="position-relative">
                        <input type="email" class="form-control newsletter-input" placeholder="<?= htmlspecialchars($t('newsletter_placeholder', 'Email...')) ?>">
                        <button class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2 rounded-3" type="submit" aria-label="<?= htmlspecialchars($t('newsletter_submit', 'Subscribe to updates')) ?>" style="background: var(--primary-neon); border:none; color: var(--button-text);">
                            <?= $iconSvg('fa-paper-plane') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="mt-5 pt-4 border-top border-secondary border-opacity-10 d-flex flex-wrap justify-content-between align-items-center">
            <div class="text-secondary small">&copy; <?= date('Y') ?> CMS-HUB. <?= htmlspecialchars($t('rights_reserved', 'All rights reserved.')) ?></div>
            <div class="d-flex gap-3 align-items-center">
                <span class="text-secondary small opacity-50"><?= htmlspecialchars($t('secured_by', 'Secured by')) ?></span>
                <span class="payment-badge">Visa</span>
                <span class="payment-badge">MC</span>
                <span class="payment-badge">BTC</span>
            </div>
        </div>
    </div>
</footer>

<?php if ($usesStorefrontLiteCss): ?>
    <script src="<?= htmlspecialchars(BASE_URL . '/assets/storefront.js?v=' . $storefrontJsVersion) ?>"></script>
<?php else: ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?= CSP_NONCE ?>">
        document.documentElement.classList.add('app-ready');
    </script>
<?php endif; ?>
</body>
</html>
