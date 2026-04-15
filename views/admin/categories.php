<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-light"><?= $t('categories_title', 'Manage Categories') ?></h2>
        <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-secondary"><?= $t('common_back', 'Back') ?></a>
    </div>

    <div class="row">
        <!-- ADD FORM -->
        <div class="col-md-4">
            <div class="glass-card p-4 mb-4">
                <h5 class="text-info mb-3"><i class="fa-solid fa-plus-circle"></i> <?= $t('categories_add_new', 'Add New') ?></h5>
                <form action="<?= BASE_URL ?>/admin/categories/store" method="POST">
                    <?= \Src\Core\Csrf::field() ?>
                    <div class="mb-3">
                        <label class="text-secondary small"><?= $t('categories_name', 'Category Name') ?></label>
                        <input type="text" name="name" class="form-control bg-dark text-light border-secondary" placeholder="<?= htmlspecialchars($t('categories_name_placeholder', 'e.g. Plugins')) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-cyber w-100"><?= $t('categories_create', 'Create Category') ?></button>
                </form>
            </div>
        </div>

        <!-- LIST -->
        <div class="col-md-8">
            <div class="glass-card p-0 overflow-hidden">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4"><?= $t('categories_name', 'Category Name') ?></th>
                            <th><?= $t('categories_slug', 'Slug') ?></th>
                            <th class="text-end pe-4"><?= $t('categories_actions', 'Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($categories)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4"><?= $t('categories_empty', 'No categories found. Add one!') ?></td></tr>
                        <?php else: ?>
                            <?php foreach($categories as $c): ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?= htmlspecialchars($c['name']) ?></td>
                                <td class="text-secondary"><small><?= $c['slug'] ?></small></td>
                                <td class="text-end pe-4">
                                    <form action="<?= BASE_URL ?>/admin/categories/delete/<?= $c['id'] ?>" method="POST" onsubmit="return confirm('<?= addslashes($t('categories_delete_confirm', 'Delete category? Products will be uncategorized.')) ?>');">
                                        <?= \Src\Core\Csrf::field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
