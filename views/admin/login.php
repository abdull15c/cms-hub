<div class="container py-5" style="max-width: 400px; margin-top: 50px;">
    <div class="glass-card p-4">
        <h2 class="text-center text-light mb-4"><?= htmlspecialchars($t('admin_login_title', 'Administration')) ?></h2>
        <div class="alert alert-info bg-opacity-25 text-info border-0 small">
            <i class="fa-solid fa-info-circle"></i> <?= htmlspecialchars($t('admin_login_hint', 'Please use the main login page to access the dashboard.')) ?>
        </div>
        <div class="text-center">
            <a href="<?= BASE_URL ?>/login" class="btn btn-cyber w-100"><?= htmlspecialchars($t('admin_login_go', 'Go to Login')) ?></a>
        </div>
    </div>
</div>
