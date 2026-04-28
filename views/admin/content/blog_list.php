<div class="container py-5">
    <div class="d-flex justify-content-between mb-4">
        <h2 class="text-light">Manage Blog</h2>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/faq" class="btn btn-outline-info">Manage FAQ</a>
            <a href="<?= BASE_URL ?>/admin/blog/create" class="btn btn-cyber">+ New Post</a>
            <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-secondary">Back</a>
        </div>
    </div>
    
    <div class="glass-card p-0">
        <table class="table table-dark table-hover mb-0">
            <?php foreach($posts as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['title']) ?></td>
                <td class="text-end text-muted"><?= $p['created_at'] ?></td>
                <td class="text-end">
                    <form action="<?= BASE_URL ?>/admin/blog/delete/<?= $p['id'] ?>" method="POST" onsubmit="return confirm('Delete?');">
                        <?= \Src\Core\Csrf::field() ?>
                        <button class="btn btn-sm btn-danger">X</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
