<?php
namespace Src\Services;

class ThemeService
{
    private const DEFAULT_THEME = 'dark-tech';

    public static function all(): array
    {
        return [
            'dark-tech' => [
                'slug' => 'dark-tech',
                'name' => 'Dark Tech',
                'tagline' => 'Cyber storefront for code, AI and digital tools.',
                'description' => 'High-contrast dark theme with neon cyan and magenta accents. Best fit for scripts, templates, SaaS kits and developer products.',
                'badge' => 'Default',
                'layout_name' => 'Tech Grid',
                'best_for' => 'Scripts, AI products, dev stores and code marketplaces.',
                'home_variant' => 'tech-grid',
                'palette' => [
                    'bootstrap_theme' => 'dark',
                    'bg_color' => '#0b0f19',
                    'body_gradient' => 'radial-gradient(circle at top right, rgba(0,242,234,0.12), transparent 32%), radial-gradient(circle at bottom left, rgba(255,0,80,0.12), transparent 35%), linear-gradient(180deg, #09111d 0%, #0b0f19 100%)',
                    'card_bg' => 'rgba(30, 41, 59, 0.42)',
                    'card_border' => 'rgba(255,255,255,0.08)',
                    'primary' => '#00f2ea',
                    'secondary' => '#ff0050',
                    'primary_soft' => 'rgba(0,242,234,0.20)',
                    'secondary_soft' => 'rgba(255,0,80,0.18)',
                    'text_main' => '#e2e8f0',
                    'muted_text' => '#94a3b8',
                    'nav_bg' => 'rgba(11,15,25,0.95)',
                    'nav_border' => 'rgba(255,255,255,0.10)',
                    'dropdown_bg' => 'rgba(15,23,42,0.96)',
                    'dropdown_hover' => 'rgba(0,242,234,0.10)',
                    'footer_border' => 'rgba(0,242,234,0.16)',
                    'footer_glow' => 'rgba(0,242,234,0.14)',
                    'surface_bg' => '#171e2f',
                    'surface_bg_alt' => '#111827',
                    'surface_border' => '#283247',
                    'button_text' => '#02181b',
                    'badge_bg' => 'rgba(0,242,234,0.12)',
                    'badge_text' => '#9ffcf6',
                ],
                'preview' => [
                    'hero' => 'linear-gradient(135deg, #00f2ea 0%, #09111d 55%, #ff0050 100%)',
                    'panel' => '#121a2c',
                    'accent' => '#00f2ea',
                    'accent_alt' => '#ff0050',
                ],
            ],
            'midnight-luxe' => [
                'slug' => 'midnight-luxe',
                'name' => 'Midnight Luxe',
                'tagline' => 'Premium dark storefront with gold editorial accents.',
                'description' => 'A polished luxury palette with graphite panels, warm highlights and a more premium tone for curated shops and high-ticket products.',
                'badge' => 'Premium',
                'layout_name' => 'Luxe Editorial',
                'best_for' => 'Premium catalogs, agency delivery and high-ticket offers.',
                'home_variant' => 'luxe-editorial',
                'palette' => [
                    'bootstrap_theme' => 'dark',
                    'bg_color' => '#111111',
                    'body_gradient' => 'radial-gradient(circle at top left, rgba(224,166,74,0.15), transparent 28%), radial-gradient(circle at bottom right, rgba(127,29,29,0.16), transparent 30%), linear-gradient(180deg, #141414 0%, #111111 100%)',
                    'card_bg' => 'rgba(35, 29, 24, 0.56)',
                    'card_border' => 'rgba(224,166,74,0.16)',
                    'primary' => '#e0a64a',
                    'secondary' => '#f97316',
                    'primary_soft' => 'rgba(224,166,74,0.20)',
                    'secondary_soft' => 'rgba(249,115,22,0.18)',
                    'text_main' => '#f5efe6',
                    'muted_text' => '#c7b8a4',
                    'nav_bg' => 'rgba(17,17,17,0.94)',
                    'nav_border' => 'rgba(224,166,74,0.14)',
                    'dropdown_bg' => 'rgba(32,26,21,0.97)',
                    'dropdown_hover' => 'rgba(224,166,74,0.10)',
                    'footer_border' => 'rgba(224,166,74,0.18)',
                    'footer_glow' => 'rgba(224,166,74,0.16)',
                    'surface_bg' => '#231d18',
                    'surface_bg_alt' => '#1a1613',
                    'surface_border' => '#48392d',
                    'button_text' => '#1b1306',
                    'badge_bg' => 'rgba(224,166,74,0.12)',
                    'badge_text' => '#f5d28a',
                ],
                'preview' => [
                    'hero' => 'linear-gradient(135deg, #e0a64a 0%, #231d18 48%, #f97316 100%)',
                    'panel' => '#231d18',
                    'accent' => '#e0a64a',
                    'accent_alt' => '#f97316',
                ],
            ],
            'ocean-market' => [
                'slug' => 'ocean-market',
                'name' => 'Ocean Market',
                'tagline' => 'Deep blue storefront for modern product catalogs.',
                'description' => 'Cool blue and emerald palette with softer surfaces. Great for digital catalogs, memberships and marketplaces with a calm professional look.',
                'badge' => 'Fresh',
                'layout_name' => 'Calm Catalog',
                'best_for' => 'Broad catalogs, memberships, services and evergreen products.',
                'home_variant' => 'ocean-catalog',
                'palette' => [
                    'bootstrap_theme' => 'dark',
                    'bg_color' => '#071722',
                    'body_gradient' => 'radial-gradient(circle at top right, rgba(45,212,191,0.16), transparent 32%), radial-gradient(circle at bottom left, rgba(56,189,248,0.18), transparent 35%), linear-gradient(180deg, #071722 0%, #0a2233 100%)',
                    'card_bg' => 'rgba(12, 37, 53, 0.52)',
                    'card_border' => 'rgba(45,212,191,0.14)',
                    'primary' => '#38bdf8',
                    'secondary' => '#2dd4bf',
                    'primary_soft' => 'rgba(56,189,248,0.20)',
                    'secondary_soft' => 'rgba(45,212,191,0.18)',
                    'text_main' => '#e0f2fe',
                    'muted_text' => '#9fc8db',
                    'nav_bg' => 'rgba(7,23,34,0.95)',
                    'nav_border' => 'rgba(56,189,248,0.12)',
                    'dropdown_bg' => 'rgba(8,30,46,0.97)',
                    'dropdown_hover' => 'rgba(45,212,191,0.10)',
                    'footer_border' => 'rgba(56,189,248,0.18)',
                    'footer_glow' => 'rgba(45,212,191,0.16)',
                    'surface_bg' => '#0c2535',
                    'surface_bg_alt' => '#092030',
                    'surface_border' => '#204960',
                    'button_text' => '#05131b',
                    'badge_bg' => 'rgba(56,189,248,0.12)',
                    'badge_text' => '#9fe8ff',
                ],
                'preview' => [
                    'hero' => 'linear-gradient(135deg, #38bdf8 0%, #0a2233 48%, #2dd4bf 100%)',
                    'panel' => '#0c2535',
                    'accent' => '#38bdf8',
                    'accent_alt' => '#2dd4bf',
                ],
            ],
        ];
    }

