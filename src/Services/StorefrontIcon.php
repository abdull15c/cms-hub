<?php
namespace Src\Services;

class StorefrontIcon
{
    public static function render(string $icon, string $class = '', string $label = ''): string
    {
        $name = self::normalize($icon);
        $classes = trim('icon-svg ' . $class);
        $label = trim($label);
        $ariaHidden = $label === '' ? 'true' : 'false';
        $ariaLabel = $label === '' ? '' : ' aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"';

        return '<svg class="' . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" role="img" aria-hidden="' . $ariaHidden . '"' . $ariaLabel . ' xmlns="http://www.w3.org/2000/svg">' . self::markup($name) . '</svg>';
    }

    private static function normalize(string $icon): string
    {
        $tokens = preg_split('/\s+/', strtolower(trim($icon))) ?: [];
        $ignore = [
            '',
            'fa',
            'fa-solid',
            'fa-regular',
            'fa-brands',
            'fa-brand',
            'fa-light',
            'fa-thin',
            'fa-duotone',
            'fa-sharp',
            'fa-classic',
        ];

        $name = '';
        foreach ($tokens as $token) {
            if (in_array($token, $ignore, true)) {
                continue;
            }

            if (strpos($token, 'fa-') === 0) {
                $name = substr($token, 3);
                continue;
            }

            if ($name === '') {
                $name = $token;
            }
        }

        $name = preg_replace('/[^a-z0-9-]+/', '-', $name ?: 'sparkles') ?: 'sparkles';

        $aliases = [
            'arrow-left' => 'arrow-left',
            'arrow-right' => 'arrow-right',
            'bitcoin' => 'coins',
            'bolt' => 'bolt',
            'boxes-stacked' => 'stack',
            'calendar' => 'calendar',
            'cart-shopping' => 'cart',
            'cc-mastercard' => 'card',
            'cc-visa' => 'card',
            'chart-line' => 'chart-line',
            'chart-simple' => 'chart-bars',
            'circle-check' => 'circle-check',
            'circle-question' => 'question',
            'circle-xmark' => 'circle-x',
            'code' => 'code',
            'cog' => 'settings',
            'coins' => 'coins',
            'comments' => 'chat',
            'discord' => 'discord',
            'download' => 'download',
            'envelope' => 'mail',
            'fire' => 'fire',
            'ghost' => 'ghost',
            'house' => 'home',
            'image' => 'image',
            'key' => 'key',
            'language' => 'language',
            'layer-group' => 'layers',
            'microchip' => 'chip',
            'newspaper' => 'newspaper',
            'palette' => 'palette',
            'paper-plane' => 'send',
            'robot' => 'robot',
            'ruble-sign' => 'coins',
            'satellite-dish' => 'satellite',
            'scale-balanced' => 'scale',
            'search' => 'search',
            'shield-halved' => 'shield',
            'sliders' => 'sliders',
            'sparkles' => 'sparkles',
            'star' => 'star',
            'tag' => 'tag',
            'tags' => 'tags',
            'telegram' => 'telegram',
            'user-shield' => 'user-shield',
            'youtube' => 'youtube',
        ];

        return $aliases[$name] ?? 'sparkles';
    }

