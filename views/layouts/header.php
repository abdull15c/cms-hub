<?php
if (!defined('CSP_NONCE')) define('CSP_NONCE', '');
$theme = $theme ?? \Src\Services\ThemeService::forContext((bool)($is_admin_view ?? false));
$themePalette = $theme['palette'] ?? [];
$themeSlug = preg_replace('/[^a-z0-9-]+/', '-', strtolower((string)($theme['slug'] ?? 'dark-tech')));
$pageMeta = is_array($page_meta ?? null) ? $page_meta : [];
$siteTitle = \Src\Services\SettingsService::get('site_title') ?: $t('site_title', 'CMS-HUB');
$pageTitle = trim((string)($pageMeta['title'] ?? $siteTitle));
$metaDescription = trim((string)($pageMeta['description'] ?? ''));
$metaKeywords = trim((string)($pageMeta['keywords'] ?? ''));
$metaRobots = trim((string)($pageMeta['robots'] ?? 'index,follow'));
$canonicalUrl = trim((string)($pageMeta['canonical'] ?? ''));
$alternateUrls = is_array($pageMeta['alternates'] ?? null) ? $pageMeta['alternates'] : [];
$ogType = trim((string)($pageMeta['og_type'] ?? 'website'));
$ogImage = trim((string)($pageMeta['og_image'] ?? ''));
$ogLocale = trim((string)($pageMeta['locale'] ?? (($langCode ?? 'ru') === 'ru' ? 'ru_RU' : 'en_US')));
$structuredData = is_array($pageMeta['structured_data'] ?? null) ? $pageMeta['structured_data'] : [];
$languageQuery = is_array($current_query ?? null) ? $current_query : [];
$currentPath = (string)($current_path ?? '/');
unset($languageQuery['lang']);
$buildCurrentLink = static function (string $language) use ($currentPath, $languageQuery) {
    $params = $languageQuery;
    $params['lang'] = $language;
    $queryString = http_build_query($params);
    return (defined('BASE_URL') ? BASE_URL : '') . ($currentPath === '/' ? '' : $currentPath) . ($queryString !== '' ? '?' . $queryString : '');
};
$currentLanguage = (string)($langCode ?? 'ru');
$publicUrl = static function (string $path = '/', array $params = []) use ($currentLanguage) {
    $normalizedPath = $path === '/' ? '' : $path;
    $queryString = http_build_query(array_merge(['lang' => $currentLanguage], $params));
    return (defined('BASE_URL') ? BASE_URL : '') . $normalizedPath . ($queryString !== '' ? '?' . $queryString : '');
};
$themeVars = [
    '--bg-color' => $themePalette['bg_color'] ?? '#0b0f19',
    '--body-gradient' => $themePalette['body_gradient'] ?? 'linear-gradient(180deg, #0b0f19 0%, #0b0f19 100%)',
    '--card-bg' => $themePalette['card_bg'] ?? 'rgba(30, 41, 59, 0.4)',
    '--card-border' => $themePalette['card_border'] ?? 'rgba(255,255,255,0.08)',
    '--primary-neon' => $themePalette['primary'] ?? '#00f2ea',
    '--secondary-neon' => $themePalette['secondary'] ?? '#ff0050',
    '--primary-soft' => $themePalette['primary_soft'] ?? 'rgba(0,242,234,0.20)',
    '--secondary-soft' => $themePalette['secondary_soft'] ?? 'rgba(255,0,80,0.18)',
    '--text-main' => $themePalette['text_main'] ?? '#e2e8f0',
    '--muted-text' => $themePalette['muted_text'] ?? '#94a3b8',
    '--nav-bg' => $themePalette['nav_bg'] ?? 'rgba(11,15,25,0.95)',
    '--nav-border' => $themePalette['nav_border'] ?? 'rgba(255,255,255,0.10)',
    '--dropdown-bg' => $themePalette['dropdown_bg'] ?? 'rgba(15,23,42,0.96)',
    '--dropdown-hover' => $themePalette['dropdown_hover'] ?? 'rgba(0,242,234,0.10)',
    '--footer-border' => $themePalette['footer_border'] ?? 'rgba(112,0,255,0.20)',
    '--footer-glow' => $themePalette['footer_glow'] ?? 'rgba(112,0,255,0.15)',
    '--surface-bg' => $themePalette['surface_bg'] ?? '#1c1d33',
    '--surface-bg-alt' => $themePalette['surface_bg_alt'] ?? '#14162a',
    '--surface-border' => $themePalette['surface_border'] ?? '#2d2e4f',
    '--button-text' => $themePalette['button_text'] ?? '#02181b',
    '--badge-bg' => $themePalette['badge_bg'] ?? 'rgba(0,242,234,0.12)',
    '--badge-text' => $themePalette['badge_text'] ?? '#9ffcf6',
];
$optimizedStorefrontViews = [
    'home',
    'product',
    'content/blog_index',
    'content/blog_show',
    'content/faq',
    'pages/contact',
    'pages/privacy',
    'pages/terms',
];
$usesStorefrontSvgIcons = empty($is_admin_view) && in_array((string)($view_name ?? ''), $optimizedStorefrontViews, true);
$usesStorefrontLiteCss = $usesStorefrontSvgIcons;
$loadFontAwesome = !empty($is_admin_view) || !$usesStorefrontSvgIcons;
$needsGoogleFonts = !$usesStorefrontLiteCss;
$needsJsDelivr = !$usesStorefrontLiteCss;
$storefrontCssPath = ROOT_PATH . '/public/assets/storefront.css';
$storefrontCssVersion = file_exists($storefrontCssPath) ? (string)filemtime($storefrontCssPath) : '1';
$outfitFontHref = 'https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&display=swap';
$themeVars['--font-sans'] = $usesStorefrontLiteCss
    ? 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
    : '"Outfit", sans-serif';
