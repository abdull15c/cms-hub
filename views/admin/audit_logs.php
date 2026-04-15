<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-light"><i class="fa-solid fa-eye text-info"></i> Global Audit Log</h2>
        <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-secondary">Back</a>
    </div>

    <!-- Filters -->
    <div class="glass-card p-3 mb-4">
        <form method="GET" class="d-flex gap-2">
            <select name="type" class="form-select bg-dark text-light border-secondary w-auto">
                <option value="">All Events</option>
                <option value="auth" <?= ($_GET['type']??'')=='auth'?'selected':'' ?>>Auth (Login/Out)</option>
                <option value="payment" <?= ($_GET['type']??'')=='payment'?'selected':'' ?>>Payments</option>
                <option value="user" <?= ($_GET['type']??'')=='user'?'selected':'' ?>>Users</option>
                <option value="product" <?= ($_GET['type']??'')=='product'?'selected':'' ?>>Products</option>
            </select>
            <input type="text" name="uid" placeholder="User ID" class="form-control bg-dark text-light border-secondary w-auto" value="<?= htmlspecialchars($_GET['uid']??'') ?>">
            <button type="submit" class="btn btn-cyber">Filter</button>
            <a href="<?= BASE_URL ?>/admin/audit" class="btn btn-outline-secondary">Reset</a>
        </form>
    </div>

    <!-- Table -->
    <div class="glass-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle" style="font-size: 0.9rem;">
                <thead class="bg-black">
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>Details</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $log): ?>
                    <?php 
                        // Color coding
                        $badgeClass = 'bg-secondary';
                        if($log['event_type'] == 'payment') $badgeClass = 'bg-success';
                        if($log['event_type'] == 'auth') $badgeClass = 'bg-primary';
                        if($log['event_type'] == 'security') $badgeClass = 'bg-danger';
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $log['created_at'] ?></td>
                        <td>
                            <?php if($log['user_id']): ?>
                                <a href="<?= BASE_URL ?>/admin/users?q=<?= $log['user_id'] ?>" class="text-info text-decoration-none">
                                    <?= htmlspecialchars($log['user_email'] ?? 'ID:'.$log['user_id']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-secondary">System/Guest</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= $badgeClass ?>"><?= strtoupper($log['event_type']) ?></span></td>
                        <td class="fw-bold"><?= $log['action'] ?></td>
                        <td><?= $log['target_id'] ? '#'.$log['target_id'] : '-' ?></td>
                        <td class="small text-light opacity-75" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($log['details']) ?>">
                            <?= htmlspecialchars($log['details']) ?>
                        </td>
                        <td class="font-monospace small text-warning"><?= $log['ip'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-3">
        <?php $totalPages = ceil($total / $perPage); ?>
        <?php if($totalPages > 1): ?>
            <?php for($i=1; $i<=$totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="btn btn-sm <?= $i==$page ? 'btn-cyber' : 'btn-outline-secondary' ?>"><?= $i ?></a>
            <?php endfor; ?>
        <?php endif; ?>
    </div>
</div>