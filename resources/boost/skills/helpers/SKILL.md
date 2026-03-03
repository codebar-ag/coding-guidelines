---
name: helpers
description: Stateless utility classes providing shared formatting, conversion, or calculation logic needed across multiple parts of the application.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# Helpers

## When to Use

- Utility logic is reused in multiple bounded contexts.
- Logic is stateless and does not need DB, HTTP, or authorization concerns.
- The same formatting/conversion logic appears in resources, views, and domain services.

## When Not to Use

- Logic is used once in one method (keep it inline for clarity).
- Logic contains business rules (use Action/Service).
- Logic requires external dependencies or stateful configuration (prefer an injected service).

## Preconditions

- Project PHP version supports typed signatures used by helper methods (nullable/union types as needed).
- Target formatter/parser dependencies are installed (for example `nesbot/carbon` for date parsing).
- Shared behavior and expected null handling are agreed before extracting to helper.
- Existing call sites are identified so extraction actually removes duplication.

## Step-by-Step Helper Creation Checklist

- [ ] Confirm the logic is duplicated in at least 2 places.
- [ ] Confirm logic is utility-only (no business decisions, DB queries, or HTTP calls).
- [ ] Decide API surface: method name, input types, null behavior, return type.
- [ ] Implement typed method in `app/Helpers/*Helper.php`.
- [ ] Add focused unit tests including edge cases (`null`, invalid format, boundary values).
- [ ] Replace duplicate inline logic at call sites.
- [ ] Re-run PHPStan/PHPUnit for touched scope.

## Rules

- Helper classes live in `app/Helpers/`
- Naming: `PascalCase` with a `Helper` suffix → `StringHelper`, `MoneyHelper`, `DateHelper`
- Helpers are for utilities needed in **multiple places** across the project
- Static methods are preferred for pure stateless transformations
- Use instance helpers only when collaborators/config must be injected (locale, formatter strategy, tenant settings)
- As helper count grows, group by namespace (`App\Helpers\Formatting\`, `App\Helpers\Conversion\`)
- Never put business logic in helpers — use Actions or Services
- Never put database queries in helpers — use Model Scopes
- Never put HTTP requests in helpers — use Services

## Static vs Instance Guidance

- Use `static` when output depends only on input arguments.
- Use instance methods when behavior depends on constructor-provided collaborators/config.
- Do not create instance helpers just to mimic service-container usage when no dependencies are needed.

## Examples

```php
namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    public static function format(mixed $date, string $format = 'd M Y'): ?string
    {
        if (is_null($date)) {
            return null;
        }

        return Carbon::parse($date)->format($format);
    }
}
```

```php
namespace App\Helpers;

class MoneyHelper
{
    public static function format(int $amountInCents, string $currency = 'CHF'): string
    {
        return number_format($amountInCents / 100, 2) . ' ' . $currency;
    }
}
```

```php
// Usage in a Resource
'created_at' => DateHelper::format($this->created_at),
'amount'     => MoneyHelper::format($this->amount_in_cents),
```

```php
// Instance helper for injectable collaborators or config
namespace App\Helpers\Formatting;

class LocalizedMoneyFormatter
{
    public function __construct(
        private readonly string $defaultCurrency = 'CHF',
    ) {}

    public function format(int $amountInCents, ?string $currency = null): string
    {
        $resolvedCurrency = $currency ?? $this->defaultCurrency;

        return number_format($amountInCents / 100, 2) . ' ' . $resolvedCurrency;
    }
}
```

## Testing Guidance

```php
use App\Helpers\DateHelper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DateHelperTest extends TestCase
{
    #[Test]
    public function it_formats_dates_and_handles_null(): void
    {
        $this->assertSame('21 Jan 2026', DateHelper::format('2026-01-21', 'd M Y'));
        $this->assertNull(DateHelper::format(null));
    }
}
```

## Anti-Patterns

- Putting business logic in a helper (belongs in an Action or Service)
- Putting database queries in a helper (use Model scopes instead)
- Using a helper for logic only needed in one place (keep it inline)
- Creating a catch-all `AppHelper` or `Utils` class — keep helpers domain-specific
- Silent null fallback that hides invalid upstream data

## References

- [Carbon Documentation](https://carbon.nesbot.com/)
- Related: `Actions/SKILL.md` — for business logic
- Related: `Resources/SKILL.md` — helpers are commonly used for formatting in API resources

## Null-Handling Pitfall (Before/After)

```php
// BEFORE (anti-pattern): null silently becomes "now", masking missing data bugs
public static function format(?string $date, string $format = 'd M Y'): string
{
    return Carbon::parse($date)->format($format);
}
```

```php
// AFTER: explicit null contract preserves correctness at call sites
public static function format(?string $date, string $format = 'd M Y'): ?string
{
    if ($date === null) {
        return null;
    }

    return Carbon::parse($date)->format($format);
}
```
