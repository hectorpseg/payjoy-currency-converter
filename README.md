# Currency Converter

A small PHP/Symfony REST API that converts USD amounts into a requested target
currency using the ExchangeRate API. The project also includes a Landbot
integration that provides a conversational interface for performing
conversions.

## Features

- USD-to-target-currency conversion
- ExchangeRate API integration with provider error handling
- Request validation and clear API errors
- Decimal-safe calculations using BCMath
- Landbot widget embedded on the homepage
- Automated tests using mocked external API responses
- Docker and Docker Compose support

## Tech Stack

- PHP 8.5
- Symfony 7.4
- PHPUnit 11
- Symfony HttpClient
- BCMath
- Docker / Docker Compose
- ExchangeRate API
- Landbot

## Architecture

The project keeps the HTTP, conversion, and provider responsibilities separate:

- `ConversionController` validates HTTP query parameters and returns JSON.
- `CurrencyConversionService` performs the conversion and applies output
  rounding.
- `ExchangeRateClient` calls the external provider and validates its response.
- `ExchangeRateParser` extracts the provider's decimal rate without first
  converting it to a PHP float.
- `ExchangeRateProviderException` represents provider failures for the
  controller to map to the API error contract.
- `LandbotController` serves the page that embeds the Landbot widget.

The structure is intentionally small for this project; the controller
delegates business logic and provider communication to dedicated classes.

```text
src/
  Client/
  Controller/
  Exception/
  Service/
tests/
  Client/
  Controller/
  Service/
templates/
  landbot/
config/
```

## Technical Decisions

- **PHP 8.5 and Symfony 7.4** provide a familiar structure for a small REST API
  and a maintainable PHP implementation.
- **Symfony HttpClient** keeps provider communication in a dedicated client and
  supports deterministic HTTP mocking in tests.
- **BCMath and decimal strings** avoid floating-point arithmetic for monetary
  calculations.
- **Environment-based configuration** keeps the ExchangeRate API key out of
  application source.
- **Automated tests** cover business logic, HTTP behavior, and provider
  responses without relying on the live exchange-rate service.
- **No database or additional infrastructure** is needed for the current
  stateless conversion flow; adding it would increase complexity without
  meeting a present requirement.

## Local Setup

### Requirements

- PHP 8.5 or later
- Composer
- PHP extensions: BCMath, Ctype, and Iconv

Docker users can use the included PHP 8.5 image instead of installing PHP and
these extensions on the host.

### Run with PHP and Composer

```bash
cp .env.example .env
```

Start from the repository root. Edit `.env`: and replace the ExchangeRate API key placeholder with a real key. Then install dependencies and start the local PHP server:

```bash
composer install
php -S 127.0.0.1:8000 -t public
```

The API is available at `http://127.0.0.1:8000/convert`. For example:

```bash
curl 'http://127.0.0.1:8000/convert?amount=200&currency=BRL'
```

### Run with Docker Compose

From the repository root, configure `.env` as described above, then install
dependencies and start the service:

```bash
cp .env.example .env
# Edit .env and set a real EXCHANGE_RATE_API_KEY.
docker compose run --rm php composer install
docker compose up -d --build
```

The service listens on port `8000`:

```bash
curl 'http://localhost:8000/convert?amount=200&currency=BRL'
```

Stop it with `docker compose down`. The Compose service bind-mounts the project
at `/app`; the Dockerfile installs the PHP extensions and Composer.

## Environment Variables

`.env.example` is the configuration template. Create a local `.env` and provide
your own values; never commit `.env` or place a real API key in source control.

| Variable | Required | Description |
| --- | --- | --- |
| `EXCHANGE_RATE_API_KEY` | Yes | ExchangeRate API key. Replace the template placeholder with a real key. |
| `EXCHANGE_RATE_API_URL` | Yes | Provider API base URL; the template uses `https://v6.exchangerate-api.com/v6`. |
| `EXCHANGE_RATE_API_TIMEOUT` | Yes | Provider request timeout in seconds; the template sets it to `5`. |
| `APP_SECRET` | No | Standard Symfony framework secret. The local template includes a placeholder value. |
| `APP_ENV` | No | Standard Symfony runtime environment; the template sets `dev`. |

`APP_SECRET` and `APP_ENV` are standard Symfony framework/runtime settings, not
currency-conversion business settings. The provider settings are expected by
the application configuration even though USD-to-USD conversions do not make a
provider request.

## API

### `GET /convert`

Query parameters:

| Parameter | Description |
| --- | --- |
| `amount` | Non-negative USD amount with up to 6 decimal places. |
| `currency` | Three-letter alphabetic target currency code. Input is normalized to uppercase. |

The API obtains the current rate from ExchangeRate API for the target currency.
A successful response has this shape:

```json
{
  "amount_usd": "200",
  "currency": "BRL",
  "rate": "5.25",
  "converted": "1050"
}
```

The values above illustrate the response format; provider rates change. All
amount and rate fields are returned as decimal strings.

Invalid or missing parameters return HTTP `400`:

```json
{
  "error": {
    "code": "INVALID_REQUEST",
    "message": "amount is required."
  }
}
```

Provider failures, including a rate that is unavailable from the provider,
return HTTP `502` without exposing internal provider details:

