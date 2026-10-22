<?php

declare(strict_types=1);

namespace App\Client;

use App\Exception\ExchangeRateProviderException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExchangeRateClient
{
    private const BASE_CURRENCY = 'USD';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiUrl,
        private readonly string $apiKey,
        private readonly int $timeout = 5,
    ) {
    }

    public function fetchRate(string $currency): string
    {
        $url = sprintf(
            '%s/%s/latest/%s',
            rtrim($this->apiUrl, '/'),
            $this->apiKey,
            self::BASE_CURRENCY,
        );

        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => $this->timeout,
                'headers' => ['Accept' => 'application/json'],
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 400) {
                throw new ExchangeRateProviderException(
                    sprintf('ExchangeRate API returned HTTP %d.', $statusCode),
                );
            }

            $data = $response->toArray();
        } catch (ExceptionInterface $e) {
            throw new ExchangeRateProviderException(
                'Failed to contact ExchangeRate API: ' . $e->getMessage(),
                0,
                $e,
            );
        }

        if (!isset($data['result']) || $data['result'] !== 'success') {
            throw new ExchangeRateProviderException('ExchangeRate API returned an unsuccessful result.');
        }

        if (!isset($data['conversion_rates'][$currency]) || !is_numeric($data['conversion_rates'][$currency])) {
            throw new ExchangeRateProviderException(
                sprintf('Exchange rate for currency "%s" is missing or unusable.', $currency),
            );
        }

        return (string) $data['conversion_rates'][$currency];
    }
}
