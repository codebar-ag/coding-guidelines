---
name: phpstan
description: Static analysis tool configured at Level 9. All code must pass PHPStan Level 9 with strict typing, no untyped signatures, and minimal suppression of errors.
---

# PHPStan

## When to Apply

- After implementing or refactoring PHP code in `app/**` or `tests/**`.
- Before merging pull requests to ensure static analysis passes.
- When raising PHP or PHPStan levels or tightening type strictness.

## When NOT to Apply

- Do not run as a blocker for non-PHP changes (pure docs, static assets only).
- Do not require full-project runs while debugging unrelated frontend-only issues.
- Do not update baseline as part of formatting-only or dependency-only changes unless analysis output changed.

## Preconditions

- PHP, Composer dependencies, and PHPStan are installed.
- `phpstan.neon` exists at repo root and is used as the default project config.
- If `phpstan.neon` is missing, initialize config first; do not guess flags ad hoc:
  - `vendor/bin/phpstan init`
  - Commit generated config before enforcing this skill.
- If `phpstan-baseline.neon` exists, it is included intentionally and reviewed before merge.
- If baseline is expected but missing, generate once from current known debt, then review before commit:
  - `vendor/bin/phpstan analyse --generate-baseline`
- The working tree contains the relevant changes you want to analyze.

## Process

### 1. Run PHPStan and Review Findings

- Run the default project scope from repo root:
  - `vendor/bin/phpstan analyse`
- Narrow scope only while iterating on a focused change:
  - `vendor/bin/phpstan analyse app/ tests/`
  - `vendor/bin/phpstan analyse app/Services/Billing`
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

### 4. Re-run and Baseline Check

- Re-run full `vendor/bin/phpstan analyse` after fixes.
- If baseline is used, ensure no new ignored errors were added accidentally.
- Keep baseline updates explicit and documented in the PR description.
- Review baseline diffs line-by-line:
  - New entries for edited files are not allowed unless a reviewer accepts a temporary exception.
  - Removed entries are preferred and should be called out.
  - Broad regex ignores must be replaced by narrower paths/messages.

### 5. Common Level 9 Errors and Direct Fixes

- Missing generic value types:
  - Error pattern: `array has no value type specified`.
  - Fix: add `array<string, mixed>` or specific shapes (`array<int, InvoiceDto>`).
- Unknown mixed member/method access:
  - Error pattern: `Cannot call method ... on mixed`.
  - Fix: narrow first with `instanceof`, `assert()`, or typed wrappers.
- Invalid nullable access:
  - Error pattern: `Cannot call method ... on Type|null`.
  - Fix: add null guard clause before access.
- Template/generic mismatch:
  - Error pattern: invalid `@template`, `Collection` generic count/type mismatch.
  - Fix: align declared template params with actual return/param usage.
- Unreachable branch or always-true/false checks:
  - Error pattern: strict comparison impossible/already guaranteed.
  - Fix: remove dead condition and simplify control flow.

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

- [ ] Ran `vendor/bin/phpstan analyse` from repo root.
- [ ] Used narrowed scope only for local iteration.
- [ ] Confirmed `phpstan.neon` exists (or initialized it before applying this skill).
- [ ] Confirmed baseline file existence is intentional (created once if required).
- [ ] Ensured all parameters, properties, and return types are typed where possible.
- [ ] Replaced bare `mixed` and `array` usages with more specific types or PHPDoc generics.
- [ ] Added `@throws` annotations to methods that can throw.
- [ ] Used type narrowing and assertions instead of broad suppressions.
- [ ] Any remaining `@phpstan-ignore` usages are justified with clear comments.
- [ ] Baseline diff was reviewed line-by-line; no silent new ignores for edited files.

## Safety / Things to Avoid

- Using bare `mixed` when a more specific type is possible.
- Using bare `array` without a PHPDoc generic shape such as `array<string, mixed>`.
- Using `@phpstan-ignore` without an explanation of why it is necessary.
- Omitting `@throws` annotations on methods that throw.
- Leaving parameters, properties, or return types untyped.
- Relying on `(int)` or `(string)` casts as substitutes for proper type narrowing.
- Ignoring common Level 9 issues such as union narrowing gaps or invalid generic/template annotations.

## References

- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [PHPStan Rule Levels](https://phpstan.org/user-guide/rule-levels)
- [PHPStan Baseline](https://phpstan.org/user-guide/baseline)
- `resources/boost/skills/php/SKILL.md` (type-first PHP conventions)
