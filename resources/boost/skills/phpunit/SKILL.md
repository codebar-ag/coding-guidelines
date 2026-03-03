---
name: phpunit
description: PHPUnit test structure, naming, assertions, and factory conventions for Laravel feature and unit tests.
compatible_agents:
  - test
  - refactor
  - review
---

# PHPUnit

## When to Use

- Writing or refactoring PHPUnit-style tests in Laravel codebases.
- Maintaining existing class-based test suites alongside Pest-based files.
- Creating focused unit tests for pure PHP logic and feature tests for HTTP/database flows.

## When Not to Use

- Browser/E2E flows requiring JavaScript execution (use Dusk).
- New tests in a code area that is standardized on Pest syntax only.
- Integration scenarios where external systems should be faked/stubbed at a higher level.

## Preconditions

- `phpunit.xml` / `phpunit.xml.dist` is present and configured for this project.
- Test database configuration is isolated from development data.
- Factories exist (or are added) for models under test.
- Team conventions for Pest vs PHPUnit style are known for the touched test area.

## Process Checklist

- [ ] Pick the right test type: unit (`tests/Unit`) vs feature (`tests/Feature`).
- [ ] Use `RefreshDatabase` in feature tests that modify/query DB state.
- [ ] Keep one assertion concern per test method.
- [ ] Use strict assertions (`assertSame`) when type and value matter.
- [ ] Use model factories and states; avoid manual low-level inserts.
- [ ] Run targeted tests first, then full impacted suite.

## Rules

- Feature tests extend `Tests\TestCase`; unit tests extend `PHPUnit\Framework\TestCase`.
- Use `test_` snake_case method names and `: void` return types.
- Keep tests deterministic and specific in assertions.
- Favor factories/states over hand-crafted persistence setup.

## Examples

```php
// Feature test
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_invoice(): void
    {
        $user  = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson('/api/invoices', ['order_id' => $order->id]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('invoices', ['order_id' => $order->id]);
    }
}
```

```php
// Unit test — no Laravel bootstrap
use PHPUnit\Framework\TestCase;

class MoneyHelperTest extends TestCase
{
    public function test_formats_amount_in_cents_as_currency_string(): void
    {
        $result = MoneyHelper::format(1000, 'CHF');

        $this->assertSame('10.00 CHF', $result);
    }
}
```

```php
// Factory with states
// database/factories/UserFactory.php
public function unverified(): static
{
    return $this->state(fn (array $attributes) => [
        'email_verified_at' => null,
    ]);
}

// Usage in tests
User::factory()->create();
User::factory()->unverified()->create();
```

## Run Commands

```bash
# Full Laravel test run (recommended entrypoint)
php artisan test

# Run PHPUnit directly
vendor/bin/phpunit

# Run a single file
vendor/bin/phpunit tests/Feature/InvoiceControllerTest.php

# Filter by test method name
vendor/bin/phpunit --filter=test_user_can_create_invoice
```

## Testing Guidance

- Run the smallest relevant subset while iterating, then run full impacted suites.
- Keep test naming aligned with behavior, not implementation details.
- Prefer explicit assertions for DB rows, response structure, and domain outputs.

## Anti-Patterns

- Not using `RefreshDatabase` in feature tests that write to the database
- Using raw `insert()` or `create()` calls in tests instead of factories
- Mixing multiple assertion concerns in a single test method
- Using `assertEquals` when `assertSame` is needed for strict comparison
- Asserting truthiness (`assertTrue($result !== null)`) instead of a specific value
- Unit tests that bootstrap the full Laravel application (use `Tests\TestCase` only for feature tests)

## References

- [PHPUnit Documentation](https://phpunit.de/)
- [Laravel Testing](https://laravel.com/docs/testing)
- Related: `PestTesting/SKILL.md` — the preferred test framework (Pest over raw PHPUnit)
