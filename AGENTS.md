# Project Guidelines

## Project

PHP REST API for the PayJoy technical assessment.

The API converts a USD amount into a requested target currency using ExchangeRate API.

## Stack

- PHP 8.5
- Symfony 7.4 LTS
- PHPUnit
- Docker
- GitHub Actions

## Architecture

- Controllers handle HTTP concerns only.
- Business logic belongs in services.
- External API communication belongs in dedicated clients.
- Keep the ExchangeRate integration isolated from business logic.
- Prefer dependency injection.
- Use typed PHP code and strict types.
- Keep the solution intentionally simple.

## Testing

- Business logic must have unit tests.
- External integrations must be tested without depending on the real external API.
- HTTP behavior should have functional/API tests.
- Tests must be deterministic.

## Security

- Never hardcode API keys or secrets.
- Never commit `.env`.
- Use `.env.example` for required configuration.
- Validate input on the API even when the chatbot performs validation.

## Scope

The API converts USD to a target currency.

Do not introduce:

- a database
- Redis
- queues
- microservices
- an LLM
- a frontend expansion

unless a concrete requirement emerges.

## Engineering Principles

- Prefer simple solutions over unnecessary abstraction.
- Every architectural decision should have a clear reason.
- Do not add infrastructure without a concrete requirement.
- Do not modify the API contract without discussing the change.
- New behavior should include tests.