$iconSvg = static function (string $icon, string $class = '', string $label = ''): string {
    return \Src\Services\StorefrontIcon::render($icon, $class, $label);
};
?>
<!DOCTYPE html>
<html lang="<?= $langCode ?? 'en' ?>" data-bs-theme="<?= htmlspecialchars((string)($themePalette['bootstrap_theme'] ?? 'dark')) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <?php if ($metaDescription !== ''): ?><meta name="description" content="<?= htmlspecialchars($metaDescription) ?>"><?php endif; ?>
    <?php if ($metaKeywords !== ''): ?><meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>"><?php endif; ?>
    <?php if ($metaRobots !== ''): ?><meta name="robots" content="<?= htmlspecialchars($metaRobots) ?>"><?php endif; ?>
    <?php if ($canonicalUrl !== ''): ?><link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>"><?php endif; ?>
    <?php foreach ($alternateUrls as $altLang => $altUrl): ?>
        <?php if ((string)$altUrl !== ''): ?><link rel="alternate" hreflang="<?= htmlspecialchars((string)$altLang) ?>" href="<?= htmlspecialchars((string)$altUrl) ?>"><?php endif; ?>
    <?php endforeach; ?>
    <?php if ($alternateUrls): ?><link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars((string)reset($alternateUrls)) ?>"><?php endif; ?>
    <meta property="og:site_name" content="<?= htmlspecialchars($siteTitle) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:type" content="<?= htmlspecialchars($ogType) ?>">
    <?php if ($metaDescription !== ''): ?><meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>"><?php endif; ?>
    <?php if ($canonicalUrl !== ''): ?><meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>"><?php endif; ?>
    <?php if ($ogImage !== ''): ?><meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>"><?php endif; ?>
    <?php if ($ogLocale !== ''): ?><meta property="og:locale" content="<?= htmlspecialchars($ogLocale) ?>"><?php endif; ?>
    <meta name="twitter:card" content="<?= htmlspecialchars($ogImage !== '' ? 'summary_large_image' : 'summary') ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <?php if ($metaDescription !== ''): ?><meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>"><?php endif; ?>
    <?php if ($ogImage !== ''): ?><meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>"><?php endif; ?>

    <?php $fav = \Src\Services\SettingsService::get('site_favicon'); ?>
    <?php if($fav): ?>
        <link rel="icon" href="<?= BASE_URL ?>/uploads/branding/<?= $fav ?>" type="image/x-icon">
    <?php endif; ?>

    <?php if ($needsGoogleFonts): ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php endif; ?>
    <?php if ($needsJsDelivr): ?><link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin><?php endif; ?>
    <?php if ($loadFontAwesome): ?><link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin><?php endif; ?>
    <?php if ($usesStorefrontLiteCss): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL . '/assets/storefront.css?v=' . $storefrontCssVersion) ?>">
    <?php else: ?>
        <link href="<?= htmlspecialchars($outfitFontHref) ?>" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    <?php if ($loadFontAwesome): ?><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><?php endif; ?>

    <style>
        :root {
<?php foreach ($themeVars as $var => $value): ?>
            <?= $var ?>: <?= $value ?>;
<?php endforeach; ?>
        }
        body { background-color: var(--bg-color); background-image: var(--body-gradient); color: var(--text-main); font-family: var(--font-sans); min-height: 100vh; overflow-x: hidden; cursor: auto; }
        .glass-card { background: var(--card-bg); border: 1px solid var(--card-border); backdrop-filter: blur(10px); border-radius: 16px; position: relative; z-index: 2; }
        .btn-cyber { border: 1px solid var(--primary-neon); color: var(--primary-neon); background: transparent; transition: 0.3s; }
        .btn-cyber:hover { background: var(--primary-neon); color: var(--button-text); box-shadow: 0 0 15px var(--primary-soft); }
        #particles-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; overflow: hidden; }
        .floating-icon { position: absolute; color: var(--primary-neon); opacity: 0.05; animation: floatUp linear infinite; }
        @keyframes floatUp { 0% { transform: translateY(110vh) rotate(0deg); opacity: 0; } 100% { transform: translateY(-10vh) rotate(360deg); opacity: 0; } }
        .glitch { position: relative; color: white; font-weight: 900; letter-spacing: -1px; text-shadow: 0 0 22px var(--primary-soft); }
        .hover-info:hover { color: var(--primary-neon) !important; transition: 0.2s; }
        .dropdown-menu { background: var(--dropdown-bg); backdrop-filter: blur(10px); border: 1px solid var(--card-border); }
        .dropdown-item:hover { background: var(--dropdown-hover); color: var(--primary-neon) !important; }
        .navbar-search-input { background: var(--surface-bg-alt); color: var(--text-main); border-color: var(--surface-border); }
        .navbar-search-input:focus { background: var(--surface-bg); color: var(--text-main); border-color: var(--primary-neon); box-shadow: 0 0 0 0.2rem var(--primary-soft); }
        .navbar-search-btn { border-color: var(--surface-border); color: var(--muted-text); }
        .navbar-search-btn:hover { border-color: var(--primary-neon); color: var(--primary-neon); background: transparent; }
        .theme-chip { background: var(--badge-bg); color: var(--badge-text); border: 1px solid transparent; border-radius: 999px; font-size: 0.72rem; padding: 0.25rem 0.55rem; }
        .admin-theme-link:hover { color: var(--primary-neon) !important; }
        .icon-svg { width: 1em; height: 1em; display: inline-block; vertical-align: -0.125em; flex-shrink: 0; }
        .fa-lg { font-size: 1.3333333em; line-height: 0.75em; vertical-align: -0.0667em; }
        .fa-2x { font-size: 2em; }
        .fa-3x { font-size: 3em; }
        .fa-4x { font-size: 4em; }
        .fa-5x { font-size: 5em; }
    </style>
    <meta name="csrf-token" content="<?= \Src\Core\Csrf::token() ?>">
    <?php foreach ($structuredData as $jsonLd): ?>
        <?php if (is_array($jsonLd) && !empty($jsonLd)): ?>
            <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
        <?php endif; ?>
    <?php endforeach; ?>
