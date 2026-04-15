<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="glass-card p-4">
                <h3 class="text-center mb-4 text-light"><?= htmlspecialchars($t('auth_register_title', 'Create Account')) ?></h3>
                
                <?php if(!empty($flashes['error'])): ?>
                    <div class="alert alert-danger text-center">
                        <?= htmlspecialchars((string)$flashes['error'][0], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/register" method="POST">
                    <?= \Src\Core\Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label text-secondary"><?= htmlspecialchars($t('auth_email_address', 'Email Address')) ?></label>
                        <input type="email" name="email" class="form-control bg-dark text-light border-secondary" required placeholder="name@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary"><?= htmlspecialchars($t('auth_password', 'Password')) ?></label>
                        <input type="password" name="password" class="form-control bg-dark text-light border-secondary" required placeholder="<?= htmlspecialchars($t('auth_password_min', 'Min 8 characters')) ?>">
                    </div>
                    <button type="submit" class="btn btn-success w-100"><?= htmlspecialchars($t('auth_sign_up', 'Sign Up')) ?></button>
                </form>
                <div class="text-center mt-3">
                    <a href="<?= BASE_URL ?>/login" class="text-secondary small"><?= htmlspecialchars($t('auth_have_account', 'Already have an account?')) ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
