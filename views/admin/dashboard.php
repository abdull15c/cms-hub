<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-5" data-aos="fade-down">
        <div>
            <h2 class="fw-bold text-light mb-0"><?= $t('dashboard_title', 'Command Center') ?></h2>
            <div class="text-secondary small d-flex align-items-center gap-2">
                <span id="liveClock" class="font-monospace text-info"></span>
                <span>&bull;</span>
                <span><?= $t('dashboard_online', 'System Online') ?></span>
            </div>
        </div>
        <div class="d-flex gap-3">
            <a href="<?= BASE_URL ?>/" class="btn btn-outline-light rounded-pill px-4">
                <i class="fa-solid fa-globe me-2"></i><?= $t('dashboard_view_site', 'View Site') ?>
            </a>
            <form action="<?= BASE_URL ?>/logout" method="POST" class="m-0">
                <?= \Src\Core\Csrf::field() ?>
                <button type="submit" class="btn btn-danger rounded-circle shadow-lg" title="<?= $t('dashboard_logout_title', 'Logout') ?>">
                    <i class="fa-solid fa-power-off"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
            <div class="glass-card stat-card p-4 position-relative overflow-hidden h-100 border-0">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fa-solid fa-box-open"></i></div>
                <h3 class="text-light fw-bold mt-3"><?= $prod_count ?? 0 ?></h3>
                <p class="text-secondary small mb-0"><?= $t('dashboard_active_products', 'Active Products') ?></p>
                <div class="progress mt-3 bg-dark" style="height: 4px;"><div class="progress-bar bg-primary" style="width: 70%"></div></div>
            </div>
        </div>
        <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
            <div class="glass-card stat-card p-4 position-relative overflow-hidden h-100 border-0">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fa-solid fa-users"></i></div>
                <h3 class="text-light fw-bold mt-3"><?= $registered_count ?? 0 ?></h3>
                <p class="text-secondary small mb-0"><?= $t('dashboard_registered_users', 'Registered Users') ?></p>
                <div class="progress mt-3 bg-dark" style="height: 4px;"><div class="progress-bar bg-success" style="width: 85%"></div></div>
            </div>
        </div>
        <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
            <div class="glass-card stat-card p-4 position-relative overflow-hidden h-100 border-0">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fa-solid fa-user-check"></i></div>
                <h3 class="text-light fw-bold mt-3"><?= $visitors_7d ?? 0 ?></h3>
                <p class="text-secondary small mb-0"><?= $t('dashboard_unique_visitors', 'Unique Visitors / 7 Days') ?></p>
                <div class="progress mt-3 bg-dark" style="height: 4px;"><div class="progress-bar bg-warning" style="width: 40%"></div></div>
            </div>
        </div>
        <div class="col-md-3" data-aos="fade-up" data-aos-delay="400">
            <div class="glass-card stat-card p-4 position-relative overflow-hidden h-100 border-0">
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fa-solid fa-chart-bar"></i></div>
                <h3 class="text-light fw-bold mt-3"><?= $page_views_7d ?? 0 ?></h3>
                <p class="text-secondary small mb-0"><?= $t('dashboard_page_views', 'Page Views / 7 Days') ?></p>
                <div class="progress mt-3 bg-dark" style="height: 4px;"><div class="progress-bar bg-info" style="width: 60%"></div></div>
            </div>
        </div>
    </div>

    <div class="glass-card p-4 mb-5 border-0" data-aos="fade-up">
        <div class="row g-3 align-items-center">
            <div class="col-lg-3 col-sm-6">
                <div class="text-secondary small mb-1"><?= $t('dashboard_registrations_today', 'Registrations Today') ?></div>
                <div class="text-light fw-bold fs-5">+<?= (int)($registrations_today ?? 0) ?></div>
                <div class="text-secondary small">
                    <?= htmlspecialchars($t('dashboard_registrations_split', 'Local / Social')) ?>:
                    <?= (int)($registrations_today_local ?? 0) ?> / <?= (int)($registrations_today_social ?? 0) ?>
                </div>
                <div class="text-secondary small">
                    <?= htmlspecialchars($t('dashboard_registrations_providers', 'Google / GitHub')) ?>:
                    <?= (int)($registrations_today_google ?? 0) ?> / <?= (int)($registrations_today_github ?? 0) ?>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="text-secondary small mb-1"><?= $t('dashboard_logins_today', 'Logins Today') ?></div>
                <div class="text-light fw-bold fs-5">+<?= (int)($logins_today ?? 0) ?></div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="text-secondary small mb-1"><?= $t('dashboard_top_page', 'Top Page / 7 Days') ?></div>
                <div class="text-info fw-bold small text-break"><?= htmlspecialchars($top_page['path'] ?? '/') ?> <span class="text-secondary">(<?= (int)($top_page['views'] ?? 0) ?>)</span></div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="text-secondary small mb-1"><?= $t('dashboard_total_revenue', 'Total Revenue') ?></div>
                <div class="text-success fw-bold fs-5"><?= number_format((float)($revenue_total ?? 0), 2) ?> RUB</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="glass-card p-4 mb-4 border-0" data-aos="zoom-in">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="text-light fw-bold"><i class="fa-solid fa-chart-line text-info me-2"></i><?= $t('dashboard_traffic_analytics', 'Traffic Analytics') ?></h5>
                    <a href="<?= BASE_URL ?>/admin/analytics" class="btn btn-sm btn-outline-info rounded-pill px-3"><?= $t('dashboard_open_analytics', 'Open Analytics') ?></a>
                </div>
                <div style="height: 300px;">
                    <canvas id="mainChart"></canvas>
                </div>
            </div>

            <div class="glass-card p-0 border-0 overflow-hidden" data-aos="fade-up">
                <div class="p-4 border-bottom border-secondary border-opacity-25 d-flex justify-content-between">
                    <h5 class="text-light fw-bold m-0"><?= $t('dashboard_recent_transactions', 'Recent Transactions') ?></h5>
                    <a href="<?= BASE_URL ?>/admin/transactions" class="btn btn-sm btn-link text-info text-decoration-none"><?= $t('dashboard_view_all', 'View All') ?></a>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle">
                        <thead class="bg-black bg-opacity-50 text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4"><?= $t('dashboard_id', 'ID') ?></th>
                                <th><?= $t('dashboard_user', 'User') ?></th>
                                <th><?= $t('dashboard_amount', 'Amount') ?></th>
                                <th><?= $t('dashboard_status', 'Status') ?></th>
                                <th class="text-end pe-4"><?= $t('dashboard_date', 'Date') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $s): ?>
                            <tr>
                                <td class="ps-4"><span class="font-monospace text-muted">#<?= $s['id'] ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-gradient-primary d-flex align-items-center justify-content-center me-2" style="width:30px;height:30px;font-size:12px;">
                                            <?= strtoupper(substr($s['email'], 0, 1)) ?>
                                        </div>
                                        <span class="text-light"><?= explode('@', $s['email'])[0] ?></span>
                                    </div>
                                </td>
                                <td class="text-success fw-bold text-shadow">+<?= number_format((float)$s['amount'], 2) ?> RUB</td>
                                <td>
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 rounded-pill px-3"><?= $t('dashboard_paid', 'PAID') ?></span>
                                </td>
                                <td class="text-end pe-4 text-secondary small"><?= date('M d, H:i', strtotime($s['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-card p-4 mb-4 border-0" data-aos="fade-left">
                <h5 class="text-light fw-bold mb-4"><?= $t('dashboard_quick_actions', 'Quick Actions') ?></h5>
                <div class="row g-3">
                    <div class="col-6">
                        <a href="<?= BASE_URL ?>/admin/product/new" class="action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="fa-solid fa-plus-circle fa-2x text-info mb-2"></i>
                            <span class="text-light small"><?= $t('dashboard_add_product', 'Add Product') ?></span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= BASE_URL ?>/admin/products" class="action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="fa-solid fa-boxes-stacked fa-2x text-primary mb-2"></i>
                            <span class="text-light small"><?= $t('dashboard_manage_products', 'Products') ?></span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= BASE_URL ?>/admin/tickets" class="action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="fa-solid fa-headset fa-2x text-warning mb-2"></i>
                            <span class="text-light small"><?= $t('dashboard_support', 'Support') ?></span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= BASE_URL ?>/admin/users" class="action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="fa-solid fa-users fa-2x text-success mb-2"></i>
                            <span class="text-light small"><?= $t('dashboard_users', 'Users') ?></span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= BASE_URL ?>/admin/categories" class="action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="fa-solid fa-tags fa-2x text-warning mb-2"></i>
                            <span class="text-light small"><?= $t('dashboard_categories', 'Categories') ?></span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= BASE_URL ?>/admin/themes" class="action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="fa-solid fa-palette fa-2x text-info mb-2"></i>
                            <span class="text-light small"><?= $t('dashboard_themes', 'Themes') ?></span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= BASE_URL ?>/admin/settings" class="action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="fa-solid fa-sliders fa-2x text-secondary mb-2"></i>
                            <span class="text-light small"><?= $t('dashboard_settings', 'Settings') ?></span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= BASE_URL ?>/admin/coupons" class="action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="fa-solid fa-ticket fa-2x text-success mb-2"></i>
                            <span class="text-light small"><?= $t('dashboard_coupons', 'Coupons') ?></span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= BASE_URL ?>/admin/logs" class="action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="fa-solid fa-terminal fa-2x text-danger mb-2"></i>
                            <span class="text-light small"><?= $t('dashboard_system_logs', 'System Logs') ?></span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= BASE_URL ?>/admin/reviews" class="action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="fa-solid fa-star fa-2x text-warning mb-2"></i>
                            <span class="text-light small"><?= $t('dashboard_reviews', 'Reviews') ?></span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= BASE_URL ?>/admin/analytics" class="action-btn d-flex flex-column align-items-center justify-content-center p-3 rounded text-decoration-none">
                            <i class="fa-solid fa-chart-simple fa-2x text-info mb-2"></i>
                            <span class="text-light small"><?= $t('dashboard_analytics_center', 'Analytics') ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="glass-card p-4 border-0" data-aos="fade-up" data-aos-delay="200">
                <h5 class="text-light fw-bold mb-3"><?= $t('dashboard_system_health', 'System Health') ?></h5>

                <div class="mb-3">
                    <div class="d-flex justify-content-between text-secondary small mb-1">
                        <span><?= $t('dashboard_cpu_load', 'CPU Load') ?></span>
                        <span class="text-info">12%</span>
                    </div>
                    <div class="progress bg-dark" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: 12%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between text-secondary small mb-1">
                        <span><?= $t('dashboard_memory', 'Memory') ?></span>
                        <span class="text-warning">45%</span>
                    </div>
                    <div class="progress bg-dark" style="height: 6px;">
                        <div class="progress-bar bg-warning" style="width: 45%"></div>
                    </div>
                </div>

                <div class="alert alert-success bg-opacity-10 border-success border-opacity-25 text-success small mb-0 d-flex align-items-center">
                    <i class="fa-solid fa-check-circle me-2"></i> <?= $t('dashboard_all_operational', 'All Systems Operational') ?>
                </div>

                <a href="<?= BASE_URL ?>/admin/pulse" class="btn btn-outline-secondary w-100 mt-3 btn-sm"><?= $t('dashboard_full_diagnostics', 'Full Diagnostics') ?></a>
            </div>

            <div class="glass-card p-0 border-0 overflow-hidden mt-4" data-aos="fade-left">
                <div class="p-4 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                    <h5 class="text-light fw-bold m-0"><?= $t('dashboard_recent_products', 'Recent Products') ?></h5>
                    <a href="<?= BASE_URL ?>/admin/products" class="btn btn-sm btn-link text-info text-decoration-none"><?= $t('dashboard_view_all', 'View All') ?></a>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle">
                        <thead class="bg-black bg-opacity-50 text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Product</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr><td colspan="4" class="text-center text-secondary py-4">No products yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td class="ps-4 text-secondary font-monospace">#<?= (int)$product['id'] ?></td>
                                        <td>
                                            <div class="text-light fw-semibold"><?= htmlspecialchars($product['title'] ?? '') ?></div>
                                            <div class="small text-secondary"><?= number_format((float)$product['price'], 2) ?> RUB</div>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill <?= ($product['status'] ?? 'published') === 'draft' ? 'bg-warning text-dark' : 'bg-success' ?>">
                                                <?= htmlspecialchars(ucfirst($product['status'] ?? 'published')) ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="<?= BASE_URL ?>/admin/product/edit/<?= (int)$product['id'] ?>" class="btn btn-sm btn-outline-info">Edit</a>
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
</div>

<style>
    .glass-card {
        background: rgba(20, 20, 30, 0.6);
        backdrop-filter: blur(12px);
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 16px;
    }

    .stat-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 242, 234, 0.1);
        border-color: rgba(0, 242, 234, 0.3);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .action-btn {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.05);
        transition: all 0.2s;
    }

    .action-btn:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(0, 242, 234, 0.5);
        transform: scale(1.02);
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #00f2ea 0%, #00a8c6 100%);
        color: #000;
        font-weight: bold;
    }

    .text-shadow {
        text-shadow: 0 0 10px rgba(0, 242, 234, 0.4);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script nonce="<?= CSP_NONCE ?>">
    function updateClock() {
        const now = new Date();
        document.getElementById('liveClock').innerText = now.toLocaleTimeString();
    }

    setInterval(updateClock, 1000);
    updateClock();

    const ctx = document.getElementById('mainChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(0, 242, 234, 0.5)');
    gradient.addColorStop(1, 'rgba(0, 242, 234, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart['labels'] ?? [], JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: <?= json_encode($t('dashboard_chart_views', 'Page Views'), JSON_UNESCAPED_UNICODE) ?>,
                data: <?= json_encode($chart['page_views'] ?? []) ?>,
                borderColor: '#00f2ea',
                backgroundColor: gradient,
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#00f2ea',
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4
            }, {
                label: <?= json_encode($t('dashboard_chart_visitors', 'Unique Visitors'), JSON_UNESCAPED_UNICODE) ?>,
                data: <?= json_encode($chart['unique_visitors'] ?? []) ?>,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.08)',
                borderWidth: 2,
                pointRadius: 3,
                fill: false,
                tension: 0.35
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: '#cbd5e1' }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#fff',
                    bodyColor: '#00f2ea',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#6c757d' },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#6c757d' },
                    border: { display: false }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
</script>
