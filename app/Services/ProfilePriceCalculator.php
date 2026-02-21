<?php

namespace App\Services;

class ProfilePriceCalculator
{
    private const NIGHT_MULTIPLIER = 8;
    private const DAY_MULTIPLIER = 24;

    public static function apply(array $price): array
    {
        $price['price_1h'] = self::normalize($price['price_1h'] ?? null);
        $price['price_1h_out'] = self::normalize($price['price_1h_out'] ?? null);

        self::fillIncall($price);
        self::fillOutcall($price);

        return $price;
    }

    private static function fillIncall(array &$price): void
    {
        $base = $price['price_1h'] ?? null;

        $price['price_2h'] = self::multiply($base, 2);
        $price['price_4h'] = self::multiply($base, 4);
        $price['price_night'] = self::multiply($base, self::NIGHT_MULTIPLIER);
        $price['price_day'] = self::multiply($base, self::DAY_MULTIPLIER);
    }

    private static function fillOutcall(array &$price): void
    {
        $base = $price['price_1h_out'] ?? null;

        $price['price_2h_out'] = self::multiply($base, 2);
        $price['price_4h_out'] = self::multiply($base, 4);
        $price['price_night_out'] = self::multiply($base, self::NIGHT_MULTIPLIER);
        $price['price_day_out'] = self::multiply($base, self::DAY_MULTIPLIER);
    }

    private static function multiply(?int $base, int $multiplier): ?int
    {
        if ($base === null) {
            return null;
        }

        return (int) round($base * $multiplier);
    }

    private static function normalize(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }

            $value = preg_replace('/[^\d,.\-]/u', '', $value) ?? '';
            $value = str_replace(',', '.', $value);
        }

        if (!is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        if ($number <= 0) {
            return null;
        }

        return (int) round($number);
    }
}
