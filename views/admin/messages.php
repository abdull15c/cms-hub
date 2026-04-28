<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-light">Support Inbox</h2>
        <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-secondary">Back</a>
    </div>

    <?php if(empty($messages)): ?>
        <p class="text-secondary">No messages yet.</p>
    <?php else: ?>
        <div class="row">
        <?php foreach($messages as $m): ?>
            <div class="col-12 mb-3">
                <div class="glass-card p-3 border-start border-4 border-info">
                    <div class="d-flex justify-content-between">
                        <h5 class="text-light"><?= htmlspecialchars($m['name']) ?> <small class="text-secondary fs-6">< <?= htmlspecialchars($m['email']) ?> ></small></h5>
                        <small class="text-muted"><?= $m['created_at'] ?></small>
                    </div>
                    <p class="mt-2 mb-0 text-light"><?= nl2br(htmlspecialchars($m['message'])) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>