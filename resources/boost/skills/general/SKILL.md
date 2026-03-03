---
name: general
description: Project-wide Laravel conventions that always apply: configuration access, database patterns, logging, activity logging, and code formatting.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# Laravel General Conventions

## When to Apply

- Apply to most PHP application code in `app/` and related tests.
- Apply during implementation, refactors, and reviews to keep behavior consistent.
- Do not apply activity-log rules to third-party package classes, migrations, or disposable test fixtures.
- For schema-only guidance, use the migrations skill.

## When Not to Apply

- Skip activity logging for pure technical models with no business/audit value.
- Skip these conventions in throwaway prototypes not intended for production.
- Defer to package conventions when extending vendor internals.

## Preconditions

- Config files exist in `config/`, and new keys can be added there.
- Spatie Activity Log is installed when activity logging rules are used (`composer show spatie/laravel-activitylog`).
- If Spatie is not installed, use structured application logs and add a TODO to align once package support exists.
- `config/activitylog.php` exists when configuring model activity behavior.
- Formatting tooling is available (`vendor/bin/pint`).

## Process

### 1. Use Config and Logging Conventions

- Read values with `config()`, not `env()` in runtime app code.
- Add new environment-backed keys to `config/*.php` first.
- Log with structured context arrays.

### 2. Protect Multi-step Writes

- Wrap related writes in `DB::transaction()`.
- Ensure exceptions roll back all dependent changes.

### 3. Apply Activity Logging to Business Models

- Use `LogsActivity` on business models that need audit trails (e.g., invoice status changes, payout approvals, role/permission changes).
- Configure `getActivitylogOptions()` with `logAll()`, `logOnlyDirty()`, and `dontSubmitEmptyLogs()`: log tracked attributes, only when values changed, and skip no-op writes.
- Add manual `activity()->performedOn($model)->log(...)` entries for key workflow events.

### 4. Format and Verify

- Run `vendor/bin/pint` before commit.
- Re-run relevant tests after behavior changes.

## Examples

```php
// Configuration: always use config().
$secret = config('services.stripe.secret');
// Bad: env('STRIPE_SECRET')
```

```php
// Add env-backed value in config/services.php, then consume through config().
'stripe' => [
    'secret' => env('STRIPE_SECRET'),
];
```

```php
// Structured logging.
Log::info('Invoice created.', [
    'invoice_id' => $invoice->id,
    'order_id' => $order->id,
    'amount' => $invoice->amount,
]);
```

```php
// Multi-step writes must be transactional.
$payment = DB::transaction(function () use ($order) {
    $invoice = $this->createInvoice->execute($order);
    return $this->chargePaymentMethod->execute($order, $invoice);
});
```

```php
// Anti-pattern: partial write risk without transaction.
$invoice = $this->createInvoice->execute($order);
$this->chargePaymentMethod->execute($order, $invoice); // throws
// Invariant breaks: accounting now has an invoice record without matching payment state.
```

## Checklists

- [ ] No direct `env()` calls outside config files.
- [ ] Multi-step writes use `DB::transaction()`.
- [ ] Structured logging context is included for key events.
- [ ] Activity logging is applied where business audit trails are required.
- [ ] `vendor/bin/pint` ran successfully.

## Anti-Patterns

- Using `env('KEY')` in application code outside config files.
- Writing raw SQL when Eloquent/Query Builder covers the use case.
- Logging messages without context arrays.
- Running related writes outside transactions.
- Adding `LogsActivity` to models with no business audit value.

## References

- [Laravel Configuration](https://laravel.com/docs/configuration)
- [Laravel Logging](https://laravel.com/docs/logging)
- [Spatie Activity Log](https://spatie.be/docs/laravel-activitylog/)
- [Laravel Pint](https://laravel.com/docs/pint)
- `resources/boost/skills/migrations/SKILL.md` (schema-specific conventions)
