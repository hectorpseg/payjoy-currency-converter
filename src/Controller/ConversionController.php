<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\ExchangeRateProviderException;
use App\Service\CurrencyConversionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConversionController extends AbstractController
{
    public function __construct(
        private readonly CurrencyConversionService $conversionService,
    ) {
    }

    #[Route('/convert', name: 'convert', methods: ['GET'])]
    public function convert(Request $request): JsonResponse
    {
        $amount = $request->query->get('amount');
        $currency = $request->query->get('currency');

        $error = $this->validateInput($amount, $currency);
        if ($error !== null) {
            return $this->json([
                'error' => [
                    'code' => 'INVALID_REQUEST',
                    'message' => $error,
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var string $amount */
        /** @var string $currency */
        $currency = strtoupper($currency);

        try {
            $result = $this->conversionService->convert($amount, $currency);
        } catch (ExchangeRateProviderException $e) {
            return $this->json([
                'error' => [
                    'code' => 'PROVIDER_ERROR',
                    'message' => 'Unable to retrieve exchange rates.',
                ],
            ], Response::HTTP_BAD_GATEWAY);
        }

        return $this->json($result);
    }

    private function validateInput(mixed $amount, mixed $currency): ?string
    {
        if (!is_string($amount) || $amount === '') {
            return 'amount is required.';
        }

        if (!preg_match('/^\d+(\.\d{1,6})?$/', $amount)) {
            return 'amount must be a non-negative decimal number with up to 6 decimal places.';
        }

        if (!is_string($currency) || $currency === '') {
            return 'currency is required.';
        }

        if (!preg_match('/^[A-Za-z]{3}$/', $currency)) {
            return 'currency must be a 3-letter alphabetic currency code.';
        }

        return null;
    }
}
