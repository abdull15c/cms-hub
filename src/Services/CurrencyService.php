<?php
namespace Src\Services;

class CurrencyService {
    // Hardcoded Rates (Base: RUB)
    const RATES = [
        'RUB' => ['rate' => 1,      'symbol' => '₽', 'icon' => 'fa-ruble-sign'],
        'USD' => ['rate' => 0.011,  'symbol' => '$', 'icon' => 'fa-dollar-sign'], // ~90 RUB
        'EUR' => ['rate' => 0.010,  'symbol' => '€', 'icon' => 'fa-euro-sign']    // ~100 RUB
    ];

    public static function current() {
        return SessionService::get('currency', 'RUB');
    }

    public static function format($amountRub) {
        $code = self::current();
        $cfg = self::RATES[$code];
        
        if ($amountRub <= 0) return '<span class="text-success fw-bold">FREE</span>';

        $converted = $amountRub * $cfg['rate'];
        $val = ($code === 'RUB') ? round($converted) : number_format($converted, 2);
        
        // Output with data attribute for JS/CSS animations
        return '<span class="currency-anim" data-currency="'.$code.'">' . $cfg['symbol'] . ' ' . $val . '</span>';
    }
}
