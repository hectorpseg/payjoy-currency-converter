<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Exception\ExchangeRateProviderException;
use App\Service\CurrencyConversionService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ConversionControllerTest extends WebTestCase
{
    public function testValidUsdToEurConversion(): void
    {
        $client = static::createClient();
        $this->mockConversionService($client, '100.00', 'EUR', [
            'amount_usd' => '100.00',
            'currency' => 'EUR',
            'converted' => '85',
            'rate' => '0.85',
        ]);

        $client->request('GET', '/convert?amount=100.00&currency=EUR');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonStringEqualsJsonString(json_encode([
            'amount_usd' => '100.00',
            'currency' => 'EUR',
            'converted' => '85',
            'rate' => '0.85',
        ]), (string) $client->getResponse()->getContent());
    }

    public function testValidUsdToUsdConversion(): void
    {
        $client = static::createClient();
        $this->mockConversionService($client, '50.00', 'USD', [
            'amount_usd' => '50.00',
            'currency' => 'USD',
            'converted' => '50',
            'rate' => '1',
        ]);

        $client->request('GET', '/convert?amount=50.00&currency=USD');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonStringEqualsJsonString(json_encode([
            'amount_usd' => '50.00',
            'currency' => 'USD',
            'converted' => '50',
            'rate' => '1',
        ]), (string) $client->getResponse()->getContent());
    }

    public function testMissingAmountReturnsBadRequest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/convert?currency=EUR');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertErrorResponse($client, 'amount is required.');
    }

    public function testMissingCurrencyReturnsBadRequest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/convert?amount=100.00');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertErrorResponse($client, 'currency is required.');
    }

    public function testNegativeAmountReturnsBadRequest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/convert?amount=-10&currency=EUR');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertErrorResponse($client, 'amount must be a non-negative decimal number');
    }

    public function testNonNumericAmountReturnsBadRequest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/convert?amount=abc&currency=EUR');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertErrorResponse($client, 'amount must be a non-negative decimal number');
    }

    public function testAmountWithMoreThanSixDecimalPlacesReturnsBadRequest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/convert?amount=100.1234567&currency=EUR');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertErrorResponse($client, 'amount must be a non-negative decimal number');
    }

    public function testScientificNotationAmountReturnsBadRequest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/convert?amount=1e309&currency=EUR');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertErrorResponse($client, 'amount must be a non-negative decimal number');
    }

    public function testAmountWithTrailingNewlineReturnsBadRequest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/convert?amount=100%0A&currency=EUR');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertErrorResponse($client, 'amount must be a non-negative decimal number');
    }

    public function testLargeAmountWithinDecimalRulesIsAcceptedWithoutPrecisionLoss(): void
    {
        $client = static::createClient();
        $amount = '999999999999999999999999.000001';
        $this->mockConversionService($client, $amount, 'EUR', [
            'amount_usd' => $amount,
            'currency' => 'EUR',
            'converted' => '1999999999999999999999998',
            'rate' => '2',
        ]);

        $client->request('GET', '/convert?amount=' . $amount . '&currency=EUR');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonStringEqualsJsonString(json_encode([
            'amount_usd' => $amount,
            'currency' => 'EUR',
            'converted' => '1999999999999999999999998',
            'rate' => '2',
        ]), (string) $client->getResponse()->getContent());
    }

    public function testInvalidCurrencyFormatReturnsBadRequest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/convert?amount=100.00&currency=EURO');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertErrorResponse($client, 'currency must be a 3-letter alphabetic currency code.');
    }

    public function testLowercaseCurrencyIsNormalized(): void
    {
        $client = static::createClient();
        $this->mockConversionService($client, '100.00', 'EUR', [
            'amount_usd' => '100.00',
            'currency' => 'EUR',
            'converted' => '85',
            'rate' => '0.85',
        ]);

        $client->request('GET', '/convert?amount=100.00&currency=eur');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonStringEqualsJsonString(json_encode([
            'amount_usd' => '100.00',
            'currency' => 'EUR',
            'converted' => '85',
            'rate' => '0.85',
        ]), (string) $client->getResponse()->getContent());
    }

    public function testProviderFailureReturnsBadGatewayWithoutInternalDetails(): void
    {
        $client = static::createClient();
        $mock = $this->createMock(CurrencyConversionService::class);
        $mock->method('convert')
            ->with('100.00', 'EUR')
            ->willThrowException(new ExchangeRateProviderException('provider exploded'));

        static::getContainer()->set(CurrencyConversionService::class, $mock);

        $client->request('GET', '/convert?amount=100.00&currency=EUR');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_GATEWAY);
        $content = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('PROVIDER_ERROR', $content['error']['code']);
        self::assertSame('Unable to retrieve exchange rates.', $content['error']['message']);
        self::assertArrayNotHasKey('trace', $content);
        self::assertStringNotContainsString('provider exploded', (string) $client->getResponse()->getContent());
    }

    private function mockConversionService($client, string $amount, string $currency, array $return): void
    {
        $mock = $this->createMock(CurrencyConversionService::class);
        $mock->method('convert')
            ->with($amount, $currency)
            ->willReturn($return);

        static::getContainer()->set(CurrencyConversionService::class, $mock);
    }

    private function assertErrorResponse($client, string $messageFragment): void
    {
        $content = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('INVALID_REQUEST', $content['error']['code']);
        self::assertStringContainsString($messageFragment, $content['error']['message']);
    }
}
