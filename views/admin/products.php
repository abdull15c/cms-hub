<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="text-light fw-bold mb-1"><i class="fa-solid fa-boxes-stacked text-info me-2"></i><?= $t('products_title', 'Product Library') ?></h2>
            <p class="text-secondary mb-0"><?= $t('products_subtitle', 'Manage all products, drafts, localized content, duplication and publishing flow.') ?></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>/admin/categories" class="btn btn-outline-warning rounded-pill px-4"><i class="fa-solid fa-tags me-2"></i><?= $t('products_categories', 'Categories') ?></a>
            <a href="<?= BASE_URL ?>/admin/product/new" class="btn btn-info text-dark fw-semibold rounded-pill px-4"><i class="fa-solid fa-plus me-2"></i><?= $t('products_new', 'New Product') ?></a>
        </div>
    </div>

    <div class="glass-card p-4 mb-4">
        <form method="GET" action="<?= BASE_URL ?>/admin/products" class="row g-3 align-items-end">
            <div class="col-lg-5">
                <label class="form-label text-secondary small"><?= $t('products_search', 'Search') ?></label>
                <input type="text" name="q" value="<?= htmlspecialchars($term) ?>" class="form-control bg-dark text-light border-secondary" placeholder="<?= htmlspecialchars($t('products_search_placeholder', 'Search title, RU title, EN title')) ?>">
            </div>
            <div class="col-lg-4">
                <label class="form-label text-secondary small"><?= $t('products_status', 'Status') ?></label>
                <select name="status" class="form-select bg-dark text-light border-secondary">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>><?= $t('products_all', 'All') ?> (<?= (int)$counts['all'] ?>)</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>><?= $t('products_drafts', 'Drafts') ?> (<?= (int)$counts['draft'] ?>)</option>
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>><?= $t('products_published', 'Published') ?> (<?= (int)$counts['published'] ?>)</option>
                </select>
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-light w-100"><?= $t('products_filter', 'Filter') ?></button>
                <a href="<?= BASE_URL ?>/admin/products" class="btn btn-outline-secondary w-100"><?= $t('products_reset', 'Reset') ?></a>
            </div>
        </form>
    </div>

    <div class="glass-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead class="text-secondary small text-uppercase bg-black bg-opacity-50">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th><?= $t('products_product', 'Product') ?></th>
                        <th><?= $t('products_status', 'Status') ?></th>
                        <th><?= $t('products_category', 'Category') ?></th>
                        <th><?= $t('products_price', 'Price') ?></th>
                        <th><?= $t('products_media', 'Media') ?></th>
                        <th class="text-end pe-4"><?= $t('products_actions', 'Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="7" class="text-center text-secondary py-5"><?= $t('products_empty', 'No products match the current filter.') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="ps-4 text-secondary font-monospace">#<?= (int)$product['id'] ?></td>
                                <td>
                                    <div class="text-light fw-semibold mb-1"><?= htmlspecialchars($product['title'] ?? '') ?></div>
                                    <div class="small text-secondary">RU: <?= htmlspecialchars($product['title_ru'] ?: '-') ?></div>
                                    <div class="small text-secondary">EN: <?= htmlspecialchars($product['title_en'] ?: '-') ?></div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill <?= ($product['status'] ?? 'published') === 'draft' ? 'bg-warning text-dark' : 'bg-success' ?>">
                                        <?= htmlspecialchars(($product['status'] ?? 'published') === 'draft' ? $t('products_draft', 'Draft') : $t('products_published_one', 'Published')) ?>
                                    </span>
                                </td>
                                <td class="text-light"><?= htmlspecialchars($product['category_name'] ?: $t('products_uncategorized', 'Uncategorized')) ?></td>
                                <td class="text-info fw-semibold">$<?= number_format((float)$product['price'], 2) ?></td>
                                <td class="text-secondary small"><?= (int)$product['images_count'] ?> <?= $t('products_images', 'images') ?><?= !empty($product['file_path']) ? ' | ' . $t('products_file_ready', 'file ready') : ' | ' . $t('products_no_file', 'no file') ?></td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                                        <a href="<?= BASE_URL ?>/product/<?= (int)$product['id'] ?>" class="btn btn-sm btn-outline-light" target="_blank"><?= $t('products_view', 'View') ?></a>
                                        <a href="<?= BASE_URL ?>/admin/product/edit/<?= (int)$product['id'] ?>" class="btn btn-sm btn-outline-info"><?= $t('products_edit', 'Edit') ?></a>
                                        <form action="<?= BASE_URL ?>/admin/product/duplicate/<?= (int)$product['id'] ?>" method="POST" class="m-0">
                                            <?= \Src\Core\Csrf::field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-warning"><?= $t('products_duplicate', 'Duplicate') ?></button>
                                        </form>
                                        <form action="<?= BASE_URL ?>/admin/product/delete/<?= (int)$product['id'] ?>" method="POST" class="m-0" onsubmit="return confirm('<?= addslashes($t('products_delete_confirm', 'Delete this product?')) ?>');">
                                            <?= \Src\Core\Csrf::field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><?= $t('products_delete', 'Delete') ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
