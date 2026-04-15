<?php
namespace Src\Controllers\Admin;

use Src\Services\AnalyticsService;

class AnalyticsController extends BaseAdminController {
    public function index() {
        $this->checkAuth();

        $allowedDays = [7, 30, 90];
        $days = (int)($_GET['days'] ?? 30);
        if (!in_array($days, $allowedDays, true)) {
            $days = 30;
        }

        /** @var AnalyticsService $analytics */
        $analytics = $this->service('analytics', function () {
            return new AnalyticsService();
        });

        $overview = $analytics->getOverview($days);
        $daily = $analytics->dailyActivity($days);
        $topPages = $analytics->getTopPages($days, 10);
        $topCountries = $analytics->getTopCountries($days, 10);
        $recentLogins = $analytics->getRecentLogins(12);

        $this->view('admin/analytics', compact('days', 'overview', 'daily', 'topPages', 'topCountries', 'recentLogins'));
    }
}
