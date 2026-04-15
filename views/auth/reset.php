<div class="container py-5" style="max-width: 500px;">
    <div class="glass-card p-4">
        <h3 class="text-light mb-3"><?= htmlspecialchars($t('auth_reset_title', 'Set New Password')) ?></h3>
        <form action="<?= BASE_URL ?>/reset" method="POST">
            <?= \Src\Core\Csrf::field() ?>
            <input type="hidden" name="token" value="<?= $token ?>">
            <div class="mb-3">
                <label class="text-secondary"><?= htmlspecialchars($t('auth_new_password', 'New Password')) ?></label>
                <input type="password" name="password" class="form-control bg-dark text-light border-secondary" required>
            </div>
            <button type="submit" class="btn btn-cyber w-100"><?= htmlspecialchars($t('auth_change_password', 'Change Password')) ?></button>
        </form>
    </div>
</div>
