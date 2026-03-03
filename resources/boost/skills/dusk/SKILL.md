---
name: dusk
description: End-to-end browser testing with Laravel Dusk. Used exclusively for full user flows requiring a real Chrome browser — JavaScript interactions, multi-step forms, and visual assertions.
compatible_agents:
  - test
  - refactor
  - review
---

# Dusk (Browser Testing)

## When to Use

- End-to-end flows requiring a real browser and JavaScript execution.
- Multi-step forms, modal interactions, and client-side validation states.
- Regression testing for key UI journeys before release.

## When Not to Use

- Unit-level business logic validation (use unit tests).
- Controller/API contract validation without browser behavior (use feature/API tests).
- Fast feedback checks where browser startup cost is unnecessary.

## Preconditions

- Laravel Dusk is installed and browser tests run from `tests/Browser/`.
- Chrome/Chromium and compatible driver are available on the test environment.
- `.env.dusk.local` is configured with isolated test DB and app URL.
- Test data setup strategy uses `DatabaseTruncation` or `DatabaseMigrations`, never `RefreshDatabase`.

## Process Checklist

- [ ] Choose Dusk only if browser execution is required.
- [ ] Add/verify `dusk=""` selectors for all interactive elements under test.
- [ ] Use `loginAs()` to bypass repetitive auth UI unless auth flow is under test.
- [ ] Replace arbitrary waits with `waitFor*` assertions.
- [ ] Call `assertNoJavaScriptErrors()` after each `visit()`.
- [ ] Extract repeated navigation/selectors into Page Objects or Dusk Components.

## Rules

- Browser tests live in `tests/Browser/`.
- Use `DatabaseTruncation` (preferred) or `DatabaseMigrations`; never `RefreshDatabase`.
- Use only `dusk=""` selectors for test targeting.
- Do not use `pause()`; wait for explicit UI state transitions.

## Examples

```php
uses(DatabaseTruncation::class);

it('user can submit an invoice', function () {
    // Arrange
    $user = User::factory()->create();

    // Act + Assert
    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
                ->visit('/invoices/create')
                ->assertNoJavaScriptErrors()
                ->type('@amount-input', '100')
                ->press('@submit-button')
                ->waitForText('Invoice created')
                ->assertSee('Invoice created');
    });
});
```

```html
<!-- Blade/HTML — use dusk attributes, not CSS classes -->
<button dusk="submit-invoice">Submit</button>
<input dusk="amount-input" type="number" name="amount">
```

```php
// Page Object
class InvoicePage extends Page
{
    public function __construct(private readonly int $invoiceId) {}

    public function url(): string { return "/invoices/{$this->invoiceId}"; }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@mark-paid-button' => '[dusk="mark-paid-button"]',
        ];
    }

    public function markAsPaid(Browser $browser): void
    {
        $browser->click('@mark-paid-button')
                ->waitForText('Invoice marked as paid');
    }

    public function fillForm(Browser $browser, string $amount): void
    {
        $browser->type('@amount-input', $amount);
    }

    public function submitForm(Browser $browser): void
    {
        $browser->press('@submit-button');
    }

    public function assertSuccess(Browser $browser): void
    {
        $browser->waitForText('Invoice created')
                ->assertSee('Invoice created');
    }
}
```

```php
// Waiting — never use pause()
$browser->waitFor('@invoice-table');          // ❌ pause(3000)
$browser->waitForText('Invoice created');     // ❌ pause(2000)
$browser->waitUntilMissing('@loading-spinner'); // ❌ pause(5000)
```

## Testing Guidance

- Run all browser tests: `php artisan dusk`.
- Run a specific file: `php artisan dusk tests/Browser/InvoiceFlowTest.php`.
- Use CI-friendly headless Chrome settings and isolate Dusk database from local dev DB.

## Anti-Patterns

- Using `RefreshDatabase` in Dusk tests (transactions invisible across HTTP processes)
- Using `pause(3000)` — arbitrary sleeps make tests slow and brittle
- Using CSS classes or IDs as selectors instead of `dusk=""` attributes
- Testing pure business logic or JSON API responses with Dusk (use feature tests instead)
- Not calling `assertNoJavaScriptErrors()` after `visit()`
- Repeating navigation and selector logic across test files instead of using Page Objects

## References

- [Laravel Dusk](https://laravel.com/docs/dusk)
- Related: `PestTesting/SKILL.md` — Pest syntax used for Dusk tests
- Related: `PHPUnit/SKILL.md` — feature tests for non-browser scenarios
