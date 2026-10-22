<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Client\ExchangeRateClient;
use App\Service\CurrencyConversionService;
use PHPUnit\Framework\TestCase;

class CurrencyConversionServiceTest extends TestCase
{
    public function testConvertUsdToUsdReturnsOriginalAmountAndRateOne(): void
    {
        $client = $this->createMock(ExchangeRateClient::class);
        $service = new CurrencyConversionService($client);

        $result = $service->convert('100.50', 'USD');

        self::assertSame([
            'amount_usd' => '100.50',
            'currency' => 'USD',
            'rate' => '1',
            'converted' => '100.50',
        ], $result);
    }

    public function testConvertUsdToUsdDoesNotCallExchangeRateClient(): void
    {
        $client = $this->createMock(ExchangeRateClient::class);
        $client->expects($this->never())->method('fetchRate');

        $service = new CurrencyConversionService($client);
        $service->convert('100.50', 'USD');
    }

    public function testConvertUsdToOtherCurrencyUsesRateFromClient(): void
    {
        $client = $this->createMock(ExchangeRateClient::class);
        $client->method('fetchRate')
            ->with('EUR')
            ->willReturn('0.85');

        $service = new CurrencyConversionService($client);
        $result = $service->convert('100.00', 'EUR');

        self::assertSame('EUR', $result['currency']);
        self::assertSame('0.85', $result['rate']);
        self::assertSame('85.0000000000', $result['converted']);
    }
}
