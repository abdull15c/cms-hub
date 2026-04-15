<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-light"><?= $t('users_title', 'User Management') ?></h2>
        <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-secondary"><?= $t('common_back', 'Back') ?></a>
    </div>

    <div class="glass-card p-4">
        <table class="table table-dark table-hover align-middle">
            <thead><tr><th><?= $t('users_email', 'Email') ?></th><th><?= $t('users_role', 'Role') ?></th><th><?= $t('users_balance', 'Balance') ?></th><th><?= $t('users_status', 'Status') ?></th><th><?= $t('users_action', 'Action') ?></th></tr></thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge bg-secondary"><?= $u['role'] ?></span></td>
                    <td class="font-monospace text-info">$<?= number_format($u['balance'] ?? 0, 2) ?></td>
                    <td>
                        <?php if($u['is_banned']): ?>
                            <span class="badge bg-danger"><?= $t('users_banned', 'BANNED') ?></span>
                        <?php else: ?>
                            <span class="badge bg-success"><?= $t('users_active', 'Active') ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= BASE_URL ?>/admin/users/manage/<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning me-1" title="<?= $t('users_manage_funds', 'Manage Funds') ?>"><i class="fa-solid fa-coins"></i></a>
                        <form action="<?= BASE_URL ?>/admin/users/ban/<?= $u['id'] ?>" method="POST">
                            <?= \Src\Core\Csrf::field() ?>
                            <button type="submit" class="btn btn-sm <?= $u['is_banned'] ? 'btn-outline-success' : 'btn-outline-danger' ?>">
                                <?= $u['is_banned'] ? $t('users_unban', 'Unban') : $t('users_ban', 'Ban') ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="mt-3">
            <?php 
            $totalPages = ceil($total / $perPage);
            for($i=1; $i<=$totalPages; $i++): 
            ?>
                <a href="<?= BASE_URL ?>/admin/users?page=<?= $i ?>" class="btn btn-sm <?= $i==$page ? 'btn-cyber' : 'btn-outline-secondary' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
</div>
