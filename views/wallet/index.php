<div class="container py-5">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="glass-card p-4 text-center border-info">
                <i class="fa-solid fa-wallet fa-3x text-info mb-3"></i>
                <h3 class="text-secondary"><?= htmlspecialchars($t('wallet_current_balance', 'Current Balance')) ?></h3>
                <h1 class="display-4 fw-bold text-light my-3"><?= htmlspecialchars(number_format((float) $balance, 2), ENT_QUOTES, 'UTF-8') ?></h1>

                <hr class="border-secondary opacity-25">

                <form action="<?= BASE_URL ?>/payment/deposit" method="POST">
                    <?= \Src\Core\Csrf::field() ?>
                    <label for="wallet-amount" class="text-secondary small mb-2"><?= htmlspecialchars($t('wallet_add_funds', 'Add Funds (USD)')) ?></label>
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-dark border-secondary text-secondary">$</span>
                        <input id="wallet-amount" type="number" name="amount" min="1" step="0.01" class="form-control bg-dark text-light border-secondary" placeholder="10.00" required>
                    </div>

                    <div class="d-grid gap-2">
                        <?php if (\Src\Services\SettingsService::get('yoomoney_enabled') !== '0'): ?>
                        <button type="submit" name="provider" value="yoomoney" class="btn btn-cyber">
                            <i class="fa-solid fa-bolt me-2"></i> <?= $t('wallet_deposit_yoomoney', 'Deposit via YooMoney') ?>
                        </button>
                        <?php endif; ?>
                        <?php if (\Src\Services\SettingsService::get('yookassa_enabled') === '1'): ?>
                        <button type="submit" name="provider" value="yookassa" class="btn btn-outline-info">
                            <i class="fa-solid fa-credit-card me-2"></i> <?= htmlspecialchars($t('wallet_deposit_yookassa', 'Deposit via YooKassa')) ?>
                        </button>
                        <?php endif; ?>
                        <?php if (\Src\Services\SettingsService::get('lemonsqueezy_enabled') === '1'): ?>
                        <button type="submit" name="provider" value="lemonsqueezy" class="btn btn-outline-light">
                            <i class="fa-solid fa-globe me-2"></i> <?= htmlspecialchars($t('wallet_deposit_global_card', 'Deposit via Global Card')) ?>
                        </button>
                        <?php endif; ?>
                        <?php if (\Src\Services\SettingsService::get('stripe_enabled') === '1'): ?>
                        <button type="submit" name="provider" value="stripe" class="btn btn-outline-primary">
                            <i class="fa-solid fa-credit-card me-2"></i> <?= htmlspecialchars($t('wallet_deposit_stripe', 'Deposit via Stripe')) ?>
                        </button>
                        <?php endif; ?>
                        <?php if (\Src\Services\SettingsService::get('cryptomus_enabled') === '1'): ?>
                        <button type="submit" name="provider" value="cryptomus" class="btn btn-outline-success">
                            <i class="fa-brands fa-bitcoin me-2"></i> <?= htmlspecialchars($t('wallet_deposit_crypto', 'Deposit via Crypto')) ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <h3 class="text-light mb-4"><?= $t('wallet_history', 'Transaction History') ?></h3>
            <div class="glass-card p-0 overflow-hidden">
                <table class="table table-dark table-hover mb-0 align-middle" aria-label="<?= htmlspecialchars($t('wallet_history', 'Transaction History')) ?>">
                    <thead>
                        <tr>
                            <th><?= $t('wallet_date', 'Date') ?></th>
                            <th><?= $t('wallet_type', 'Type') ?></th>
                            <th><?= $t('wallet_description', 'Description') ?></th>
                            <th><?= $t('wallet_amount', 'Amount') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="4" class="text-center text-secondary py-4"><?= $t('wallet_empty', 'No transactions yet.') ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $l): ?>
                            <tr>
                                <td class="text-muted small"><?= date('M d, Y H:i', strtotime($l['created_at'])) ?></td>
                                <td>
                                    <?php
                                        $c = 'secondary';
                                        if ($l['type'] === 'deposit') {
                                            $c = 'success';
                                        }
                                        if ($l['type'] === 'purchase') {
                                            $c = 'danger';
                                        }
                                    ?>
                                    <span class="badge bg-<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(strtoupper((string) $l['type']), ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td><?= htmlspecialchars($l['description']) ?></td>
                                <td class="fw-bold <?= $l['amount'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $l['amount'] > 0 ? '+' : '' ?><?= htmlspecialchars(number_format((float) $l['amount'], 2), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
