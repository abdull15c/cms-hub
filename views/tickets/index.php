<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-light"><?= htmlspecialchars($t('tickets_my', 'My Support Tickets')) ?></h2>
        <a href="<?= BASE_URL ?>/tickets/new" class="btn btn-cyber"><?= htmlspecialchars($t('tickets_new', '+ New Ticket')) ?></a>
    </div>

    <div class="glass-card p-4">
        <?php if (empty($tickets)): ?>
            <p class="text-secondary text-center"><?= $t('tickets_empty', 'No tickets yet. Need help?') ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle" aria-label="<?= htmlspecialchars($t('tickets_my', 'My Support Tickets')) ?>">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?= $t('tickets_subject', 'Subject') ?></th>
                            <th><?= $t('tickets_department', 'Dept') ?></th>
                            <th><?= $t('tickets_last_update', 'Last Update') ?></th>
                            <th><?= $t('tickets_status', 'Status') ?></th>
                            <th><?= $t('tickets_action', 'Action') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $tkt): ?>
                        <tr>
                            <td>#<?= (int) $tkt['id'] ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($tkt['subject']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst((string) $tkt['department']), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="text-muted small"><?= date('M d H:i', strtotime($tkt['updated_at'])) ?></td>
                            <td>
                                <?php
                                    $cls = 'bg-secondary';
                                    if ($tkt['status'] === 'answered') {
                                        $cls = 'bg-success';
                                    }
                                    if ($tkt['status'] === 'closed') {
                                        $cls = 'bg-dark border border-secondary';
                                    }
                                    if ($tkt['status'] === 'customer_reply') {
                                        $cls = 'bg-info text-dark';
                                    }
                                ?>
                                <span class="badge <?= htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(strtoupper(str_replace('_', ' ', (string) $tkt['status'])), ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td><a href="<?= BASE_URL ?>/tickets/view/<?= $tkt['id'] ?>" class="btn btn-sm btn-outline-light"><?= $t('tickets_open', 'Open') ?></a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
