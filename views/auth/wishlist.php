<div class="container py-5">
    <h2 class="text-light mb-4"><i class="fa-solid fa-heart text-danger"></i> <?= $t('wishlist_title', 'My Wishlist') ?></h2>

    <div class="row g-4">
        <?php if (empty($products)): ?>
            <div class="col-12"><div class="glass-card p-5 text-center text-muted"><?= $t('wishlist_empty', 'Your wishlist is empty.') ?></div></div>
        <?php endif; ?>

        <?php foreach ($products as $p): ?>
        <div class="col-md-3">
            <div class="glass-card h-100 p-0 d-flex flex-column overflow-hidden position-relative">
                <a href="<?= BASE_URL ?>/wishlist/toggle/<?= $p['id'] ?>" class="position-absolute top-0 end-0 p-2 text-danger z-3"><i class="fa-solid fa-times-circle fa-2x"></i></a>

                <div style="height: 150px; overflow: hidden;">
                    <?php if (!empty($p['thumbnail'])): ?>
                        <img src="<?= BASE_URL ?>/uploads/images/<?= $p['thumbnail'] ?>" style="width:100%; height:100%; object-fit: cover;" alt="<?= htmlspecialchars($p['title']) ?>">
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center h-100 bg-dark"><i class="fa-solid fa-cube text-secondary fa-2x"></i></div>
                    <?php endif; ?>
                </div>
                <div class="p-3">
                    <h6 class="text-truncate"><?= htmlspecialchars($p['title']) ?></h6>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="text-info fw-bold">$<?= $p['price'] ?></span>
                        <a href="<?= BASE_URL ?>/product/<?= $p['id'] ?>" class="btn btn-sm btn-cyber"><?= $t('wishlist_view', 'View') ?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
