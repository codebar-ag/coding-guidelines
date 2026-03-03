---
name: saloon
description: Saloon-based service layer pattern for all external API integrations. Every new external API integration must use Saloon — no raw HTTP calls.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# Saloon

## When to Use

- For all external API integrations from application code.
- When an integration needs request classes, typed DTO mapping, and testable connector wiring.
- When building or refactoring service-layer API clients under `app/Services/{ServiceName}/`.

## When NOT to Use

- For internal Laravel-to-Laravel calls inside the same app boundary.
- For one-off, simple binary file downloads (for example downloading a public PDF by URL), where inline `Http::get()` is acceptable.
- For LLM integrations that already use Prism conventions.

## Preconditions

- `saloonphp/saloon` is installed and configured.
- API credentials and endpoints are defined in `config/services.php`.
- A service class exists (or is planned) to wrap connector usage and DTO mapping.

## Rules

- **All** new external API integrations must use the Saloon pattern
- No raw HTTP calls (`Http::get(...)`, `file_get_contents`, `curl`)
- Organize each external integration under `app/Services/{ServiceName}/`
- The connector extends `Saloon\Http\Connector` and handles authentication and base URL
- One class per API endpoint, extending `Saloon\Http\Request`
- Use constructor promotion for request parameters
- GET requests define `defaultQuery()` for query parameters
- POST/PUT requests implement `HasBody` + use `HasJsonBody` trait with `defaultBody()`
- The service class wraps the connector, sends requests, and returns typed DTOs
- Register the connector as a singleton in a service provider when needed
- Accept an optional connector in the service constructor for testability
- Exceptions: **Prism** is acceptable for LLM integrations; inline `Http::get()` is acceptable for simple binary file downloads (not API integrations)
- Follow general service conventions from `Services/SKILL.md` for naming and class boundaries

## Examples

**Directory Structure:**
```
app/Services/Stripe/
  StripeConnector.php
  StripeService.php
  Requests/
    Charges/
      CreateChargeRequest.php
      ListChargesRequest.php
  DataObjects/
    ChargeData.php
```

```php
// Connector
namespace App\Services\Stripe;

use Saloon\Http\Connector;

class StripeConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return 'https://api.stripe.com/v1';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . config('services.stripe.secret'),
            'Content-Type'  => 'application/json',
        ];
    }
}
```

```php
// GET Request
use Saloon\Enums\Method;
use Saloon\Http\Request;

class ListChargesRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $limit = 10,
        protected ?string $customer = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/charges';
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'limit'    => $this->limit,
            'customer' => $this->customer,
        ]);
    }
}
```

```php
// POST Request
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class CreateChargeRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $amount,
        protected string $currency,
        protected string $customerId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/charges';
    }

    protected function defaultBody(): array
    {
        return [
            'amount'   => $this->amount,
            'currency' => $this->currency,
            'customer' => $this->customerId,
        ];
    }
}
```

```php
// Service class
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StripeService
{
    public function __construct(?StripeConnector $connector = null)
    {
        $this->connector = $connector ?? new StripeConnector();
    }

    /** @return Collection<int, ChargeData> */
    public function listCharges(string $customerId): Collection
    {
        try {
            $response = $this->connector->send(
                new ListChargesRequest(customer: $customerId)
            );
        } catch (RequestException|FatalRequestException $exception) {
            Log::warning('Stripe request failed.', [
                'customer_id' => $customerId,
                'message' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        return collect($response->json('data'))
            ->map(fn (array $item) => ChargeData::fromArray($item));
    }
}
```

## Testing Guidance

- Inject a fake or mocked connector into the service constructor.
- Assert request class behavior (`resolveEndpoint()`, `defaultQuery()`, `defaultBody()`) independently.
- Service tests should verify DTO mapping and error propagation, not raw HTTP internals.

## Anti-Patterns

- Using `Http::get(...)` or `file_get_contents()` for API integrations
- Using `curl` directly
- Creating one massive connector class with all API logic — separate requests into individual classes
- Not returning typed DTOs from the service class (returning raw arrays)
- Not caching expensive or frequently accessed API responses
- Hard-coding API credentials in the connector — always use `config()`

## References

- [Saloon Documentation](https://docs.saloon.dev/)
- Related: `DTO/SKILL.md` — service classes return typed DTOs
- Related: `Services/SKILL.md` — general service class conventions
