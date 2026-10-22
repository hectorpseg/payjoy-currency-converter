<?php

declare(strict_types=1);

namespace App\Service;

use App\Client\ExchangeRateClient;

class CurrencyConversionService
{
    private const int SCALE = 10;

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
                'converted' => $amount,
            ];
        }

        $rate = $this->exchangeRateClient->fetchRate($currency);

        return [
            'amount_usd' => $amount,
            'currency' => $currency,
            'rate' => $rate,
            'converted' => bcmul($amount, $rate, self::SCALE),
        ];
    }
}
