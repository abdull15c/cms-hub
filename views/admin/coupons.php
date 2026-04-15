<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-light"><?= $t('coupons_title', 'Promo Codes') ?></h2>
        <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-secondary"><?= $t('common_back', 'Back') ?></a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="glass-card p-4">
                <h5 class="text-info mb-3"><?= $t('coupons_create', 'Create Coupon') ?></h5>
                <form action="<?= BASE_URL ?>/admin/coupons/store" method="POST">
                    <?= \Src\Core\Csrf::field() ?>
                    <div class="mb-3">
                        <label class="text-secondary"><?= $t('coupons_code', 'Code (e.g. SALE20)') ?></label>
                        <input type="text" name="code" class="form-control bg-dark text-light border-secondary" style="text-transform:uppercase" required>
                    </div>
                    <div class="mb-3">
                        <label class="text-secondary"><?= $t('coupons_discount', 'Discount (%)') ?></label>
                        <input type="number" name="percent" min="1" max="100" class="form-control bg-dark text-light border-secondary" required>
                    </div>
                    <button type="submit" class="btn btn-cyber w-100"><?= $t('coupons_create_button', 'Create') ?></button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="glass-card p-4">
                <table class="table table-dark table-hover">
                    <thead><tr><th><?= $t('coupons_code_short', 'Code') ?></th><th><?= $t('coupons_discount_short', 'Discount') ?></th><th><?= $t('coupons_uses', 'Uses') ?></th><th><?= $t('coupons_action', 'Action') ?></th></tr></thead>
                    <tbody>
                        <?php foreach($coupons as $c): ?>
                        <tr>
                            <td class="text-info fw-bold"><?= htmlspecialchars($c['code']) ?></td>
                            <td>-<?= $c['discount_percent'] ?>%</td>
                            <td><?= $c['used_count'] ?> / <?= $c['max_uses'] ?></td>
                            <td>
                                <form action="<?= BASE_URL ?>/admin/coupons/delete/<?= $c['id'] ?>" method="POST" onsubmit="return confirm('<?= addslashes($t('coupons_delete_confirm', 'Delete?')) ?>');">
                                    <?= \Src\Core\Csrf::field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
