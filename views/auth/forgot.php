<div class="container py-5" style="max-width: 500px;">
    <div class="glass-card p-4">
        <h3 class="text-light mb-3"><?= htmlspecialchars($t('auth_forgot_title', 'Recover Password')) ?></h3>
        <form action="<?= BASE_URL ?>/forgot" method="POST">
            <?= \Src\Core\Csrf::field() ?>
            <div class="mb-3">
                <label class="text-secondary"><?= htmlspecialchars($t('auth_enter_email', 'Enter your email')) ?></label>
                <input type="email" name="email" class="form-control bg-dark text-light border-secondary" required>
            </div>
            <button type="submit" class="btn btn-cyber w-100"><?= htmlspecialchars($t('auth_send_reset', 'Send Reset Link')) ?></button>
        </form>
        <div class="mt-3 text-center">
            <a href="<?= BASE_URL ?>/login" class="text-secondary small"><?= htmlspecialchars($t('auth_back_to_login', 'Back to Login')) ?></a>
        </div>
    </div>
</div>
