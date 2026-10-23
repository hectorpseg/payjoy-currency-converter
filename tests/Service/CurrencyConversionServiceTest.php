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
            'converted' => '100.5',
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
        self::assertSame('85', $result['converted']);
    }

    public function testConvertUsdToOtherCurrencyPreservesPrecisionAndRemovesInsignificantZeros(): void
    {
        $client = $this->createMock(ExchangeRateClient::class);
        $client->method('fetchRate')
            ->with('EUR')
            ->willReturnOnConsecutiveCalls('0.3400000000006', '0.8916000000', '0.1337400000');

        $service = new CurrencyConversionService($client);

        self::assertSame('34', $service->convert('100', 'EUR')['converted']);
        self::assertSame('89.16', $service->convert('100', 'EUR')['converted']);
        self::assertSame('13.374', $service->convert('100', 'EUR')['converted']);
    }

    public function testConvertUsdToOtherCurrencyRoundsHalfAwayFromZeroToThreeDecimalPlaces(): void
    {
        $client = $this->createMock(ExchangeRateClient::class);
        $client->method('fetchRate')
            ->willReturnOnConsecutiveCalls('1.2345', '1.2344', '0.0005');

        $service = new CurrencyConversionService($client);

        self::assertSame('1.235', $service->convert('1', 'EUR')['converted']);
        self::assertSame('1.234', $service->convert('1', 'EUR')['converted']);
        self::assertSame('0.001', $service->convert('1', 'EUR')['converted']);
    }

    public function testConvertUsdToOtherCurrencySupportsVerySmallAndLargeValues(): void
    {
        $client = $this->createMock(ExchangeRateClient::class);
        $client->method('fetchRate')
            ->willReturnOnConsecutiveCalls('1', '2', '0.000001');

        $service = new CurrencyConversionService($client);

        self::assertSame('0', $service->convert('0.000001', 'EUR')['converted']);
        self::assertSame(
            '1999999999999999999999998',
            $service->convert('999999999999999999999999', 'EUR')['converted'],
        );
        self::assertSame('0', $service->convert('0.000001', 'EUR')['converted']);
    }

    public function testConvertUsdToUsdNormalizesConvertedValueWithoutChangingInputAmount(): void
    {
        $client = $this->createMock(ExchangeRateClient::class);
        $service = new CurrencyConversionService($client);

        $result = $service->convert('34.000000', 'USD');

        self::assertSame('34.000000', $result['amount_usd']);
        self::assertSame('34', $result['converted']);
    }

    public function testConvertUsdToUsdRoundsHalfAwayFromZeroToThreeDecimalPlaces(): void
    {
        $client = $this->createMock(ExchangeRateClient::class);
        $service = new CurrencyConversionService($client);

        self::assertSame('3.963', $service->convert('3.962627', 'USD')['converted']);
    }
}
