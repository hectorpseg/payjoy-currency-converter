# PayJoy Currency Converter API

PHP REST API that converts USD amounts into a requested target currency.

The API's `converted` output is calculated with BCMath and rounded to a maximum
of three decimal places using half-away-from-zero rounding. Trailing zeros are
removed, and values always use plain decimal notation.

## Requirements

- Docker
- Docker Compose

## Setup

1. Copy the environment template:

   ```bash
   cp .env.example .env
   ```

2. Build and start the PHP container:

   ```bash
   docker compose up -d --build
   ```

3. Install PHP dependencies:

   ```bash
   docker compose run --rm php composer install
   ```

## Verify

- Check the application responds:

  ```bash
  curl http://localhost:8000/
  ```

- Run the test suite:

  ```bash
  docker compose run --rm php vendor/bin/phpunit
  ```

## Development

The application runs on PHP 8.5 inside Docker. Use `docker compose run --rm php <command>` to execute PHP/Composer commands in the container.
