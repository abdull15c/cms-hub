<?php $isRu = ($langCode ?? 'ru') === 'ru'; ?>
<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="text-light mb-1"><?= htmlspecialchars($t('themes_title', 'Themes')) ?></h2>
            <p class="text-secondary mb-0"><?= htmlspecialchars($isRu ? 'Выбирайте не только палитру, но и стиль подачи главной страницы. Админка остаётся в своём стабильном оформлении.' : 'Choose not only the palette, but also the homepage presentation style. The admin panel keeps its own stable appearance.') ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/settings" class="btn btn-outline-secondary"><?= htmlspecialchars($t('themes_open_settings', 'Brand Settings')) ?></a>
            <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-secondary"><?= htmlspecialchars($t('common_back', 'Back')) ?></a>
        </div>
    </div>

    <div class="glass-card p-4 border-secondary border border-opacity-50 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="text-secondary small mb-1"><?= htmlspecialchars($t('themes_current_label', 'Active Theme')) ?></div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h4 class="text-light mb-0"><?= htmlspecialchars($activeTheme['name'] ?? 'Dark Tech') ?></h4>
                    <span class="badge rounded-pill" style="background: <?= htmlspecialchars(($activeTheme['palette']['badge_bg'] ?? 'rgba(0,242,234,0.12)')) ?>; color: <?= htmlspecialchars(($activeTheme['palette']['badge_text'] ?? '#9ffcf6')) ?>;">
                        <?= htmlspecialchars($activeTheme['badge'] ?? 'Default') ?>
                    </span>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <?php if (!empty($activeTheme['layout_name'])): ?>
                        <span class="theme-meta-chip"><?= htmlspecialchars($isRu ? 'Макет' : 'Layout') ?>: <?= htmlspecialchars($activeTheme['layout_name']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($activeTheme['best_for'])): ?>
                        <span class="theme-meta-chip"><?= htmlspecialchars($isRu ? 'Подходит для' : 'Best for') ?>: <?= htmlspecialchars($activeTheme['best_for']) ?></span>
                    <?php endif; ?>
                </div>
                <p class="text-secondary mb-0 mt-2" style="max-width: 760px;"><?= htmlspecialchars($activeTheme['description'] ?? '') ?></p>
            </div>
            <a href="<?= BASE_URL ?>/" target="_blank" rel="noopener" class="btn btn-cyber px-4"><?= htmlspecialchars($t('themes_preview_site', 'Open Storefront')) ?></a>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($themes as $slug => $themeItem): ?>
            <?php
                $preview = $themeItem['preview'] ?? [];
                $palette = $themeItem['palette'] ?? [];
                $isActive = $activeThemeSlug === $slug;
            ?>
            <div class="col-12 col-lg-6 col-xl-4">
                <div class="theme-card glass-card h-100 border-0 overflow-hidden">
                    <div class="theme-preview p-3" style="background: <?= htmlspecialchars($preview['hero'] ?? 'linear-gradient(135deg, #00f2ea 0%, #0b0f19 55%, #ff0050 100%)') ?>;">
                        <div class="theme-browser">
                            <div class="theme-browser-bar">
                                <span></span><span></span><span></span>
                            </div>
                            <div class="theme-browser-body" style="background: <?= htmlspecialchars($preview['panel'] ?? '#14162a') ?>;">
                                <div class="theme-browser-hero">
                                    <div class="theme-dot" style="background: <?= htmlspecialchars($preview['accent'] ?? '#00f2ea') ?>;"></div>
                                    <div class="theme-lines">
                                        <div></div>
                                        <div></div>
                                    </div>
                                </div>
                                <div class="theme-browser-grid">
                                    <div style="background: <?= htmlspecialchars($preview['accent'] ?? '#00f2ea') ?>;"></div>
                                    <div style="background: <?= htmlspecialchars($preview['accent_alt'] ?? '#ff0050') ?>;"></div>
                                    <div style="background: <?= htmlspecialchars($palette['surface_bg_alt'] ?? '#101828') ?>;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 d-flex flex-column h-100">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                            <div>
                                <h4 class="text-light mb-1"><?= htmlspecialchars($themeItem['name']) ?></h4>
                                <div class="text-secondary small"><?= htmlspecialchars($themeItem['tagline'] ?? '') ?></div>
                            </div>
                            <span class="badge rounded-pill <?= $isActive ? 'text-dark' : 'text-light' ?>" style="background: <?= $isActive ? htmlspecialchars($palette['primary'] ?? '#00f2ea') : 'rgba(255,255,255,0.08)' ?>;">
                                <?= htmlspecialchars($isActive ? $t('themes_status_active', 'Active') : ($themeItem['badge'] ?? 'Theme')) ?>
                            </span>
                        </div>

                        <p class="text-secondary small flex-grow-1 mb-4"><?= htmlspecialchars($themeItem['description'] ?? '') ?></p>

                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <?php if (!empty($themeItem['layout_name'])): ?>
                                <span class="theme-meta-chip"><?= htmlspecialchars($isRu ? 'Макет' : 'Layout') ?>: <?= htmlspecialchars($themeItem['layout_name']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($themeItem['best_for'])): ?>
                                <span class="theme-meta-chip"><?= htmlspecialchars($isRu ? 'Подходит для' : 'Best for') ?>: <?= htmlspecialchars($themeItem['best_for']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex gap-2 align-items-center">
                            <?php if ($isActive): ?>
                                <button class="btn btn-outline-light w-100" disabled><?= htmlspecialchars($t('themes_active_button', 'Current Theme')) ?></button>
                            <?php else: ?>
                                <form action="<?= BASE_URL ?>/admin/themes/activate/<?= urlencode($slug) ?>" method="POST" class="w-100">
                                    <?= \Src\Core\Csrf::field() ?>
                                    <button type="submit" class="btn btn-cyber w-100"><?= htmlspecialchars($t('themes_activate_button', 'Activate Theme')) ?></button>
                                </form>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/?preview_theme=<?= urlencode($slug) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary"><?= htmlspecialchars($t('themes_live_preview', 'Preview')) ?></a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.theme-card {
    transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}

.theme-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.25);
}

.theme-preview {
    min-height: 240px;
}

.theme-browser {
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 20px;
    padding: 14px;
    backdrop-filter: blur(10px);
}

.theme-browser-bar {
    display: flex;
    gap: 8px;
    margin-bottom: 14px;
}

.theme-browser-bar span {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.46);
}

.theme-browser-body {
    border-radius: 16px;
    padding: 18px;
    min-height: 170px;
}

.theme-browser-hero {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 16px;
}

.theme-dot {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.22);
}

.theme-lines {
    flex: 1;
}

.theme-lines div {
    height: 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    margin-bottom: 10px;
}

.theme-lines div:last-child {
    width: 72%;
    margin-bottom: 0;
}

.theme-browser-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.theme-browser-grid div {
    height: 72px;
    border-radius: 14px;
    opacity: 0.9;
}

.theme-meta-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.4rem 0.75rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    color: #cbd5e1;
    font-size: 0.72rem;
    line-height: 1.3;
}
</style>
