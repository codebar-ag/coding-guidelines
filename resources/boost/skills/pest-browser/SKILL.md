---
name: pest-browser
description: End-to-end browser testing with Pest 5 browser testing (pestphp/pest-plugin-browser, Playwright). Used for full user flows that need a real browser — JavaScript interactions, multi-step forms, modals and visual assertions. Laravel Dusk is not used.
compatible_agents:
  - test
  - refactor
  - review
---

# Pest Browser (Browser Testing)

## When to Use

- End-to-end flows that need a real browser and JavaScript execution.
- Multi-step forms, modal interactions, and client-side validation states.
- Smoke tests that key pages render without JavaScript errors.
- Regression tests for key UI journeys before release.

## When Not to Use

- Business logic (use unit tests).
- Controller/API contracts without browser behaviour (use feature tests).
- Anything a feature test can prove faster.

## Preconditions

- Pest 5 with `pestphp/pest-plugin-browser` (^5.1) as a dev dependency and `playwright` in `package.json` devDependencies.
- Browser tests live in `tests/Browser/` and are registered in `tests/Pest.php` with the same `TestCase` and `RefreshDatabase` as the feature tests.
- Assets are built (`npm run build`) and Chromium is installed (`npx playwright install chromium`).
- Laravel Dusk is **not** installed: no `laravel/dusk`, no `DuskTestCase`, no `phpunit.dusk.xml`, no `php artisan dusk`.

## Process Checklist

- [ ] Choose a browser test only if browser execution is required.
- [ ] Add `data-testid` attributes to interactive elements that have no stable label or text.
- [ ] Use `$this->actingAs($user)` before `visit()` unless the login flow itself is under test.
- [ ] Rely on auto-waiting assertions (`assertSee`, `assertPathIs`, …) instead of fixed waits.
- [ ] End page checks with `assertNoJavaScriptErrors()`.
- [ ] Extract repeated flows into small helper functions in `tests/Pest.php` or the test file.

## Rules

- Browser tests live in `tests/Browser/` and use Pest functions (`it()` / `test()`), not classes.
- The app is served in-process: `RefreshDatabase`, factories, `Notification::fake()` and config set in the test apply to the page.
- Target elements by visible text or label first, `@name` second (`@name` means `[data-testid="name"]` or `[data-test="name"]`), CSS selectors last. Never CSS classes.
- Never hard-code database ids or rely on auto-increment values; use the models the test created.
- No arbitrary `wait()` calls; wait for a visible state instead.
- Every browser test file runs in the `pull-request | ci pest browser` CI lane; the regular Pest and coverage lanes do not run `tests/Browser`.

## Examples

```php
it('lets a user submit an invoice', function () {
    $this->actingAs(User::factory()->create());

    visit('/invoices/create')
        ->assertNoJavaScriptErrors()
        ->type('@amount-input', '100')
        ->press('Create invoice')
        ->assertSee('Invoice created')
        ->assertPathBeginsWith('/invoices/');
});
```

```php
it('logs in through the form', function () {
    $user = User::factory()->create();

    visit(route('login'))
        ->type('input[name="email"]', $user->email)
        ->type('input[name="password"]', 'password')
        ->press(__('auth.sign-in'))
        ->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();
});
```

```php
it('renders the public pages', function (string $path) {
    visit($path)->assertNoJavaScriptErrors();
})->with(['/', '/imprint', '/privacy']);
```

```html
<!-- Blade — a test id for an element without a stable label -->
<input data-testid="amount-input" type="number" name="amount">
```

```php
// Waiting — assertions wait on their own
visit('/invoices')->assertSee('Invoice created');   // ❌ ->wait(2)->assertSee(...)
```

## Testing Guidance

- Run all browser tests: `vendor/bin/pest tests/Browser`.
- Run one file: `vendor/bin/pest tests/Browser/InvoiceFlowTest.php`.
- Debug a failing test: add `->debug()` to the chain, or check the screenshot in `tests/Browser/Screenshots/` (git-ignored).
- CI: a separate `pull-request | ci pest browser` workflow builds the assets, runs `npx playwright install --with-deps chromium` and `vendor/bin/pest tests/Browser` against a PostgreSQL service.

## Anti-Patterns

- Using Laravel Dusk (`$this->browse()`, `DuskTestCase`, `dusk=""` attributes, page objects extending `Laravel\Dusk\Page`)
- Fixed waits (`wait(3)`) instead of waiting assertions
- Selecting by CSS classes
- Hard-coded ids such as `/resources/users/1` instead of `$user->id`
- Testing pure business logic or JSON APIs in the browser (use feature tests)
- Not calling `assertNoJavaScriptErrors()` on pages under test
- Running `tests/Browser` in the regular Pest or coverage lane

## References

- [Pest Browser Testing](https://pestphp.com/docs/browser-testing)
- Related: `pesttesting/SKILL.md` — Pest syntax and conventions
- Related: `phpunit/SKILL.md` — feature tests for non-browser scenarios
