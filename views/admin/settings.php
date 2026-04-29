<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-light"><?= htmlspecialchars($t('admin_settings_title', 'System Configuration')) ?></h2>
        <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-secondary"><?= htmlspecialchars($t('common_back', 'Back')) ?></a>
    </div>

    <form action="<?= BASE_URL ?>/admin/settings/save" method="POST" enctype="multipart/form-data">
        <?= \Src\Core\Csrf::field() ?>

        <ul class="nav nav-pills mb-4 gap-2" id="settingsTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active btn-outline-light" data-bs-toggle="tab" data-bs-target="#tab-general" type="button"><i class="fa-solid fa-sliders"></i> <?= htmlspecialchars($t('admin_settings_general', 'General')) ?></button></li>
            <li class="nav-item"><button class="nav-link btn-outline-light" data-bs-toggle="tab" data-bs-target="#tab-payments" type="button"><i class="fa-solid fa-wallet"></i> <?= htmlspecialchars($t('admin_settings_gateways', 'Gateways')) ?></button></li>
            <li class="nav-item"><button class="nav-link btn-outline-light" data-bs-toggle="tab" data-bs-target="#tab-social" type="button"><i class="fa-brands fa-google"></i> <?= htmlspecialchars($t('admin_settings_social', 'Social Auth')) ?></button></li>
            <li class="nav-item"><button class="nav-link btn-outline-light" data-bs-toggle="tab" data-bs-target="#tab-ai" type="button"><i class="fa-solid fa-robot"></i> <?= htmlspecialchars($t('admin_settings_ai', 'AI Integration')) ?></button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-general">
                <div class="glass-card p-4 border-secondary border border-opacity-50">
                    <h4 class="text-light mb-3"><?= htmlspecialchars($t('admin_settings_branding', 'Branding & System')) ?></h4>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="text-secondary"><?= htmlspecialchars($t('admin_settings_site_title', 'Site Title')) ?></label>
                            <input type="text" name="site_title" value="<?= htmlspecialchars($s['site_title'] ?? 'CMS-HUB') ?>" class="form-control bg-dark text-light border-secondary">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-secondary"><?= htmlspecialchars($t('admin_settings_logo', 'Logo')) ?></label>
                            <input type="file" name="site_logo" class="form-control bg-dark text-light border-secondary" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">
                            <?php if(!empty($s['site_logo'])): ?><img src="<?= BASE_URL ?>/uploads/branding/<?= $s['site_logo'] ?>" class="mt-2" style="height: 40px;"><?php endif; ?>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-secondary"><?= htmlspecialchars($t('admin_settings_favicon', 'Favicon')) ?></label>
                            <input type="file" name="site_favicon" class="form-control bg-dark text-light border-secondary" accept=".ico,.png,image/x-icon,image/vnd.microsoft.icon,image/png">
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-md-6 mb-3">
                            <label class="text-secondary"><?= htmlspecialchars($t('admin_settings_hero_title', 'Hero Title')) ?></label>
                            <input type="text" name="hero_title" value="<?= htmlspecialchars($s['hero_title'] ?? '') ?>" class="form-control bg-dark text-light border-secondary" placeholder="<?= htmlspecialchars($t('admin_settings_hero_title_placeholder', 'Ready-made sites, scripts and templates')) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-secondary"><?= htmlspecialchars($t('admin_settings_hero_primary_cta', 'Hero Primary Button')) ?></label>
                            <input type="text" name="hero_primary_cta" value="<?= htmlspecialchars($s['hero_primary_cta'] ?? '') ?>" class="form-control bg-dark text-light border-secondary" placeholder="<?= htmlspecialchars($t('admin_settings_hero_primary_cta_placeholder', 'Browse catalog')) ?>">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="text-secondary"><?= htmlspecialchars($t('admin_settings_hero_subtitle', 'Hero Subtitle')) ?></label>
                            <textarea name="hero_subtitle" rows="3" class="form-control bg-dark text-light border-secondary" placeholder="<?= htmlspecialchars($t('admin_settings_hero_subtitle_placeholder', 'Short storefront pitch for ready-made products, scripts and templates.')) ?>"><?= htmlspecialchars($s['hero_subtitle'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-secondary"><?= htmlspecialchars($t('admin_settings_hero_secondary_cta', 'Hero Secondary Button')) ?></label>
                            <input type="text" name="hero_secondary_cta" value="<?= htmlspecialchars($s['hero_secondary_cta'] ?? '') ?>" class="form-control bg-dark text-light border-secondary" placeholder="<?= htmlspecialchars($t('admin_settings_hero_secondary_cta_placeholder', 'Why Dark Tech')) ?>">
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-md-6 mb-3">
                            <label class="text-secondary"><?= htmlspecialchars($t('admin_settings_footer_text', 'Footer Description')) ?></label>
                            <textarea name="footer_text" rows="3" class="form-control bg-dark text-light border-secondary" placeholder="<?= htmlspecialchars($t('admin_settings_footer_text_placeholder', 'Short footer text about your marketplace.')) ?>"><?= htmlspecialchars($s['footer_text'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="text-secondary"><?= htmlspecialchars($t('admin_settings_contact_email', 'Contact Email')) ?></label>
                                    <input type="email" name="contact_email" value="<?= htmlspecialchars($s['contact_email'] ?? '') ?>" class="form-control bg-dark text-light border-secondary" placeholder="support@example.com">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-secondary"><?= htmlspecialchars($t('admin_settings_telegram_url', 'Telegram URL')) ?></label>
                                    <input type="url" name="telegram_url" value="<?= htmlspecialchars($s['telegram_url'] ?? '') ?>" class="form-control bg-dark text-light border-secondary" placeholder="https://t.me/...">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-secondary"><?= htmlspecialchars($t('admin_settings_discord_url', 'Discord URL')) ?></label>
                                    <input type="url" name="discord_url" value="<?= htmlspecialchars($s['discord_url'] ?? '') ?>" class="form-control bg-dark text-light border-secondary" placeholder="https://discord.gg/...">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="text-secondary"><?= htmlspecialchars($t('admin_settings_youtube_url', 'YouTube URL')) ?></label>
                                    <input type="url" name="youtube_url" value="<?= htmlspecialchars($s['youtube_url'] ?? '') ?>" class="form-control bg-dark text-light border-secondary" placeholder="https://youtube.com/...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="border-secondary opacity-25">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" <?= ($s['maintenance_mode']??0) ? 'checked' : '' ?>>
                        <label class="form-check-label text-warning fw-bold"><?= htmlspecialchars($t('admin_settings_maintenance', 'Maintenance Mode (Site Offline)')) ?></label>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-payments">
                <div class="row g-4">
                    <div class="col-md-6"><div class="glass-card p-4 border-warning h-100"><div class="d-flex justify-content-between mb-3"><h5 class="text-warning"><i class="fa-solid fa-ruble-sign"></i> YooMoney</h5><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="yoomoney_enabled" value="1" <?= ($s['yoomoney_enabled']??0) ? 'checked' : '' ?>></div></div><input type="text" name="yoomoney_wallet" value="<?= htmlspecialchars($s['yoomoney_wallet'] ?? '') ?>" class="form-control bg-dark text-light border-secondary mb-2" placeholder="<?= htmlspecialchars($t('admin_settings_wallet_number', 'Wallet Number')) ?>"><input type="password" name="yoomoney_secret" value="<?= htmlspecialchars($s['yoomoney_secret'] ?? '') ?>" class="form-control bg-dark text-light border-secondary" placeholder="<?= htmlspecialchars($t('admin_settings_secret_key', 'Secret Key')) ?>"></div></div>
                    <div class="col-md-6"><div class="glass-card p-4 border-info h-100"><div class="d-flex justify-content-between mb-3"><h5 class="text-info"><i class="fa-solid fa-credit-card"></i> YooKassa</h5><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="yookassa_enabled" value="1" <?= ($s['yookassa_enabled']??0) ? 'checked' : '' ?>></div></div><input type="text" name="yookassa_shop_id" value="<?= htmlspecialchars($s['yookassa_shop_id'] ?? '') ?>" class="form-control bg-dark text-light border-secondary mb-2" placeholder="<?= htmlspecialchars($t('admin_settings_shop_id', 'Shop ID')) ?>"><input type="password" name="yookassa_secret_key" value="<?= htmlspecialchars($s['yookassa_secret_key'] ?? '') ?>" class="form-control bg-dark text-light border-secondary mb-2" placeholder="<?= htmlspecialchars($t('admin_settings_secret_key', 'Secret Key')) ?>"><input type="text" name="yookassa_currency" value="<?= htmlspecialchars($s['yookassa_currency'] ?? 'RUB') ?>" class="form-control bg-dark text-light border-secondary" placeholder="RUB"><div class="alert alert-dark small mt-2 py-2 mb-0">Webhook: <code><?= BASE_URL ?>/payment/webhook/yookassa</code></div></div></div>
                    <div class="col-md-6"><div class="glass-card p-4 border-primary h-100"><div class="d-flex justify-content-between mb-3"><h5 class="text-primary"><i class="fa-solid fa-p"></i> Payeer</h5><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="payeer_enabled" value="1" <?= ($s['payeer_enabled']??0) ? 'checked' : '' ?>></div></div><input type="text" name="payeer_merchant_id" value="<?= htmlspecialchars($s['payeer_merchant_id'] ?? '') ?>" class="form-control bg-dark text-light border-secondary mb-2" placeholder="<?= htmlspecialchars($t('admin_settings_merchant_id', 'Merchant ID')) ?>"><input type="password" name="payeer_secret_key" value="<?= htmlspecialchars($s['payeer_secret_key'] ?? '') ?>" class="form-control bg-dark text-light border-secondary" placeholder="<?= htmlspecialchars($t('admin_settings_secret_key', 'Secret Key')) ?>"></div></div>
                    <div class="col-md-6"><div class="glass-card p-4 border-success h-100"><div class="d-flex justify-content-between mb-3"><h5 class="text-success"><i class="fa-brands fa-bitcoin"></i> Cryptomus</h5><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="cryptomus_enabled" value="1" <?= ($s['cryptomus_enabled']??0) ? 'checked' : '' ?>></div></div><input type="text" name="cryptomus_merchant_uuid" value="<?= htmlspecialchars($s['cryptomus_merchant_uuid'] ?? '') ?>" class="form-control bg-dark text-light border-secondary mb-2" placeholder="Merchant UUID"><input type="password" name="cryptomus_payment_key" value="<?= htmlspecialchars($s['cryptomus_payment_key'] ?? '') ?>" class="form-control bg-dark text-light border-secondary mb-2" placeholder="Payment API Key"><input type="text" name="cryptomus_currency" value="<?= htmlspecialchars($s['cryptomus_currency'] ?? 'USD') ?>" class="form-control bg-dark text-light border-secondary" placeholder="USD"><div class="alert alert-dark small mt-2 py-2 mb-0">Webhook: <code><?= BASE_URL ?>/payment/webhook/cryptomus</code></div></div></div>
                    <div class="col-md-6"><div class="glass-card p-4 border-light h-100"><div class="d-flex justify-content-between mb-3"><h5 class="text-light"><i class="fa-solid fa-globe"></i> Lemon Squeezy</h5><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="lemonsqueezy_enabled" value="1" <?= ($s['lemonsqueezy_enabled']??0) ? 'checked' : '' ?>></div></div><input type="text" name="lemonsqueezy_store_id" value="<?= htmlspecialchars($s['lemonsqueezy_store_id'] ?? '') ?>" class="form-control bg-dark text-light border-secondary mb-2" placeholder="Store ID"><input type="text" name="lemonsqueezy_variant_id" value="<?= htmlspecialchars($s['lemonsqueezy_variant_id'] ?? '') ?>" class="form-control bg-dark text-light border-secondary mb-2" placeholder="Variant ID"><input type="password" name="lemonsqueezy_api_key" value="<?= htmlspecialchars($s['lemonsqueezy_api_key'] ?? '') ?>" class="form-control bg-dark text-light border-secondary mb-2" placeholder="API Key"><input type="password" name="lemonsqueezy_webhook_secret" value="<?= htmlspecialchars($s['lemonsqueezy_webhook_secret'] ?? '') ?>" class="form-control bg-dark text-light border-secondary mb-2" placeholder="Webhook Signing Secret"><input type="text" name="lemonsqueezy_currency" value="<?= htmlspecialchars($s['lemonsqueezy_currency'] ?? 'USD') ?>" class="form-control bg-dark text-light border-secondary" placeholder="USD"><div class="alert alert-dark small mt-2 py-2 mb-0">Webhook: <code><?= BASE_URL ?>/payment/webhook/lemonsqueezy</code></div></div></div>
                    <div class="col-md-6"><div class="glass-card p-4 border-primary h-100"><div class="d-flex justify-content-between mb-3"><h5 class="text-primary"><i class="fa-brands fa-cc-stripe"></i> Stripe</h5><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="stripe_enabled" value="1" <?= ($s['stripe_enabled']??0) ? 'checked' : '' ?>></div></div><input type="password" name="stripe_secret_key" value="<?= htmlspecialchars($s['stripe_secret_key'] ?? '') ?>" class="form-control bg-dark text-light border-secondary mb-2" placeholder="Secret Key (sk_...)"><input type="password" name="stripe_webhook_secret" value="<?= htmlspecialchars($s['stripe_webhook_secret'] ?? '') ?>" class="form-control bg-dark text-light border-secondary mb-2" placeholder="Webhook Secret (whsec_...)"><input type="text" name="stripe_currency" value="<?= htmlspecialchars($s['stripe_currency'] ?? 'USD') ?>" class="form-control bg-dark text-light border-secondary" placeholder="USD"><div class="alert alert-dark small mt-2 py-2 mb-0">Webhook: <code><?= BASE_URL ?>/payment/webhook/stripe</code></div></div></div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-social">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="glass-card p-4 border-info">
                            <h5 class="text-info mb-3"><i class="fa-brands fa-google"></i> Google OAuth</h5>
                            <div class="mb-2"><label class="text-secondary small"><?= htmlspecialchars($t('admin_settings_client_id', 'Client ID')) ?></label><input type="text" name="google_client_id" value="<?= htmlspecialchars($s['google_client_id'] ?? '') ?>" class="form-control bg-dark text-light border-secondary"></div>
                            <div class="mb-2"><label class="text-secondary small"><?= htmlspecialchars($t('admin_settings_client_secret', 'Client Secret')) ?></label><input type="password" name="google_client_secret" value="<?= htmlspecialchars($s['google_client_secret'] ?? '') ?>" class="form-control bg-dark text-light border-secondary"></div>
                            <div class="alert alert-dark small mt-2 py-2"><?= htmlspecialchars($t('admin_settings_callback_uri', 'URI')) ?>: <code><?= BASE_URL ?>/auth/callback/google</code></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="glass-card p-4 border-light">
                            <h5 class="text-light mb-3"><i class="fa-brands fa-github"></i> GitHub OAuth</h5>
                            <div class="mb-2"><label class="text-secondary small"><?= htmlspecialchars($t('admin_settings_client_id', 'Client ID')) ?></label><input type="text" name="github_client_id" value="<?= htmlspecialchars($s['github_client_id'] ?? '') ?>" class="form-control bg-dark text-light border-secondary"></div>
                            <div class="mb-2"><label class="text-secondary small"><?= htmlspecialchars($t('admin_settings_client_secret', 'Client Secret')) ?></label><input type="password" name="github_client_secret" value="<?= htmlspecialchars($s['github_client_secret'] ?? '') ?>" class="form-control bg-dark text-light border-secondary"></div>
                            <div class="alert alert-dark small mt-2 py-2"><?= htmlspecialchars($t('admin_settings_callback_uri', 'URI')) ?>: <code><?= BASE_URL ?>/auth/callback/github</code></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-ai">
                <div class="glass-card p-4 border-info">
                    <h4 class="text-info mb-4"><i class="fa-solid fa-brain"></i> <?= htmlspecialchars($t('admin_settings_ai_full', 'Artificial Intelligence')) ?></h4>
                    <div class="mb-4">
                        <label class="text-secondary"><?= htmlspecialchars($t('admin_settings_provider', 'Active Provider')) ?></label>
                        <select name="ai_provider" class="form-control bg-dark text-light border-secondary w-50">
                            <option value="openai" <?= ($s['ai_provider']??'')=='openai'?'selected':'' ?>><?= htmlspecialchars($t('admin_settings_openai_provider', 'OpenAI (ChatGPT)')) ?></option>
                            <option value="gemini" <?= ($s['ai_provider']??'')=='gemini'?'selected':'' ?>><?= htmlspecialchars($t('admin_settings_gemini_provider', 'Google Gemini')) ?></option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 border-end border-secondary">
                            <h6 class="text-light"><?= htmlspecialchars($t('admin_settings_openai', 'OpenAI Settings')) ?></h6>
                            <div class="mb-2"><label class="text-secondary small"><?= htmlspecialchars($t('admin_settings_api_key', 'API Key')) ?></label><input type="password" name="openai_key" value="<?= htmlspecialchars($s['openai_key'] ?? '') ?>" class="form-control bg-dark text-light border-secondary"></div>
                            <div class="mb-2"><label class="text-secondary small"><?= htmlspecialchars($t('admin_settings_model', 'Model')) ?></label><input type="text" name="openai_model" value="<?= htmlspecialchars($s['openai_model'] ?? 'gpt-4o-mini') ?>" class="form-control bg-dark text-light border-secondary" placeholder="gpt-4o-mini"></div>
                        </div>
                        <div class="col-md-6 ps-4">
                            <h6 class="text-light"><?= htmlspecialchars($t('admin_settings_gemini', 'Google Gemini Settings')) ?></h6>
                            <div class="mb-2"><label class="text-secondary small"><?= htmlspecialchars($t('admin_settings_api_key', 'API Key')) ?></label><input type="password" name="gemini_key" value="<?= htmlspecialchars($s['gemini_key'] ?? '') ?>" class="form-control bg-dark text-light border-secondary"></div>
                            <div class="mb-2"><label class="text-secondary small"><?= htmlspecialchars($t('admin_settings_model', 'Model')) ?></label><input type="text" name="gemini_model" value="<?= htmlspecialchars($s['gemini_model'] ?? 'gemini-1.5-flash') ?>" class="form-control bg-dark text-light border-secondary" placeholder="gemini-1.5-flash"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 pt-3 border-top border-secondary border-opacity-25">
            <button type="submit" class="btn btn-cyber btn-lg w-100"><?= htmlspecialchars($t('admin_settings_save', 'Save Configuration')) ?></button>
        </div>
    </form>
</div>

<style>
.nav-pills .nav-link { color: #aaa; border: 1px solid rgba(255,255,255,0.1); }
.nav-pills .nav-link.active { background-color: #00f2ea; color: #000; font-weight: bold; box-shadow: 0 0 10px rgba(0,242,234,0.4); }
</style>
