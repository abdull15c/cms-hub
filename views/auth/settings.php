<div class="container py-5" style="max-width: 700px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-light"><?= htmlspecialchars($t('settings_account', 'Account Settings')) ?></h2>
        <a href="<?= BASE_URL ?>/profile" class="btn btn-secondary"><?= htmlspecialchars($t('common_back', 'Back')) ?></a>
    </div>

    <div class="glass-card p-5">
        <form action="<?= BASE_URL ?>/profile/settings/update" method="POST" enctype="multipart/form-data">
            <?= \Src\Core\Csrf::field() ?>
            
            <div class="mb-4">
                <label class="text-secondary mb-3 d-block"><?= htmlspecialchars($t('settings_choose_avatar', 'Choose Avatar')) ?></label>
                <div class="d-flex gap-3 mb-3 justify-content-center flex-wrap">
                    <?php if(!empty($default_avatars)): ?>
                        <?php foreach($default_avatars as $da): ?>
                            <label style="cursor: pointer;">
                                <input type="radio" name="default_avatar" value="<?= $da ?>" class="d-none peer">
                                <img src="<?= BASE_URL ?>/uploads/avatars/defaults/<?= $da ?>" class="rounded-circle border border-2 border-secondary peer-checked:border-info" style="width: 60px; height: 60px;">
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="text-center">
                    <label class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-upload me-2"></i> <?= htmlspecialchars($t('settings_upload_custom', 'Upload Custom')) ?>
                        <input type="file" name="avatar" class="d-none" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    </label>
                </div>
            </div>

            <div class="mb-3">
                <label class="text-secondary"><?= htmlspecialchars($t('settings_display_name', 'Display Name')) ?></label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" class="form-control bg-dark text-light border-secondary" placeholder="<?= htmlspecialchars($t('settings_nickname', 'Your Nickname')) ?>">
            </div>

            <div class="mb-3">
                <label class="text-secondary"><?= htmlspecialchars($t('settings_email_readonly', 'Email (Read Only)')) ?></label>
                <input type="text" value="<?= htmlspecialchars($user['email']) ?>" class="form-control bg-black text-secondary border-0" readonly>
            </div>

            <hr class="border-secondary opacity-25 my-4">
            
            <h5 class="text-warning mb-3"><i class="fa-solid fa-lock"></i> <?= htmlspecialchars($t('settings_security', 'Security')) ?></h5>
            <?php if (empty($user['oauth_provider'])): ?>
                <div class="mb-3">
                    <label class="text-secondary"><?= htmlspecialchars($t('settings_current_password', 'Current Password')) ?></label>
                    <input type="password" name="current_password" class="form-control bg-dark text-light border-secondary" placeholder="<?= htmlspecialchars($t('settings_current_password_hint', 'Required to change your password')) ?>">
                </div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="text-secondary"><?= htmlspecialchars($t('settings_new_password', 'New Password')) ?></label>
                <input type="password" name="password" class="form-control bg-dark text-light border-secondary" placeholder="<?= htmlspecialchars($t('settings_keep_current', 'Leave empty to keep current')) ?>">
                <small class="text-secondary d-block mt-2"><?= htmlspecialchars($t('settings_password_min', 'Minimum 8 characters')) ?></small>
            </div>

            <button type="submit" class="btn btn-cyber w-100 btn-lg mt-3"><?= htmlspecialchars($t('settings_save_changes', 'Save Changes')) ?></button>
        </form>
    </div>
</div>
<style>
input[type="radio"]:checked + img {
    border-color: #00f2ea !important;
    box-shadow: 0 0 10px #00f2ea;
    transform: scale(1.1);
    transition: 0.2s;
}
</style>
