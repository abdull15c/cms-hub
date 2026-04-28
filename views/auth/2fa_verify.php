<div class="container py-5" style="max-width: 400px; margin-top: 50px;">
    <div class="glass-card p-4 text-center">
        <div class="mb-4">
            <i class="fa-solid fa-mobile-screen-button fa-4x text-info"></i>
        </div>
        <h3 class="text-light mb-3">Two-Factor Auth</h3>
        <p class="text-secondary small">Enter the 6-digit code from your authenticator app.</p>
        
        <form action="<?= BASE_URL ?>/login" method="POST">
            <?= \Src\Core\Csrf::field() ?>
            <div class="mb-4">
                <input type="text" name="totp_code" class="form-control bg-dark text-light border-info text-center fs-4 letter-spacing-2" placeholder="000 000" maxlength="6" autofocus required autocomplete="off">
            </div>
            <button type="submit" class="btn btn-cyber w-100">Verify & Login</button>
        </form>
        <div class="mt-3">
            <form action="<?= BASE_URL ?>/logout" method="POST" class="d-inline">
                <?= \Src\Core\Csrf::field() ?>
                <button type="submit" class="btn btn-link text-secondary small p-0 text-decoration-none">Cancel</button>
            </form>
        </div>
    </div>
</div>
<style>.letter-spacing-2 { letter-spacing: 5px; }</style>
