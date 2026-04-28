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
        $code = strtoupper((string)SessionService::get('currency', 'RUB'));
        return array_key_exists($code, self::RATES) ? $code : 'RUB';
    }

    public static function symbol(?string $code = null): string
    {
        $normalized = self::normalizeCode($code);
        return self::RATES[$normalized]['symbol'];
    }

    public static function convertFromRub(float $amountRub, ?string $code = null): float
    {
        $normalized = self::normalizeCode($code);
        return (float)$amountRub * (float)self::RATES[$normalized]['rate'];
    }

    public static function codeForSchema(?string $code = null): string
    {
        return self::normalizeCode($code);
    }

    public static function format($amountRub) {
        $code = self::normalizeCode(null);
        $cfg = self::RATES[$code];
        
        if ($amountRub <= 0) return '<span class="text-success fw-bold">FREE</span>';

        $converted = self::convertFromRub((float)$amountRub, $code);
        $val = ($code === 'RUB') ? round($converted) : number_format($converted, 2);
        
        // Output with explicit currency code so UI always shows chosen currency.
        return '<span class="currency-anim" data-currency="'.$code.'" title="'.$code.'">' . $cfg['symbol'] . ' ' . $val . ' <small class="text-secondary">' . $code . '</small></span>';
    }

    private static function normalizeCode(?string $code): string
    {
        $candidate = strtoupper((string)($code ?? self::current()));
        return array_key_exists($candidate, self::RATES) ? $candidate : 'RUB';
    }
}
