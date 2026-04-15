<?php $queueHealthy = (bool)($queue['status'] ?? false); ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-light mb-0"><i class="fa-solid fa-heart-pulse text-danger"></i> <?= $t('pulse_title', 'System Pulse') ?></h2>
            <small class="text-secondary"><?= $t('pulse_subtitle', 'Real-time health monitoring') ?></small>
        </div>
        <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-secondary"><?= $t('common_back', 'Back') ?></a>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-xl-4">
            <div class="glass-card p-4 text-center h-100 <?= $db['status'] ? 'border-success' : 'border-danger' ?>">
                <h5 class="text-secondary"><?= $t('pulse_database', 'Database') ?></h5>
                <?php if ($db['status']): ?>
                    <div class="display-6 fw-bold text-success my-2"><?= $db['ms'] ?> ms</div>
                    <small class="text-light"><i class="fa-solid fa-check-circle"></i> <?= $t('pulse_connected', 'Connected') ?></small>
                <?php else: ?>
                    <div class="text-danger my-2"><?= $t('pulse_error', 'ERROR') ?></div>
                    <small class="text-muted"><?= htmlspecialchars($db['error']) ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="glass-card p-4 text-center h-100 <?= $errors > 0 ? ($errors > 10 ? 'border-danger' : 'border-warning') : 'border-success' ?>">
                <h5 class="text-secondary"><?= $t('pulse_errors_today', 'Errors (Today)') ?></h5>
                <div class="display-6 fw-bold text-light my-2"><?= $errors ?></div>
                <?php if ($errors === 0): ?>
                    <small class="text-success"><?= $t('pulse_clean_logs', 'Clean Logs') ?></small>
                <?php else: ?>
                    <small class="text-warning"><?= $t('pulse_check_logs', 'Check logs/error.log') ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="glass-card p-4 text-center h-100 border-info">
                <h5 class="text-secondary"><?= $t('pulse_storage', 'Storage') ?></h5>
                <div class="progress bg-dark mt-3 mb-2" style="height: 10px;">
                    <div class="progress-bar bg-info" style="width: <?= $disk['percent'] ?>%"></div>
                </div>
                <div class="d-flex justify-content-between text-muted small">
                    <span><?= $disk['percent'] ?>% <?= $t('pulse_used', 'Used') ?></span>
                    <span><?= $t('pulse_free', 'Free') ?>: <?= $disk['free_gb'] ?> GB</span>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="glass-card p-4 text-center h-100 <?= $cron['status'] ? 'border-success' : 'border-danger' ?>">
                <h5 class="text-secondary"><?= $t('pulse_cron_schedule', 'Cron Schedule') ?></h5>
                <div class="display-6 fw-bold text-light my-2"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <small class="<?= $cron['status'] ? 'text-success' : 'text-danger' ?>">
                    <?= $t('pulse_last_run', 'Last run') ?>: <?= $cron['ago'] ?>
                </small>
                <?php if (!$cron['status']): ?>
                    <div class="mt-2 badge bg-danger"><?= $t('pulse_stalled', 'STALLED') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="glass-card p-4 text-center h-100 <?= $worker['status'] ? 'border-success' : 'border-danger' ?>">
                <h5 class="text-secondary"><?= $t('pulse_queue_worker', 'Queue Worker') ?></h5>
                <div class="display-6 fw-bold text-light my-2"><i class="fa-solid fa-microchip"></i></div>
                <small class="<?= $worker['status'] ? 'text-success' : 'text-danger' ?>">
                    <?= $t('pulse_last_run', 'Last run') ?>: <?= $worker['ago'] ?>
                </small>
                <?php if (!$worker['status']): ?>
                    <div class="mt-2 badge bg-danger"><?= $t('pulse_stalled', 'STALLED') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="glass-card p-4 h-100 <?= $queueHealthy ? (($queue['dead'] ?? 0) > 0 ? 'border-warning' : 'border-success') : 'border-danger' ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="text-secondary mb-1"><?= $t('pulse_queue_backlog', 'Queue Backlog') ?></h5>
                        <small class="text-muted"><?= $t('pulse_oldest_job', 'Oldest active job') ?>: <?= htmlspecialchars((string)($queue['oldest_age'] ?? 'Unavailable')) ?></small>
                    </div>
                    <span class="badge <?= $queueHealthy ? 'bg-dark text-info' : 'bg-danger' ?>"><?= $queueHealthy ? $t('pulse_connected', 'Connected') : $t('pulse_error', 'ERROR') ?></span>
                </div>

                <?php if ($queueHealthy): ?>
                    <div class="display-6 fw-bold text-light mt-3"><?= (int)($queue['backlog'] ?? 0) ?></div>
                    <div class="small text-secondary mb-3"><?= $t('pulse_pending', 'Pending') ?> + <?= $t('pulse_retry', 'Retry') ?></div>
                    <div class="row text-center small g-2">
                        <div class="col-6">
                            <div class="rounded-3 bg-dark p-2">
                                <div class="text-secondary"><?= $t('pulse_pending', 'Pending') ?></div>
                                <div class="text-light fw-semibold"><?= (int)($queue['pending'] ?? 0) ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded-3 bg-dark p-2">
                                <div class="text-secondary"><?= $t('pulse_retry', 'Retry') ?></div>
                                <div class="text-light fw-semibold"><?= (int)($queue['retry'] ?? 0) ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded-3 bg-dark p-2">
                                <div class="text-secondary"><?= $t('pulse_processing', 'Processing') ?></div>
                                <div class="text-light fw-semibold"><?= (int)($queue['processing'] ?? 0) ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded-3 bg-dark p-2">
                                <div class="text-secondary"><?= $t('pulse_dead', 'Dead') ?></div>
                                <div class="text-light fw-semibold"><?= (int)($queue['dead'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-danger my-3"><?= $t('pulse_unavailable', 'Unavailable') ?></div>
                    <small class="text-muted"><?= htmlspecialchars((string)($queue['error'] ?? 'Unknown queue error')) ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="glass-card p-4 mt-4">
        <h5 class="text-info border-bottom border-secondary pb-2 mb-3"><?= $t('pulse_server_env', 'Server Environment') ?></h5>
        <div class="row text-light">
            <div class="col-md-3"><strong class="text-secondary"><?= $t('pulse_php_version', 'PHP Version') ?>:</strong><br> <?= $serverInfo['php'] ?></div>
            <div class="col-md-3"><strong class="text-secondary"><?= $t('pulse_os', 'OS') ?>:</strong><br> <?= $serverInfo['os'] ?></div>
            <div class="col-md-3"><strong class="text-secondary"><?= $t('pulse_web_server', 'Web Server') ?>:</strong><br> <?= $serverInfo['server'] ?></div>
            <div class="col-md-3"><strong class="text-secondary"><?= $t('pulse_memory_limit', 'Memory Limit') ?>:</strong><br> <?= $serverInfo['memory_limit'] ?></div>
        </div>
    </div>
</div>
