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
$themeVars['--font-display'] = $usesStorefrontLiteCss
    ? '"Segoe UI Variable Display", "Segoe UI", "Trebuchet MS", system-ui, sans-serif'
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
        <link rel="icon" href="<?= htmlspecialchars(BASE_URL . '/uploads/branding/' . $fav, ENT_QUOTES, 'UTF-8') ?>" type="image/x-icon">
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
        .glitch {
            position: relative;
            display: inline-block;
            color: #fff;
            font-family: var(--font-display);
            font-size: clamp(1.28rem, 1.05rem + 0.72vw, 1.78rem);
            font-weight: 850;
            letter-spacing: -0.035em;
            line-height: 1;
            padding-bottom: 0.32rem;
            text-decoration: none;
            text-shadow: 0 0 18px var(--primary-soft);
        }
        .glitch::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 2px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--primary-neon), var(--secondary-neon));
            box-shadow: 0 0 18px var(--primary-soft);
            opacity: 0.82;
        }
        .glitch span {
            color: #fff;
        }
        .glitch:hover {
            text-shadow: 0 0 24px var(--primary-soft);
        }
        .hover-info:hover { color: var(--primary-neon) !important; transition: 0.2s; }
        .dropdown-menu { background: var(--dropdown-bg); backdrop-filter: blur(10px); border: 1px solid var(--card-border); }
        .dropdown-item:hover { background: var(--dropdown-hover); color: var(--primary-neon) !important; }
        .site-navbar {
            background: rgba(11, 15, 25, 0.92);
            background: color-mix(in srgb, var(--nav-bg) 92%, rgba(8, 12, 20, 0.98));
            backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--nav-border);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
        }
        .site-nav-shell { gap: 1rem; min-height: 78px; }
        .site-nav-branding { display: flex; align-items: center; gap: 0.8rem; min-width: 0; }
        .site-nav-collapse { gap: 0.9rem; }
        .site-nav-links,
        .site-nav-actions { gap: 0.35rem; }
        .site-nav-links .nav-link,
        .site-nav-actions .nav-link {
            border: 1px solid transparent;
            border-radius: 999px;
            padding: 0.6rem 0.9rem;
            color: var(--muted-text);
            transition: 0.2s ease;
        }
        .site-nav-links .nav-link:hover,
        .site-nav-actions .nav-link:hover,
        .site-nav-links .nav-link:focus-visible,
        .site-nav-actions .nav-link:focus-visible {
            color: #fff !important;
            background: rgba(255,255,255,0.04);
            border-color: rgba(255,255,255,0.08);
            text-decoration: none;
        }
        .site-nav-actions .dropdown-toggle,
        .site-nav-actions .nav-link,
        .site-nav-logout {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
        }
        .site-nav-userform { display: flex; align-items: center; }
        .site-nav-logout {
            border: 1px solid transparent;
            border-radius: 999px;
            padding: 0.6rem 0.95rem;
            color: #f38ba8;
            background: transparent;
            transition: 0.2s ease;
        }
        .site-nav-logout:hover,
        .site-nav-logout:focus-visible {
            color: #fff;
            background: rgba(255, 0, 80, 0.12);
            border-color: rgba(255, 0, 80, 0.22);
            text-decoration: none;
        }
        .site-nav-search {
            position: relative;
            display: flex;
            align-items: center;
            flex: 1 1 300px;
            max-width: 360px;
            margin-left: auto;
        }
        .navbar-search-input {
            min-height: 48px;
            padding-right: 3.2rem;
            border-radius: 999px !important;
            background: rgba(255,255,255,0.04);
            color: var(--text-main);
            border-color: rgba(255,255,255,0.08);
        }
        .navbar-search-input::placeholder { color: color-mix(in srgb, var(--muted-text) 90%, #fff); }
        .navbar-search-input:focus {
            background: rgba(255,255,255,0.06);
            color: var(--text-main);
            border-color: var(--primary-neon);
            box-shadow: 0 0 0 0.2rem var(--primary-soft);
        }
        .navbar-search-btn {
            position: absolute;
            top: 50%;
            right: 0.35rem;
            transform: translateY(-50%);
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 999px !important;
            border: 1px solid transparent;
            color: #041518;
            background: linear-gradient(135deg, var(--primary-neon), #7df8f2);
            box-shadow: 0 10px 22px var(--primary-soft);
        }
        .navbar-search-btn:hover {
            border-color: transparent;
            color: #041518;
            background: linear-gradient(135deg, #8ffcf6, var(--primary-neon));
        }
        .theme-chip {
            background: rgba(255,255,255,0.04);
            color: var(--badge-text);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 999px;
            font-size: 0.68rem;
            padding: 0.42rem 0.72rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .theme-chip-inline { box-shadow: inset 0 0 0 1px rgba(255,255,255,0.02); }
        .admin-theme-link:hover { color: var(--primary-neon) !important; }
        .icon-svg { width: 1em; height: 1em; display: inline-block; vertical-align: -0.125em; flex-shrink: 0; }
        .fa-lg { font-size: 1.3333333em; line-height: 0.75em; vertical-align: -0.0667em; }
        .fa-2x { font-size: 2em; }
        .fa-3x { font-size: 3em; }
        .fa-4x { font-size: 4em; }
        .fa-5x { font-size: 5em; }
        body.public-view .glitch {
            color: #fff;
            text-shadow: 0 0 18px var(--primary-soft);
            padding-bottom: 0.32rem;
            font-size: clamp(1.28rem, 1.05rem + 0.72vw, 1.78rem);
            letter-spacing: -0.035em;
        }
        body.public-view .glitch::after,
        body.public-view .theme-chip-inline {
            display: block;
        }
        body.public-view .glitch span {
            color: #fff;
        }
        body.public-view .site-navbar {
            background: rgba(11, 15, 25, 0.92);
            border-bottom: 1px solid var(--nav-border);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
            backdrop-filter: blur(16px);
        }
        body.public-view .site-nav-shell { min-height: 72px; }
        body.public-view .site-nav-links .nav-link,
        body.public-view .site-nav-actions .nav-link {
            color: var(--muted-text) !important;
            background: transparent;
        }
        body.public-view .site-nav-links .nav-link:hover,
        body.public-view .site-nav-actions .nav-link:hover,
        body.public-view .site-nav-links .nav-link:focus-visible,
        body.public-view .site-nav-actions .nav-link:focus-visible {
            color: #fff !important;
            background: rgba(255,255,255,0.04);
            border-color: rgba(255,255,255,0.08);
        }
        body.public-view .navbar-search-input {
            background: rgba(255,255,255,0.04);
            color: var(--text-main);
            border-color: rgba(255,255,255,0.08);
            box-shadow: none;
        }
        body.public-view .navbar-search-input::placeholder { color: var(--muted-text); }
        body.public-view .navbar-search-input:focus {
            background: rgba(255,255,255,0.06);
            color: var(--text-main);
            border-color: var(--primary-neon);
            box-shadow: 0 0 0 0.2rem var(--primary-soft);
        }
        body.public-view .navbar-search-btn {
            background: linear-gradient(135deg, var(--primary-neon), #7df8f2);
            color: #041518;
            border-color: transparent;
            box-shadow: 0 10px 22px var(--primary-soft);
        }
        body.public-view .navbar-search-btn:hover {
            background: linear-gradient(135deg, #8ffcf6, var(--primary-neon));
            color: #041518;
        }
        body.public-view .navbar-toggler {
            color: #fff;
            border-color: rgba(255,255,255,0.2);
        }
        body.public-view .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3E%3Cpath stroke='rgba(255,255,255,0.92)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
        }
        body.public-view .dropdown-menu {
            background: var(--dropdown-bg);
            border-color: var(--card-border);
            box-shadow: 0 18px 50px rgba(0,0,0,0.25);
        }
        body.public-view .dropdown-item { color: var(--text-main) !important; }
        body.public-view .dropdown-item:hover {
            background: var(--dropdown-hover);
            color: var(--primary-neon) !important;
        }
        @media (max-width: 991.98px) {
            .site-nav-shell { min-height: 72px; }
            .site-nav-branding { max-width: calc(100% - 72px); }
            .site-nav-collapse {
                margin-top: 0.85rem;
                padding: 1rem;
                border: 1px solid rgba(255,255,255,0.08);
                border-radius: 1.3rem;
                background: rgba(8, 12, 20, 0.9);
                box-shadow: 0 18px 40px rgba(0,0,0,0.22);
            }
            .site-nav-search {
                order: 1;
                width: 100%;
                max-width: none;
                margin: 0 0 0.5rem;
            }
            .site-nav-links,
            .site-nav-actions {
                width: 100%;
            }
            .site-nav-links { order: 2; }
            .site-nav-actions { order: 3; }
            .site-nav-links .nav-item,
            .site-nav-actions .nav-item,
            .site-nav-userform { width: 100%; }
            .site-nav-links .nav-link,
            .site-nav-actions .nav-link,
            .site-nav-logout {
                width: 100%;
                justify-content: space-between;
                border-radius: 1rem;
                padding: 0.82rem 0.95rem;
            }
            .theme-chip-inline { display: none; }
            body.public-view .site-nav-collapse {
                background: rgba(8, 12, 20, 0.9);
                border-color: rgba(255,255,255,0.08);
                box-shadow: 0 18px 40px rgba(0,0,0,0.22);
            }
        }
        @media (min-width: 992px) {
            .site-nav-shell { flex-wrap: nowrap !important; }
            .site-nav-collapse {
                display: flex !important;
                align-items: center;
                justify-content: flex-end;
                flex-basis: auto;
                width: auto;
            }
            .site-nav-links,
            .site-nav-actions {
                align-items: center;
                flex-direction: row;
            }
            .site-nav-links { margin-left: 0.35rem; }
        }
    </style>
    <meta name="csrf-token" content="<?= \Src\Core\Csrf::token() ?>">
    <?php foreach ($structuredData as $jsonLd): ?>
        <?php if (is_array($jsonLd) && !empty($jsonLd)): ?>
            <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
        <?php endif; ?>
    <?php endforeach; ?>
</head>
<body class="theme-<?= htmlspecialchars($themeSlug) ?> <?= !empty($is_admin_view) ? 'admin-view' : 'public-view' ?>">
<a href="#main-content" class="visually-hidden-focusable position-absolute top-0 start-0 m-3 px-3 py-2 rounded bg-dark text-light text-decoration-none"><?= htmlspecialchars($t('skip_to_content', 'Skip to content')) ?></a>

<?php if(!empty($flashes)): ?>
<div class="position-fixed top-0 end-0 p-3 flash-stack" aria-live="polite">
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

<nav class="navbar navbar-expand-lg navbar-dark sticky-top site-navbar">
  <div class="container site-nav-shell">
    <?php $logo = \Src\Services\SettingsService::get('site_logo'); ?>
    <div class="site-nav-branding">
        <a class="navbar-brand<?= $logo ? '' : ' glitch' ?>" href="<?= htmlspecialchars($publicUrl('/')) ?>">
            <?php if($logo): ?>
                <img src="<?= htmlspecialchars(BASE_URL . '/uploads/branding/' . $logo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) (\Src\Services\SettingsService::get('site_title') ?: $t('site_title', 'CMS-HUB')), ENT_QUOTES, 'UTF-8') ?>" style="height: 40px; max-width: 150px;">
            <?php else: ?>
                <span><?= htmlspecialchars(\Src\Services\SettingsService::get('site_title') ?: $t('site_title', 'CMS-HUB')) ?></span>
            <?php endif; ?>
        </a>
        <?php if (empty($is_admin_view) && !empty($theme['name'])): ?>
            <span class="theme-chip theme-chip-inline"><?= htmlspecialchars($theme['name']) ?></span>
        <?php endif; ?>
    </div>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="<?= htmlspecialchars($t('nav_toggle', 'Toggle navigation')) ?>"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse site-nav-collapse" id="nav">
      <ul class="navbar-nav site-nav-links">
        <li class="nav-item"><a class="nav-link text-secondary hover-info" href="<?= htmlspecialchars($publicUrl('/blog')) ?>"><?= htmlspecialchars($t('nav_blog', 'Blog')) ?></a></li>
        <li class="nav-item"><a class="nav-link text-secondary hover-info" href="<?= htmlspecialchars($publicUrl('/faq')) ?>"><?= htmlspecialchars($t('nav_faq', 'FAQ')) ?></a></li>
      </ul>

      <form class="site-nav-search" action="<?= BASE_URL ?>/" method="GET">
          <input type="hidden" name="lang" value="<?= htmlspecialchars($currentLanguage) ?>">
          <input class="form-control navbar-search-input" type="search" name="q" placeholder="<?= htmlspecialchars($t('nav_search', 'Search...')) ?>" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
          <button class="btn navbar-search-btn" type="submit" aria-label="<?= htmlspecialchars($t('nav_search_submit', 'Search catalog')) ?>"><?= $iconSvg('fa-search') ?></button>
      </form>

      <ul class="navbar-nav site-nav-actions">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-info" href="#" data-bs-toggle="dropdown" aria-haspopup="true">
                <?= $iconSvg('fa-language', 'me-1') ?> <?= htmlspecialchars(strtoupper($langCode ?? 'ru')) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                <li><a class="dropdown-item text-light" href="<?= htmlspecialchars($buildCurrentLink('ru')) ?>" rel="alternate" hreflang="ru"><?= htmlspecialchars($t('lang_ru', 'Russian')) ?></a></li>
                <li><a class="dropdown-item text-light" href="<?= htmlspecialchars($buildCurrentLink('en')) ?>" rel="alternate" hreflang="en"><?= htmlspecialchars($t('lang_en', 'English')) ?></a></li>
            </ul>
        </li>
        <?php $currentCurrency = \Src\Services\CurrencyService::current(); ?>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-warning" href="#" data-bs-toggle="dropdown" aria-haspopup="true">
                <?= $iconSvg('fa-coins', 'me-1') ?> <?= htmlspecialchars($currentCurrency) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                <li><a class="dropdown-item text-light d-flex justify-content-between align-items-center <?= $currentCurrency === 'RUB' ? 'active' : '' ?>" href="<?= BASE_URL ?>/currency/switch/RUB" <?= $currentCurrency === 'RUB' ? 'aria-current="true"' : '' ?>>₽ <?= htmlspecialchars($t('currency_rub', 'Ruble')) ?><?= $currentCurrency === 'RUB' ? $iconSvg('fa-check', 'ms-2 text-success') : '' ?></a></li>
                <li><a class="dropdown-item text-light d-flex justify-content-between align-items-center <?= $currentCurrency === 'USD' ? 'active' : '' ?>" href="<?= BASE_URL ?>/currency/switch/USD" <?= $currentCurrency === 'USD' ? 'aria-current="true"' : '' ?>>$ <?= htmlspecialchars($t('currency_usd', 'Dollar')) ?><?= $currentCurrency === 'USD' ? $iconSvg('fa-check', 'ms-2 text-success') : '' ?></a></li>
                <li><a class="dropdown-item text-light d-flex justify-content-between align-items-center <?= $currentCurrency === 'EUR' ? 'active' : '' ?>" href="<?= BASE_URL ?>/currency/switch/EUR" <?= $currentCurrency === 'EUR' ? 'aria-current="true"' : '' ?>>€ <?= htmlspecialchars($t('currency_eur', 'Euro')) ?><?= $currentCurrency === 'EUR' ? $iconSvg('fa-check', 'ms-2 text-success') : '' ?></a></li>
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
                <form action="<?= BASE_URL ?>/logout" method="POST" class="site-nav-userform">
                    <?= \Src\Core\Csrf::field() ?>
                    <button type="submit" class="nav-link btn btn-link border-0 site-nav-logout"><?= htmlspecialchars($t('nav_logout', 'Logout')) ?></button>
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
