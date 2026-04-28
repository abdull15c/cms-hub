<?php
namespace Src\Services;

class MoneyService
{
    public static function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public static function fromCents(int $cents): float
    {
        return $cents / 100;
    }

    public static function decimalStringFromCents(int $cents): string
    {
        return number_format(self::fromCents($cents), 2, '.', '');
    }

    public static function applyPercentDiscountCents(int $amountCents, float $percent): int
    {
        $discountCents = (int) round($amountCents * ($percent / 100));
        return max(0, $amountCents - $discountCents);
    }

    public static function nearlyEqual(float $left, float $right, int $precisionCents = 1): bool
    {
        return abs(self::toCents($left) - self::toCents($right)) <= $precisionCents;
    }
}