</head>
<body class="theme-<?= htmlspecialchars($themeSlug) ?> <?= !empty($is_admin_view) ? 'admin-view' : 'public-view' ?>">

<?php if (empty($is_admin_view)): ?>
<div id="particles-container"></div>
<?php endif; ?>

<?php if(!empty($flashes)): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999">
    <?php foreach($flashes as $type => $messages): ?>
        <?php foreach($messages as $msg): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show glass-card border-<?= $type === 'error' ? 'danger' : 'success' ?> text-light shadow-lg" role="alert">
                <?= $iconSvg($type === 'error' ? 'fa-circle-xmark' : 'fa-circle-check', 'me-2') ?> <?= htmlspecialchars($msg) ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="<?= htmlspecialchars($t('close_alert', 'Dismiss message')) ?>"></button>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: var(--nav-bg); backdrop-filter: blur(10px); border-bottom: 1px solid var(--nav-border);">
  <div class="container">
    <a class="navbar-brand glitch" href="<?= htmlspecialchars($publicUrl('/')) ?>">
        <?php $logo = \Src\Services\SettingsService::get('site_logo'); ?>
        <?php if($logo): ?>
            <img src="<?= BASE_URL ?>/uploads/branding/<?= $logo ?>" alt="Logo" style="height: 40px; max-width: 150px;">
        <?php else: ?>
            <?= htmlspecialchars(\Src\Services\SettingsService::get('site_title') ?: $t('site_title', 'CMS-HUB')) ?>
        <?php endif; ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="<?= htmlspecialchars($t('nav_toggle', 'Toggle navigation')) ?>"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <form class="d-flex mx-auto my-2 my-lg-0" action="<?= BASE_URL ?>/" method="GET" style="width: 100%; max-width: 400px;">
          <input type="hidden" name="lang" value="<?= htmlspecialchars($currentLanguage) ?>">
          <input class="form-control navbar-search-input rounded-start" type="search" name="q" placeholder="<?= htmlspecialchars($t('nav_search', 'Search...')) ?>" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
          <button class="btn navbar-search-btn rounded-end" type="submit" aria-label="<?= htmlspecialchars($t('nav_search_submit', 'Search catalog')) ?>"><?= $iconSvg('fa-search') ?></button>
      </form>

      <ul class="navbar-nav ms-3 d-none d-lg-flex">
        <li class="nav-item"><a class="nav-link text-secondary hover-info" href="<?= htmlspecialchars($publicUrl('/blog')) ?>"><?= htmlspecialchars($t('nav_blog', 'Blog')) ?></a></li>
        <li class="nav-item"><a class="nav-link text-secondary hover-info" href="<?= htmlspecialchars($publicUrl('/faq')) ?>"><?= htmlspecialchars($t('nav_faq', 'FAQ')) ?></a></li>
      </ul>

      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item dropdown me-3">
            <a class="nav-link dropdown-toggle text-info" href="#" data-bs-toggle="dropdown">
                <?= $iconSvg('fa-language', 'me-1') ?> <?= htmlspecialchars(strtoupper($langCode ?? 'ru')) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                <li><a class="dropdown-item text-light" href="<?= htmlspecialchars($buildCurrentLink('ru')) ?>" rel="alternate" hreflang="ru"><?= htmlspecialchars($t('lang_ru', 'Russian')) ?></a></li>
                <li><a class="dropdown-item text-light" href="<?= htmlspecialchars($buildCurrentLink('en')) ?>" rel="alternate" hreflang="en"><?= htmlspecialchars($t('lang_en', 'English')) ?></a></li>
            </ul>
        </li>
        <li class="nav-item dropdown me-3">
            <a class="nav-link dropdown-toggle text-warning" href="#" data-bs-toggle="dropdown">
                <?= $iconSvg('fa-coins', 'me-1') ?> <?= \Src\Services\SessionService::get('currency', 'RUB') ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                <li><a class="dropdown-item text-light" href="<?= BASE_URL ?>/currency/switch/RUB">₽ <?= htmlspecialchars($t('currency_rub', 'Ruble')) ?></a></li>
                <li><a class="dropdown-item text-light" href="<?= BASE_URL ?>/currency/switch/USD">$ <?= htmlspecialchars($t('currency_usd', 'Dollar')) ?></a></li>
                <li><a class="dropdown-item text-light" href="<?= BASE_URL ?>/currency/switch/EUR">€ <?= htmlspecialchars($t('currency_eur', 'Euro')) ?></a></li>
            </ul>
        </li>

        <?php if(!empty($user_id)): ?>
            <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($publicUrl('/profile')) ?>"><?= htmlspecialchars($t('nav_profile', 'Profile')) ?></a></li>
            <?php if(($role ?? 'guest') === 'admin'): ?>
                <li class="nav-item"><a class="nav-link text-warning" title="<?= htmlspecialchars($t('nav_admin', 'Admin')) ?>" href="<?= BASE_URL ?>/admin/dashboard"><?= $iconSvg('fa-shield-halved') ?></a></li>
                <li class="nav-item"><a class="nav-link text-primary" title="<?= htmlspecialchars($t('nav_products', 'Products')) ?>" href="<?= BASE_URL ?>/admin/products"><?= $iconSvg('fa-boxes-stacked') ?></a></li>
                <li class="nav-item"><a class="nav-link text-warning" title="<?= htmlspecialchars($t('nav_categories', 'Categories')) ?>" href="<?= BASE_URL ?>/admin/categories"><?= $iconSvg('fa-tags') ?></a></li>
                <li class="nav-item"><a class="nav-link text-info" title="<?= htmlspecialchars($t('nav_analytics', 'Analytics')) ?>" href="<?= BASE_URL ?>/admin/analytics"><?= $iconSvg('fa-chart-simple') ?></a></li>
                <li class="nav-item"><a class="nav-link admin-theme-link text-light" title="<?= htmlspecialchars($t('nav_themes', 'Themes')) ?>" href="<?= BASE_URL ?>/admin/themes"><?= $iconSvg('fa-palette') ?></a></li>
                <li class="nav-item"><a class="nav-link text-secondary" title="<?= htmlspecialchars($t('nav_settings', 'Settings')) ?>" href="<?= BASE_URL ?>/admin/settings"><?= $iconSvg('fa-cog') ?></a></li>
            <?php endif; ?>
            <li class="nav-item">
                <form action="<?= BASE_URL ?>/logout" method="POST" class="d-inline">
                    <?= \Src\Core\Csrf::field() ?>
                    <button type="submit" class="nav-link btn btn-link text-danger border-0 p-0 ms-2"><?= htmlspecialchars($t('nav_logout', 'Logout')) ?></button>
                </form>
            </li>
        <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($publicUrl('/login')) ?>"><?= htmlspecialchars($t('nav_login', 'Login')) ?></a></li>
            <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($publicUrl('/register')) ?>"><?= htmlspecialchars($t('nav_register', 'Register')) ?></a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<main id="main-content">
<?php if (empty($is_admin_view) && !empty($theme['name'])): ?>
    <div class="container mt-3">
        <div class="d-flex justify-content-end">
            <span class="theme-chip"><?= htmlspecialchars($theme['name']) ?></span>
        </div>
    </div>
<?php endif; ?>