```json
{
  "error": {
    "code": "PROVIDER_ERROR",
    "message": "Unable to retrieve exchange rates."
  }
}
```

A three-letter code that the provider does not support is syntactically valid,
but its missing rate is reported as a provider error (`502`).

## Decimal Handling and Rounding

Conversion uses decimal strings and BCMath rather than PHP floating-point
arithmetic. The provider response is structurally validated, while its raw JSON
is retained so the exact decimal rate can be extracted before calculation.
Intermediate calculations are not rounded; the result is rounded at the API
output boundary to a maximum of three decimal places using half-away-from-zero
rounding, with unnecessary trailing zeros removed. This is a presentation
policy for this project, not a currency-specific minor-unit rule.

## Landbot Integration

The homepage (`GET /`) embeds the public Landbot widget. The currently served
welcome prompt greets the user and asks for the USD installment amount. The
Landbot conversation configuration is hosted externally and is not versioned in
this repository; its subsequent prompts and API actions cannot be verified from
the checked-in application. The current Landbot API/webhook action points to the
project's deployed `/convert` endpoint.

### Local API

After following the local setup instructions, the Symfony API is available at
`http://localhost:8000/convert`. Test it directly with:

```bash
curl 'http://localhost:8000/convert?amount=200&currency=BRL'
```

Direct requests are the simplest way to develop and test the REST API locally.

### Existing Landbot Flow

The Landbot conversation/workflow is hosted and configured in Landbot and is
not stored or versioned in this repository. This repository contains the
Symfony application and the page that embeds the widget. The current hosted
Landbot flow calls:

`https://currency-converter-demo.onrender.com/convert`

Running this Symfony project locally does not change the endpoint configured
inside Landbot. Anyone with access to the Landbot workspace can update the
conversion API/webhook action to use another `/convert` endpoint and ensure it
sends the expected `amount` and `currency` parameters, then save/publish and
test the bot. A hosted Landbot service cannot directly call a developer's
localhost; local end-to-end testing requires an externally reachable
development URL configured as the action target.

### Using Your Own Landbot Bot

To use your own bot, create and configure its conversation to collect the USD
amount and target currency, then configure its API/webhook action to call the
desired `/convert` endpoint. Replace the existing widget embed configuration in
[templates/landbot/index.html.twig](templates/landbot/index.html.twig) with your
bot's embed configuration and verify it against the local or deployed API. The
Landbot workflow is not committed here; this replaces the widget embed, not a
versioned flow definition.

## Testing

Run the complete PHPUnit suite after installing dependencies:

```bash
vendor/bin/phpunit
```

With Docker Compose:

```bash
docker compose run --rm php vendor/bin/phpunit
```

The suite currently passes **30 tests with 94 assertions**. It covers input and
HTTP behavior, conversion and rounding, provider response handling, and the
Landbot page. Provider behavior is tested with Symfony's `MockHttpClient` and
does not depend on live ExchangeRate API responses.

## What I Would Improve With More Time

For a production customer-facing service, I would prioritize:

- **Rate caching with an explicit freshness policy** to reduce provider traffic,
  latency, and dependence on provider availability.
- **Rate limiting** to protect the endpoint and stay within provider limits.
- **Observability** through structured logs, conversion/error metrics, provider
  latency tracking, and alerts.
- **Provider resilience** by reviewing the timeout policy and adding carefully
  bounded retries for transient errors; fallback or circuit-breaker behavior
  would depend on business requirements.
- **Currency-specific precision rules** instead of applying one three-decimal
  presentation policy to every target currency.
- **Operational hardening** such as deployment-managed secret storage and
  authentication if the endpoint is not intended to be public.
- **OpenAPI documentation** if multiple consumers need a stable, discoverable
  contract.

These are potential next steps, not features currently implemented.
The current scope does not require persistent storage or additional
infrastructure such as queues, Redis, or microservices.

## Production Success Metrics

If this became a production customer-facing service, I would instrument:

- API conversion success and failure rates.
- API response time.
- Chatbot completion and drop-off rates using the chatbot platform's analytics.
- ExchangeRate API request volume and usage against provider limits.

I would establish baselines after launch and set targets based on real usage and product requirements rather than assuming arbitrary thresholds upfront. The main goal would be to ensure the service reliably completes conversions and provides a useful customer experience.

## Known Scope and Limitations

- Rates depend on ExchangeRate API availability and data freshness; each
  non-USD conversion currently requests the provider.
- There is no persistent application storage, caching, authentication, or
  rate limiting.
- Output rounding is a simple three-decimal presentation rule, not a
  currency-specific monetary policy.
- The Landbot flow is externally hosted; its full configuration is not stored in
  this repository.

## AI-Assisted Development

AI tools including Claude and OpenCode were used during development for
implementation assistance, debugging, test development, repository inspection,
and documentation. The project code was reviewed and validated by the author,
including application behavior, test results, and technical decisions.

## Live Demo

The configured deployment URL is
[https://currency-converter-demo.onrender.com/](https://currency-converter-demo.onrender.com/).
The root page is intended to embed the Landbot widget, and the REST API
endpoint is `https://currency-converter-demo.onrender.com/convert`. Example API
request:

```bash
curl 'https://currency-converter-demo.onrender.com/convert?amount=200&currency=BRL'
```

Provider rates and deployment availability may change.
