<?php
namespace Src\Controllers\Admin;

use Src\Services\AuditService;
use Src\Services\SettingsService;
use Src\Services\ThemeService;

class ThemeController extends BaseAdminController
{
    public function index(): void
    {
        $this->checkAuth();

        $this->view('admin/themes', [
            'themes' => ThemeService::all(),
            'activeTheme' => ThemeService::active(),
            'activeThemeSlug' => ThemeService::activeSlug(),
        ]);
    }

    public function activate(string $slug): void
    {
        $this->checkAuth();
        $this->verifyCsrf();

        if (!ThemeService::exists($slug)) {
            $this->redirect('/admin/themes', 'Theme not found.');
        }

        SettingsService::set('active_theme', $slug);
        AuditService::log('theme', 'activate', null, ['theme' => $slug]);
        $this->redirect('/admin/themes', null, 'Theme activated successfully.');
    }
}
