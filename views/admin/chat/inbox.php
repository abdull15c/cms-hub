<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-light">Pre-Sales Questions</h2>
        <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-secondary">Back</a>
    </div>

    <div class="glass-card p-4">
        <?php if(empty($threads)): ?>
            <p class="text-secondary">No active chats.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach($threads as $t): ?>
                <a href="<?= BASE_URL ?>/admin/chat/<?= $t['id'] ?>" class="list-group-item list-group-item-action bg-dark text-light border-secondary d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 text-info"><?= htmlspecialchars($t['product_title']) ?></h5>
                        <p class="mb-1 text-light"><i class="fa-solid fa-user me-2"></i> <?= htmlspecialchars($t['email']) ?></p>
                        <small class="text-secondary"><?= substr(htmlspecialchars($t['last_msg']), 0, 50) ?>...</small>
                    </div>
                    <small class="text-muted"><?= $t['updated_at'] ?></small>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>