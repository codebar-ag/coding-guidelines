---
name: pesttesting
description: Laravel testing conventions using the Pest PHP framework. Covers test structure, AAA pattern, HTTP assertions, datasets, mocking, browser tests, and architecture tests.
compatible_agents:
  - test
  - refactor
  - review
---

# Pest Testing

## When to Use

- Writing new tests in projects that standardize on Pest syntax.
- Refactoring older tests into fluent, readable behavior descriptions.
- Adding datasets, architecture tests, and expressive HTTP assertions.

## When Not to Use

- Existing areas intentionally locked to class-based PHPUnit style.
- Browser tests without real browser requirements (use feature tests instead).
- Non-test automation scripts where Pest semantics do not apply.

## Preconditions

- Pest is installed and configured for Laravel (`vendor/bin/pest` works).
- Test bootstrap and DB setup are available (`phpunit.xml`/env test config).
- Team conventions for test file placement and naming are understood.
- Fakes/mocks strategy is clear for external integrations.

## Process Checklist

- [ ] Write tests in Pest syntax (`it`, `describe`, `expect`).
- [ ] Structure each test as AAA with concise comments.
- [ ] Use named response assertions (`assertCreated`, `assertForbidden`, etc.).
- [ ] Add `uses(RefreshDatabase::class)` for DB-touching files.
- [ ] Replace duplicated cases with datasets.
- [ ] Run targeted tests first, then full impacted scope.

## Rules

- Use Pest syntax consistently for new and refactored tests.
- Use named HTTP assertions, not raw numeric status checks.
- Prefer Laravel fakes for framework integrations (events, mail, queue, notifications).
- Keep browser-test constraints aligned with Dusk guidance.
- Never delete tests without explicit approval.

## Examples

```php
// Basic test — AAA pattern
uses(RefreshDatabase::class);

it('creates an invoice for the given order', function () {
    // Arrange
    $order = Order::factory()->create();

    // Act
    $response = $this->postJson('/api/invoices', ['order_id' => $order->id]);

    // Assert
    $response->assertCreated();
    expect(Invoice::count())->toBe(1);
});
```

```php
// Grouped with describe() and beforeEach()
describe('InvoiceController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    it('lists all invoices', function () {
        Invoice::factory()->count(3)->create();
        $this->getJson('/api/invoices')->assertSuccessful();
    });

    it('creates an invoice', function () {
        $order = Order::factory()->create();
        $this->postJson('/api/invoices', ['order_id' => $order->id])
            ->assertCreated();
    });
});
```

```php
// Datasets for multiple inputs
it('rejects invalid email addresses', function (string $email) {
    $this->postJson('/api/users', ['email' => $email])
        ->assertUnprocessable();
})->with([
    'empty string'  => [''],
    'missing @'     => ['notanemail'],
    'missing tld'   => ['user@domain'],
]);
```

```php
// Named HTTP assertion methods
$response->assertSuccessful();    // ❌ assertStatus(200)
$response->assertCreated();       // ❌ assertStatus(201)
$response->assertNoContent();     // ❌ assertStatus(204)
$response->assertUnprocessable(); // ❌ assertStatus(422)
$response->assertForbidden();     // ❌ assertStatus(403)
$response->assertUnauthorized();  // ❌ assertStatus(401)
$response->assertNotFound();      // ❌ assertStatus(404)
```

```php
// Architecture tests
arch('no debug calls in production code')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'var_dump', 'ray']);

arch('controllers have the correct suffix')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');
```

```php
// Browser test baseline with Dusk + DatabaseTruncation
uses(\Illuminate\Foundation\Testing\DatabaseTruncation::class);

it('submits invoice flow in browser', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/invoices/create')
            ->assertNoJavaScriptErrors()
            ->waitFor('@submit-button');
    });
});
```

## Run Commands

```bash
# Run all tests through Laravel
php artisan test

# Run Pest directly
vendor/bin/pest

# Run a specific file
vendor/bin/pest tests/Feature/InvoiceControllerTest.php

# Filter by test name/description
vendor/bin/pest --filter="creates an invoice"

# Run a focused group (if groups are configured)
vendor/bin/pest --group=feature
```

## Testing Guidance

- Keep tests behavior-focused and easy to read at a glance.
- Use datasets and `beforeEach()` to reduce duplication before adding new assertions.
- For browser behavior, defer selector/waiting details to `Dusk/SKILL.md`.

## Anti-Patterns

- Using `assertStatus(200)` instead of named assertion methods
- Using `RefreshDatabase` in browser (Dusk) tests — use `DatabaseTruncation`
- Using `pause(3000)` in browser tests — use `waitFor()` or `waitForText()`
- Repeating the same test for multiple inputs instead of using datasets
- Using CSS class or ID selectors in Dusk — use `dusk=""` attributes
- Missing `uses(RefreshDatabase::class)` in feature/unit files that touch the database
- Deleting a test without explicit approval
- Missing `assertNoJavaScriptErrors()` after `visit()` in browser tests

## References

- [Pest PHP Documentation](https://pestphp.com/)
- [Laravel Testing](https://laravel.com/docs/testing)
- Related: `Dusk/SKILL.md` — full browser (E2E) test conventions
- Related: `PHPStan/SKILL.md` — static analysis runs alongside tests
