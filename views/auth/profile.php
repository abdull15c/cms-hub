<div class="container py-5">
    <div class="glass-card p-4 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-4">
            <div class="position-relative">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= BASE_URL ?>/uploads/avatars/<?= $user['avatar'] ?>" class="rounded-circle border border-2 border-info shadow" style="width: 80px; height: 80px; object-fit: cover;" alt="<?= htmlspecialchars($user['name'] ?: $t('profile_default_name', 'Pilot')) ?>">
                <?php else: ?>
                    <div class="rounded-circle bg-dark d-flex align-items-center justify-content-center border border-secondary" style="width: 80px; height: 80px;">
                        <i class="fa-solid fa-user-astronaut fa-2x text-secondary"></i>
                    </div>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/profile/settings" class="position-absolute bottom-0 end-0 bg-dark text-light rounded-circle p-1 border border-secondary" title="<?= $t('profile_edit', 'Edit Profile') ?>">
                    <i class="fa-solid fa-pencil small"></i>
                </a>
            </div>
            <div>
                <h2 class="mb-0 text-light fw-bold"><?= htmlspecialchars($user['name'] ?: $t('profile_default_name', 'Pilot')) ?></h2>
                <div class="text-secondary small"><?= htmlspecialchars($user['email']) ?></div>
                <div class="mt-2 d-flex gap-2">
                    <a href="<?= BASE_URL ?>/profile/api" class="badge bg-dark text-decoration-none border border-secondary"><i class="fa-solid fa-code"></i> <?= $t('profile_api', 'API') ?></a>
                    <?php if ($is2faEnabled): ?>
                        <span class="badge bg-success bg-opacity-25 text-success border border-success"><i class="fa-solid fa-shield"></i> <?= $t('profile_2fa_on', '2FA ON') ?></span>
                        <form action="<?= BASE_URL ?>/auth/2fa/disable" method="POST" class="d-inline-flex gap-1 align-items-center">
                            <?= \Src\Core\Csrf::field() ?>
                            <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="2FA code" style="width: 105px;">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Disable</button>
                        </form>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/auth/2fa/setup" class="badge bg-warning bg-opacity-25 text-warning border border-warning text-decoration-none"><i class="fa-solid fa-triangle-exclamation"></i> <?= $t('profile_enable_2fa', 'Enable 2FA') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="bg-black bg-opacity-25 p-3 rounded border border-secondary text-end" style="min-width: 200px;">
            <div class="text-secondary small text-uppercase"><?= $t('profile_balance', 'Balance') ?></div>
            <div class="fs-2 fw-bold text-info">$<?= number_format($user['balance'], 2) ?></div>
            <a href="<?= BASE_URL ?>/wallet" class="btn btn-sm btn-cyber w-100 mt-2"><?= $t('profile_top_up', 'Top Up') ?></a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="glass-card p-3 mb-3 text-center">
                <div class="text-secondary small"><?= $t('profile_total_spent', 'Total Spent') ?></div>
                <div class="fs-4 fw-bold text-white">$<?= number_format($stats['spent'], 2) ?></div>
            </div>
            <div class="glass-card p-3 mb-3 text-center">
                <div class="text-secondary small"><?= $t('profile_products_owned', 'Products Owned') ?></div>
                <div class="fs-4 fw-bold text-white"><?= $stats['count'] ?></div>
            </div>
            <div class="glass-card p-3 mb-3 text-center">
                <div class="text-secondary small"><?= $t('profile_support_tickets', 'Support Tickets') ?></div>
                <div class="fs-4 fw-bold text-warning"><?= $stats['tickets'] ?></div>
                <a href="<?= BASE_URL ?>/tickets" class="btn btn-link btn-sm text-decoration-none text-light"><?= $t('profile_view_all', 'View All') ?></a>
            </div>

            <form action="<?= BASE_URL ?>/logout" method="POST" class="w-100">
                <?= \Src\Core\Csrf::field() ?>
                <button type="submit" class="btn btn-outline-danger w-100"><i class="fa-solid fa-right-from-bracket"></i> <?= $t('profile_logout', 'Logout') ?></button>
            </form>
        </div>

        <div class="col-md-9">
            <h4 class="text-light mb-3"><i class="fa-solid fa-box-open text-info me-2"></i> <?= $t('profile_library', 'My Library') ?></h4>

            <?php if (empty($purchases)): ?>
                <div class="glass-card p-5 text-center">
                    <i class="fa-solid fa-ghost fa-3x text-secondary opacity-25 mb-3"></i>
                    <p class="text-secondary"><?= $t('profile_library_empty', 'Your library is empty.') ?></p>
                    <a href="<?= BASE_URL ?>/" class="btn btn-cyber"><?= $t('profile_browse_catalog', 'Browse Catalog') ?></a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($purchases as $p): ?>
                    <div class="col-md-6">
                        <div class="glass-card p-3 d-flex gap-3 align-items-center h-100">
                            <div class="rounded overflow-hidden flex-shrink-0 border border-secondary" style="width: 80px; height: 80px;">
                                <?php if ($p['thumbnail']): ?>
                                    <img src="<?= BASE_URL ?>/uploads/images/<?= $p['thumbnail'] ?>" class="w-100 h-100 object-fit-cover" alt="<?= htmlspecialchars($p['title']) ?>">
                                <?php else: ?>
                                    <div class="w-100 h-100 bg-dark d-flex align-items-center justify-content-center"><i class="fa-solid fa-cube"></i></div>
                                <?php endif; ?>
                            </div>

                            <div class="flex-grow-1">
                                <h6 class="mb-1 text-truncate"><?= htmlspecialchars($p['title']) ?></h6>
                                <div class="small text-secondary mb-2"><?= date('M d, Y', strtotime($p['created_at'])) ?></div>

                                <div class="d-flex gap-2">
                                    <a href="<?= BASE_URL ?>/download/<?= $p['product_id'] ?>" class="btn btn-xs btn-cyber"><i class="fa-solid fa-download"></i> <?= $t('profile_files', 'Files') ?></a>
                                    <?php if ($p['license_key']): ?>
                                        <button type="button" class="btn btn-xs btn-outline-secondary" data-license-key="<?= htmlspecialchars((string)$p['license_key'], ENT_QUOTES, 'UTF-8') ?>" onclick="prompt('<?= htmlspecialchars($t('profile_license_key', 'License Key:'), ENT_QUOTES, 'UTF-8') ?>', this.dataset.licenseKey)">
                                            <i class="fa-solid fa-key"></i> <?= $t('profile_license', 'License') ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