    public static function adminTheme(): array
    {
        return [
            'slug' => 'admin-console',
            'name' => 'Admin Console',
            'tagline' => 'Stable control panel palette.',
            'description' => 'A fixed admin palette so storefront theme changes never make the management area harder to use.',
            'badge' => 'Admin',
            'palette' => [
                'bootstrap_theme' => 'dark',
                'bg_color' => '#0b0f19',
                'body_gradient' => 'linear-gradient(180deg, #0b0f19 0%, #0b0f19 100%)',
                'card_bg' => 'rgba(30, 41, 59, 0.4)',
                'card_border' => 'rgba(255,255,255,0.08)',
                'primary' => '#00f2ea',
                'secondary' => '#ff0050',
                'primary_soft' => 'rgba(0,242,234,0.20)',
                'secondary_soft' => 'rgba(255,0,80,0.18)',
                'text_main' => '#e2e8f0',
                'muted_text' => '#94a3b8',
                'nav_bg' => 'rgba(11,15,25,0.95)',
                'nav_border' => 'rgba(255,255,255,0.10)',
                'dropdown_bg' => 'rgba(15,23,42,0.96)',
                'dropdown_hover' => 'rgba(0,242,234,0.10)',
                'footer_border' => 'rgba(112,0,255,0.20)',
                'footer_glow' => 'rgba(112,0,255,0.15)',
                'surface_bg' => '#1c1d33',
                'surface_bg_alt' => '#14162a',
                'surface_border' => '#2d2e4f',
                'button_text' => '#02181b',
                'badge_bg' => 'rgba(0,242,234,0.12)',
                'badge_text' => '#9ffcf6',
            ],
            'preview' => [
                'hero' => 'linear-gradient(135deg, #00f2ea 0%, #0b0f19 55%, #ff0050 100%)',
                'panel' => '#14162a',
                'accent' => '#00f2ea',
                'accent_alt' => '#ff0050',
            ],
        ];
    }

    public static function get(string $slug): array
    {
        $themes = self::all();
        return $themes[$slug] ?? $themes[self::DEFAULT_THEME];
    }

    public static function exists(string $slug): bool
    {
        return isset(self::all()[$slug]);
    }

    public static function activeSlug(): string
    {
        $preview = trim((string) ($_GET['preview_theme'] ?? ''));
        if ($preview !== '' && self::exists($preview) && SessionService::get('role') === 'admin') {
            return $preview;
        }

        $stored = trim((string) SettingsService::get('active_theme'));
        if ($stored !== '' && self::exists($stored)) {
            return $stored;
        }
        return self::DEFAULT_THEME;
    }

    public static function active(): array
    {
        return self::get(self::activeSlug());
    }

    public static function forContext(bool $isAdminView): array
    {
        return $isAdminView ? self::adminTheme() : self::active();
    }
}
