---
name: routing
description: Route file conventions for organising API and web routes. Covers file separation, naming, grouping, middleware, and route model binding.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# Routing

## When to Use

- Defining or refactoring HTTP entrypoints in monolith Laravel apps.
- Standardizing route naming, grouping, and middleware application.
- Enforcing route model binding consistency for maintainable controllers.

## When Not to Use

- Package-specific routing where package provider conventions take precedence.
- Non-HTTP message entrypoints (queues/events/commands).
- Ad-hoc prototype routes that should not reach production.

## Preconditions

- `routes/web.php` and `routes/api.php` are present and loaded.
- Route provider/bootstrap loading is configured so new route files are actually registered.
- Controllers and middleware classes referenced by routes exist.
- Bound models define expected route key behavior (`getRouteKeyName()` when needed).
- Team naming conventions for route names and URL segments are agreed.

## Process Checklist

- [ ] Place routes in correct file (`web.php` vs `api.php`).
- [ ] Group by prefix/domain/middleware to avoid repeated declarations.
- [ ] Use explicit route names for stable references.
- [ ] Prefer implicit model binding; add explicit binding only when required.
- [ ] Verify middleware order for auth, throttling, and custom verification.
- [ ] Run route list and targeted feature tests after updates.

## Rules

- Keep API and web routes separated by file and middleware stack intent.
- Keep middleware ordering explicit: `web` routes prioritize session/CSRF/auth flow; `api` routes prioritize auth/throttle/binding consistency.
- Use kebab-case URL segments and resourceful route names.
- Apply middleware at group level whenever possible.
- Use route model binding consistently to reduce manual lookup boilerplate.
- Prefer URL-based API versioning for public APIs (`/api/v1/...`) and keep version groups isolated.

## Examples

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('invoices', InvoiceController::class);

    Route::prefix('invoices')->group(function () {
        Route::post('{invoice}/pay', PayInvoiceController::class)->name('invoices.pay');
    });
});

Route::middleware(['throttle:webhook'])->group(function () {
    Route::post('webhooks/stripe', ProcessStripeWebhookController::class)
        // Middleware ordering matters: signature check before heavy work.
        ->middleware([VerifyStripeSignature::class])
        ->name('webhooks.stripe');
});
```

```php
// Implicit route model binding
Route::get('invoices/{invoice}', ShowInvoiceController::class);

// Controller resolves the model automatically
public function __invoke(Invoice $invoice): JsonResponse { ... }
```

```php
// Explicit binding for custom resolution/gotchas
// App\Providers\RouteServiceProvider::boot()
Route::bind('invoice', function (string $value) {
    return Invoice::where('uuid', $value)->firstOrFail();
});

// Route uses the same placeholder name: {invoice}
Route::get('invoices/{invoice}', ShowInvoiceController::class);
```

```php
// Naming with resourceful convention
Route::apiResource('invoice-lines', InvoiceLineController::class);
// Generates: invoice-lines.index, invoice-lines.store, invoice-lines.show, etc.
```

## Model Binding Gotchas

- Placeholder mismatch breaks binding: `{invoice}` binds to `Invoice $invoice`; `{invoiceId}` does not.
- Custom keys require alignment: if route uses slug/uuid, set `getRouteKeyName()` or explicit `Route::bind`.
- Soft-deleted model access requires explicit handling (`withTrashed()` patterns) when expected.

```php
// Soft-deleted model binding when restore/history endpoints must resolve trashed records
// routes/api.php
Route::get('invoices/{invoice}/audit', ShowInvoiceAuditController::class);

// App\Providers\RouteServiceProvider::boot()
Route::bind('invoice', function (string $value) {
    return Invoice::withTrashed()->where('uuid', $value)->firstOrFail();
});
```

## Middleware Ordering Note

- Put request-rejection middleware first (auth/signature checks), then rate limiting, then expensive processing.
- Incorrect order can leak information, increase load, or bypass expected guards.
- In `web`, keep session/cookies/CSRF stack intact before auth-gated routes.
- In `api`, apply auth and throttle consistently at version/group boundaries to avoid route drift.

## Route Cache Gotcha

- Closures in route definitions break `route:cache`; use controller classes for cache-safe production routing.
- Run `php artisan route:cache` in CI and deployment pipelines after route/provider changes.
- Do not run `route:cache` as part of normal local development loop; prefer uncached routes locally for faster iteration/debugging.
- If you cached routes locally for testing, clear before normal work: `php artisan route:clear`.

## Testing Guidance

- Run `php artisan route:list` to verify names, middleware, and URI shape.
- Add feature tests for auth middleware behavior and binding failures (`404` when model missing).
- Add webhook tests validating signature middleware runs before controller logic.

```php
// Middleware ordering test: reject unauthenticated request before controller side effects
public function test_pay_invoice_requires_auth_before_controller_runs(): void
{
    Event::fake([InvoicePaid::class]);

    $this->postJson('/api/invoices/uuid-123/pay')
        ->assertUnauthorized();

    Event::assertNotDispatched(InvoicePaid::class);
}
```

```php
// Explicit 404 binding-failure test
public function test_show_invoice_returns_404_for_missing_binding(): void
{
    $this->getJson('/api/invoices/non-existing-uuid')
        ->assertNotFound();
}
```

## Anti-Patterns

- Mixing API and web routes in the same file
- Not using route model binding when an ID is passed to look up a model
- Applying authentication middleware per route instead of at the group level
- Using camelCase or snake_case in URL segments instead of kebab-case
- Not naming routes (anonymous routes are harder to reference and refactor)

## References

- [Laravel Routing](https://laravel.com/docs/routing)
- Related: `Controllers/SKILL.md` — controllers that handle routes
- Related: `Middleware/SKILL.md` — middleware applied at the route group level
