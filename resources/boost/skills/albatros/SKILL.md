---
name: albatros
description: Albatros accounting API integration via Saloon. Use when working with app/Services/Albatros/, AlbatrosConnector, or Albatros DTOs.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# Albatros API Integration

## When to Use

- You are integrating with the Albatros accounting API specifically.
- You are editing `app/Services/Albatros/`, its connector, requests, or DTO mapping.
- You need typed DTO responses, pagination support, and cached reference data.

## When Not to Use

- The integration target is not Albatros (use the generic integration/service skills instead).
- The operation is local business logic with no external API call.
- You need one-off scripts without reusable connector/request architecture.

## Rules

- `AlbatrosConnector` extends `Saloon\Http\Connector`
- Token-based authentication with Mandant header
- Base URL from `config('albatros.base_url')`
- Keep credentials and Mandant values in `.env`, mapped through `config/albatros.php`; never hardcode secrets.
- `AlbatrosService` wraps all API calls and returns typed DTOs or `Collection` of DTOs
- Use `Cache` for reference data (addresses, accounts, VAT codes, etc.)
- Paginated endpoints use `fetchAllPages()` helper with `lastIndex` parameter
- Organize requests by resource under `Requests/{Resource}/`: `Requests/Adresse/`, `Requests/PkKreditor/`, `Requests/Mandant/`, etc.
- List requests accept `$lastIndex` and `$pageSize` for pagination
- Create requests implement `HasBody` + `HasJsonBody`
- Readonly DTOs in `DataObjects/` with `fromArray()` factory
- **German domain terms are acceptable** in DTO property names and request classes (they match the Albatros API)
- Handle API field name casing variations in `fromArray()`
- Cache reference data to minimize API calls
- Use `clearCache()` method when data needs refreshing
- Scope cache keys by Mandant to avoid cross-tenant contamination
- Fail fast on non-success responses and use retries only for transient errors (timeouts/429/5xx)
- In `fetchAllPages()`, document and keep retry/backoff explicit (attempts + delay) for predictable operations.
- Treat empty-string `lastIndex` as the stop sentinel unless API docs specify a different terminal token.
- Normalize response shape at service boundary (for example `items` + `lastIndex`) before DTO mapping.
- All monetary values as `decimal:2`

## Examples

```php
// AlbatrosConnector
class AlbatrosConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return config('albatros.base_url');
    }

    protected function defaultHeaders(): array
    {
        return [
            'Mandant' => config('albatros.mandant'),
            'Authorization' => 'Bearer ' . config('albatros.token'),
        ];
    }
}
```

```php
// List request with pagination
class ListAdressenRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $lastIndex = '',
        protected int $pageSize = 100,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/Adresse';
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'lastIndex' => $this->lastIndex,
            'pageSize' => $this->pageSize,
        ]);
    }
}
```

```php
// DTO with German domain terms — acceptable for Albatros API
readonly class AdresseData
{
    public function __construct(
        public string $ID,
        public ?string $Name,
        public ?string $Strasse,
        public ?string $PLZ,
        public ?string $Ort,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            ID: (string) ($data['ID'] ?? $data['id'] ?? ''),
            Name: $data['Name'] ?? $data['name'] ?? null,
            Strasse: $data['Strasse'] ?? $data['strasse'] ?? null,
            PLZ: $data['PLZ'] ?? $data['plz'] ?? null,
            Ort: $data['Ort'] ?? $data['ort'] ?? null,
        );
    }
}
```

```php
// Service with caching
public function getAdressen(): Collection
{
    return Cache::remember($this->cacheKey('adressen'), 3600, fn () =>
        $this->fetchAllPages(fn ($lastIndex) => new ListAdressenRequest(lastIndex: $lastIndex))
    );
}

public function clearCache(): void
{
    Cache::forget($this->cacheKey('adressen'));
}
```

```php
// Service pagination + error handling pattern
use Illuminate\Support\Collection;
use Saloon\Exceptions\Request\RequestException;

private function fetchAllPages(callable $requestFactory): Collection
{
    $all = collect();
    $lastIndex = '';

    do {
        $response = retry(3, function () use ($requestFactory, $lastIndex) {
            return $this->connector->send($requestFactory($lastIndex));
        }, 200);

        if (! $response->successful()) {
            throw new RequestException($response, 'Albatros request failed.');
        }

        $payload = $response->json();
        $items = collect($payload['items'] ?? []);
        $all = $all->merge($items->map(fn (array $row) => AdresseData::fromArray($row)));
        $lastIndex = (string) ($payload['lastIndex'] ?? '');
    } while ($lastIndex !== '');

    return $all;
}

private function cacheKey(string $segment): string
{
    return sprintf('albatros.%s.%s', config('albatros.mandant'), $segment);
}
```

```php
// Minimal config/albatros.php pattern
return [
    'base_url' => env('ALBATROS_BASE_URL'),
    'mandant' => env('ALBATROS_MANDANT'),
    'token' => env('ALBATROS_TOKEN'),
];
```

```php
// Mocked Saloon test note (no real API calls)
Saloon::fake([
    '*' => MockResponse::make(['items' => [], 'lastIndex' => ''], 200),
]);
```

## Anti-Patterns

- Making uncached API calls for reference data that rarely changes
- Not using `fetchAllPages()` for paginated endpoints — manual pagination loops
- Translating German API field names to English in DTOs (keep matching API)
- Not handling API field name casing variations in `fromArray()`
- Storing monetary values as float or string instead of `decimal:2`
- Forgetting to call `clearCache()` when reference data is updated
- Reusing non-Mandant-specific cache keys across Mandant contexts

## References

- [Saloon Documentation](https://docs.saloon.dev/)
- Related: `Saloon/SKILL.md` — Saloon connector and request patterns
- Related: `DTO/SKILL.md` — DTO conventions with fromArray factory
- Related: `Services/SKILL.md` — service class wrapping API calls
