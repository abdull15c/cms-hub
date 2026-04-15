<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-light mb-1"><i class="fa-solid fa-chart-simple text-info me-2"></i><?= htmlspecialchars($t('analytics_title', 'Traffic Analytics')) ?></h2>
            <p class="text-secondary mb-0"><?= htmlspecialchars($t('analytics_subtitle', 'Registrations, logins, visitors, top pages and countries in one place.')) ?></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php foreach ([7, 30, 90] as $period): ?>
                <a href="<?= BASE_URL ?>/admin/analytics?days=<?= $period ?>" class="btn <?= $days === $period ? 'btn-info text-dark' : 'btn-outline-secondary' ?> rounded-pill px-3">
                    <?= $period ?> <?= htmlspecialchars($t('analytics_days', 'days')) ?>
                </a>
            <?php endforeach; ?>
            <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-outline-light rounded-pill px-4"><?= htmlspecialchars($t('common_back', 'Back')) ?></a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="glass-card p-4 h-100">
                <div class="text-secondary small mb-2"><?= htmlspecialchars($t('analytics_total_registrations', 'Total registrations')) ?></div>
                <div class="display-6 fw-bold text-light"><?= (int)$overview['registrations_total'] ?></div>
                <div class="small text-info mt-2">+<?= (int)$overview['registrations_today'] ?> <?= htmlspecialchars($t('analytics_today', 'today')) ?></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="glass-card p-4 h-100">
                <div class="text-secondary small mb-2"><?= htmlspecialchars($t('analytics_total_logins', 'Total logins')) ?></div>
                <div class="display-6 fw-bold text-light"><?= (int)$overview['logins_total'] ?></div>
                <div class="small text-success mt-2">+<?= (int)$overview['logins_today'] ?> <?= htmlspecialchars($t('analytics_today', 'today')) ?></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="glass-card p-4 h-100">
                <div class="text-secondary small mb-2"><?= htmlspecialchars($t('analytics_unique_visitors', 'Unique visitors')) ?> / <?= (int)$days ?> <?= htmlspecialchars($t('analytics_days', 'days')) ?></div>
                <div class="display-6 fw-bold text-light"><?= (int)$overview['unique_visitors_period'] ?></div>
                <div class="small text-warning mt-2">+<?= (int)$overview['unique_visitors_today'] ?> <?= htmlspecialchars($t('analytics_today', 'today')) ?></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="glass-card p-4 h-100">
                <div class="text-secondary small mb-2"><?= htmlspecialchars($t('analytics_page_views', 'Page views')) ?></div>
                <div class="display-6 fw-bold text-light"><?= (int)$overview['page_views_total'] ?></div>
                <div class="small text-primary mt-2">+<?= (int)$overview['page_views_today'] ?> <?= htmlspecialchars($t('analytics_today', 'today')) ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="glass-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="text-light fw-bold mb-0"><?= htmlspecialchars($t('analytics_daily_activity', 'Daily activity')) ?></h5>
                    <span class="badge bg-info bg-opacity-25 text-info border border-info border-opacity-25 rounded-pill px-3"><?= (int)$days ?> <?= htmlspecialchars($t('analytics_days', 'days')) ?></span>
                </div>
                <div style="height: 320px;">
                    <canvas id="analyticsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="glass-card p-4 h-100">
                <div class="text-secondary small mb-2"><?= htmlspecialchars($t('analytics_most_visited_page', 'Most visited page')) ?></div>
                <div class="fw-bold text-light fs-4 text-break"><?= htmlspecialchars((string)($overview['top_page']['path'] ?? '/')) ?></div>
                <div class="small text-info mt-2"><?= (int)($overview['top_page']['views'] ?? 0) ?> <?= htmlspecialchars($t('analytics_views', 'views')) ?></div>

                <hr class="border-secondary border-opacity-25 my-4">

                <div class="small text-secondary mb-2"><?= htmlspecialchars($t('analytics_period_hint', 'The data becomes more accurate from the moment analytics collection is enabled.')) ?></div>
                <div class="small text-secondary"><?= htmlspecialchars($t('analytics_geo_hint', 'Country is detected by reverse-proxy headers or cached IP lookup.')) ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="glass-card p-0 overflow-hidden h-100">
                <div class="p-4 border-bottom border-secondary border-opacity-25">
                    <h5 class="text-light fw-bold mb-0"><?= htmlspecialchars($t('analytics_top_pages', 'Top pages')) ?></h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle">
                        <thead class="text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4"><?= htmlspecialchars($t('analytics_page', 'Page')) ?></th>
                                <th><?= htmlspecialchars($t('analytics_views', 'Views')) ?></th>
                                <th class="pe-4"><?= htmlspecialchars($t('analytics_unique_visitors', 'Unique visitors')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topPages)): ?>
                                <tr><td colspan="3" class="ps-4 text-secondary py-4"><?= htmlspecialchars($t('analytics_no_data', 'No data yet.')) ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($topPages as $page): ?>
                                    <tr>
                                        <td class="ps-4 text-light text-break"><?= htmlspecialchars($page['path']) ?></td>
                                        <td class="text-info fw-bold"><?= (int)$page['views'] ?></td>
                                        <td class="pe-4 text-light"><?= (int)$page['unique_visitors'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="glass-card p-0 overflow-hidden h-100">
                <div class="p-4 border-bottom border-secondary border-opacity-25">
                    <h5 class="text-light fw-bold mb-0"><?= htmlspecialchars($t('analytics_top_countries', 'Top countries')) ?></h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle">
                        <thead class="text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4"><?= htmlspecialchars($t('analytics_country', 'Country')) ?></th>
                                <th><?= htmlspecialchars($t('analytics_views', 'Views')) ?></th>
                                <th class="pe-4"><?= htmlspecialchars($t('analytics_unique_visitors', 'Unique visitors')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topCountries)): ?>
                                <tr><td colspan="3" class="ps-4 text-secondary py-4"><?= htmlspecialchars($t('analytics_no_data', 'No data yet.')) ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($topCountries as $country): ?>
                                    <tr>
                                        <td class="ps-4 text-light"><?= htmlspecialchars(($country['country_code'] ?? 'ZZ') . ' - ' . ($country['country_name'] ?? 'Unknown')) ?></td>
                                        <td class="text-info fw-bold"><?= (int)$country['views'] ?></td>
                                        <td class="pe-4 text-light"><?= (int)$country['unique_visitors'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="glass-card p-0 overflow-hidden">
                <div class="p-4 border-bottom border-secondary border-opacity-25">
                    <h5 class="text-light fw-bold mb-0"><?= htmlspecialchars($t('analytics_recent_logins', 'Recent logins')) ?></h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle">
                        <thead class="text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4"><?= htmlspecialchars($t('analytics_user', 'User')) ?></th>
                                <th><?= htmlspecialchars($t('analytics_country', 'Country')) ?></th>
                                <th class="pe-4"><?= htmlspecialchars($t('analytics_when', 'When')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentLogins)): ?>
                                <tr><td colspan="3" class="ps-4 text-secondary py-4"><?= htmlspecialchars($t('analytics_no_data', 'No data yet.')) ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($recentLogins as $login): ?>
                                    <tr>
                                        <td class="ps-4 text-light"><?= htmlspecialchars($login['email'] ?: ($t('analytics_unknown', 'Unknown'))) ?></td>
                                        <td class="text-light"><?= htmlspecialchars(($login['country_code'] ?? 'ZZ') . ' - ' . ($login['country_name'] ?? 'Unknown')) ?></td>
                                        <td class="pe-4 text-secondary"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($login['created_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script nonce="<?= CSP_NONCE ?>">
    const analyticsCtx = document.getElementById('analyticsChart').getContext('2d');

    new Chart(analyticsCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($daily['labels'], JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                {
                    label: <?= json_encode($t('analytics_page_views', 'Page views'), JSON_UNESCAPED_UNICODE) ?>,
                    data: <?= json_encode($daily['page_views']) ?>,
                    borderColor: '#00f2ea',
                    backgroundColor: 'rgba(0, 242, 234, 0.12)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2
                },
                {
                    label: <?= json_encode($t('analytics_unique_visitors', 'Unique visitors'), JSON_UNESCAPED_UNICODE) ?>,
                    data: <?= json_encode($daily['unique_visitors']) ?>,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.08)',
                    fill: false,
                    tension: 0.35,
                    borderWidth: 2
                },
                {
                    label: <?= json_encode($t('analytics_registrations', 'Registrations'), JSON_UNESCAPED_UNICODE) ?>,
                    data: <?= json_encode($daily['registrations']) ?>,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.08)',
                    fill: false,
                    tension: 0.35,
                    borderWidth: 2
                },
                {
                    label: <?= json_encode($t('analytics_logins', 'Logins'), JSON_UNESCAPED_UNICODE) ?>,
                    data: <?= json_encode($daily['logins']) ?>,
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.08)',
                    fill: false,
                    tension: 0.35,
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: '#cbd5e1'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.06)' },
                    ticks: { color: '#94a3b8' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });
</script>
