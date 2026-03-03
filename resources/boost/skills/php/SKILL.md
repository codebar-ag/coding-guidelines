---
name: php
description: General PHP coding standards covering strict typing, formatting, control flow, and error handling. Applies to all PHP files in the application.
compatible_agents:
  - architect
  - implement
  - refactor
  - review
---

# PHP

## When to Apply

- For all first-party PHP code in the repository (`app/`, `tests/`, and supporting project PHP files).
- During new implementation, refactors, and review feedback passes.
- Use this as a repo-wide baseline; stricter feature-specific skills may add extra rules.

## When NOT to Apply

- Do not use this as the primary rule set for non-PHP files (Blade-only markup, JS/CSS-only edits, docs-only edits).
- Do not force style-only rewrites of untouched legacy files in feature PRs.
- Do not relax stricter local constraints from focused skills (`phpstan`, `controllers`, `requests`, etc.).

## Preconditions

- PHPStan and Laravel Pint are installed through Composer.
- Run formatting and analysis from project root before committing.
- Project targets high static-analysis quality (PHPStan Level 9 as the standard target).
- If a baseline is used, treat it as temporary debt.
- Edited lines must move toward compliance; do not add new baseline entries for touched files unless explicitly justified and reviewed.
- If you must suppress temporarily, scope it to the smallest location and include a removal follow-up.

## Rules

- Type hints on **all** parameters, return types, and properties — no untyped signatures
- Use union types or nullable (`?Type`) where needed
- Target **PHPStan Level 9** compliance
- All code in **English**: variable names, comments, docblocks
- Foreign-language domain terms from external APIs are acceptable in DTOs where they match the API
- Run **Laravel Pint** before committing
- Default behavior: let exceptions bubble to Laravel's exception handler
- Catch exceptions only when you **must** react locally (retry logic, API fallback, structured context logging)
- If you catch, either recover intentionally or re-throw; never swallow exceptions
- Use **early returns** (guard clauses) to handle edge cases at the top of a method
- Never use `else`, `elseif`, or nested `if` blocks — invert the condition and return early
- Prefer immutability — use `readonly` properties where possible; use mutable properties only when state must evolve across method calls
- One class per file
- Align with the PHPStan skill for array-shape/docblock expectations and suppression policy

## Guard Clauses vs Normal Returns

- Use guard clauses for invalid/precondition branches and fast-fail behavior.
- A normal final return for the happy path is expected and does not violate the no-`else` rule.
- Do not force guard clauses when they reduce readability (for example, tiny pure mapping methods with one direct return).
- Rule of thumb: guard early for exceptional branches, keep the main path linear, end with one clear final return where possible.

## Examples

```php
// ✓ Early returns — guard clauses
public function process(Order $order): void
{
    if (! $order->isPaid()) {
        throw new UnpaidOrderException();
    }

    if (! $order->hasItems()) {
        throw new EmptyOrderException();
    }

    // Happy-path logic here
}
```

```php
// ✗ Nested if/else — never do this
public function process(Order $order): void
{
    if ($order->isPaid()) {
        if ($order->hasItems()) {
            // deep logic
        } else {
            throw new EmptyOrderException();
        }
    } else {
        throw new UnpaidOrderException();
    }
}
```

```php
// ✓ Fully typed class
class CreateInvoice
{
    public function __construct(
        private readonly GenerateInvoicePdf $generatePdf,
    ) {}

    public function execute(Order $order): Invoice
    {
        // implementation
    }
}
```

```php
// ✗ Anti-pattern: swallowed exception
try {
    $this->externalService->call($data);
} catch (Throwable $e) {
    // no logging, no rethrow, no fallback
}
```

```php
// ✓ Structured logging when catching
try {
    $this->externalService->call($data);
} catch (ServiceException $e) {
    Log::error('External service failed.', [
        'message' => $e->getMessage(),
        'data'    => $data,
    ]);
    throw $e;
}
```

```php
// ✓ Retry transient failures, then catch once for context and rethrow
try {
    return retry(3, fn () => $this->gateway->charge($payload), 200);
} catch (GatewayTimeoutException $e) {
    Log::warning('Charge failed after retries.', ['order_id' => $orderId]);
    throw $e;
}
```

```php
// ✓ Foreign-language DTO terms are acceptable when mirroring external API fields
readonly class AdresseData
{
    public function __construct(
        public string $ID,
        public ?string $Strasse,
        public ?string $PLZ,
    ) {}
}
```

## Anti-Patterns

- Untyped function parameters or return types
- Using `else` or `elseif` — invert and return early instead
- Silently swallowing exceptions with empty catch blocks
- Using `env()` directly in application code (only in config files)
- Non-English variable names, comments, or method names
- Multiple classes in a single file

## PHPStan Alignment (Level 9)

- For edited code, fix these first:
  - `mixed` access/calls without narrowing
  - untyped arrays without generic/value shape
  - nullable dereference without null guard
  - invalid template/generic docblocks
- Treat `@phpstan-ignore*` as last resort; each usage needs an inline reason.
- Prefer correcting signatures/docblocks over adding baseline ignores.

## Baseline Policy for Edited Lines

- Baseline is legacy debt tracking, not a shield for new changes.
- In touched files, do not introduce new baseline entries for lines you edited.
- If a touched file still has existing baseline debt outside edited lines, leave it untouched unless you can safely reduce it.
- Any unavoidable new baseline entry requires:
  - why it cannot be fixed in this change,
  - precise scope (single message/path),
  - planned removal timing.

## References

- [PHPStan](https://phpstan.org/)
- [Laravel Pint](https://laravel.com/docs/pint)
- `pint.json` (if present) defines local Pint behavior
- `phpstan.neon` defines local static-analysis scope and strictness
- Related: `PHPStan/SKILL.md` — static analysis compliance

## Quick Checklist

- [ ] Parameters, return values, and properties are typed
- [ ] Control flow uses guard clauses and avoids `else`/`elseif`
- [ ] Exception handling either bubbles or re-throws after intentional local handling
- [ ] Pint and PHPStan pass for the edited scope