    private static function markup(string $name): string
    {
        $icons = [
            'arrow-left' => '<path d="M15 6l-6 6 6 6"></path><path d="M9 12h11"></path>',
            'arrow-right' => '<path d="M9 6l6 6-6 6"></path><path d="M4 12h11"></path>',
            'bolt' => '<path d="M13 2 5 14h5l-1 8 8-12h-5l1-8Z"></path>',
            'calendar' => '<rect x="3.5" y="5.5" width="17" height="15" rx="2.5"></rect><path d="M7.5 3.5v4"></path><path d="M16.5 3.5v4"></path><path d="M3.5 9.5h17"></path>',
            'card' => '<rect x="3.5" y="6" width="17" height="12" rx="2.5"></rect><path d="M3.5 10h17"></path><path d="M7 15h3"></path>',
            'cart' => '<circle cx="9" cy="19" r="1.6"></circle><circle cx="17" cy="19" r="1.6"></circle><path d="M4 5h2l2.2 9.2a1 1 0 0 0 1 .8h7.7a1 1 0 0 0 1-.7L20 8H7.2"></path>',
            'chat' => '<path d="M20 14a4 4 0 0 1-4 4H9l-5 3v-7a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4Z"></path><path d="M8 6a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"></path>',
            'chart-bars' => '<path d="M4 20h16"></path><path d="M7 17v-6"></path><path d="M12 17V7"></path><path d="M17 17v-3"></path>',
            'chart-line' => '<path d="M4 20h16"></path><path d="M5 15l4-4 3 2 7-7"></path><circle cx="5" cy="15" r=".8" fill="currentColor" stroke="none"></circle><circle cx="9" cy="11" r=".8" fill="currentColor" stroke="none"></circle><circle cx="12" cy="13" r=".8" fill="currentColor" stroke="none"></circle><circle cx="19" cy="6" r=".8" fill="currentColor" stroke="none"></circle>',
            'chip' => '<rect x="7" y="7" width="10" height="10" rx="2"></rect><rect x="10" y="10" width="4" height="4" rx="1"></rect><path d="M9 3v4"></path><path d="M15 3v4"></path><path d="M9 17v4"></path><path d="M15 17v4"></path><path d="M3 9h4"></path><path d="M3 15h4"></path><path d="M17 9h4"></path><path d="M17 15h4"></path>',
            'circle-check' => '<circle cx="12" cy="12" r="9"></circle><path d="m8.5 12.2 2.3 2.3 4.7-5"></path>',
            'circle-x' => '<circle cx="12" cy="12" r="9"></circle><path d="m9 9 6 6"></path><path d="m15 9-6 6"></path>',
            'code' => '<path d="m8 8-4 4 4 4"></path><path d="m16 8 4 4-4 4"></path><path d="M14 5 10 19"></path>',
            'coins' => '<ellipse cx="12" cy="6.5" rx="5.5" ry="2.5"></ellipse><path d="M6.5 6.5v5c0 1.4 2.5 2.5 5.5 2.5s5.5-1.1 5.5-2.5v-5"></path><path d="M8.5 13v4.2c0 1.2 1.6 2.2 3.5 2.2s3.5-1 3.5-2.2V13"></path>',
            'discord' => '<path d="M7 8c1.6-1.1 3.3-1.7 5-1.7S15.4 6.9 17 8c1.2 1.6 1.8 3.5 2 5.7-1.4 1.8-3.8 3.1-7 4-3.2-.9-5.6-2.2-7-4 .2-2.2.8-4.1 2-5.7Z"></path><circle cx="9.7" cy="12.1" r=".9" fill="currentColor" stroke="none"></circle><circle cx="14.3" cy="12.1" r=".9" fill="currentColor" stroke="none"></circle><path d="M9.6 15c.8.6 1.6.9 2.4.9s1.6-.3 2.4-.9"></path>',
            'download' => '<path d="M12 4v11"></path><path d="m8 11 4 4 4-4"></path><path d="M5 20h14"></path>',
            'fire' => '<path d="M12 21c3.5 0 6-2.6 6-6.1 0-2.9-1.6-4.8-3.3-6.5-1.1-1-1.9-2.3-2-3.8-2.8 1.6-4.8 4.6-4.8 7.7 0 1 .2 2 .7 3C7 16.1 6 17.4 6 18.9 6 20.4 8.6 21 12 21Z"></path><path d="M12 21c1.8 0 3-1.3 3-3.1 0-1.5-.9-2.4-1.8-3.4-.5-.5-.9-1.2-1-2-1.5 1-2.2 2.5-2.2 4.2 0 2.4 1.2 4.3 2 4.3Z"></path>',
            'ghost' => '<path d="M6 20v-8a6 6 0 1 1 12 0v8l-3-2-3 2-3-2-3 2Z"></path><circle cx="10" cy="12" r="1" fill="currentColor" stroke="none"></circle><circle cx="14" cy="12" r="1" fill="currentColor" stroke="none"></circle>',
            'home' => '<path d="M3 10.5 12 3l9 7.5"></path><path d="M6 9.5V20h12V9.5"></path>',
            'image' => '<rect x="3.5" y="5.5" width="17" height="13" rx="2.5"></rect><circle cx="9" cy="10" r="1.4"></circle><path d="m6 16 4-4 3 3 2-2 3 3"></path>',
            'key' => '<circle cx="8" cy="12" r="3.5"></circle><path d="M11.5 12H20"></path><path d="M17 12v3"></path><path d="M14.5 12v2"></path>',
            'language' => '<circle cx="12" cy="12" r="9"></circle><path d="M3 12h18"></path><path d="M12 3c3 2.8 4.7 5.9 4.7 9S15 18.2 12 21"></path><path d="M12 3C9 5.8 7.3 8.9 7.3 12S9 18.2 12 21"></path>',
            'layers' => '<path d="M12 4l8 4-8 4-8-4 8-4Z"></path><path d="M4 12l8 4 8-4"></path><path d="M4 16l8 4 8-4"></path>',
            'mail' => '<rect x="3.5" y="5.5" width="17" height="13" rx="2.5"></rect><path d="m4.5 7 7.5 6 7.5-6"></path>',
            'newspaper' => '<rect x="4" y="5" width="16" height="14" rx="2"></rect><path d="M8 9h8"></path><path d="M8 12h8"></path><path d="M8 15h5"></path><path d="M6.5 8.5h.01"></path><path d="M6.5 11.5h.01"></path>',
            'palette' => '<path d="M12 3a9 9 0 1 0 0 18h1.2a2.3 2.3 0 0 0 0-4.6h-1a1.8 1.8 0 0 1 0-3.6H14A5 5 0 0 0 12 3Z"></path><circle cx="7.5" cy="11" r=".9" fill="currentColor" stroke="none"></circle><circle cx="10" cy="7.5" r=".9" fill="currentColor" stroke="none"></circle><circle cx="15.5" cy="8.5" r=".9" fill="currentColor" stroke="none"></circle>',
            'question' => '<circle cx="12" cy="12" r="9"></circle><path d="M9.8 9.3a2.7 2.7 0 1 1 4.5 2c-.9.8-1.8 1.3-1.8 2.4"></path><circle cx="12" cy="17.2" r=".9" fill="currentColor" stroke="none"></circle>',
            'robot' => '<rect x="6.5" y="7.5" width="11" height="10" rx="2.5"></rect><path d="M12 3.5v4"></path><path d="M4.5 11.5h2"></path><path d="M17.5 11.5h2"></path><circle cx="10" cy="11.5" r=".9" fill="currentColor" stroke="none"></circle><circle cx="14" cy="11.5" r=".9" fill="currentColor" stroke="none"></circle><path d="M9.5 15h5"></path>',
            'satellite' => '<path d="M6.5 14.5 10 11l3.5 3.5a5 5 0 0 1-7 0Z"></path><path d="M14.5 14.5 20 20"></path><path d="M14.5 9.5A6 6 0 0 1 18 6"></path><path d="M16.5 12.5A9 9 0 0 1 21 8"></path><circle cx="10" cy="11" r=".9" fill="currentColor" stroke="none"></circle>',
            'scale' => '<path d="M12 4v16"></path><path d="M7 7h10"></path><path d="M5 7 2.5 12h5L5 7Z"></path><path d="m19 7-2.5 5h5L19 7Z"></path><path d="M8 20h8"></path>',
            'search' => '<circle cx="11" cy="11" r="6.5"></circle><path d="m20 20-4.2-4.2"></path>',
            'send' => '<path d="M21 3 10 14"></path><path d="M21 3 14 21l-4-7-7-4 18-7Z"></path>',
            'settings' => '<circle cx="12" cy="12" r="3.2"></circle><path d="M12 2.8v2.4"></path><path d="M12 18.8v2.4"></path><path d="M21.2 12h-2.4"></path><path d="M5.2 12H2.8"></path><path d="m18.4 5.6-1.7 1.7"></path><path d="m7.3 16.7-1.7 1.7"></path><path d="m18.4 18.4-1.7-1.7"></path><path d="M7.3 7.3 5.6 5.6"></path>',
            'shield' => '<path d="m12 3 7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3Z"></path><path d="M12 3v18"></path>',
            'sliders' => '<path d="M5 5v14"></path><path d="M12 5v14"></path><path d="M19 5v14"></path><circle cx="5" cy="9" r="1.8"></circle><circle cx="12" cy="14" r="1.8"></circle><circle cx="19" cy="8" r="1.8"></circle>',
            'sparkles' => '<path d="m12 3 1.8 4.2L18 9l-4.2 1.8L12 15l-1.8-4.2L6 9l4.2-1.8L12 3Z"></path><path d="m18.5 3.5.8 1.8 1.8.8-1.8.8-.8 1.8-.8-1.8-1.8-.8 1.8-.8.8-1.8Z"></path><path d="m5.5 15.5.8 1.8 1.8.8-1.8.8-.8 1.8-.8-1.8-1.8-.8 1.8-.8.8-1.8Z"></path>',
            'stack' => '<rect x="6" y="6" width="12" height="4" rx="1.2"></rect><rect x="4.5" y="10.5" width="15" height="4" rx="1.2"></rect><rect x="6" y="15" width="12" height="4" rx="1.2"></rect>',
            'star' => '<path d="m12 3 2.6 5.3 5.9.9-4.2 4.1 1 5.7-5.3-2.8-5.3 2.8 1-5.7L3.5 9.2l5.9-.9L12 3Z" fill="currentColor" stroke="none"></path>',
            'tag' => '<path d="M20 10.5 13.5 4H6v7.5l6.5 6.5a2.1 2.1 0 0 0 3 0l4.5-4.5a2.1 2.1 0 0 0 0-3Z"></path><circle cx="8.5" cy="8.5" r="1.1"></circle>',
            'tags' => '<path d="M12 5H6v6l6 6 6-6-6-6Z"></path><path d="M15 8h4v4l-4 4"></path><circle cx="8.8" cy="8.8" r="1"></circle>',
            'telegram' => '<path d="M21 4 10 13"></path><path d="M21 4 14 20l-4-7-7-4 18-5Z"></path>',
            'user-shield' => '<circle cx="9" cy="9" r="3"></circle><path d="M4 19a5.5 5.5 0 0 1 10 0"></path><path d="m17.5 10 3 1.3v2.1c0 2.1-1.4 3.7-3 4.6-1.6-.9-3-2.5-3-4.6v-2.1l3-1.3Z"></path>',
            'youtube' => '<rect x="3.5" y="6.5" width="17" height="11" rx="4"></rect><path d="m10 9.7 5.5 2.3-5.5 2.3V9.7Z" fill="currentColor" stroke="none"></path>',
        ];

        return $icons[$name] ?? $icons['sparkles'];
    }
}
