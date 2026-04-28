<div class="container py-5">
    <div class="d-flex justify-content-between mb-4">
        <h2 class="text-light"><?= $t('transactions_title', 'Transaction Manager') ?></h2>
        <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-secondary"><?= $t('common_back', 'Back') ?></a>
    </div>
    
    <div class="glass-card p-0">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead><tr><th>ID</th><th><?= $t('transactions_user', 'User') ?></th><th><?= $t('transactions_item', 'Item') ?></th><th><?= $t('transactions_amount', 'Amount') ?></th><th><?= $t('transactions_gateway', 'Gateway') ?></th><th><?= $t('transactions_status', 'Status') ?></th><th><?= $t('transactions_action', 'Action') ?></th></tr></thead>
            <tbody>
                <?php foreach($trans as $t): ?>
                <tr>
                    <td>#<?= $t['id'] ?></td>
                    <td><?= htmlspecialchars($t['email']) ?></td>
                    <td><?= $t['product_id'] == 0 ? '<span class="text-info">' . htmlspecialchars($t('transactions_wallet_deposit', 'Wallet Deposit')) . '</span>' : htmlspecialchars($t['product_title']) ?></td>
                    <td><?= number_format((float)$t['amount'], 2) ?> RUB</td>
                    <td class="text-uppercase small"><?= $t['provider'] ?></td>
                    <td>
                        <?php 
                            $c = 'secondary';
                            if($t['status']=='paid') $c='success';
                            if($t['status']=='pending') $c='warning text-dark';
                            if($t['status']=='cancelled') $c='danger';
                        ?>
                        <span class="badge bg-<?= $c ?>"><?= htmlspecialchars(strtoupper($t('transactions_status_' . strtolower($t['status']), strtoupper($t['status'])))) ?></span>
                    </td>
                    <td>
                        <?php if($t['status'] === 'pending'): ?>
                            <form action="<?= BASE_URL ?>/admin/transactions/approve/<?= $t['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('<?= addslashes($t('transactions_confirm_payment', 'Confirm payment? User will get product.')) ?>');">
                                <?= \Src\Core\Csrf::field() ?>
                                <button class="btn btn-sm btn-outline-success" title="<?= $t('transactions_approve', 'Approve') ?>"><i class="fa-solid fa-check"></i></button>
                            </form>
                            <form action="<?= BASE_URL ?>/admin/transactions/cancel/<?= $t['id'] ?>" method="POST" class="d-inline">
                                <?= \Src\Core\Csrf::field() ?>
                                <button class="btn btn-sm btn-outline-danger" title="<?= $t('transactions_cancel', 'Cancel') ?>"><i class="fa-solid fa-ban"></i></button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="mt-3">
        <?php for($i=1; $i<=ceil($total/$perPage); $i++): ?>
            <a href="?page=<?= $i ?>" class="btn btn-sm <?= $i==$page?'btn-cyber':'btn-outline-secondary' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>
