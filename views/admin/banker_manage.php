<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-light"><?= $t('banker_title', 'Manage Balance') ?>: <span class="text-info"><?= htmlspecialchars($user['email']) ?></span></h2>
        <a href="<?= BASE_URL ?>/admin/users" class="btn btn-secondary"><?= $t('common_back', 'Back') ?></a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="glass-card p-4 text-center mb-4 border-info">
                <small class="text-secondary"><?= $t('banker_current_balance', 'Current Balance') ?></small>
                <h1 class="display-3 fw-bold text-light my-2"><?= number_format((float)$user['balance'], 2) ?> RUB</h1>
            </div>

            <div class="glass-card p-4">
                <h5 class="text-warning mb-3"><?= $t('banker_adjust_funds', 'Adjust Funds') ?></h5>
                <form action="<?= BASE_URL ?>/admin/banker/update" method="POST">
                    <?= \Src\Core\Csrf::field() ?>
                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                    
                    <div class="mb-3">
                        <label class="text-secondary"><?= $t('banker_action', 'Action') ?></label>
                        <select name="action" class="form-control bg-dark text-light border-secondary">
                            <option value="add"><?= $t('banker_add_funds', 'Add Funds (+)') ?></option>
                            <option value="sub"><?= $t('banker_remove_funds', 'Remove Funds (-)') ?></option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="text-secondary"><?= $t('banker_amount', 'Amount') ?></label>
                        <input type="number" step="0.01" name="amount" class="form-control bg-dark text-light border-secondary" required>
                    </div>

                    <div class="mb-3">
                        <label class="text-secondary"><?= $t('banker_reason', 'Reason (Audit Log)') ?></label>
                        <input type="text" name="reason" class="form-control bg-dark text-light border-secondary" placeholder="<?= htmlspecialchars($t('banker_reason_placeholder', 'Bonus, Refund, etc.')) ?>" required>
                    </div>

                    <button type="submit" class="btn btn-cyber w-100"><?= $t('banker_execute', 'Execute') ?></button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="glass-card p-0">
                <div class="p-3 border-bottom border-secondary"><h5 class="m-0 text-light"><?= $t('banker_recent_wallet', 'Recent Wallet Activity') ?></h5></div>
                <table class="table table-dark table-hover mb-0">
                    <thead><tr><th><?= $t('banker_date', 'Date') ?></th><th><?= $t('banker_type', 'Type') ?></th><th><?= $t('banker_amount_short', 'Amount') ?></th><th><?= $t('banker_reason_short', 'Reason') ?></th></tr></thead>
                    <tbody>
                        <?php foreach($logs as $l): ?>
                        <tr>
                            <td class="text-muted small"><?= $l['created_at'] ?></td>
                            <td><span class="badge bg-secondary"><?= $l['type'] ?></span></td>
                            <td class="<?= $l['amount'] > 0 ? 'text-success' : 'text-danger' ?>">
                                <?= $l['amount'] > 0 ? '+' : '' ?><?= $l['amount'] ?>
                            </td>
                            <td class="small"><?= htmlspecialchars($l['description']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
