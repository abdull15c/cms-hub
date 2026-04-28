<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-light">Support Desk</h2>
        <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-secondary">Back</a>
    </div>

    <div class="glass-card p-0">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Last Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($tickets as $t): ?>
                <tr class="<?= $t['status']=='customer_reply' ? 'table-active' : '' ?>">
                    <td><?= $t['id'] ?></td>
                    <td>
                        <?= htmlspecialchars($t['email']) ?><br>
                        <?php if($t['priority']=='high'): ?><span class="badge bg-danger">HIGH</span><?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= BASE_URL ?>/admin/tickets/view/<?= $t['id'] ?>" class="text-decoration-none text-info fw-bold">
                            <?= htmlspecialchars($t['subject']) ?>
                        </a>
                        <div class="small text-muted"><?= ucfirst($t['department']) ?></div>
                    </td>
                    <td>
                        <?php 
                            $c = 'secondary';
                            if($t['status']=='customer_reply') $c='warning text-dark';
                            if($t['status']=='answered') $c='success';
                            if($t['status']=='open') $c='primary';
                        ?>
                        <span class="badge bg-<?= $c ?>"><?= strtoupper(str_replace('_',' ',$t['status'])) ?></span>
                    </td>
                    <td><?= $t['updated_at'] ?></td>
                    <td><a href="<?= BASE_URL ?>/admin/tickets/view/<?= $t['id'] ?>" class="btn btn-sm btn-cyber">Reply</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>