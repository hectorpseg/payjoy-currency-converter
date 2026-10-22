<?php

declare(strict_types=1);

namespace App\Tests\Client;

use App\Client\ExchangeRateClient;
use App\Exception\ExchangeRateProviderException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ExchangeRateClientTest extends TestCase
{
    public function testFetchRateReturnsRateForRequestedCurrency(): void
    {
        $client = $this->createClient([
            'result' => 'success',
            'conversion_rates' => ['EUR' => 0.85],
        ]);

        self::assertSame('0.85', $client->fetchRate('EUR'));
    }

    public function testFetchRateThrowsWhenProviderReportsUnsuccessfulResult(): void
    {
        $client = $this->createClient([
            'result' => 'error',
            'error-type' => 'invalid-key',
        ]);

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage('unsuccessful');

        $client->fetchRate('EUR');
    }

    public function testFetchRateThrowsWhenCurrencyIsMissing(): void
    {
        $client = $this->createClient([
            'result' => 'success',
            'conversion_rates' => ['GBP' => 0.75],
        ]);

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage('EUR');

        $client->fetchRate('EUR');
    }

    public function testFetchRateThrowsWhenRateIsNotNumeric(): void
    {
        $client = $this->createClient([
            'result' => 'success',
            'conversion_rates' => ['EUR' => 'nope'],
        ]);

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage('unusable');

        $client->fetchRate('EUR');
    }

    public function testFetchRateThrowsWhenResponseIsNotValidJson(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('not valid json'),
        ]);

        $client = new ExchangeRateClient($httpClient, 'https://api.example.com', 'key', 5);

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage('Failed to contact ExchangeRate API');

        $client->fetchRate('EUR');
    }

    public function testFetchRateThrowsWhenHttpStatusCodeIsError(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('{}', ['http_code' => 500]),
        ]);

        $client = new ExchangeRateClient($httpClient, 'https://api.example.com', 'key', 5);

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage('HTTP 500');

        $client->fetchRate('EUR');
    }

    private function createClient(array $responseData, int $httpCode = 200): ExchangeRateClient
    {
        $httpClient = new MockHttpClient([
            new MockResponse(json_encode($responseData), ['http_code' => $httpCode]),
        ]);

        return new ExchangeRateClient($httpClient, 'https://api.example.com', 'key', 5);
    }
}
