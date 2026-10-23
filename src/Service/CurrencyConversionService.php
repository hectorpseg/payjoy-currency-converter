<?php

declare(strict_types=1);

namespace App\Service;

use App\Client\ExchangeRateClient;

class CurrencyConversionService
{
    private const int OUTPUT_SCALE = 3;

    public function __construct(
        private readonly ExchangeRateClient $exchangeRateClient,
    ) {
    }

    /**
     * @return array{amount_usd: string, currency: string, rate: string, converted: string}
     */
    public function convert(string $amount, string $currency): array
    {
        $currency = strtoupper($currency);

        if ($currency === 'USD') {
            return [
                'amount_usd' => $amount,
                'currency' => $currency,
                'rate' => '1',
                'converted' => $this->normalizeDecimal(
                    bcround($amount, self::OUTPUT_SCALE, \RoundingMode::HalfAwayFromZero),
                ),
            ];
        }

        $rate = $this->exchangeRateClient->fetchRate($currency);
        $scale = $this->decimalPlaces($amount) + $this->decimalPlaces($rate);
        $converted = bcmul($amount, $rate, $scale);

        return [
            'amount_usd' => $amount,
            'currency' => $currency,
            'rate' => $rate,
            'converted' => $this->normalizeDecimal(
                bcround($converted, self::OUTPUT_SCALE, \RoundingMode::HalfAwayFromZero),
            ),
        ];
    }

    private function decimalPlaces(string $value): int
    {
        $decimalPoint = strpos($value, '.');

        return $decimalPoint === false ? 0 : strlen($value) - $decimalPoint - 1;
    }

    private function normalizeDecimal(string $value): string
    {
        $negative = str_starts_with($value, '-');
        $unsignedValue = $negative ? substr($value, 1) : $value;
        [$integer, $fraction] = array_pad(explode('.', $unsignedValue, 2), 2, '');
        $integer = ltrim($integer, '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = rtrim($fraction, '0');
        $normalized = $integer . ($fraction === '' ? '' : '.' . $fraction);

        return $negative && $normalized !== '0' ? '-' . $normalized : $normalized;
    }
}
