<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-light"><i class="fa-solid fa-terminal text-warning"></i> <?= $t('logs_title', 'System Logs') ?></h2>
        <div>
            <form action="<?= BASE_URL ?>/admin/logs/clear" method="POST" class="d-inline" onsubmit="return confirm('<?= addslashes($t('logs_clear_confirm', 'Clear all logs?')) ?>');">
                <?= \Src\Core\Csrf::field() ?>
                <button class="btn btn-outline-danger btn-sm me-2"><?= $t('logs_clear_all', 'Clear All') ?></button>
            </form>
            <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-secondary btn-sm"><?= $t('common_back', 'Back') ?></a>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar: Files -->
        <div class="col-md-3">
            <div class="glass-card p-0 overflow-hidden">
                <div class="p-3 border-bottom border-secondary bg-black bg-opacity-25">
                    <h6 class="m-0 text-light"><?= $t('logs_files', 'Log Files') ?></h6>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach($files as $f): ?>
                        <a href="?file=<?= urlencode((string)$f) ?>" class="list-group-item list-group-item-action bg-transparent text-light border-secondary <?= $current === $f ? 'active border-start border-info border-3' : '' ?>">
                            <i class="fa-regular fa-file-lines me-2"></i> <?= htmlspecialchars((string)$f) ?>
                        </a>
                    <?php endforeach; ?>
                    <?php if(empty($files)): ?>
                        <div class="p-3 text-muted small"><?= $t('logs_none', 'No logs found.') ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Content: Lines -->
        <div class="col-md-9">
            <div class="glass-card p-0">
                <div class="p-3 border-bottom border-secondary bg-black bg-opacity-50 font-monospace text-info small">
                    <?= $current ? $t('logs_reading', 'Reading') . ': ' . $current : $t('logs_select_file', 'Select a file') ?>
                </div>
                <div class="p-3 bg-black text-success font-monospace" style="height: 600px; overflow-y: auto; font-size: 0.85rem;">
                    <?php if(!empty($logs)): ?>
                        <?php foreach($logs as $line): ?>
                            <?php 
                                $color = 'text-light';
                                if(strpos($line, '[ERROR]')) $color = 'text-danger fw-bold';
                                if(strpos($line, '[WARNING]')) $color = 'text-warning';
                                if(strpos($line, '[INFO]')) $color = 'text-info';
                            ?>
                            <div class="<?= $color ?> border-bottom border-secondary border-opacity-10 py-1">
                                <?= htmlspecialchars($line) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-secondary"><?= $t('logs_empty_file', '// End of file or empty') ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
