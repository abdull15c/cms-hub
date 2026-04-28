<div class="container py-5">
    <div class="d-flex justify-content-between mb-4">
        <h2 class="text-light">Manage FAQ</h2>
        <a href="<?= BASE_URL ?>/admin/blog" class="btn btn-secondary">Back to Blog</a>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <form action="<?= BASE_URL ?>/admin/faq/store" method="POST" class="glass-card p-4">
                <?= \Src\Core\Csrf::field() ?>
                <h5 class="text-info mb-3">Add Question</h5>
                <input type="number" name="sort_order" class="form-control bg-dark text-light border-secondary mb-2" placeholder="Order (1, 2...)" value="1">
                <input type="text" name="question" class="form-control bg-dark text-light border-secondary mb-2" placeholder="Question" required>
                <textarea name="answer" rows="4" class="form-control bg-dark text-light border-secondary mb-2" placeholder="Answer" required></textarea>
                <button class="btn btn-cyber w-100">Add</button>
            </form>
        </div>
        <div class="col-md-8">
            <div class="glass-card p-0">
                <table class="table table-dark table-hover mb-0">
                    <?php foreach($faqs as $f): ?>
                    <tr>
                        <td width="50" class="text-muted text-center"><?= $f['sort_order'] ?></td>
                        <td>
                            <strong class="d-block text-light"><?= htmlspecialchars($f['question']) ?></strong>
                            <small class="text-secondary"><?= substr(htmlspecialchars($f['answer']), 0, 50) ?>...</small>
                        </td>
                        <td class="text-end">
                            <form action="<?= BASE_URL ?>/admin/faq/delete/<?= $f['id'] ?>" method="POST">
                                <?= \Src\Core\Csrf::field() ?>
                                <button class="btn btn-sm btn-danger">X</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</div>
