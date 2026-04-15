<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="glass-card p-4">
                <h3 class="text-center mb-4 text-light"><?= htmlspecialchars($t('auth_welcome_back', 'Welcome Back')) ?></h3>
                <form action="<?= BASE_URL ?>/login" method="POST">
                    <?= \Src\Core\Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label text-secondary"><?= htmlspecialchars($t('auth_email', 'Email')) ?></label>
                        <input type="email" name="email" class="form-control bg-dark text-light border-secondary" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary"><?= htmlspecialchars($t('auth_password', 'Password')) ?></label>
                        <input type="password" name="password" class="form-control bg-dark text-light border-secondary" required>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember">
                            <label class="form-check-label text-secondary small" for="remember"><?= htmlspecialchars($t('auth_remember', 'Remember me')) ?></label>
                        </div>
                        <a href="<?= BASE_URL ?>/forgot" class="text-info small"><?= htmlspecialchars($t('auth_forgot', 'Forgot Password?')) ?></a>
                    </div>
                    <div class="text-center my-3 text-secondary position-relative">
                        <hr class="border-secondary opacity-25">
                        <span class="position-absolute top-50 start-50 translate-middle bg-dark px-2 small"><?= htmlspecialchars($t('auth_or', 'OR')) ?></span>
                    </div>
                    <div class="d-grid gap-2 mb-3">
                        <a href="<?= BASE_URL ?>/auth/google" class="btn btn-outline-danger"><i class="fa-brands fa-google me-2"></i> <?= htmlspecialchars($t('auth_continue_google', 'Continue with Google')) ?></a>
                        <a href="<?= BASE_URL ?>/auth/github" class="btn btn-outline-light"><i class="fa-brands fa-github me-2"></i> <?= htmlspecialchars($t('auth_continue_github', 'Continue with GitHub')) ?></a>
                    </div>
                    <button type="submit" class="btn btn-cyber w-100"><?= htmlspecialchars($t('auth_login_button', 'Login')) ?></button>
                </form>
                <div class="text-center mt-3"><a href="<?= BASE_URL ?>/register" class="text-secondary small"><?= htmlspecialchars($t('auth_create_account', 'Create new account')) ?></a></div>
            </div>
        </div>
    </div>
</div>
