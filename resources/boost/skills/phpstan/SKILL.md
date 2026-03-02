---
name: phpstan
description: Static analysis tool configured at Level 9. All code must pass PHPStan Level 9 with strict typing, no untyped signatures, and minimal suppression of errors.
---

# PHPStan

**Name:** PHPStan  
**Description:** Static analysis tool configured at Level 9. All code must pass PHPStan Level 9 with strict typing, no untyped signatures, and minimal suppression of errors.  
**Compatible Agents:** general-purpose, testing, backend  
**Tags:** app/**/*.php, tests/**/*.php, laravel, php, static-analysis, phpstan, types

## When to Apply

- After implementing or refactoring PHP code in `app/**` or `tests/**`.
- Before merging pull requests to ensure static analysis passes.
- When raising PHP or PHPStan levels or tightening type strictness.

## Preconditions

- PHP, Composer dependencies, and PHPStan are installed.
- `phpstan.neon` (and baseline, if any) are present and understood.
- The working tree contains the relevant changes you want to analyze.

## Process

### 1. Run PHPStan and Review Findings

- Run PHPStan over the relevant scope (project default or narrowed paths).
- Capture and review all reported errors:
  - Identify missing or weak type hints.
  - Look for untyped signatures and `mixed` usage.
  - Note any `@phpstan-ignore` usage and whether it is justified.

### 2. Strengthen Types and Annotations

- Ensure **all parameters, return types, and properties are typed** wherever possible.
- Replace broad `mixed` types with more specific union types.
- Use `array<string, mixed>` or more precise shapes instead of bare `array` in PHPDoc.
- Add `@throws` annotations to methods that can throw exceptions.
- Use `phpstan-assert`, `phpstan-param`, and `phpstan-return` tags when generics or advanced shapes are needed.

### 3. Prefer Narrowing to Suppression

- Use runtime type narrowing (for example, `assert()`, `instanceof`) instead of suppressing errors.
- When suppression is unavoidable:
  - Limit it to the narrowest possible scope.
  - Add a clear justification comment.

## Examples

```php
// ✓ Specific types instead of mixed
public function process(Order $order): Invoice { ... }

// ✓ PHPDoc with typed arrays
/**
 * @return array<string, array<int, string|object>>
 */
public function rules(): array { ... }

// ✓ Union types instead of mixed
public function format(string|int|null $value): string { ... }

// ✓ Generics for collections
/**
 * @return Collection<int, Invoice>
 */
public function getInvoices(): Collection { ... }
```

```php
// ✓ @throws annotation
/**
 * @throws InvoiceAlreadyPaidException
 */
public function markAsPaid(Invoice $invoice): void
{
    if ($invoice->isPaid()) {
        throw InvoiceAlreadyPaidException::for($invoice);
    }
    // ...
}
```

```php
// ✓ Type narrowing with assert() instead of suppression
public function handle(mixed $model): void
{
    assert($model instanceof Invoice);
    $model->markAsPaid(); // PHPStan now knows it's an Invoice
}
```

```php
// ✓ Justified suppression with comment
/** @phpstan-ignore-next-line Property accessed via magic method — documented in IDE helper */
$user->custom_attribute;
```

## Checklists

### Execution Checklist

- [ ] Ran PHPStan over the relevant scope (full project or narrowed paths).
- [ ] Ensured all parameters, properties, and return types are typed where possible.
- [ ] Replaced bare `mixed` and `array` usages with more specific types or PHPDoc generics.
- [ ] Added `@throws` annotations to methods that can throw.
- [ ] Used type narrowing and assertions instead of broad suppressions.
- [ ] Any remaining `@phpstan-ignore` usages are justified with clear comments.

## Safety / Things to Avoid

- Using bare `mixed` when a more specific type is possible.
- Using bare `array` without a PHPDoc generic shape such as `array<string, mixed>`.
- Using `@phpstan-ignore` without an explanation of why it is necessary.
- Omitting `@throws` annotations on methods that throw.
- Leaving parameters, properties, or return types untyped.
- Relying on `(int)` or `(string)` casts as substitutes for proper type narrowing.

## References

- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [PHPStan Rule Levels](https://phpstan.org/user-guide/rule-levels)
- Related: `PHP/SKILL.md` — general PHP conventions including strict typing

## Further reading

For a detailed workflow (when to apply, preconditions, iterative fixing, verification), see [docs/skills/phpstan-analysis.md](../../../../docs/skills/phpstan-analysis.md).
