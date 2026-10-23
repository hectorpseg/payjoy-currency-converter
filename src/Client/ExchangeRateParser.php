<?php

declare(strict_types=1);

namespace App\Client;

final class ExchangeRateParser
{
    public static function extract(string $json, string $currency): ?string
    {
        if (
            !preg_match('/"conversion_rates"\s*:\s*\{([^{}]*)\}/s', $json, $ratesMatch)
            || !preg_match(
                '/' . preg_quote(json_encode($currency), '/') . '\s*:\s*("(?:\\\\.|[^"\\\\])*"|'
                    . '-?(?:0|[1-9]\d*)(?:\.\d+)?(?:[eE][+-]?\d+)?)/',
                $ratesMatch[1],
                $rateMatch,
            )
        ) {
            return null;
        }

        $rate = str_starts_with($rateMatch[1], '"')
            ? json_decode($rateMatch[1], true, 512, JSON_THROW_ON_ERROR)
            : $rateMatch[1];

        if (!is_string($rate)) {
            return null;
        }

        return self::expandExponent((string) $rate);
    }

    private static function expandExponent(string $rate): ?string
    {
        if (!preg_match('/^(-?)(\d+)(?:\.(\d+))?(?:[eE]([+-]?\d+))?$/', $rate, $matches)) {
            return null;
        }

        $fraction = $matches[3] ?? '';
        $exponent = (int) ($matches[4] ?? '0');
        $digits = $matches[2] . $fraction;
        $decimalPosition = strlen($matches[2]) + $exponent;

        if (abs($decimalPosition) > 1000) {
            return null;
        }

        if ($decimalPosition <= 0) {
            $expanded = '0.' . str_repeat('0', -$decimalPosition) . $digits;
        } elseif ($decimalPosition >= strlen($digits)) {
            $expanded = $digits . str_repeat('0', $decimalPosition - strlen($digits));
        } else {
            $expanded = substr($digits, 0, $decimalPosition) . '.' . substr($digits, $decimalPosition);
        }

        return $matches[1] . $expanded;
    }
}
