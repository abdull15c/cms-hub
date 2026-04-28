<div class="container py-5">
    <h2 class="text-light mb-4"><?= $t('reviews_title', 'Reviews Moderation') ?></h2>
    <div class="glass-card p-0">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead><tr><th><?= $t('reviews_product', 'Product') ?></th><th><?= $t('reviews_user', 'User') ?></th><th><?= $t('reviews_rating', 'Rating') ?></th><th><?= $t('reviews_comment', 'Comment') ?></th><th><?= $t('reviews_status', 'Status') ?></th><th><?= $t('reviews_action', 'Action') ?></th></tr></thead>
            <tbody>
                <?php foreach($reviews as $r): ?>
                <tr class="<?= $r['is_approved'] ? '' : 'table-active' ?>">
                    <td><small><?= htmlspecialchars($r['product_title']) ?></small></td>
                    <td><small><?= htmlspecialchars($r['email']) ?></small></td>
                    <td class="text-warning"><?= str_repeat('★', $r['rating']) ?></td>
                    <td style="max-width:300px;">
                        <div class="text-light"><?= htmlspecialchars($r['comment']) ?></div>
                        <?php if($r['reply']): ?>
                            <div class="text-info small mt-1 border-start border-info ps-2"><strong><?= $t('reviews_admin', 'Admin') ?>:</strong> <?= htmlspecialchars($r['reply']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($r['is_approved']): ?><span class="badge bg-success"><?= $t('reviews_live', 'Live') ?></span>
                        <?php else: ?><span class="badge bg-warning text-dark"><?= $t('reviews_pending', 'Pending') ?></span><?php endif; ?>
                    </td>
                    <td>
                        <?php if(!$r['is_approved']): ?>
                            <form action="<?= BASE_URL ?>/admin/reviews/approve/<?= $r['id'] ?>" method="POST" class="d-inline">
                                <?= \Src\Core\Csrf::field() ?>
                                <button class="btn btn-sm btn-success" title="<?= $t('reviews_approve', 'Approve') ?>"><i class="fa-solid fa-check"></i></button>
                            </form>
                        <?php endif; ?>
                        
                        <button class="btn btn-sm btn-info" onclick="document.getElementById('replyRow<?= $r['id'] ?>').classList.toggle('d-none')"><i class="fa-solid fa-reply"></i></button>
                        
                        <form action="<?= BASE_URL ?>/admin/reviews/delete/<?= $r['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('<?= addslashes($t('reviews_delete_confirm', 'Delete?')) ?>');">
                            <?= \Src\Core\Csrf::field() ?>
                            <button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <tr id="replyRow<?= $r['id'] ?>" class="d-none bg-dark">
                    <td colspan="6">
                        <form action="<?= BASE_URL ?>/admin/reviews/reply/<?= $r['id'] ?>" method="POST" class="d-flex gap-2 p-2">
                            <?= \Src\Core\Csrf::field() ?>
                            <input type="text" name="reply" class="form-control form-control-sm bg-black text-light border-secondary" placeholder="<?= htmlspecialchars($t('reviews_reply_placeholder', 'Write reply...')) ?>" value="<?= htmlspecialchars($r['reply']??'') ?>">
                            <button class="btn btn-sm btn-cyber"><?= $t('reviews_send_approve', 'Send & Approve') ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
